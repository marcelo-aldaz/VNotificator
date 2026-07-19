<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');
require_once($CFG->dirroot . '/local/pceinotifications/classes/local/analytics/student_profile_service.php');
require_once($CFG->dirroot . '/local/pceinotifications/classes/local/analytics/novelty_service.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/pceinotifications:receive', $context);

$PAGE->set_url('/local/pceinotifications/student_dashboard.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('student_dashboard', 'local_pceinotifications'));
$PAGE->set_heading($course->fullname);

$service = new \local_pceinotifications\local\analytics\student_profile_service();
$payload = $service->get_student_course_payload($USER->id, $courseid);
$riskrecord = $payload['riskrecord'];
$progress = $payload['progress'];
$noveltyservice = new \local_pceinotifications\local\analytics\novelty_service();
$sharednovelties = $noveltyservice->get_shared_student_novelties($courseid, $USER->id, 10);
$evidencebar = ['high' => '100%', 'medium' => '66%', 'low' => '33%'][$payload['evidence']] ?? '33%';
$lastsignal = !empty($payload['lastsignal']) ? userdate($payload['lastsignal']) : get_string('notavailable', 'local_pceinotifications');
$lastactivity = ($riskrecord && !empty($riskrecord->lastactivity)) ? userdate($riskrecord->lastactivity) : get_string('notavailable', 'local_pceinotifications');
$notificationsummary = get_string('student_notifications_summary', 'local_pceinotifications', (object)['sent' => $payload['notifications']['sent'], 'success' => $payload['notifications']['success']]);
$inactivitydisplay = $service->get_inactivity_display_value($riskrecord);
$styles = \local_pceinotifications\util::page_styles() . '@media print {.student-actions{display:none!important;}.vtn-card{box-shadow:none!important;border-color:#d8e8fb!important}} .student-evidence-bar{height:12px;border-radius:999px;background:#d9dde3;overflow:hidden}.student-evidence-bar>span{display:block;height:12px;background:linear-gradient(90deg,#0f4c81,#1d6fd8)} .student-chip{display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;background:#eef3ff;color:#0f4c81;font-weight:600;margin-right:8px;margin-bottom:8px;}';
$risktone = ['Crítico' => 'red', 'Prioritario' => 'orange', 'Preventivo' => 'orange', 'Normal' => 'green', 'Recuperado' => 'blue'][$payload['risklabel']] ?? 'blue';
$prioritytone = ['Alta' => 'red', 'Media' => 'orange', 'Preventiva' => 'blue', 'Ordinaria' => 'green'][$payload['prioritylabel']] ?? 'blue';
$followuptone = ['Sin seguimiento' => 'slate', 'Pendiente' => 'orange', 'En progreso' => 'blue', 'Atendido' => 'green'][$payload['followuplabel']] ?? 'slate';

echo $OUTPUT->header();
echo html_writer::tag('style', $styles);
echo html_writer::start_div('vtn-shell');
echo html_writer::start_div('vtn-hero');
echo html_writer::tag('div', get_string('student_dashboard', 'local_pceinotifications'), ['class' => 'vtn-hero__title']);
echo html_writer::tag('p', get_string('student_dashboard_desc_v914', 'local_pceinotifications'), ['class' => 'vtn-hero__text']);
echo html_writer::end_div();

echo html_writer::start_div('student-actions vtn-toolbar');
echo html_writer::link(new moodle_url('/local/pceinotifications/progress.php', ['id' => $courseid]), get_string('student_dashboard_go_progress', 'local_pceinotifications'), ['class' => 'btn btn-primary']);
echo html_writer::link(new moodle_url('/local/pceinotifications/notifications.php', ['id' => $courseid, 'mode' => 'mine']), get_string('my_notifications', 'local_pceinotifications'), ['class' => 'btn btn-outline-primary']);
echo html_writer::link(new moodle_url('/local/pceinotifications/student_report_print.php', ['id' => $courseid]), get_string('student_print_view', 'local_pceinotifications'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::end_div();

echo html_writer::start_div('vtn-kpis');
foreach ([
    ['label' => get_string('student_current_risk', 'local_pceinotifications'), 'value' => $payload['risklabel'], 'tone' => $risktone],
    ['label' => get_string('student_current_priority', 'local_pceinotifications'), 'value' => $payload['prioritylabel'], 'tone' => $prioritytone],
    ['label' => get_string('followupstatus', 'local_pceinotifications'), 'value' => $payload['followuplabel'], 'tone' => $followuptone],
    ['label' => get_string('student_evidence_level', 'local_pceinotifications'), 'value' => $payload['evidencelabel'], 'tone' => 'blue'],
] as $card) {
    echo html_writer::start_div('vtn-card vtn-kpi vtn-kpi--' . $card['tone']);
    echo html_writer::start_div('vtn-card__body');
    echo html_writer::tag('div', s($card['value']), ['class' => 'vtn-kpi__value']);
    echo html_writer::tag('div', $card['label'], ['class' => 'vtn-kpi__label']);
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('vtn-split');

echo html_writer::start_div('vtn-card');
echo html_writer::start_div('vtn-card__body');
echo html_writer::tag('div', get_string('student_traceability_title', 'local_pceinotifications'), ['class' => 'vtn-section-title']);
echo html_writer::tag('p', get_string('student_recommendation_title', 'local_pceinotifications'), ['class' => 'vtn-section-subtitle']);
echo html_writer::start_div('vtn-meta-list');
foreach ([
    get_string('student_last_activity', 'local_pceinotifications') => $lastactivity,
    get_string('student_last_signal', 'local_pceinotifications') => $lastsignal,
    get_string('inactivitydays', 'local_pceinotifications') => ($inactivitydisplay !== null ? (string)$inactivitydisplay : get_string('notavailable', 'local_pceinotifications')),
    get_string('student_notifications_title', 'local_pceinotifications') => $notificationsummary,
] as $label => $value) {
    echo html_writer::start_div('vtn-meta-item');
    echo html_writer::tag('div', s($label), ['class' => 'text-muted small']);
    echo html_writer::tag('div', s($value), ['class' => 'fw-semibold']);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::start_div('vtn-highlight mt-3');
echo html_writer::tag('div', get_string('student_recommendation_title', 'local_pceinotifications'), ['class' => 'fw-bold mb-2']);
echo html_writer::tag('div', format_text($payload['recommendation'], FORMAT_PLAIN));
echo html_writer::tag('div', get_string('student_evidence_level', 'local_pceinotifications') . ': ' . s($payload['evidencelabel']), ['class' => 'mt-3 mb-1 fw-semibold']);
echo html_writer::start_div('student-evidence-bar');
echo html_writer::tag('span', '', ['style' => 'width:' . $evidencebar]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('vtn-card');
echo html_writer::start_div('vtn-card__body');
echo html_writer::tag('div', get_string('student_progress_snapshot', 'local_pceinotifications'), ['class' => 'vtn-section-title']);
echo html_writer::tag('p', get_string('student_quick_actions', 'local_pceinotifications'), ['class' => 'vtn-section-subtitle']);
if (!$progress['enabled']) {
    echo html_writer::div(get_string('progress_completion_disabled', 'local_pceinotifications'), 'vtn-empty');
} else {
    echo html_writer::start_div('vtn-meta-list');
    foreach ([
        get_string('progress_total', 'local_pceinotifications', $progress['total']),
        get_string('progress_done', 'local_pceinotifications', $progress['done']),
        get_string('progress_todo', 'local_pceinotifications', $progress['todo']),
        get_string('student_progress_percent', 'local_pceinotifications', $progress['percent'] . '%'),
    ] as $line) {
        echo html_writer::tag('div', $line, ['class' => 'vtn-meta-item fw-semibold']);
    }
    echo html_writer::end_div();
}
echo html_writer::start_div('vtn-highlight mt-3');
echo html_writer::tag('div', get_string('student_quick_actions', 'local_pceinotifications'), ['class' => 'fw-bold mb-2']);
foreach ([get_string('student_action_review_pending', 'local_pceinotifications'), get_string('student_action_check_notifications', 'local_pceinotifications'), get_string('student_action_contact_teacher', 'local_pceinotifications')] as $tip) {
    echo html_writer::tag('span', s($tip), ['class' => 'student-chip']);
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::start_div('vtn-card');
echo html_writer::start_div('vtn-card__body');
echo html_writer::tag('div', get_string('student_shared_actions_title', 'local_pceinotifications'), ['class' => 'vtn-section-title']);
echo html_writer::tag('p', get_string('student_shared_actions_desc', 'local_pceinotifications'), ['class' => 'vtn-section-subtitle']);
if ($sharednovelties) {
    echo html_writer::start_div('vtn-timeline');
    foreach ($sharednovelties as $novelty) {
        $teacher = core_user::get_user($novelty->teacherid, 'id,firstname,lastname,middlename,alternatename,firstnamephonetic,lastnamephonetic');
        echo html_writer::start_div('vtn-timeline__item');
        echo html_writer::tag('div', s($novelty->title), ['class' => 'fw-bold mb-1']);
        echo html_writer::tag('div', s($novelty->detail), ['class' => 'mb-2']);
        echo html_writer::tag('div', \local_pceinotifications\util::badge(get_string('novelty_status_' . $novelty->status, 'local_pceinotifications'), $novelty->status === 'closed' ? 'green' : ($novelty->status === 'reviewed' ? 'blue' : 'orange')), ['class' => 'mb-2']);
        echo html_writer::tag('div', get_string('student_shared_actions_teacher', 'local_pceinotifications') . ': ' . s($teacher ? fullname($teacher) : get_string('notavailable', 'local_pceinotifications')), ['class' => 'mb-1']);
        echo html_writer::tag('div', get_string('student_shared_actions_date', 'local_pceinotifications') . ': ' . s(userdate($novelty->timemodified)), ['class' => 'mb-1']);
        if (!empty($novelty->studentresponse)) {
            echo html_writer::tag('div', get_string('case_resolution_student_response', 'local_pceinotifications') . ': ' . s($novelty->studentresponse), ['class' => 'mb-1']);
        }
        if (!empty($novelty->teachervalidation)) {
            echo html_writer::tag('div', get_string('case_resolution_teacher_validation', 'local_pceinotifications') . ': ' . s($novelty->teachervalidation), ['class' => 'mb-1']);
        }
        if (!empty($novelty->timeclosed)) {
            echo html_writer::tag('div', get_string('case_resolution_closed_on', 'local_pceinotifications', userdate($novelty->timeclosed)), ['class' => 'mb-1']);
        }
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
} else {
    echo html_writer::div(get_string('student_shared_actions_none', 'local_pceinotifications'), 'vtn-empty');
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();
