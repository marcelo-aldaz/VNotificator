<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');
require_once($CFG->dirroot . '/local/pceinotifications/classes/local/analytics/novelty_service.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$context = context_system::instance();
$status = optional_param('status', '', PARAM_ALPHAEXT);
$risk = optional_param('risk', '', PARAM_ALPHAEXT);
$courseid = optional_param('courseid', 0, PARAM_INT);

$PAGE->set_url('/local/pceinotifications/admin_novelties.php', ['status' => $status, 'risk' => $risk, 'courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('admin_novelties', 'local_pceinotifications'));
$PAGE->set_heading(get_string('admin_novelties', 'local_pceinotifications'));

$service = new \local_pceinotifications\local\analytics\novelty_service();
$summary = $service->get_summary();
$records = $service->get_recent_novelties(['status' => $status, 'risklevel' => $risk, 'courseid' => $courseid], 200);
$courses = [0 => get_string('filter_all', 'local_pceinotifications')];
foreach ($DB->get_records_select('course', 'id > 1', null, 'fullname ASC', 'id,fullname') as $course) {
    $courses[(int)$course->id] = format_string($course->fullname);
}
$styles = \local_pceinotifications\util::page_styles();

echo $OUTPUT->header();
echo html_writer::tag('style', $styles . '
.vtn-admin-filters .form-select{min-height:56px;line-height:1.35;padding-top:.95rem;padding-bottom:.95rem;padding-right:2.8rem;font-size:1rem;white-space:normal;background-position:right .9rem center;}
.vtn-admin-filters .form-label{font-weight:700;color:#12344d;margin-bottom:.45rem;}
.vtn-admin-filters .col-lg-6{display:flex;flex-direction:column;}
.vtn-admin-filters .form-select option{white-space:normal;}
.vtn-admin-filters__hint{margin-top:.15rem;color:#5b7083;font-size:.88rem;}');
echo html_writer::start_div('vtn-shell');
echo html_writer::start_div('vtn-hero');
echo html_writer::tag('div', get_string('admin_novelties', 'local_pceinotifications'), ['class' => 'vtn-hero__title']);
echo html_writer::tag('p', get_string('admin_novelties_desc_v920', 'local_pceinotifications'), ['class' => 'vtn-hero__text']);
echo html_writer::end_div();

echo html_writer::start_div('vtn-kpis');
foreach ([
    ['label' => get_string('novelty_total', 'local_pceinotifications'), 'value' => $summary['total'], 'tone' => 'blue'],
    ['label' => get_string('novelty_status_open', 'local_pceinotifications'), 'value' => $summary['open'], 'tone' => 'orange'],
    ['label' => get_string('novelty_status_reviewed', 'local_pceinotifications'), 'value' => $summary['reviewed'], 'tone' => 'blue'],
    ['label' => get_string('novelty_status_closed', 'local_pceinotifications'), 'value' => $summary['closed'], 'tone' => 'green'],
] as $card) {
    echo html_writer::start_div('vtn-card vtn-kpi vtn-kpi--' . $card['tone']);
    echo html_writer::start_div('vtn-card__body');
    echo html_writer::tag('div', s((string)$card['value']), ['class' => 'vtn-kpi__value']);
    echo html_writer::tag('div', $card['label'], ['class' => 'vtn-kpi__label']);
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('vtn-card');
echo html_writer::start_div('vtn-card__body');
echo html_writer::tag('div', get_string('admin_novelties_filters_title', 'local_pceinotifications'), ['class' => 'vtn-section-title']);
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'vtn-filters vtn-admin-filters']);
echo html_writer::start_div('row g-3');
echo html_writer::start_div('col-lg-6 col-md-12');
echo html_writer::tag('label', get_string('filter_course', 'local_pceinotifications'), ['for' => 'courseid', 'class' => 'form-label']);
echo html_writer::select($courses, 'courseid', $courseid, false, ['id' => 'courseid', 'class' => 'form-select']);
echo html_writer::div('Seleccione el curso para limitar la revisión de novedades institucionales.', 'vtn-admin-filters__hint');
echo html_writer::end_div();
echo html_writer::start_div('col-lg-3 col-md-6');
echo html_writer::tag('label', get_string('novelty_status', 'local_pceinotifications'), ['for' => 'status', 'class' => 'form-label']);
echo html_writer::select(['' => get_string('filter_all', 'local_pceinotifications'), 'open' => get_string('novelty_status_open', 'local_pceinotifications'), 'reviewed' => get_string('novelty_status_reviewed', 'local_pceinotifications'), 'closed' => get_string('novelty_status_closed', 'local_pceinotifications')], 'status', $status, false, ['id' => 'status', 'class' => 'form-select']);
echo html_writer::end_div();
echo html_writer::start_div('col-lg-3 col-md-6');
echo html_writer::tag('label', get_string('filter_risk', 'local_pceinotifications'), ['for' => 'risk', 'class' => 'form-label']);
echo html_writer::select(['' => get_string('filter_all', 'local_pceinotifications'), 'red' => get_string('risk_red', 'local_pceinotifications'), 'orange' => get_string('risk_orange', 'local_pceinotifications'), 'yellow' => get_string('risk_yellow', 'local_pceinotifications'), 'green' => get_string('risk_green', 'local_pceinotifications'), 'recovered' => get_string('risk_recovered', 'local_pceinotifications')], 'risk', $risk, false, ['id' => 'risk', 'class' => 'form-select']);
echo html_writer::end_div();
echo html_writer::start_div('col-12');
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary me-2', 'value' => get_string('filter_apply', 'local_pceinotifications')]);
echo html_writer::link(new moodle_url('/local/pceinotifications/admin_novelties.php'), get_string('filter_clear', 'local_pceinotifications'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

if ($records) {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover vtn-table';
    $table->head = [get_string('col_time', 'local_pceinotifications'), get_string('col_course', 'local_pceinotifications'), get_string('student', 'local_pceinotifications'), get_string('teacher_view', 'local_pceinotifications'), get_string('student_current_risk', 'local_pceinotifications'), get_string('student_current_priority', 'local_pceinotifications'), get_string('novelty_title', 'local_pceinotifications'), get_string('novelty_detail', 'local_pceinotifications'), get_string('novelty_status', 'local_pceinotifications'), get_string('novelty_visibility', 'local_pceinotifications'), get_string('case_resolution_student_response', 'local_pceinotifications'), get_string('case_resolution_teacher_validation', 'local_pceinotifications'), get_string('case_resolution_closed_on_label', 'local_pceinotifications')];
    $table->data = [];
    foreach ($records as $item) {
        $course = $DB->get_record('course', ['id' => $item->courseid], 'id,fullname');
        $student = core_user::get_user($item->userid, 'id,firstname,lastname,middlename,alternatename,firstnamephonetic,lastnamephonetic');
        $teacher = core_user::get_user($item->teacherid, 'id,firstname,lastname,middlename,alternatename,firstnamephonetic,lastnamephonetic');
        $table->data[] = [
            userdate($item->timemodified),
            $course ? format_string($course->fullname) : '-',
            $student ? fullname($student) : '-',
            $teacher ? fullname($teacher) : '-',
            !empty($item->risklevel) ? \local_pceinotifications\util::badge(get_string('risk_' . $item->risklevel, 'local_pceinotifications'), $item->risklevel === 'red' ? 'red' : ($item->risklevel === 'orange' || $item->risklevel === 'yellow' ? 'orange' : 'green')) : '-',
            !empty($item->priority) ? \local_pceinotifications\util::badge(get_string('priority_' . $item->priority, 'local_pceinotifications'), $item->priority === 'high' ? 'red' : ($item->priority === 'medium' ? 'orange' : 'blue')) : '-',
            s($item->title),
            s($item->detail),
            \local_pceinotifications\util::badge(get_string('novelty_status_' . $item->status, 'local_pceinotifications'), $item->status === 'closed' ? 'green' : ($item->status === 'reviewed' ? 'blue' : 'orange')),
            \local_pceinotifications\util::badge(get_string('novelty_visibility_' . $item->visibility, 'local_pceinotifications'), $item->visibility === 'shared' ? 'blue' : 'slate'),
            !empty($item->studentresponse) ? s($item->studentresponse) : '-',
            !empty($item->teachervalidation) ? s($item->teachervalidation) : '-',
            !empty($item->timeclosed) ? userdate($item->timeclosed) : '-',
        ];
    }
    echo html_writer::start_div('vtn-card');
    echo html_writer::start_div('vtn-card__body');
    echo html_writer::table($table);
    echo html_writer::end_div();
    echo html_writer::end_div();
} else {
    echo html_writer::div(get_string('novelty_none', 'local_pceinotifications'), 'vtn-empty');
}

echo html_writer::end_div();
echo $OUTPUT->footer();
