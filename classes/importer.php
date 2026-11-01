<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * CSV upload, cohort creation, and membership assignment.
 *
 * @package    local_bulkcohortenrol
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_bulkcohortenrol;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Turns one CSV file into a single system cohort membership update.
 */
class importer {

    /** @var string Temp-file type for csv_import_reader. */
    public const IMPORT_TYPE = 'bulkcohortenrol';

    /** @var int Maximum accepted CSV size. */
    public const MAX_BYTES = 10485760;

    /** @var int How many problem rows to show. */
    public const PROBLEM_LIMIT = 50;

    /**
     * Forget a stored upload.
     */
    public static function clear_pending(): void {
        global $SESSION;

        if (!empty($SESSION->local_bulkcohortenrol_pending->iid)) {
            $reader = new \csv_import_reader($SESSION->local_bulkcohortenrol_pending->iid, self::IMPORT_TYPE);
            $reader->cleanup();
        }
        unset($SESSION->local_bulkcohortenrol_pending);
    }

    /**
     * Keep an uploaded CSV for the preview and confirm steps.
     *
     * @param int $draftitemid
     * @param string $cohortname
     * @param string $idnumber
     * @param string $delimiter
     * @param string $encoding
     * @param string $matchfield
     */
    public static function remember_upload(
        int $draftitemid,
        string $cohortname,
        string $idnumber,
        string $delimiter,
        string $encoding,
        string $matchfield
    ): void {
        global $SESSION, $USER;

        $file = self::draft_file($draftitemid);
        [$reader, $iid, $error] = self::open_content($file->get_content(), $encoding, $delimiter, $matchfield);
        if ($error !== null || $reader === null) {
            throw new \moodle_exception('invalidcsv', 'local_bulkcohortenrol', '', $error ?? '');
        }

        self::clear_pending();
        $SESSION->local_bulkcohortenrol_pending = (object) [
            'iid' => $iid,
            'cohortname' => trim($cohortname),
            'idnumber' => trim($idnumber),
            'matchfield' => $matchfield,
            'userid' => (int) $USER->id,
        ];
    }

    /**
     * Stored upload for the current user, if it is still available.
     *
     * @return \stdClass|null
     */
    public static function pending(): ?\stdClass {
        global $SESSION, $USER;

        $pending = $SESSION->local_bulkcohortenrol_pending ?? null;
        if (empty($pending->iid) || (int) $pending->userid !== (int) $USER->id) {
            return null;
        }
        $reader = new \csv_import_reader($pending->iid, self::IMPORT_TYPE);
        if ($reader->get_columns() === false) {
            return null;
        }
        return $pending;
    }

    /**
     * Read the CSV without writing anything.
     *
     * @param string $content
     * @param string $encoding
     * @param string $delimiter
     * @param string $matchfield username or email
     * @return array{0: \csv_import_reader|null, 1: int, 2: string|null}
     */
    public static function open_content(string $content, string $encoding, string $delimiter, string $matchfield): array {
        self::assert_options($delimiter, $encoding, $matchfield);

        $iid = \csv_import_reader::get_new_iid(self::IMPORT_TYPE);
        $reader = new \csv_import_reader($iid, self::IMPORT_TYPE);
        $loaded = $reader->load_csv_content($content, $encoding, $delimiter);
        if ($loaded === false) {
            $error = $reader->get_error() ?: get_string('invalidcsv', 'local_bulkcohortenrol', '');
            $reader->cleanup();
            return [null, 0, $error];
        }
        if ((int) $loaded < 2) {
            $reader->cleanup();
            return [null, 0, get_string('csvnodata', 'local_bulkcohortenrol')];
        }

        $map = self::header_map($reader->get_columns() ?: []);
        if (!isset($map[$matchfield])) {
            $reader->cleanup();
            $label = get_string($matchfield === 'email' ? 'matchemail' : 'matchusername', 'local_bulkcohortenrol');
            return [null, 0, get_string('missingcolumn', 'local_bulkcohortenrol', $label)];
        }
        return [$reader, $iid, null];
    }

