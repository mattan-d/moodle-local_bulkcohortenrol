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
 * Upload a CSV file, create a system cohort, and assign the users.
 *
 * @package    local_bulkcohortenrol
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$action = optional_param('action', '', PARAM_ALPHA);

admin_externalpage_setup('local_bulkcohortenrol');
if ($action !== '') {
    $PAGE->set_url(new moodle_url('/local/bulkcohortenrol/index.php', ['action' => $action]));
}

if ($action === 'cancel') {
    require_sesskey();
    \local_bulkcohortenrol\importer::clear_pending();
    redirect(new moodle_url('/local/bulkcohortenrol/index.php'));
}

if ($action === 'apply' && confirm_sesskey() && data_submitted()) {
    $result = \local_bulkcohortenrol\importer::apply();
    \local_bulkcohortenrol\importer::clear_pending();
    $message = get_string('applyresult', 'local_bulkcohortenrol', (object) [
        'name' => format_string($result->cohortname),
        'status' => get_string($result->created ? 'statuscreated' : 'statusexisting', 'local_bulkcohortenrol'),
        'added' => $result->added,
        'already' => $result->already,
        'missing' => $result->missing,
    ]);
    redirect(
        new moodle_url('/cohort/assign.php', ['id' => $result->cohortid]),
        $message,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$form = new \local_bulkcohortenrol\form\upload_form();
if ($data = $form->get_data()) {
    \local_bulkcohortenrol\importer::remember_upload(
        (int) $data->csvfile,
        $data->cohortname,
        $data->cohortidnumber ?? '',
        $data->delimiter,
        $data->encoding,
        $data->matchfield
    );
    redirect(new moodle_url('/local/bulkcohortenrol/index.php', ['action' => 'preview']));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_bulkcohortenrol'));

if ($action === 'preview') {
    echo $OUTPUT->heading(get_string('previewheading', 'local_bulkcohortenrol'), 3);
    try {
        $plan = \local_bulkcohortenrol\importer::plan();
    } catch (\moodle_exception $exception) {
        echo $OUTPUT->notification($exception->getMessage(), \core\output\notification::NOTIFY_ERROR);
        echo $OUTPUT->single_button(
            new moodle_url('/local/bulkcohortenrol/index.php'),
            get_string('back', 'local_bulkcohortenrol')
        );
        echo $OUTPUT->footer();
        exit;
    }
    local_bulkcohortenrol_render_plan($plan);
} else {
    echo $OUTPUT->notification(get_string('intro', 'local_bulkcohortenrol'), \core\output\notification::NOTIFY_INFO);
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/local/bulkcohortenrol/sample.php'),
            get_string('downloadsample', 'local_bulkcohortenrol')
        ),
        'mb-3'
    );
    echo html_writer::tag('h4', get_string('csvformat', 'local_bulkcohortenrol'));
    echo html_writer::tag('p', get_string('csvformatdesc', 'local_bulkcohortenrol'));
    $form->display();
}

echo $OUTPUT->footer();

/**
 * Render the preview summary and the confirm action.
 *
 * @param \stdClass $plan
 */
function local_bulkcohortenrol_render_plan(\stdClass $plan): void {
    global $OUTPUT;

    if ($plan->blocked !== '') {
        echo $OUTPUT->notification($plan->blocked, \core\output\notification::NOTIFY_ERROR);
    }
    foreach ($plan->notes as $note) {
        echo $OUTPUT->notification($note, \core\output\notification::NOTIFY_WARNING);
    }

    if ($plan->cohort) {
        $status = get_string('cohortstatusexisting', 'local_bulkcohortenrol', format_string($plan->cohort->name));
    } else if ($plan->blocked === '') {
        $status = get_string('cohortstatusnew', 'local_bulkcohortenrol');
    } else {
        $status = '';
    }

    $rows = [
        get_string('cohortname', 'local_bulkcohortenrol') => format_string($plan->cohortname),
        get_string('cohortidnumber', 'local_bulkcohortenrol') => $plan->idnumber !== '' ? s($plan->idnumber) : '-',
    ];
    if ($status !== '') {
        $rows[get_string('summary', 'local_bulkcohortenrol')] = $status;
    }
    $rows[get_string('rows', 'local_bulkcohortenrol')] = $plan->rowcount;
    $rows[get_string('toadd', 'local_bulkcohortenrol')] = $plan->toadd;
    $rows[get_string('already', 'local_bulkcohortenrol')] = $plan->already;
    $rows[get_string('missing', 'local_bulkcohortenrol')] = $plan->missing;
    $rows[get_string('duplicate', 'local_bulkcohortenrol')] = $plan->duplicate;
    $rows[get_string('invalid', 'local_bulkcohortenrol')] = $plan->invalid;
    if ($plan->suspended > 0) {
        $rows[get_string('suspended', 'local_bulkcohortenrol')] = $plan->suspended;
    }

    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    $table->data = [];
    foreach ($rows as $label => $value) {
        $table->data[] = [html_writer::tag('strong', $label), $value];
    }
    echo html_writer::table($table);

    local_bulkcohortenrol_render_problems(
        get_string('missingusers', 'local_bulkcohortenrol'),
        $plan->missingrows,
        $plan->missingmore,
        false
    );
    local_bulkcohortenrol_render_problems(
        get_string('invalidrows', 'local_bulkcohortenrol'),
        $plan->invalidrows,
        $plan->invalidmore,
        true
    );

    if (!$plan->canapply) {
        if ($plan->blocked === '') {
            echo $OUTPUT->notification(get_string('nousers', 'local_bulkcohortenrol'), \core\output\notification::NOTIFY_INFO);
        }
    } else {
        $confirm = $plan->cohort
            ? get_string('confirmexisting', 'local_bulkcohortenrol')
            : get_string('confirmcreate', 'local_bulkcohortenrol');
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => (new moodle_url('/local/bulkcohortenrol/index.php', ['action' => 'apply']))->out(false),
            'class' => 'mt-3',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'class' => 'btn btn-primary',
            'value' => $confirm,
        ]);
        echo ' ';
        echo html_writer::link(
            new moodle_url('/local/bulkcohortenrol/index.php', ['action' => 'cancel', 'sesskey' => sesskey()]),
            get_string('cancel'),
            ['class' => 'btn btn-secondary']
        );
        echo html_writer::end_tag('form');
    }

    if (!$plan->canapply) {
        echo html_writer::div(
            html_writer::link(
                new moodle_url('/local/bulkcohortenrol/index.php', ['action' => 'cancel', 'sesskey' => sesskey()]),
                get_string('back', 'local_bulkcohortenrol'),
                ['class' => 'btn btn-secondary']
            ),
            'mt-3'
        );
    }
}

/**
 * @param string $heading
 * @param \stdClass[] $problems
 * @param int $more
 * @param bool $withreason
 */
function local_bulkcohortenrol_render_problems(string $heading, array $problems, int $more, bool $withreason): void {
    if (!$problems) {
        return;
    }
    echo html_writer::tag('h4', $heading);
    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    $table->head = [
        get_string('line', 'local_bulkcohortenrol'),
        get_string('username', 'local_bulkcohortenrol'),
        get_string('fullname', 'local_bulkcohortenrol'),
        get_string('email', 'local_bulkcohortenrol'),
    ];
    if ($withreason) {
        $table->head[] = get_string('reason', 'local_bulkcohortenrol');
    }
    foreach ($problems as $problem) {
        $row = [
            $problem->line,
            s($problem->username),
            s($problem->fullname),
            s($problem->email),
        ];
        if ($withreason) {
            $row[] = s($problem->reason);
        }
        $table->data[] = $row;
    }
    echo html_writer::table($table);
    if ($more > 0) {
        echo html_writer::tag('p', get_string('andmore', 'local_bulkcohortenrol', $more));
    }
}
