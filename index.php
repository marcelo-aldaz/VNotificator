<?php
require_once('../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_url('/local/pceinotifications/index.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_pceinotifications'));
$PAGE->set_heading(get_string('pluginname', 'local_pceinotifications'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_pceinotifications'));
echo html_writer::tag('p', get_string('profile_tracking_desc', 'local_pceinotifications'));

$cards = [];
if (has_capability('moodle/site:config', $context)) {
    $cards[] = [
        'title' => get_string('admin_dashboard', 'local_pceinotifications'),
        'desc' => get_string('admin_dashboard_desc', 'local_pceinotifications'),
        'url' => new moodle_url('/local/pceinotifications/admin_dashboard.php'),
        'btn' => 'btn btn-primary'
    ];
    $cards[] = [
        'title' => get_string('dashboardadvanced', 'local_pceinotifications'),
        'desc' => get_string('dashboardadvancedsubtitle', 'local_pceinotifications'),
        'url' => new moodle_url('/local/pceinotifications/advanced_dashboard.php'),
        'btn' => 'btn btn-secondary'
    ];
}

$courses = enrol_get_users_courses($USER->id, true, 'id,visible');
$hastaught = false;
$hasstudent = false;
foreach ($courses as $course) {
    if (empty($course->visible)) { continue; }
    $coursecontext = context_course::instance($course->id);
    if (has_capability('local/pceinotifications:manage', $coursecontext) || has_capability('local/pceinotifications:viewlogs', $coursecontext)) { $hastaught = true; }
    if (has_capability('local/pceinotifications:receive', $coursecontext)) { $hasstudent = true; }
}
if ($hastaught) {
    $cards[] = [
        'title' => get_string('teacher_view', 'local_pceinotifications'),
        'desc' => get_string('teacher_view_desc', 'local_pceinotifications'),
        'url' => new moodle_url('/local/pceinotifications/teacher_overview.php'),
        'btn' => 'btn btn-primary'
    ];
}
if ($hasstudent) {
    $cards[] = [
        'title' => get_string('student_view', 'local_pceinotifications'),
        'desc' => get_string('student_view_desc', 'local_pceinotifications'),
        'url' => new moodle_url('/local/pceinotifications/student_overview.php'),
        'btn' => 'btn btn-primary'
    ];
}

$cards[] = [
    'title' => get_string('pluginsettings', 'local_pceinotifications'),
    'desc' => get_string('pluginsettings_desc', 'local_pceinotifications'),
    'url' => new moodle_url('/admin/settings.php', ['section' => 'local_pceinotifications']),
    'btn' => 'btn btn-outline-primary'
];

echo html_writer::start_div('row');
foreach ($cards as $card) {
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::start_div('card h-100');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', $card['title'], ['class' => 'card-title']);
    echo html_writer::tag('p', $card['desc'], ['class' => 'card-text']);
    echo html_writer::link($card['url'], get_string('open', 'local_pceinotifications'), ['class' => $card['btn']]);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo $OUTPUT->footer();