    /**
     * Build the preview. No cohort or membership records are written.
     *
     * @return \stdClass
     */
    public static function plan(): \stdClass {
        $pending = self::pending();
        if (!$pending) {
            throw new \moodle_exception('nopending', 'local_bulkcohortenrol');
        }
        return self::analyse($pending);
    }

    /**
     * Create the system cohort when needed and assign the matched users.
     *
     * @return \stdClass
     */
    public static function apply(): \stdClass {
        global $DB;

        $pending = self::pending();
        if (!$pending) {
            throw new \moodle_exception('nopending', 'local_bulkcohortenrol');
        }

        $context = \context_system::instance();
        require_capability('local/bulkcohortenrol:manage', $context);

        $plan = self::analyse($pending);
        if ($plan->blocked !== '' || !$plan->canapply) {
            throw new \moodle_exception('cannotapply', 'local_bulkcohortenrol');
        }

        require_capability('moodle/cohort:assign', $context);
        if (!$plan->cohort) {
            require_capability('moodle/cohort:manage', $context);
        }

        $created = false;
        $cohortid = 0;
        $cohortname = $plan->cohortname;
        $transaction = $DB->start_delegated_transaction();
        try {
            if ($plan->cohort) {
                $cohort = $DB->get_record('cohort', ['id' => $plan->cohort->id], '*', MUST_EXIST);
                if ((int) $cohort->contextid !== (int) $context->id) {
                    throw new \moodle_exception('cannotapply', 'local_bulkcohortenrol');
                }
                $cohortid = (int) $cohort->id;
                $cohortname = $cohort->name;
            } else {
                $existing = null;
                if ($plan->idnumber !== '') {
                    $existing = $DB->get_record('cohort', ['idnumber' => $plan->idnumber]);
                }
                if ($existing) {
                    if ((int) $existing->contextid !== (int) $context->id) {
                        throw new \moodle_exception('cannotapply', 'local_bulkcohortenrol');
                    }
                    $cohortid = (int) $existing->id;
                    $cohortname = $existing->name;
                } else {
                    $record = (object) [
                        'contextid' => $context->id,
                        'name' => $plan->cohortname,
                        'description' => '',
                        'descriptionformat' => FORMAT_HTML,
                        'visible' => 1,
                        'component' => '',
                    ];
                    if ($plan->idnumber !== '') {
                        $record->idnumber = $plan->idnumber;
                    }
                    $cohortid = cohort_add_cohort($record);
                    $created = true;
                }
            }

            foreach ($plan->userids as $userid) {
                cohort_add_member($cohortid, (int) $userid);
            }
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
        }

        $result = new \stdClass();
        $result->cohortid = $cohortid;
        $result->cohortname = $cohortname;
        $result->created = $created;
        $result->added = count($plan->userids);
        $result->already = $plan->already;
        $result->missing = $plan->missing;
        return $result;
    }

