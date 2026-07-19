<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
$context = context_course::instance($id);

require_login($course);
require_capability('local/pceinotifications:manage', $context);

$action = optional_param('action', '', PARAM_ALPHA);

$PAGE->set_url('/local/pceinotifications/course.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('pceinotifications', 'local_pceinotifications'));
$PAGE->set_heading($course->fullname);

if ($action === 'sync' && confirm_sesskey()) {
    $stats = \local_pceinotifications\util::sync_course_blocks($course);
    $msg = get_string('sync_blocks_done_stats', 'local_pceinotifications', (object)$stats);
    redirect($PAGE->url, $msg);
}

if ($action === 'calendar' && confirm_sesskey()) {
    $stats = \local_pceinotifications\util::sync_course_calendar($course);
    $msg = get_string('sync_calendar_done_stats', 'local_pceinotifications', (object)$stats);
    redirect($PAGE->url, $msg);
}

$blocks = $DB->get_records('local_pceinotif_blocks', ['courseid' => $id], 'sequenceindex ASC');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('blocks_title', 'local_pceinotifications'));

$syncurl = new moodle_url($PAGE->url, ['action' => 'sync', 'sesskey' => sesskey()]);
echo html_writer::div(
    html_writer::link($syncurl, get_string('sync_blocks', 'local_pceinotifications'), [
        'class' => 'btn btn-primary',
        'onclick' => "return confirm('".get_string('confirm_resync','local_pceinotifications')."');"
    ]),
    'mb-3'
);

if (empty($blocks)) {
    echo $OUTPUT->notification(get_string('no_blocks', 'local_pceinotifications'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('col_section', 'local_pceinotifications'),
    get_string('col_type', 'local_pceinotifications'),
    get_string('col_state', 'local_pceinotifications'),
    get_string('col_source', 'local_pceinotifications'),
    get_string('col_start', 'local_pceinotifications'),
    get_string('col_end', 'local_pceinotifications'),
    get_string('col_calendar', 'local_pceinotifications'),
    get_string('col_actions', 'local_pceinotifications'),
];

$table->data = [];
foreach ($blocks as $b) {
    $type = \local_pceinotifications\util::get_type_label($b->blocktype);
    $state = \local_pceinotifications\util::get_state_label($b->blockstate ?? 'detected');
    $source = !empty($b->syncsource) ? s($b->syncsource) : '-';
    $start = $b->startdate ? userdate($b->startdate, '%d/%m/%Y') : '-';
    $end = $b->enddate ? userdate($b->enddate, '%d/%m/%Y') : '-';
    $calendar = \local_pceinotifications\util::get_calendar_status_label($b->calendarstatus ?? 'pending');
    $editurl = new moodle_url('/local/pceinotifications/edit_block.php', ['id' => $id, 'blockid' => $b->id]);
    $actions = html_writer::link($editurl, get_string('edit_block', 'local_pceinotifications'), ['class' => 'btn btn-sm btn-secondary']);
    $table->data[] = [format_string($b->sectionname), $type, $state, $source, $start, $end, $calendar, $actions];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
