<?php
require_once('../../config.php');
require_login();

$systemcontext = context_system::instance();
$PAGE->set_url('/local/pceinotifications/teacher_overview.php');
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('teacher_view', 'local_pceinotifications'));
$PAGE->set_heading(get_string('teacher_view', 'local_pceinotifications'));

$courses = enrol_get_users_courses($USER->id, true, 'id,fullname,shortname,visible');
$eligible = [];
foreach ($courses as $course) {
    if (empty($course->visible)) {
        continue;
    }
    $context = context_course::instance($course->id);
    if (has_capability('local/pceinotifications:manage', $context) || has_capability('local/pceinotifications:viewlogs', $context)) {
        $eligible[] = $course;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('teacher_view', 'local_pceinotifications'));
echo html_writer::tag('p', get_string('teacher_view_desc', 'local_pceinotifications'));

if (empty($eligible)) {
    echo $OUTPUT->notification(get_string('no_teacher_courses', 'local_pceinotifications'), 'info');
} else {
    echo html_writer::start_div('row');
    foreach ($eligible as $course) {
        $url = new moodle_url('/local/pceinotifications/teacher_dashboard.php', ['id' => $course->id]);
        echo html_writer::start_div('col-md-4 mb-3');
        echo html_writer::start_div('card h-100');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', format_string($course->fullname), ['class' => 'card-title']);
        if (!empty($course->shortname)) {
            echo html_writer::tag('p', s($course->shortname), ['class' => 'text-muted']);
        }
        echo html_writer::link($url, get_string('open_teacher_tracking', 'local_pceinotifications'), ['class' => 'btn btn-primary']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
