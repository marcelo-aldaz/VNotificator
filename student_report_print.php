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

$PAGE->set_url('/local/pceinotifications/student_report_print.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('student_report_print_title', 'local_pceinotifications'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->js_init_code("window.localPceiPrintStudentReportNow = function() { window.print(); };");

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
$generatedon = userdate(time());

$printstyles = <<<'CSS'
<style>
html,body{background:#edf4fb}
.student-report{max-width:1040px;margin:0 auto;padding:1rem .75rem 1.35rem}
.student-report__toolbar{display:flex;gap:.75rem;justify-content:flex-end;flex-wrap:wrap;margin-bottom:1rem}
.student-sheet{background:#fff;border:1px solid #dbe8f7;border-radius:24px;box-shadow:0 24px 60px rgba(15,76,129,.16);padding:1rem 1rem 1.15rem;overflow:hidden}
.student-brand{display:grid;grid-template-columns:1.15fr .85fr;gap:.9rem;margin-bottom:1rem}
.student-brand__main{background:linear-gradient(135deg,#0f4c81 0%,#1967c8 48%,#0d87c8 100%);color:#fff;border-radius:28px;padding:1.3rem 1.35rem;box-shadow:0 18px 42px rgba(15,76,129,.22)}
.student-brand__eyebrow{font-size:.82rem;text-transform:uppercase;letter-spacing:.08em;font-weight:800;opacity:.92;margin-bottom:.35rem}
.student-brand__title{font-size:1.7rem;font-weight:900;line-height:1.05;margin:0 0 .45rem}
.student-brand__desc{margin:0;opacity:.96;max-width:54rem}
.student-meta{display:grid;gap:.75rem}
.student-meta__box{background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);border:1px solid #dbe8f7;border-radius:24px;padding:1rem 1.05rem;box-shadow:0 12px 28px rgba(31,66,115,.08)}
.student-meta__label{font-size:.82rem;color:#667085;font-weight:800;text-transform:uppercase;letter-spacing:.05em}
.student-meta__value{font-size:1.02rem;color:#12344d;font-weight:800;margin-top:.25rem}
.student-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.8rem;margin-bottom:1rem}
.student-kpi{border-radius:24px;padding:1rem;box-shadow:0 16px 34px rgba(31,66,115,.12);color:#fff;min-height:122px;display:flex;flex-direction:column;justify-content:space-between}
.student-kpi--blue{background:linear-gradient(135deg,#0f4c81,#2d77da)}.student-kpi--green{background:linear-gradient(135deg,#18794e,#28a36a)}.student-kpi--orange{background:linear-gradient(135deg,#cd6d08,#f59e0b)}.student-kpi--red{background:linear-gradient(135deg,#b42318,#ef4444)}.student-kpi--slate{background:linear-gradient(135deg,#475467,#667085)}
.student-kpi__value{font-size:1.6rem;font-weight:900;line-height:1}.student-kpi__label{font-size:.9rem;font-weight:800}.student-kpi__hint{font-size:.78rem;opacity:.92;line-height:1.25}
.student-columns{display:grid;grid-template-columns:1.06fr .94fr;gap:1rem}
.student-card{background:linear-gradient(180deg,#fff 0%,#f8fbff 100%);border:1px solid #dbe8f7;border-radius:26px;box-shadow:0 16px 34px rgba(31,66,115,.08);padding:1.05rem 1.1rem;margin-bottom:1rem;page-break-inside:avoid;break-inside:avoid}
.student-card__title{font-size:1.08rem;font-weight:900;color:#12344d;margin:0 0 .2rem}.student-card__subtitle{font-size:.92rem;color:#5b7083;margin:0 0 .9rem}
.student-meta-list{display:grid;gap:.7rem}.student-meta-item{padding:.82rem .95rem;border:1px solid #e8eef5;border-radius:16px;background:#fcfdff}
.student-meta-item__label{font-size:.82rem;color:#667085;font-weight:700;margin-bottom:.18rem}.student-meta-item__value{font-weight:700;color:#12344d}
.student-panel{border-radius:20px;padding:1rem 1.05rem;box-shadow:0 12px 28px rgba(31,66,115,.09);border:1px solid rgba(255,255,255,.28);position:relative;overflow:hidden}
.student-panel:before{content:"";position:absolute;right:-18px;top:-18px;width:92px;height:92px;border-radius:50%;background:rgba(255,255,255,.18)}
.student-panel--red{background:linear-gradient(135deg,#fff4f3 0%,#fff 100%);border-color:#f3c1bd}.student-panel--orange{background:linear-gradient(135deg,#fff7ed 0%,#fff 100%);border-color:#f7d0a4}.student-panel--green{background:linear-gradient(135deg,#effaf4 0%,#fff 100%);border-color:#bfe3cb}.student-panel--blue{background:linear-gradient(135deg,#eef5ff 0%,#fff 100%);border-color:#c8dbff}
.student-panel__title{font-size:1rem;font-weight:900;color:#12344d;margin:0 0 .35rem}.student-panel__text{margin:0;color:#4f687e}
.student-evidence-bar{height:13px;border-radius:999px;background:#d9dde3;overflow:hidden;margin-top:.55rem}.student-evidence-bar>span{display:block;height:13px;background:linear-gradient(90deg,#0f4c81,#1d6fd8)}
.student-tips{display:flex;flex-wrap:wrap;gap:.5rem}.student-chip{display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;background:#eef3ff;color:#0f4c81;font-weight:700}
.student-progress{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}.student-progress__item{padding:.78rem .9rem;border:1px solid #e8eef5;border-radius:16px;background:#fcfdff;font-weight:700;color:#12344d}
.student-timeline{display:grid;gap:.9rem}.student-timeline__item{position:relative;padding:0 0 .9rem 1rem;border-left:3px solid #d5e6fb}.student-timeline__item:last-child{padding-bottom:0}.student-timeline__item:before{content:"";position:absolute;left:-7px;top:.2rem;width:11px;height:11px;border-radius:50%;background:#1d6fd8;box-shadow:0 0 0 3px #e8f1ff}
.student-badge{display:inline-flex;align-items:center;padding:.34rem .68rem;border-radius:999px;font-size:.82rem;font-weight:800}.student-badge--red{background:#fdeceb;color:#b42318}.student-badge--orange{background:#fff2df;color:#b76600}.student-badge--green{background:#eaf7ef;color:#18794e}.student-badge--blue{background:#e7f0ff;color:#0f4c81}.student-badge--slate{background:#f2f4f7;color:#475467}
.student-footer{margin-top:1rem;padding-top:.85rem;border-top:2px solid #dbe8f7;color:#667085;font-size:.9rem;display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.student-empty{border:1px dashed #bfd7ff;background:#f7fbff;border-radius:18px;padding:1rem;color:#49657d}
.student-screen-note{background:#ecf5ff;border:1px solid #cfe0f6;border-radius:16px;padding:.8rem .9rem;color:#335b7d;margin-bottom:1rem}
@page{size:A4 portrait;margin:12mm 10mm 12mm 10mm}
@media (max-width: 991px){.student-brand,.student-grid,.student-columns,.student-progress{grid-template-columns:1fr}}
@media print{html,body{background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}.student-report{max-width:none;padding:0}.student-report__toolbar,.secondary-navigation,.navbar,.breadcrumb-nav,.page-header-headings,.page-context-header,.drawer,.drawer-toggles,.footer-popover,.footer-support-link,#page-footer,.nav-link,.paging,.header-actions-container,.tertiary-navigation,.activity-navigation,.format-actions,.addblock,.block,.moodle-dialogue-base,.skiplinks,.logininfo,.usermenu,.header-maxwidth{display:none !important}.student-sheet{border:none;box-shadow:none;padding:0;background:#fff}.student-card,.student-kpi,.student-meta__box,.student-panel{box-shadow:none!important}.student-brand__main,.student-kpi,.student-panel,.student-meta__box{break-inside:avoid;page-break-inside:avoid}}
</style>
CSS;

$risktone = ['Crítico' => 'red', 'Prioritario' => 'orange', 'Preventivo' => 'orange', 'Normal' => 'green', 'Recuperado' => 'blue'][$payload['risklabel']] ?? 'blue';
$prioritytone = ['Alta' => 'red', 'Media' => 'orange', 'Preventiva' => 'blue', 'Ordinaria' => 'green'][$payload['prioritylabel']] ?? 'blue';
$followuptone = ['Sin seguimiento' => 'slate', 'Pendiente' => 'orange', 'En progreso' => 'blue', 'Atendido' => 'green'][$payload['followuplabel']] ?? 'slate';
$paneltone = $risktone === 'red' ? 'red' : ($risktone === 'orange' ? 'orange' : ($risktone === 'green' ? 'green' : 'blue'));
$backurl = new moodle_url('/local/pceinotifications/student_dashboard.php', ['id' => $courseid]);

echo $OUTPUT->header();
echo $printstyles;
echo html_writer::start_div('student-report');
echo html_writer::start_div('student-report__toolbar');
echo html_writer::link($backurl, get_string('back_to_student_panel', 'local_pceinotifications'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::link('#', get_string('printreportnow', 'local_pceinotifications'), ['class' => 'btn btn-primary', 'onclick' => 'window.localPceiPrintStudentReportNow(); return false;']);
echo html_writer::end_div();
echo html_writer::div(get_string('student_print_hint', 'local_pceinotifications'), 'student-screen-note');
echo html_writer::start_div('student-sheet');

echo html_writer::start_div('student-brand');
echo html_writer::start_div('student-brand__main');
echo html_writer::tag('div', get_string('student_report_print_eyebrow', 'local_pceinotifications'), ['class' => 'student-brand__eyebrow']);
echo html_writer::tag('div', get_string('student_report_print_title', 'local_pceinotifications'), ['class' => 'student-brand__title']);
echo html_writer::tag('p', get_string('student_report_print_desc', 'local_pceinotifications'), ['class' => 'student-brand__desc']);
echo html_writer::end_div();
echo html_writer::start_div('student-meta');
foreach ([
    get_string('course') => format_string($course->fullname),
    get_string('generatedon', 'local_pceinotifications', $generatedon),
    get_string('student_report_document_label', 'local_pceinotifications') => get_string('student_report_document_value', 'local_pceinotifications'),
] as $label => $value) {
    echo html_writer::start_div('student-meta__box');
    echo html_writer::tag('div', $label, ['class' => 'student-meta__label']);
    echo html_writer::tag('div', $value, ['class' => 'student-meta__value']);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('student-grid');
foreach ([
    ['label' => get_string('student_current_risk', 'local_pceinotifications'), 'value' => $payload['risklabel'], 'tone' => $risktone, 'hint' => get_string('student_report_hint_risk', 'local_pceinotifications')],
    ['label' => get_string('student_current_priority', 'local_pceinotifications'), 'value' => $payload['prioritylabel'], 'tone' => $prioritytone, 'hint' => get_string('student_report_hint_priority', 'local_pceinotifications')],
    ['label' => get_string('followupstatus', 'local_pceinotifications'), 'value' => $payload['followuplabel'], 'tone' => $followuptone, 'hint' => get_string('student_report_hint_followup', 'local_pceinotifications')],
    ['label' => get_string('student_evidence_level', 'local_pceinotifications'), 'value' => $payload['evidencelabel'], 'tone' => 'blue', 'hint' => get_string('student_report_hint_evidence', 'local_pceinotifications')],
] as $card) {
    echo html_writer::start_div('student-kpi student-kpi--' . $card['tone']);
    echo html_writer::tag('div', s($card['value']), ['class' => 'student-kpi__value']);
    echo html_writer::tag('div', $card['label'], ['class' => 'student-kpi__label']);
    echo html_writer::tag('div', $card['hint'], ['class' => 'student-kpi__hint']);
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('student-columns');
echo html_writer::start_div();

echo html_writer::start_div('student-card');
echo html_writer::tag('div', get_string('student_traceability_title', 'local_pceinotifications'), ['class' => 'student-card__title']);
echo html_writer::tag('p', get_string('student_report_traceability_desc', 'local_pceinotifications'), ['class' => 'student-card__subtitle']);
echo html_writer::start_div('student-meta-list');
foreach ([
    get_string('student_last_activity', 'local_pceinotifications') => $lastactivity,
    get_string('student_last_signal', 'local_pceinotifications') => $lastsignal,
    get_string('inactivitydays', 'local_pceinotifications') => ($inactivitydisplay !== null ? (string)$inactivitydisplay : get_string('notavailable', 'local_pceinotifications')),
    get_string('student_notifications_title', 'local_pceinotifications') => $notificationsummary,
] as $label => $value) {
    echo html_writer::start_div('student-meta-item');
    echo html_writer::tag('div', s($label), ['class' => 'student-meta-item__label']);
    echo html_writer::tag('div', s($value), ['class' => 'student-meta-item__value']);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('student-card');
echo html_writer::tag('div', get_string('student_progress_snapshot', 'local_pceinotifications'), ['class' => 'student-card__title']);
echo html_writer::tag('p', get_string('student_report_progress_desc', 'local_pceinotifications'), ['class' => 'student-card__subtitle']);
if (!$progress['enabled']) {
    echo html_writer::div(get_string('progress_completion_disabled', 'local_pceinotifications'), 'student-empty');
} else {
    echo html_writer::start_div('student-progress');
    foreach ([
        get_string('progress_total', 'local_pceinotifications', $progress['total']),
        get_string('progress_done', 'local_pceinotifications', $progress['done']),
        get_string('progress_todo', 'local_pceinotifications', $progress['todo']),
        get_string('student_progress_percent', 'local_pceinotifications', $progress['percent'] . '%'),
    ] as $line) {
        echo html_writer::tag('div', $line, ['class' => 'student-progress__item']);
    }
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::start_div();

echo html_writer::start_div('student-card');
echo html_writer::tag('div', get_string('student_recommendation_title', 'local_pceinotifications'), ['class' => 'student-card__title']);
echo html_writer::tag('p', get_string('student_report_recommendation_desc', 'local_pceinotifications'), ['class' => 'student-card__subtitle']);
echo html_writer::start_div('student-panel student-panel--' . $paneltone);
echo html_writer::tag('div', get_string('student_recommendation_title', 'local_pceinotifications'), ['class' => 'student-panel__title']);
echo html_writer::tag('p', format_text($payload['recommendation'], FORMAT_PLAIN), ['class' => 'student-panel__text']);
echo html_writer::tag('div', get_string('student_evidence_level', 'local_pceinotifications') . ': ' . s($payload['evidencelabel']), ['class' => 'student-meta-item__label', 'style' => 'margin-top:.85rem']);
echo html_writer::start_div('student-evidence-bar');
echo html_writer::tag('span', '', ['style' => 'width:' . $evidencebar]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('student-panel student-panel--blue', ['style' => 'margin-top:1rem']);
echo html_writer::tag('div', get_string('student_quick_actions', 'local_pceinotifications'), ['class' => 'student-panel__title']);
echo html_writer::start_div('student-tips');
foreach ([get_string('student_action_review_pending', 'local_pceinotifications'), get_string('student_action_check_notifications', 'local_pceinotifications'), get_string('student_action_contact_teacher', 'local_pceinotifications')] as $tip) {
    echo html_writer::tag('span', s($tip), ['class' => 'student-chip']);
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('student-card');
echo html_writer::tag('div', get_string('student_shared_actions_title', 'local_pceinotifications'), ['class' => 'student-card__title']);
echo html_writer::tag('p', get_string('student_shared_actions_desc', 'local_pceinotifications'), ['class' => 'student-card__subtitle']);
if ($sharednovelties) {
    echo html_writer::start_div('student-timeline');
    foreach ($sharednovelties as $novelty) {
        $teacher = core_user::get_user($novelty->teacherid, 'id,firstname,lastname,middlename,alternatename,firstnamephonetic,lastnamephonetic');
        $tone = $novelty->status === 'closed' ? 'green' : ($novelty->status === 'reviewed' ? 'blue' : 'orange');
        echo html_writer::start_div('student-timeline__item');
        echo html_writer::tag('div', s($novelty->title), ['style' => 'font-weight:800;margin-bottom:.2rem;color:#12344d']);
        echo html_writer::tag('div', s($novelty->detail), ['style' => 'margin-bottom:.4rem;color:#4f687e']);
        echo html_writer::tag('div', html_writer::span(get_string('novelty_status_' . $novelty->status, 'local_pceinotifications'), 'student-badge student-badge--' . $tone), ['style' => 'margin-bottom:.4rem']);
        echo html_writer::tag('div', get_string('student_shared_actions_teacher', 'local_pceinotifications') . ': ' . s($teacher ? fullname($teacher) : get_string('notavailable', 'local_pceinotifications')), ['style' => 'margin-bottom:.2rem;color:#4f687e']);
        echo html_writer::tag('div', get_string('student_shared_actions_date', 'local_pceinotifications') . ': ' . s(userdate($novelty->timemodified)), ['style' => 'margin-bottom:.2rem;color:#4f687e']);
        if (!empty($novelty->studentresponse)) {
            echo html_writer::tag('div', get_string('case_resolution_student_response', 'local_pceinotifications') . ': ' . s($novelty->studentresponse), ['style' => 'margin-bottom:.2rem;color:#4f687e']);
        }
        if (!empty($novelty->teachervalidation)) {
            echo html_writer::tag('div', get_string('case_resolution_teacher_validation', 'local_pceinotifications') . ': ' . s($novelty->teachervalidation), ['style' => 'margin-bottom:.2rem;color:#4f687e']);
        }
        if (!empty($novelty->timeclosed)) {
            echo html_writer::tag('div', get_string('case_resolution_closed_on', 'local_pceinotifications', userdate($novelty->timeclosed)), ['style' => 'margin-bottom:.2rem;color:#4f687e']);
        }
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
} else {
    echo html_writer::div(get_string('student_shared_actions_none', 'local_pceinotifications'), 'student-empty');
}
echo html_writer::end_div();

echo html_writer::start_div('student-footer');
echo html_writer::tag('div', get_string('student_report_footer', 'local_pceinotifications'));
echo html_writer::tag('div', get_string('generatedon', 'local_pceinotifications', $generatedon));
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