    /**
     * The draft file selected in the form.
     *
     * @param int $draftitemid
     * @return \stored_file
     */
    public static function draft_file(int $draftitemid): \stored_file {
        global $USER;

        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);
        $files = $fs->get_area_files($context->id, 'user', 'draft', $draftitemid, 'id DESC', false);
        $file = reset($files);
        if (!$file) {
            throw new \moodle_exception('invalidcsv', 'local_bulkcohortenrol', '', get_string('required'));
        }
        if ($file->get_filesize() > self::MAX_BYTES) {
            throw new \moodle_exception('filetoobig', 'local_bulkcohortenrol');
        }
        return $file;
    }

    /**
     * Encodings offered in the upload form.
     *
     * Windows-1255 is included because Hebrew Excel files use it, and it is not
     * always present in Moodle's own encoding list.
     *
     * @return array<string, string>
     */
    public static function encodings(): array {
        $encodings = [
            'UTF-8' => 'UTF-8',
            'WINDOWS-1255' => 'WINDOWS-1255',
            'ISO-8859-8' => 'ISO-8859-8',
        ];
        foreach (\core_text::get_encodings() as $encoding => $label) {
            if (!isset($encodings[$encoding])) {
                $encodings[$encoding] = $label;
            }
        }
        return $encodings;
    }

    /**
     * @param string $delimiter
     * @param string $encoding
     * @param string $matchfield
     */
    public static function assert_options(string $delimiter, string $encoding, string $matchfield): void {
        $delimiters = \csv_import_reader::get_delimiter_list();
        if (!array_key_exists($delimiter, $delimiters)) {
            throw new \moodle_exception('invaliddelimiter', 'local_bulkcohortenrol');
        }
        if (!array_key_exists($encoding, self::encodings())) {
            throw new \moodle_exception('invalidencoding', 'local_bulkcohortenrol');
        }
        if (!in_array($matchfield, ['username', 'email'], true)) {
            throw new \moodle_exception('invalidmatchfield', 'local_bulkcohortenrol');
        }
    }

    /**
     * Resolve the target cohort and classify every data row.
     *
     * @param \stdClass $pending
     * @return \stdClass
     */
    private static function analyse(\stdClass $pending): \stdClass {
        global $DB;

        $reader = new \csv_import_reader($pending->iid, self::IMPORT_TYPE);
        $columns = $reader->get_columns();
        if ($columns === false) {
            throw new \moodle_exception('nopending', 'local_bulkcohortenrol');
        }
        $rows = self::read_rows($reader, self::header_map($columns));
        [$idnumber, $idsource, $cohortvalues] = self::resolve_idnumber($pending->idnumber, $rows);
        [$cohort, $blockcode] = self::find_cohort($pending->cohortname, $idnumber);

        $plan = new \stdClass();
        $plan->cohortname = $pending->cohortname;
        $plan->idnumber = $idnumber;
        $plan->idnumbersource = $idsource;
        $plan->cohortvalues = $cohortvalues;
        $plan->cohort = $cohort;
        $plan->notes = [];
        $plan->blocked = '';
        $plan->userids = [];

        if ($blockcode === 'notsystem' && $cohort) {
            $plan->blocked = get_string('notsystem', 'local_bulkcohortenrol', format_string($cohort->name));
        } else if ($blockcode === 'ambiguousname') {
            $plan->blocked = get_string('ambiguousname', 'local_bulkcohortenrol');
        } else if ($blockcode === 'duplicateidnumber') {
            $plan->blocked = get_string('duplicateidnumber', 'local_bulkcohortenrol');
        }

        if ($idsource === 'csv') {
            $plan->notes[] = get_string('idnumbersourcecsv', 'local_bulkcohortenrol', s($idnumber));
        } else if ($idsource === 'csvmany') {
            $shown = array_slice($cohortvalues, 0, 10);
            $plan->notes[] = get_string('csvmany', 'local_bulkcohortenrol', s(implode(', ', $shown)));
        } else if ($idsource === 'none') {
            $plan->notes[] = get_string('idnumbersourcenone', 'local_bulkcohortenrol');
        }

        if ($cohort && $blockcode === null && $cohort->name !== $pending->cohortname) {
            $plan->notes[] = get_string('namenote', 'local_bulkcohortenrol', (object) [
                'existing' => format_string($cohort->name),
                'requested' => format_string($pending->cohortname),
            ]);
        }
        if ($cohort && $blockcode === null && !empty($cohort->component)) {
            $plan->notes[] = get_string('externalcohort', 'local_bulkcohortenrol', s($cohort->component));
        }
        if (!$cohort && $idnumber !== '' && $blockcode === null) {
            $systemcontextid = \context_system::instance()->id;
            if ($DB->record_exists('cohort', ['name' => $pending->cohortname, 'contextid' => $systemcontextid])) {
                $plan->notes[] = get_string('samenameexists', 'local_bulkcohortenrol');
            }
        }

        $context = \context_system::instance();
        if ($plan->blocked === '' && !has_capability('moodle/cohort:assign', $context)) {
            $plan->blocked = get_string('missingassigncap', 'local_bulkcohortenrol');
        }
        if ($plan->blocked === '' && !$cohort && !has_capability('moodle/cohort:manage', $context)) {
            $plan->blocked = get_string('missingmanagecap', 'local_bulkcohortenrol');
        }

        $classified = self::classify_rows($rows, $pending->matchfield, ($blockcode === null) ? $cohort : null);
        foreach ($classified as $property => $value) {
            $plan->{$property} = $value;
        }
        $plan->canapply = ($plan->blocked === '' && $plan->toadd > 0);
        return $plan;
    }

    /**
     * @param string $requested Form value, possibly empty.
     * @param array $rows
     * @return array{0: string, 1: string, 2: string[]}
     */
    private static function resolve_idnumber(string $requested, array $rows): array {
        $requested = trim($requested);
        $values = [];
        foreach ($rows as $row) {
            if ($row['cohort'] !== '') {
                $values[$row['cohort']] = $row['cohort'];
            }
        }
        $cohortvalues = array_values($values);
        if ($requested !== '') {
            return [$requested, 'form', $cohortvalues];
        }
        if (count($cohortvalues) === 1 && \core_text::strlen($cohortvalues[0]) <= 100) {
            return [$cohortvalues[0], 'csv', $cohortvalues];
        }
        if (count($cohortvalues) > 1) {
            return ['', 'csvmany', $cohortvalues];
        }
        return ['', 'none', $cohortvalues];
    }

    /**
     * @param string $name
     * @param string $idnumber
     * @return array{0: \stdClass|null, 1: string|null}
     */
    private static function find_cohort(string $name, string $idnumber): array {
        global $DB;

        $systemcontextid = \context_system::instance()->id;
        if ($idnumber !== '') {
            $matches = $DB->get_records('cohort', ['idnumber' => $idnumber]);
            if (count($matches) > 1) {
                return [null, 'duplicateidnumber'];
            }
            if (count($matches) === 1) {
                $cohort = reset($matches);
                if ((int) $cohort->contextid !== (int) $systemcontextid) {
                    return [$cohort, 'notsystem'];
                }
                return [$cohort, null];
            }
            return [null, null];
        }

        $matches = $DB->get_records('cohort', ['name' => $name, 'contextid' => $systemcontextid]);
        if (count($matches) > 1) {
            return [null, 'ambiguousname'];
        }
        if (count($matches) === 1) {
            return [reset($matches), null];
        }
        return [null, null];
    }

    /**
     * @param array $rows
     * @param string $matchfield
     * @param \stdClass|null $cohort
     * @return array
     */
    private static function classify_rows(array $rows, string $matchfield, ?\stdClass $cohort): array {
        global $DB;

        $seen = [];
        $wanted = [];
        $invalidrows = [];
        $duplicate = 0;
        $rowcount = 0;

        foreach ($rows as $row) {
            if (self::row_is_blank($row)) {
                continue;
            }
            $rowcount++;
            $raw = trim($row[$matchfield]);
            if ($raw === '') {
                $invalidrows[] = self::problem_row($row, get_string('emptyidentifier', 'local_bulkcohortenrol'));
                continue;
            }
            if ($matchfield === 'email' && !validate_email($raw)) {
                $invalidrows[] = self::problem_row($row, get_string('invalidemail', 'local_bulkcohortenrol'));
                continue;
            }
            $key = \core_text::strtolower($raw);
            if (isset($seen[$key])) {
                $duplicate++;
                continue;
            }
            $seen[$key] = true;
            $row['key'] = $key;
            $wanted[] = $row;
        }

        $users = self::load_users(array_column($wanted, 'key'), $matchfield);
        $members = [];
        if ($cohort) {
            $members = $DB->get_records_menu('cohort_members', ['cohortid' => $cohort->id], '', 'userid, id');
        }

        $missingrows = [];
        $userids = [];
        $already = 0;
        $suspended = 0;
        foreach ($wanted as $row) {
            $user = $users[$row['key']] ?? null;
            if (!$user || isguestuser($user)) {
                $missingrows[] = self::problem_row($row, '');
                continue;
            }
            if (isset($members[$user->id])) {
                $already++;
                continue;
            }
            $userids[] = (int) $user->id;
            if (!empty($user->suspended)) {
                $suspended++;
            }
        }

        return [
            'rowcount' => $rowcount,
            'toadd' => count($userids),
            'already' => $already,
            'missing' => count($missingrows),
            'duplicate' => $duplicate,
            'invalid' => count($invalidrows),
            'suspended' => $suspended,
            'missingrows' => array_slice($missingrows, 0, self::PROBLEM_LIMIT),
            'missingmore' => max(0, count($missingrows) - self::PROBLEM_LIMIT),
            'invalidrows' => array_slice($invalidrows, 0, self::PROBLEM_LIMIT),
            'invalidmore' => max(0, count($invalidrows) - self::PROBLEM_LIMIT),
            'userids' => $userids,
        ];
    }

    /**
     * @param string[] $keys
     * @param string $field
     * @return \stdClass[] keyed by the lower-case match value
     */
    private static function load_users(array $keys, string $field): array {
        global $DB, $CFG;

        if (!in_array($field, ['username', 'email'], true)) {
            return [];
        }

        $found = [];
        foreach (array_chunk($keys, 200) as $chunk) {
            if (!$chunk) {
                continue;
            }
            [$insql, $params] = $DB->get_in_or_equal($chunk, SQL_PARAMS_NAMED);
            $params['mnethostid'] = $CFG->mnet_localhost_id;
            $records = $DB->get_records_select(
                'user',
                "deleted = 0 AND mnethostid = :mnethostid AND {$field} {$insql}",
                $params,
                '',
                'id, username, email, firstname, lastname, suspended'
            );
            foreach ($records as $user) {
                $key = \core_text::strtolower($field === 'username' ? $user->username : $user->email);
                $found[$key] = $user;
            }
        }
        return $found;
    }

    /**
     * @param \csv_import_reader $reader
     * @param array $map canonical column => index
     * @return array
     */
    private static function read_rows(\csv_import_reader $reader, array $map): array {
        if (!$reader->init()) {
            return [];
        }
        $rows = [];
        $line = 1;
        while ($data = $reader->next()) {
            $line++;
            $cell = function(string $key) use ($map, $data): string {
                if (!isset($map[$key])) {
                    return '';
                }
                return trim((string) ($data[$map[$key]] ?? ''));
            };
            $rows[] = [
                'line' => $line,
                'username' => $cell('username'),
                'email' => $cell('email'),
                'firstname' => $cell('firstname'),
                'lastname' => $cell('lastname'),
                'cohort' => $cell('cohort'),
            ];
        }
        $reader->close();
        return $rows;
    }

    /**
     * @param array $headers
     * @return array<string, int>
     */
    private static function header_map(array $headers): array {
        $map = [];
        foreach ($headers as $index => $header) {
            $canonical = self::canonical_header((string) $header);
            if ($canonical !== '' && !isset($map[$canonical])) {
                $map[$canonical] = (int) $index;
            }
        }
        return $map;
    }

    /**
     * @param string $header
     * @return string
     */
    private static function canonical_header(string $header): string {
        $header = trim(\core_text::trim_utf8_bom($header));
        $header = \core_text::strtolower($header);
        $aliases = [
            'username' => 'username',
            'user name' => 'username',
            'user_name' => 'username',
            'user' => 'username',
            'שם משתמש' => 'username',
            'email' => 'email',
            'e-mail' => 'email',
            'mail' => 'email',
            'דוא"ל' => 'email',
            'דואל' => 'email',
            'אימייל' => 'email',
            'firstname' => 'firstname',
            'first name' => 'firstname',
            'שם פרטי' => 'firstname',
            'lastname' => 'lastname',
            'last name' => 'lastname',
            'surname' => 'lastname',
            'שם משפחה' => 'lastname',
            'cohort' => 'cohort',
            'cohortidnumber' => 'cohort',
            'קבוצה' => 'cohort',
            'קבוצה מערכתית' => 'cohort',
            'מזהה קבוצה' => 'cohort',
        ];
        return $aliases[$header] ?? '';
    }

    /**
     * @param array $row
     * @return bool
     */
    private static function row_is_blank(array $row): bool {
        foreach (['username', 'email', 'firstname', 'lastname', 'cohort'] as $field) {
            if (($row[$field] ?? '') !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $row
     * @param string $reason
     * @return \stdClass
     */
    private static function problem_row(array $row, string $reason): \stdClass {
        $problem = new \stdClass();
        $problem->line = (int) $row['line'];
        $problem->username = $row['username'];
        $problem->email = $row['email'];
        $problem->fullname = trim($row['firstname'] . ' ' . $row['lastname']);
        $problem->reason = $reason;
        return $problem;
    }
}
