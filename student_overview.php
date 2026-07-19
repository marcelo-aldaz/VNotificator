<?php
require_once('../../config.php');
require_login();
require_once($CFG->dirroot . '/local/pceinotifications/classes/local/analytics/student_profile_service.php');

$systemcontext = context_system::instance();
$PAGE->set_url('/local/pceinotifications/student_overview.php');
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('student_view', 'local_pceinotifications'));
$PAGE->set_heading(get_string('student_view', 'local_pceinotifications'));

$courses = enrol_get_users_courses($USER->id, true, 'id,fullname,shortname,visible');
$eligible = [];
$profileservice = new \local_pceinotifications\local\analytics\student_profile_service();
foreach ($courses as $course) {
    if (empty($course->visible)) {
        continue;
    }
    $context = context_course::instance($course->id);
    if (has_capability('local/pceinotifications:receive', $context)) {
        $eligible[] = $course;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('student_view', 'local_pceinotifications'));
echo html_writer::tag('p', get_string('student_view_desc', 'local_pceinotifications'));

if (empty($eligible)) {
    echo $OUTPUT->notification(get_string('no_student_courses', 'local_pceinotifications'), 'info');
} else {
    echo html_writer::start_div('row');
    foreach ($eligible as $course) {
        $url = new moodle_url('/local/pceinotifications/student_dashboard.php', ['id' => $course->id]);
        echo html_writer::start_div('col-md-4 mb-3');
        echo html_writer::start_div('card h-100');
        echo html_writer::start_div('card-body');
        $payload = $profileservice->get_student_course_payload($USER->id, $course->id);
        echo html_writer::tag('h5', format_string($course->fullname), ['class' => 'card-title']);
        if (!empty($course->shortname)) {
            echo html_writer::tag('p', s($course->shortname), ['class' => 'text-muted']);
        }
        echo html_writer::tag('div', get_string('student_current_risk', 'local_pceinotifications') . ': ' . s($payload['risklabel']), ['class' => 'mb-1']);
        echo html_writer::tag('div', get_string('student_current_priority', 'local_pceinotifications') . ': ' . s($payload['prioritylabel']), ['class' => 'mb-1']);
        echo html_writer::tag('div', get_string('followupstatus', 'local_pceinotifications') . ': ' . s($payload['followuplabel']), ['class' => 'mb-3']);
        echo html_writer::link($url, get_string('open_student_tracking', 'local_pceinotifications'), ['class' => 'btn btn-primary']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
