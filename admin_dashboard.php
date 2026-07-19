<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$context = context_system::instance();
$PAGE->set_url('/local/pceinotifications/admin_dashboard.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('admin_dashboard', 'local_pceinotifications'));
$PAGE->set_heading(get_string('admin_dashboard', 'local_pceinotifications'));

$courses = $DB->count_records_select('course', 'id > 1');
$sent = $DB->count_records('local_pceinotif_log', ['success' => 1]);
$failed = $DB->count_records('local_pceinotif_log', ['success' => 0]);
$advancedurl = new moodle_url('/local/pceinotifications/advanced_dashboard.php');
$recalcurl = new moodle_url('/local/pceinotifications/recalculate_metrics.php', ['periodtype' => 'monthly', 'periodkey' => date('Y-m'), 'sesskey' => sesskey()]);
$settingsurl = new moodle_url('/admin/settings.php', ['section' => 'local_pceinotifications']);
$teacheroverviewurl = new moodle_url('/local/pceinotifications/teacher_overview.php');
$studentoverviewurl = new moodle_url('/local/pceinotifications/student_overview.php');
$noveltiesurl = new moodle_url('/local/pceinotifications/admin_novelties.php');
$noveltytotal = $DB->get_manager()->table_exists(new xmldb_table('local_pceinotif_novelty')) ? $DB->count_records('local_pceinotif_novelty') : 0;
$styles = \local_pceinotifications\util::page_styles();

$cards = [
    ['title' => get_string('dashboardadvanced', 'local_pceinotifications'), 'desc' => get_string('dashboardadvancedsubtitle', 'local_pceinotifications'), 'url' => $advancedurl, 'button' => 'btn btn-primary'],
    ['title' => get_string('recalculatemetrics', 'local_pceinotifications'), 'desc' => get_string('recalculate_desc', 'local_pceinotifications'), 'url' => $recalcurl, 'button' => 'btn btn-outline-primary'],
    ['title' => get_string('teacher_view', 'local_pceinotifications'), 'desc' => get_string('teacher_view_desc', 'local_pceinotifications'), 'url' => $teacheroverviewurl, 'button' => 'btn btn-outline-primary'],
    ['title' => get_string('student_view', 'local_pceinotifications'), 'desc' => get_string('student_view_desc', 'local_pceinotifications'), 'url' => $studentoverviewurl, 'button' => 'btn btn-outline-primary'],
    ['title' => get_string('admin_novelties', 'local_pceinotifications'), 'desc' => get_string('admin_novelties_desc', 'local_pceinotifications'), 'url' => $noveltiesurl, 'button' => 'btn btn-outline-primary'],
    ['title' => get_string('pluginsettings', 'local_pceinotifications'), 'desc' => get_string('pluginsettings_desc', 'local_pceinotifications'), 'url' => $settingsurl, 'button' => 'btn btn-outline-secondary'],
    // Card for global resend notifications. Provides access to the manual resend engine.
    ['title' => get_string('globalresend', 'local_pceinotifications'),
     'desc' => get_string('globalresenddesc', 'local_pceinotifications'),
     'url' => new moodle_url('/local/pceinotifications/admin_global_resend.php'),
     'button' => 'btn btn-outline-primary'],
];
$recent = $DB->get_records('local_pceinotif_log', [], 'timesent DESC', '*', 0, 30);

echo $OUTPUT->header();
echo html_writer::tag('style', $styles);
echo html_writer::start_div('vtn-shell');
echo html_writer::start_div('vtn-hero');
echo html_writer::tag('div', get_string('admin_dashboard', 'local_pceinotifications'), ['class' => 'vtn-hero__title']);
echo html_writer::tag('p', get_string('admin_dashboard_desc', 'local_pceinotifications'), ['class' => 'vtn-hero__text']);
echo html_writer::end_div();

echo html_writer::start_div('vtn-kpis');
foreach ([
    ['label' => get_string('admin_kpi_courses', 'local_pceinotifications', $courses), 'value' => $courses, 'tone' => 'blue'],
    ['label' => get_string('admin_kpi_sent', 'local_pceinotifications', $sent), 'value' => $sent, 'tone' => 'green'],
    ['label' => get_string('admin_kpi_failed', 'local_pceinotifications', $failed), 'value' => $failed, 'tone' => 'red'],
    ['label' => get_string('admin_kpi_novelties', 'local_pceinotifications', $noveltytotal), 'value' => $noveltytotal, 'tone' => 'orange'],
] as $card) {
    echo html_writer::start_div('vtn-card vtn-kpi vtn-kpi--' . $card['tone']);
    echo html_writer::start_div('vtn-card__body');
    echo html_writer::tag('div', s((string)$card['value']), ['class' => 'vtn-kpi__value']);
    echo html_writer::tag('div', $card['label'], ['class' => 'vtn-kpi__label']);
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('vtn-grid vtn-grid--equal');
foreach ($cards as $card) {
    echo html_writer::start_div('vtn-card');
    echo html_writer::start_div('vtn-card__body');
    echo html_writer::tag('div', $card['title'], ['class' => 'vtn-section-title']);
    echo html_writer::tag('p', $card['desc'], ['class' => 'vtn-section-subtitle']);
    echo html_writer::link($card['url'], get_string('open', 'local_pceinotifications'), ['class' => $card['button']]);
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('vtn-card');
echo html_writer::start_div('vtn-card__body');
echo html_writer::tag('div', get_string('admin_recent_notifications', 'local_pceinotifications'), ['class' => 'vtn-section-title']);
echo html_writer::tag('p', get_string('teacher_recent_notifications_desc', 'local_pceinotifications'), ['class' => 'vtn-section-subtitle']);
if (!empty($recent)) {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover vtn-table';
    $table->head = [get_string('col_time', 'local_pceinotifications'), get_string('col_course', 'local_pceinotifications'), get_string('col_notiftype', 'local_pceinotifications'), get_string('col_subject', 'local_pceinotifications'), get_string('col_status', 'local_pceinotifications')];
    foreach ($recent as $r) {
        $course = $DB->get_field('course', 'fullname', ['id' => $r->courseid]) ?: '-';
        $table->data[] = [
            !empty($r->timesent) ? userdate($r->timesent) : '-',
            format_string($course),
            \local_pceinotifications\util::badge((string)$r->notiftype, 'blue'),
            s($r->subject),
            ((int)$r->success === 1) ? \local_pceinotifications\util::badge(get_string('status_sent', 'local_pceinotifications'), 'green') : \local_pceinotifications\util::badge(get_string('status_failed', 'local_pceinotifications'), 'red'),
        ];
    }
    echo html_writer::table($table);
} else {
    echo html_writer::div(get_string('no_notifications', 'local_pceinotifications'), 'vtn-empty');
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();
