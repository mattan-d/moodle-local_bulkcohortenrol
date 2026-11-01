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
 * CSV upload form.
 *
 * @package    local_bulkcohortenrol
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_bulkcohortenrol\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/csvlib.class.php');

/**
 * Cohort details and the user CSV file.
 */
class upload_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'cohortname', get_string('cohortname', 'local_bulkcohortenrol'), [
            'maxlength' => 254,
            'size' => 50,
        ]);
        $mform->setType('cohortname', PARAM_TEXT);
        $mform->addRule('cohortname', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('cohortname', 'cohortname', 'local_bulkcohortenrol');

        $mform->addElement('text', 'cohortidnumber', get_string('cohortidnumber', 'local_bulkcohortenrol'), [
            'maxlength' => 100,
            'size' => 50,
        ]);
        $mform->setType('cohortidnumber', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('cohortidnumber', 'cohortidnumber', 'local_bulkcohortenrol');

        $mform->addElement('select', 'matchfield', get_string('matchfield', 'local_bulkcohortenrol'), [
            'username' => get_string('matchusername', 'local_bulkcohortenrol'),
            'email' => get_string('matchemail', 'local_bulkcohortenrol'),
        ]);
        $mform->setType('matchfield', PARAM_ALPHA);
        $mform->setDefault('matchfield', 'username');
        $mform->addHelpButton('matchfield', 'matchfield', 'local_bulkcohortenrol');

        $mform->addElement('filepicker', 'csvfile', get_string('csvfile', 'local_bulkcohortenrol'), null, [
            'maxbytes' => \local_bulkcohortenrol\importer::MAX_BYTES,
            'accepted_types' => ['.csv', '.txt'],
        ]);
        $mform->addRule('csvfile', null, 'required', null, 'client');
        $mform->addHelpButton('csvfile', 'csvfile', 'local_bulkcohortenrol');

        $mform->addElement('select', 'delimiter', get_string('delimiter', 'local_bulkcohortenrol'), [
            'comma' => get_string('delimitercomma', 'local_bulkcohortenrol'),
            'semicolon' => get_string('delimitersemicolon', 'local_bulkcohortenrol'),
            'colon' => get_string('delimitercolon', 'local_bulkcohortenrol'),
            'tab' => get_string('delimitertab', 'local_bulkcohortenrol'),
        ]);
        $mform->setType('delimiter', PARAM_ALPHA);
        $mform->setDefault('delimiter', 'comma');

        $mform->addElement('select', 'encoding', get_string('encoding', 'local_bulkcohortenrol'), \core_text::get_encodings());
        $mform->setType('encoding', PARAM_RAW_TRIMMED);
        $mform->setDefault('encoding', 'UTF-8');

        $this->add_action_buttons(false, get_string('upload', 'local_bulkcohortenrol'));
    }

    /**
     * Validate the cohort fields and the CSV contents.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $name = trim($data['cohortname'] ?? '');
        if ($name === '') {
            $errors['cohortname'] = get_string('required');
        } else if (\core_text::strlen($name) > 254) {
            $errors['cohortname'] = get_string('nametoolong', 'local_bulkcohortenrol');
        }

        $idnumber = trim($data['cohortidnumber'] ?? '');
        if (\core_text::strlen($idnumber) > 100) {
            $errors['cohortidnumber'] = get_string('idnumbertoolong', 'local_bulkcohortenrol');
        }

        if (!in_array($data['matchfield'] ?? '', ['username', 'email'], true)) {
            $errors['matchfield'] = get_string('invalidmatchfield', 'local_bulkcohortenrol');
        }
        $delimiters = \csv_import_reader::get_delimiter_list();
        if (!array_key_exists($data['delimiter'] ?? '', $delimiters)) {
            $errors['delimiter'] = get_string('invaliddelimiter', 'local_bulkcohortenrol');
        }
        $encodings = \core_text::get_encodings();
        if (!array_key_exists($data['encoding'] ?? '', $encodings)) {
            $errors['encoding'] = get_string('invalidencoding', 'local_bulkcohortenrol');
        }

        $drafts = $this->get_draft_files('csvfile');
        if (!$drafts) {
            $errors['csvfile'] = get_string('required');
            return $errors;
        }
        if (!empty($errors['csvfile']) || !empty($errors['delimiter']) || !empty($errors['encoding']) || !empty($errors['matchfield'])) {
            return $errors;
        }

        $file = reset($drafts);
        if ($file->get_filesize() > \local_bulkcohortenrol\importer::MAX_BYTES) {
            $errors['csvfile'] = get_string('filetoobig', 'local_bulkcohortenrol');
            return $errors;
        }

        [$reader, $error] = \local_bulkcohortenrol\importer::open_content(
            $file->get_content(),
            $data['encoding'],
            $data['delimiter'],
            $data['matchfield']
        );
        if ($reader) {
            $reader->cleanup();
        }
        if ($error !== null) {
            $errors['csvfile'] = $error;
        }
        return $errors;
    }
}
