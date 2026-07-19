<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);

$PAGE->set_url('/local/pceinotifications/course_dashboard.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('course_dashboard', 'local_pceinotifications'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course_dashboard', 'local_pceinotifications'));

echo html_writer::div(get_string('course_dashboard_desc', 'local_pceinotifications'), 'alert alert-info');

echo html_writer::start_div('mb-3');
echo html_writer::link(
    new moodle_url('/local/pceinotifications/progress.php', ['id' => $courseid]),
    get_string('course_dashboard_go_progress', 'local_pceinotifications'),
    ['class' => 'btn btn-primary me-2']
);
echo html_writer::link(
    new moodle_url('/local/pceinotifications/notifications.php', ['id' => $courseid, 'mode' => 'course']),
    get_string('course_dashboard_go_notifications', 'local_pceinotifications'),
    ['class' => 'btn btn-secondary me-2']
);
echo html_writer::link(
    new moodle_url('/local/pceinotifications/teacher_dashboard.php', ['id' => $courseid]),
    get_string('teacher_dashboard', 'local_pceinotifications'),
    ['class' => 'btn btn-outline-secondary']
);
echo html_writer::end_div();

$logs = $DB->get_records('local_pceinotif_log', ['courseid' => $courseid], 'timesent DESC', '*', 0, 10);
if (!empty($logs)) {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-striped table-hover';
    $table->caption = get_string('course_dashboard_recent_logs', 'local_pceinotifications');
    $table->head = [
        get_string('col_time', 'local_pceinotifications'),
        get_string('col_notiftype', 'local_pceinotifications'),
        get_string('col_subject', 'local_pceinotifications'),
        get_string('col_status', 'local_pceinotifications')
    ];
    foreach ($logs as $l) {
        $table->data[] = [
            !empty($l->timesent) ? userdate($l->timesent) : '-',
            s($l->notiftype),
            s($l->subject),
            ((int)$l->success === 1)
                ? html_writer::span(get_string('status_sent', 'local_pceinotifications'), 'badge bg-success')
                : html_writer::span(get_string('status_failed', 'local_pceinotifications'), 'badge bg-danger'),
        ];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('no_notifications', 'local_pceinotifications'), 'info');
}

echo $OUTPUT->footer();
