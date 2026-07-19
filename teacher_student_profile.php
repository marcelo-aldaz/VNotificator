<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');
require_once($CFG->dirroot . '/local/pceinotifications/classes/local/analytics/student_profile_service.php');
require_once($CFG->dirroot . '/local/pceinotifications/classes/local/analytics/followup_service.php');
require_once($CFG->dirroot . '/local/pceinotifications/classes/local/analytics/novelty_service.php');

$courseid = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$userrecord = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
if (!(has_capability('local/pceinotifications:manage', $context) || has_capability('local/pceinotifications:viewlogs', $context))) {
    require_capability('local/pceinotifications:viewlogs', $context);
}

$canviewstudent = is_enrolled($context, $userid);
if (!$canviewstudent) {
    $hastrackingdata = false;
    $trackingtables = [
        ['local_pceinotif_risk', ['courseid' => $courseid, 'userid' => $userid]],
        ['local_pceinotif_log', ['courseid' => $courseid, 'userid' => $userid]],
        ['local_pceinotif_followup', ['courseid' => $courseid, 'userid' => $userid]],
        ['local_pceinotif_novelty', ['courseid' => $courseid, 'userid' => $userid]],
    ];
    foreach ($trackingtables as [$tablename, $conditions]) {
        if ($DB->get_manager()->table_exists($tablename) && $DB->record_exists($tablename, $conditions)) {
            $hastrackingdata = true;
            break;
        }
    }
    if (!$hastrackingdata) {
        throw new moodle_exception('invaliduser');
    }
}

$PAGE->set_url('/local/pceinotifications/teacher_student_profile.php', ['id' => $courseid, 'userid' => $userid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('teacher_student_profile', 'local_pceinotifications'));
$PAGE->set_heading($course->fullname);

$followupservice = new \local_pceinotifications\local\analytics\followup_service();
$noveltyservice = new \local_pceinotifications\local\analytics\novelty_service();
if (optional_param('savenovelty', 0, PARAM_BOOL) && confirm_sesskey()) {
    require_capability('local/pceinotifications:manage', $context);

    $noveltytitle = trim(optional_param('noveltytitle', '', PARAM_TEXT));
    $noveltydetail = trim(optional_param('noveltydetail', '', PARAM_RAW_TRIMMED));
    $noveltystatus = optional_param('noveltystatus', 'open', PARAM_ALPHAEXT);
    $noveltyvisibility = optional_param('noveltyvisibility', 'internal', PARAM_ALPHAEXT);
    $allowednoveltystatus = ['open', 'reviewed', 'closed'];
    $allowedvisibility = ['internal', 'shared'];
    if (!in_array($noveltystatus, $allowednoveltystatus, true)) {
        $noveltystatus = 'open';
    }
    if (!in_array($noveltyvisibility, $allowedvisibility, true)) {
        $noveltyvisibility = 'internal';
    }
    if ($noveltytitle === '' || $noveltydetail === '') {
        redirect(new moodle_url('/local/pceinotifications/teacher_student_profile.php', ['id' => $courseid, 'userid' => $userid]), get_string('novelty_required', 'local_pceinotifications'), null, \core\output\notification::NOTIFY_ERROR);
    }
    $risklevel = $DB->get_field('local_pceinotif_risk', 'risklevel', ['userid' => $userid, 'courseid' => $courseid]) ?: '';
    $priority = (new \local_pceinotifications\local\analytics\student_profile_service())->get_student_course_payload($userid, $courseid)['priority'] ?? '';
    $noveltyservice->create_novelty($courseid, $userid, $USER->id, $noveltytitle, $noveltydetail, $noveltystatus, $noveltyvisibility, 'teacher_alert', $risklevel, $priority);
    redirect(new moodle_url('/local/pceinotifications/teacher_student_profile.php', ['id' => $courseid, 'userid' => $userid]), get_string('novelty_saved', 'local_pceinotifications'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if (optional_param('savecaseresolution', 0, PARAM_BOOL) && confirm_sesskey()) {
    require_capability('local/pceinotifications:manage', $context);

    $noveltyid = required_param('noveltyid', PARAM_INT);
    $noveltyrecord = $DB->get_record('local_pceinotif_novelty', ['id' => $noveltyid], 'id,courseid,userid', MUST_EXIST);
    if ((int)$noveltyrecord->courseid !== $courseid || (int)$noveltyrecord->userid !== $userid) {
        throw new moodle_exception('invalidparameter');
    }
    $casestatus = optional_param('casestatus', 'reviewed', PARAM_ALPHAEXT);
    $studentresponse = trim(optional_param('studentresponse', '', PARAM_RAW_TRIMMED));
    $teachervalidation = trim(optional_param('teachervalidation', '', PARAM_RAW_TRIMMED));
    $allowedcasestatus = ['open', 'reviewed', 'closed'];
    if (!in_array($casestatus, $allowedcasestatus, true)) {
        $casestatus = 'reviewed';
    }
    $noveltyservice->update_case_resolution($noveltyid, $casestatus, $studentresponse, $teachervalidation, $USER->id);
    redirect(new moodle_url('/local/pceinotifications/teacher_student_profile.php', ['id' => $courseid, 'userid' => $userid]), get_string('case_resolution_saved', 'local_pceinotifications'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if (optional_param('savefollowup', 0, PARAM_BOOL) && confirm_sesskey()) {
    require_capability('local/pceinotifications:manage', $context);

    $status = required_param('followupstatus', PARAM_ALPHAEXT);
    $contacttype = optional_param('contacttype', 'review_only', PARAM_ALPHAEXT);
    $note = trim(optional_param('followupnote', '', PARAM_RAW_TRIMMED));
    $nextreviewraw = trim(optional_param('nextreview', '', PARAM_RAW_TRIMMED));
    $allowedstatuses = ['pending', 'inprogress', 'attended'];
    $allowedcontacttypes = ['message', 'call', 'virtual_meeting', 'family_contact', 'review_only'];

    if (!in_array($status, $allowedstatuses, true)) {
        throw new moodle_exception('invalidparameter');
    }
    if (!in_array($contacttype, $allowedcontacttypes, true)) {
        $contacttype = 'review_only';
    }
    if ($note === '') {
        redirect(new moodle_url('/local/pceinotifications/teacher_student_profile.php', ['id' => $courseid, 'userid' => $userid]), get_string('followup_note_required', 'local_pceinotifications'), null, \core\output\notification::NOTIFY_ERROR);
    }

    $nextreview = null;
    if ($nextreviewraw !== '') {
        $nextreview = strtotime($nextreviewraw . ' 23:59:59');
        if ($nextreview === false) {
            redirect(new moodle_url('/local/pceinotifications/teacher_student_profile.php', ['id' => $courseid, 'userid' => $userid]), get_string('followup_invalid_date', 'local_pceinotifications'), null, \core\output\notification::NOTIFY_ERROR);
        }
    }

    $commitment = trim(optional_param('commitment', '', PARAM_TEXT));
    $responsible = trim(optional_param('responsible', '', PARAM_TEXT));
    $commitmentstatus = optional_param('commitmentstatus', 'notstarted', PARAM_ALPHAEXT);
    $evidence = trim(optional_param('evidence', '', PARAM_TEXT));
    $commitmentdateraw = trim(optional_param('commitmentdate', '', PARAM_RAW_TRIMMED));
    $allowedcommitmentstatuses = ['notstarted', 'inprogress', 'completed'];
    if (!in_array($commitmentstatus, $allowedcommitmentstatuses, true)) {
        $commitmentstatus = 'notstarted';
    }
    $commitmentdate = null;
    if ($commitmentdateraw !== '') {
        $commitmentdate = strtotime($commitmentdateraw . ' 23:59:59');
        if ($commitmentdate === false) {
            redirect(new moodle_url('/local/pceinotifications/teacher_student_profile.php', ['id' => $courseid, 'userid' => $userid]), get_string('followup_invalid_date', 'local_pceinotifications'), null, \core\output\notification::NOTIFY_ERROR);
        }
    }

    $followupservice->create_followup($courseid, $userid, $USER->id, $status, $contacttype, $note, $nextreview, $commitment, $responsible, $commitmentdate, $commitmentstatus, $evidence);
    redirect(new moodle_url('/local/pceinotifications/teacher_student_profile.php', ['id' => $courseid, 'userid' => $userid]), get_string('followup_saved', 'local_pceinotifications'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$service = new \local_pceinotifications\local\analytics\student_profile_service();
$payload = $service->get_student_course_payload($userid, $courseid);
$riskrecord = $payload['riskrecord'];
$progress = $payload['progress'];
$studentname = fullname($userrecord);
$lastsignal = !empty($payload['lastsignal']) ? userdate($payload['lastsignal']) : get_string('notavailable', 'local_pceinotifications');
$lastactivity = ($riskrecord && !empty($riskrecord->lastactivity)) ? userdate($riskrecord->lastactivity) : get_string('notavailable', 'local_pceinotifications');
$notificationsummary = get_string('student_notifications_summary', 'local_pceinotifications', (object)[
    'sent' => $payload['notifications']['sent'],
    'success' => $payload['notifications']['success'],
]);
$history = $followupservice->get_followup_history($courseid, $userid, 10);
$latestfollowup = $payload['latestfollowup'] ?? null;
$novelties = $noveltyservice->get_student_novelties($courseid, $userid, 10);
$opennovelties = $noveltyservice->get_open_student_novelties($courseid, $userid, 20);
$noveltycount = $noveltyservice->count_student_novelties($courseid, $userid);
$inactivitydisplay = $service->get_inactivity_display_value($riskrecord);
$risktone = \local_pceinotifications\util::tone_from_risk((string)($riskrecord->risklevel ?? 'green'));
$prioritytone = \local_pceinotifications\util::tone_from_priority((string)($payload['priority'] ?? ''));
$followuptone = \local_pceinotifications\util::tone_from_followup((string)($payload['followupstatus'] ?? 'none'));
$opencount = (int)($riskrecord->openalerts ?? 0);
$notifcount = (int)($payload['notifications']['sent'] ?? 0);
$alerttone = $risktone;
if ($alerttone === 'green' && ($opencount > 0 || $notifcount > 0)) {
    $alerttone = 'orange';
}
$alerttitle = get_string('student_recommendation_title', 'local_pceinotifications');
$alertmessage = trim(format_text($payload['recommendation'], FORMAT_PLAIN));
if ($alertmessage === '') {
    $alertmessage = get_string('notavailable', 'local_pceinotifications');
}

$extracss = \local_pceinotifications\util::page_styles() . '.teacher-student-chip{display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;background:#eef3ff;color:#0f4c81;font-weight:600;margin-right:6px;margin-bottom:6px}.teacher-student-strong{font-weight:600}.teacher-student-actions{display:flex;flex-wrap:wrap;gap:.75rem}.followup-timeline-item{border-left:3px solid #dfe7f5;padding-left:12px;margin-bottom:14px}.followup-meta{color:#5f6b7a;font-size:.92rem}.teacher-followup-form textarea{min-height:120px}.teacher-profile-shell{display:flex;flex-direction:column;gap:1rem}.teacher-profile-note{background:#fff8e8;border:1px solid #f6d38a;color:#7a5400;border-radius:16px;padding:.85rem 1rem}.teacher-profile-topline{display:flex;flex-wrap:wrap;gap:.65rem;margin:.4rem 0 0}.teacher-profile-summary{display:grid;grid-template-columns:1.2fr .8fr;gap:1rem}.teacher-alert-panel{border-radius:22px;padding:1.05rem 1.15rem;border:1px solid #d8e8fb;box-shadow:0 12px 28px rgba(31,66,115,.08)}.teacher-alert-panel--red{background:linear-gradient(135deg,#fff1f0 0%,#fff 100%);border-color:#f3beb8}.teacher-alert-panel--orange{background:linear-gradient(135deg,#fff7eb 0%,#fff 100%);border-color:#f4d3a8}.teacher-alert-panel--green{background:linear-gradient(135deg,#eefaf3 0%,#fff 100%);border-color:#bfe3cb}.teacher-alert-panel--blue{background:linear-gradient(135deg,#eef5ff 0%,#fff 100%);border-color:#c8dbff}.teacher-alert-panel__title{font-size:1.02rem;font-weight:700;color:#12344d;margin:0 0 .35rem}.teacher-alert-panel__meta{display:flex;flex-wrap:wrap;gap:.5rem;margin:.85rem 0}.teacher-alert-panel__text{color:#536b81;margin:0}.teacher-profile-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem}.teacher-profile-kpis .vtn-card{border:none;box-shadow:none}.teacher-profile-detail{display:grid;gap:.8rem}.teacher-profile-detail__row{display:flex;justify-content:space-between;gap:1rem;padding:.8rem .95rem;border-radius:16px;background:#f8fbff;border:1px solid #dde9f7}.teacher-profile-detail__label{font-weight:600;color:#12344d}.teacher-profile-detail__value{color:#425c72;text-align:right}.teacher-form-shell .form-label{font-weight:700;color:#12344d}.teacher-section-title{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.8rem}.teacher-section-title p{margin:0;color:#5b7083}.teacher-chart-card{padding:1rem;border-radius:18px;background:linear-gradient(180deg,#fff 0%,#f7fbff 100%);border:1px solid #d8e8fb;box-shadow:0 10px 24px rgba(31,66,115,.07)}.teacher-progress-bar{height:14px;border-radius:999px;background:#e5edf7;overflow:hidden}.teacher-progress-bar>span{display:block;height:14px;border-radius:999px;background:linear-gradient(90deg,#0f4c81,#2d77da)}@media (max-width: 991px){.teacher-profile-summary{grid-template-columns:1fr}.teacher-profile-detail__row{flex-direction:column}.teacher-profile-detail__value{text-align:left}}';

$contactoptions = [
    'message' => get_string('followup_contact_message', 'local_pceinotifications'),
    'call' => get_string('followup_contact_call', 'local_pceinotifications'),
    'virtual_meeting' => get_string('followup_contact_virtual_meeting', 'local_pceinotifications'),
    'family_contact' => get_string('followup_contact_family', 'local_pceinotifications'),
    'review_only' => get_string('followup_contact_review_only', 'local_pceinotifications'),
];

$statusoptions = [
    'pending' => get_string('followup_pending', 'local_pceinotifications'),
    'inprogress' => get_string('followup_inprogress', 'local_pceinotifications'),
    'attended' => get_string('followup_attended', 'local_pceinotifications'),
];

$commitmentstatusoptions = [
    'notstarted' => get_string('commitment_status_notstarted', 'local_pceinotifications'),
    'inprogress' => get_string('commitment_status_inprogress', 'local_pceinotifications'),
    'completed' => get_string('commitment_status_completed', 'local_pceinotifications'),
];

echo $OUTPUT->header();
echo html_writer::tag('style', $extracss);
echo html_writer::start_div('teacher-profile-shell');
echo html_writer::start_div('vtn-hero');
echo html_writer::tag('div', get_string('teacher_student_profile', 'local_pceinotifications'), ['class' => 'vtn-hero__title']);
echo html_writer::tag('p', get_string('teacher_student_profile_desc', 'local_pceinotifications'), ['class' => 'vtn-hero__text']);
echo html_writer::end_div();
echo html_writer::tag('h3', format_string($studentname), ['class' => 'h4 mb-1']);
echo html_writer::tag('div', format_string($course->fullname), ['class' => 'text-muted mb-2']);
echo html_writer::start_div('teacher-profile-topline');
echo \local_pceinotifications\util::badge($payload['risklabel'], $risktone);
echo \local_pceinotifications\util::badge($payload['prioritylabel'], $prioritytone);
echo \local_pceinotifications\util::badge($payload['followuplabel'], $followuptone);
echo html_writer::end_div();

echo html_writer::start_div('teacher-student-actions mb-3');
echo html_writer::link(new moodle_url('/local/pceinotifications/teacher_dashboard.php', ['id' => $courseid]), get_string('back_to_teacher_dashboard', 'local_pceinotifications'), ['class' => 'btn btn-secondary']);
echo html_writer::link(new moodle_url('/local/pceinotifications/progress.php', ['id' => $courseid]), get_string('teacher_dashboard_go_progress', 'local_pceinotifications'), ['class' => 'btn btn-outline-primary']);
echo html_writer::link(new moodle_url('/local/pceinotifications/teacher_student_profile.php', ['id' => $courseid, 'userid' => $userid], 'novelty-box'), get_string('novelty_quick_button', 'local_pceinotifications'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::end_div();

echo html_writer::start_div('teacher-profile-kpis');
echo \local_pceinotifications\util::metric_panel(get_string('student_current_risk', 'local_pceinotifications'), $payload['risklabel'], $risktone, get_string('student_dashboard', 'local_pceinotifications'));
echo \local_pceinotifications\util::metric_panel(get_string('student_current_priority', 'local_pceinotifications'), $payload['prioritylabel'], $prioritytone, get_string('teacher_dashboard', 'local_pceinotifications'));
echo \local_pceinotifications\util::metric_panel(get_string('followupstatus', 'local_pceinotifications'), $payload['followuplabel'], $followuptone, get_string('followup_history_title', 'local_pceinotifications'));
echo \local_pceinotifications\util::metric_panel(get_string('student_evidence_level', 'local_pceinotifications'), $payload['evidencelabel'], 'blue', get_string('student_progress_snapshot', 'local_pceinotifications'));
echo \local_pceinotifications\util::metric_panel(get_string('novelty_count', 'local_pceinotifications'), (string)$noveltycount, $noveltycount > 0 ? 'orange' : 'green', get_string('novelty_history_title', 'local_pceinotifications'));
echo html_writer::end_div();

echo html_writer::start_div('teacher-profile-summary');

echo html_writer::start_div('vtn-card h-100');
echo html_writer::start_div('vtn-card__body');
echo html_writer::start_div('teacher-section-title');
echo html_writer::tag('h3', get_string('teacher_student_traceability', 'local_pceinotifications'), ['class' => 'h5 mb-0']);
echo html_writer::tag('p', get_string('teacher_student_profile_desc', 'local_pceinotifications'));
echo html_writer::end_div();
echo html_writer::start_div('teacher-alert-panel teacher-alert-panel--' . $alerttone);
echo html_writer::tag('div', $alerttitle, ['class' => 'teacher-alert-panel__title']);
echo html_writer::tag('p', s($alertmessage), ['class' => 'teacher-alert-panel__text']);
echo html_writer::start_div('teacher-alert-panel__meta');
echo \local_pceinotifications\util::badge(get_string('openalerts', 'local_pceinotifications') . ': ' . (string)$opencount, $opencount > 0 ? 'red' : 'green');
echo \local_pceinotifications\util::badge(get_string('student_notifications_title', 'local_pceinotifications') . ': ' . $payload['notifications']['sent'], $payload['notifications']['sent'] > 0 ? 'orange' : 'green');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('teacher-profile-detail mt-3');
echo html_writer::div(html_writer::span(get_string('student_last_activity', 'local_pceinotifications'), 'teacher-profile-detail__label') . html_writer::span(s($lastactivity), 'teacher-profile-detail__value'), 'teacher-profile-detail__row');
echo html_writer::div(html_writer::span(get_string('student_last_signal', 'local_pceinotifications'), 'teacher-profile-detail__label') . html_writer::span(s($lastsignal), 'teacher-profile-detail__value'), 'teacher-profile-detail__row');
echo html_writer::div(html_writer::span(get_string('inactivitydays', 'local_pceinotifications'), 'teacher-profile-detail__label') . html_writer::span(s($inactivitydisplay !== null ? (string)$inactivitydisplay : get_string('notavailable', 'local_pceinotifications')), 'teacher-profile-detail__value'), 'teacher-profile-detail__row');
echo html_writer::div(html_writer::span(get_string('openalerts', 'local_pceinotifications'), 'teacher-profile-detail__label') . html_writer::span((string)$opencount, 'teacher-profile-detail__value'), 'teacher-profile-detail__row');
echo html_writer::div(html_writer::span(get_string('student_notifications_title', 'local_pceinotifications'), 'teacher-profile-detail__label') . html_writer::span(s($notificationsummary), 'teacher-profile-detail__value'), 'teacher-profile-detail__row');
if ($latestfollowup) {
    echo html_writer::tag('div', get_string('followup_last_registered', 'local_pceinotifications') . ': ' . userdate($latestfollowup->timemodified), ['class' => 'mb-2']);
    if (!empty($latestfollowup->nextreview)) {
        echo html_writer::tag('div', get_string('followup_nextreview', 'local_pceinotifications') . ': ' . userdate($latestfollowup->nextreview, get_string('strftimedate')), ['class' => 'mb-2']);
    }
    if (!empty($latestfollowup->commitment)) {
        echo html_writer::tag('div', get_string('commitment_title', 'local_pceinotifications') . ': ' . s($latestfollowup->commitment), ['class' => 'mb-2']);
        echo html_writer::tag('div', get_string('commitment_status', 'local_pceinotifications') . ': ' . s(get_string('commitment_status_' . $latestfollowup->commitmentstatus, 'local_pceinotifications')), ['class' => 'mb-2']);
        if (!empty($latestfollowup->responsible)) {
            echo html_writer::tag('div', get_string('commitment_responsible', 'local_pceinotifications') . ': ' . s($latestfollowup->responsible), ['class' => 'mb-2']);
        }
        if (!empty($latestfollowup->commitmentdate)) {
            echo html_writer::tag('div', get_string('commitment_date', 'local_pceinotifications') . ': ' . userdate($latestfollowup->commitmentdate, get_string('strftimedate')), ['class' => 'mb-2']);
        }
    }
}
echo html_writer::tag('div', get_string('student_recommendation_title', 'local_pceinotifications'), ['class' => 'fw-bold mb-1']);
echo html_writer::tag('div', format_text($payload['recommendation'], FORMAT_PLAIN), ['class' => 'alert alert-light border']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('vtn-card h-100');
echo html_writer::start_div('vtn-card__body');
echo html_writer::start_div('teacher-section-title');
echo html_writer::tag('h3', get_string('student_progress_snapshot', 'local_pceinotifications'), ['class' => 'h5 mb-0']);
echo html_writer::tag('p', get_string('teacher_recommended_actions', 'local_pceinotifications'));
echo html_writer::end_div();
echo html_writer::start_div('teacher-chart-card mb-3');
if (!$progress['enabled']) {
    echo $OUTPUT->notification(get_string('progress_completion_disabled', 'local_pceinotifications'), 'info');
} else {
    echo html_writer::tag('div', get_string('student_progress_percent', 'local_pceinotifications', $progress['percent'] . '%'), ['class' => 'h4 mb-2']);
    echo html_writer::tag('div', get_string('progress_total', 'local_pceinotifications', $progress['total']), ['class' => 'mb-1']);
    echo html_writer::tag('div', get_string('progress_done', 'local_pceinotifications', $progress['done']), ['class' => 'mb-1']);
    echo html_writer::tag('div', get_string('progress_todo', 'local_pceinotifications', $progress['todo']), ['class' => 'mb-2']);
    echo html_writer::div(html_writer::span('', '', ['style' => 'width:' . max(4, min(100, (int)$progress['percent'])) . '%;']), 'teacher-progress-bar mb-2');
}
echo html_writer::end_div();
echo html_writer::tag('div', get_string('teacher_recommended_actions', 'local_pceinotifications'), ['class' => 'fw-bold mb-2']);
foreach ([
    get_string('teacher_action_contact_student', 'local_pceinotifications'),
    get_string('teacher_action_review_pending', 'local_pceinotifications'),
    get_string('teacher_action_register_followup', 'local_pceinotifications'),
] as $tip) {
    echo html_writer::tag('span', s($tip), ['class' => 'teacher-student-chip']);
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::start_div('row');
echo html_writer::start_div('col-lg-6 mb-3');
echo html_writer::start_div('vtn-card h-100');
echo html_writer::start_div('vtn-card__body teacher-followup-form teacher-form-shell');
echo html_writer::tag('h3', get_string('followup_register_title', 'local_pceinotifications'), ['class' => 'h5']);
echo html_writer::tag('div', get_string('followup_register_desc', 'local_pceinotifications'), ['class' => 'text-muted mb-3']);

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $courseid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'userid', 'value' => $userid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savefollowup', 'value' => 1]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('followupstatus', 'local_pceinotifications'), ['for' => 'followupstatus', 'class' => 'form-label']);
echo html_writer::start_tag('select', ['name' => 'followupstatus', 'id' => 'followupstatus', 'class' => 'form-select']);
foreach ($statusoptions as $value => $label) {
    $selected = ($value === 'inprogress') ? ['selected' => 'selected'] : [];
    echo html_writer::tag('option', s($label), ['value' => $value] + $selected);
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('followup_contacttype', 'local_pceinotifications'), ['for' => 'contacttype', 'class' => 'form-label']);
echo html_writer::start_tag('select', ['name' => 'contacttype', 'id' => 'contacttype', 'class' => 'form-select']);
foreach ($contactoptions as $value => $label) {
    echo html_writer::tag('option', s($label), ['value' => $value]);
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('followup_note', 'local_pceinotifications'), ['for' => 'followupnote', 'class' => 'form-label']);
echo html_writer::tag('textarea', '', ['name' => 'followupnote', 'id' => 'followupnote', 'class' => 'form-control', 'required' => 'required']);
echo html_writer::tag('div', get_string('followup_note_help', 'local_pceinotifications'), ['class' => 'form-text']);
echo html_writer::end_div();

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('followup_nextreview', 'local_pceinotifications'), ['for' => 'nextreview', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['type' => 'date', 'name' => 'nextreview', 'id' => 'nextreview', 'class' => 'form-control']);
echo html_writer::end_div();

echo html_writer::tag('h4', get_string('commitment_section_title', 'local_pceinotifications'), ['class' => 'h6 mt-4']);

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('commitment_title', 'local_pceinotifications'), ['for' => 'commitment', 'class' => 'form-label']);
echo html_writer::tag('textarea', '', ['name' => 'commitment', 'id' => 'commitment', 'class' => 'form-control']);
echo html_writer::tag('div', get_string('commitment_title_help', 'local_pceinotifications'), ['class' => 'form-text']);
echo html_writer::end_div();

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('commitment_responsible', 'local_pceinotifications'), ['for' => 'responsible', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'responsible', 'id' => 'responsible', 'class' => 'form-control', 'placeholder' => get_string('commitment_responsible_placeholder', 'local_pceinotifications')]);
echo html_writer::end_div();

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('commitment_date', 'local_pceinotifications'), ['for' => 'commitmentdate', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['type' => 'date', 'name' => 'commitmentdate', 'id' => 'commitmentdate', 'class' => 'form-control']);
echo html_writer::end_div();

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('commitment_status', 'local_pceinotifications'), ['for' => 'commitmentstatus', 'class' => 'form-label']);
echo html_writer::start_tag('select', ['name' => 'commitmentstatus', 'id' => 'commitmentstatus', 'class' => 'form-select']);
foreach ($commitmentstatusoptions as $value => $label) {
    echo html_writer::tag('option', s($label), ['value' => $value]);
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('commitment_evidence', 'local_pceinotifications'), ['for' => 'evidence', 'class' => 'form-label']);
echo html_writer::tag('textarea', '', ['name' => 'evidence', 'id' => 'evidence', 'class' => 'form-control']);
echo html_writer::tag('div', get_string('commitment_evidence_help', 'local_pceinotifications'), ['class' => 'form-text']);
echo html_writer::end_div();

echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('followup_save_button', 'local_pceinotifications')]);
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-lg-6 mb-3', ['id' => 'novelty-box']);
echo html_writer::start_div('vtn-card h-100');
echo html_writer::start_div('vtn-card__body teacher-followup-form teacher-form-shell');
echo html_writer::tag('h3', get_string('novelty_section_title', 'local_pceinotifications'), ['class' => 'h5']);
echo html_writer::tag('div', get_string('novelty_section_desc_v920', 'local_pceinotifications'), ['class' => 'text-muted mb-3']);

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $courseid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'userid', 'value' => $userid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savenovelty', 'value' => 1]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('novelty_title', 'local_pceinotifications'), ['for' => 'noveltytitle', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'noveltytitle', 'id' => 'noveltytitle', 'class' => 'form-control', 'required' => 'required', 'placeholder' => get_string('novelty_title_placeholder', 'local_pceinotifications')]);
echo html_writer::end_div();

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('novelty_detail', 'local_pceinotifications'), ['for' => 'noveltydetail', 'class' => 'form-label']);
echo html_writer::tag('textarea', '', ['name' => 'noveltydetail', 'id' => 'noveltydetail', 'class' => 'form-control', 'required' => 'required']);
echo html_writer::tag('div', get_string('novelty_detail_help', 'local_pceinotifications'), ['class' => 'form-text']);
echo html_writer::end_div();

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('novelty_status', 'local_pceinotifications'), ['for' => 'noveltystatus', 'class' => 'form-label']);
echo html_writer::start_tag('select', ['name' => 'noveltystatus', 'id' => 'noveltystatus', 'class' => 'form-select']);
foreach (['open' => get_string('novelty_status_open', 'local_pceinotifications'), 'reviewed' => get_string('novelty_status_reviewed', 'local_pceinotifications'), 'closed' => get_string('novelty_status_closed', 'local_pceinotifications')] as $value => $label) {
    echo html_writer::tag('option', s($label), ['value' => $value]);
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('novelty_visibility', 'local_pceinotifications'), ['for' => 'noveltyvisibility', 'class' => 'form-label']);
echo html_writer::start_tag('select', ['name' => 'noveltyvisibility', 'id' => 'noveltyvisibility', 'class' => 'form-select']);
foreach (['internal' => get_string('novelty_visibility_internal', 'local_pceinotifications'), 'shared' => get_string('novelty_visibility_shared', 'local_pceinotifications')] as $value => $label) {
    $attrs = ['value' => $value];
    if ($value === 'shared') {
        $attrs['selected'] = 'selected';
    }
    echo html_writer::tag('option', s($label), $attrs);
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-secondary', 'value' => get_string('novelty_save_button', 'local_pceinotifications')]);
echo html_writer::end_tag('form');

if ($opennovelties) {
    echo html_writer::tag('hr', '');
    echo html_writer::tag('h4', get_string('case_resolution_title', 'local_pceinotifications'), ['class' => 'h6 mt-3']);
    echo html_writer::tag('div', get_string('case_resolution_desc', 'local_pceinotifications'), ['class' => 'text-muted mb-3']);
    echo html_writer::start_tag('form', ['method' => 'post']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $courseid]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'userid', 'value' => $userid]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savecaseresolution', 'value' => 1]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    echo html_writer::start_div('mb-3');
    echo html_writer::tag('label', get_string('case_resolution_case', 'local_pceinotifications'), ['for' => 'noveltyid', 'class' => 'form-label']);
    echo html_writer::start_tag('select', ['name' => 'noveltyid', 'id' => 'noveltyid', 'class' => 'form-select']);
    foreach ($opennovelties as $openitem) {
        $optionlabel = $openitem->title . ' · ' . userdate($openitem->timemodified);
        echo html_writer::tag('option', s($optionlabel), ['value' => $openitem->id]);
    }
    echo html_writer::end_tag('select');
    echo html_writer::end_div();

    echo html_writer::start_div('mb-3');
    echo html_writer::tag('label', get_string('case_resolution_status', 'local_pceinotifications'), ['for' => 'casestatus', 'class' => 'form-label']);
    echo html_writer::start_tag('select', ['name' => 'casestatus', 'id' => 'casestatus', 'class' => 'form-select']);
    foreach (['reviewed' => get_string('novelty_status_reviewed', 'local_pceinotifications'), 'closed' => get_string('novelty_status_closed', 'local_pceinotifications'), 'open' => get_string('novelty_status_open', 'local_pceinotifications')] as $value => $label) {
        echo html_writer::tag('option', s($label), ['value' => $value]);
    }
    echo html_writer::end_tag('select');
    echo html_writer::end_div();

    echo html_writer::start_div('mb-3');
    echo html_writer::tag('label', get_string('case_resolution_student_response', 'local_pceinotifications'), ['for' => 'studentresponse', 'class' => 'form-label']);
    echo html_writer::tag('textarea', '', ['name' => 'studentresponse', 'id' => 'studentresponse', 'class' => 'form-control']);
    echo html_writer::end_div();

    echo html_writer::start_div('mb-3');
    echo html_writer::tag('label', get_string('case_resolution_teacher_validation', 'local_pceinotifications'), ['for' => 'teachervalidation', 'class' => 'form-label']);
    echo html_writer::tag('textarea', '', ['name' => 'teachervalidation', 'id' => 'teachervalidation', 'class' => 'form-control']);
    echo html_writer::end_div();

    echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-outline-primary', 'value' => get_string('case_resolution_save_button', 'local_pceinotifications')]);
    echo html_writer::end_tag('form');
}

echo html_writer::tag('hr', '');
echo html_writer::tag('h4', get_string('novelty_history_title', 'local_pceinotifications'), ['class' => 'h6 mt-3']);
if ($novelties) {
    foreach ($novelties as $novelty) {
        $teacher = core_user::get_user($novelty->teacherid, 'id,firstname,lastname,middlename,alternatename,firstnamephonetic,lastnamephonetic');
        echo html_writer::start_div('followup-timeline-item');
        echo html_writer::tag('div', s($novelty->title), ['class' => 'fw-bold']);
        echo html_writer::tag('div', s($novelty->detail), ['class' => 'mb-2']);
        echo html_writer::tag('div', get_string('novelty_status', 'local_pceinotifications') . ': ' . s(get_string('novelty_status_' . $novelty->status, 'local_pceinotifications')), ['class' => 'mb-1']);
        echo html_writer::tag('div', get_string('novelty_visibility', 'local_pceinotifications') . ': ' . s(get_string('novelty_visibility_' . $novelty->visibility, 'local_pceinotifications')), ['class' => 'mb-1']);
        if (!empty($novelty->studentresponse)) {
            echo html_writer::tag('div', get_string('case_resolution_student_response', 'local_pceinotifications') . ': ' . s($novelty->studentresponse), ['class' => 'mb-1']);
        }
        if (!empty($novelty->teachervalidation)) {
            echo html_writer::tag('div', get_string('case_resolution_teacher_validation', 'local_pceinotifications') . ': ' . s($novelty->teachervalidation), ['class' => 'mb-1']);
        }
        $meta = userdate($novelty->timemodified) . ' · ' . get_string('followup_registered_by', 'local_pceinotifications', fullname($teacher));
        if (!empty($novelty->timeclosed)) {
            $meta .= ' · ' . get_string('case_resolution_closed_on', 'local_pceinotifications', userdate($novelty->timeclosed));
        }
        echo html_writer::tag('div', s($meta), ['class' => 'followup-meta']);
        echo html_writer::end_div();
    }
} else {
    echo $OUTPUT->notification(get_string('novelty_none', 'local_pceinotifications'), 'info');
}

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-lg-6 mb-3');
echo html_writer::start_div('vtn-card h-100');
echo html_writer::start_div('vtn-card__body');
echo html_writer::tag('h3', get_string('followup_history_title', 'local_pceinotifications'), ['class' => 'h5']);
if ($history) {
    foreach ($history as $item) {
        $teacher = core_user::get_user($item->teacherid, 'id,firstname,lastname,middlename,alternatename,firstnamephonetic,lastnamephonetic');
        echo html_writer::start_div('followup-timeline-item');
        echo html_writer::tag('div', s(get_string('followup_' . $item->status, 'local_pceinotifications')), ['class' => 'fw-bold']);
        echo html_writer::tag('div', s(get_string('followup_contact_' . $item->contacttype, 'local_pceinotifications')), ['class' => 'mb-1']);
        echo html_writer::tag('div', format_text($item->note, FORMAT_PLAIN), ['class' => 'mb-2']);
        if (!empty($item->commitment)) {
            echo html_writer::tag('div', get_string('commitment_title', 'local_pceinotifications') . ': ' . s($item->commitment), ['class' => 'mb-1']);
            echo html_writer::tag('div', get_string('commitment_status', 'local_pceinotifications') . ': ' . s(get_string('commitment_status_' . $item->commitmentstatus, 'local_pceinotifications')), ['class' => 'mb-1']);
            if (!empty($item->responsible)) {
                echo html_writer::tag('div', get_string('commitment_responsible', 'local_pceinotifications') . ': ' . s($item->responsible), ['class' => 'mb-1']);
            }
            if (!empty($item->evidence)) {
                echo html_writer::tag('div', get_string('commitment_evidence', 'local_pceinotifications') . ': ' . s($item->evidence), ['class' => 'mb-1']);
            }
        }
        $meta = userdate($item->timemodified) . ' · ' . get_string('followup_registered_by', 'local_pceinotifications', fullname($teacher));
        if (!empty($item->nextreview)) {
            $meta .= ' · ' . get_string('followup_nextreview_short', 'local_pceinotifications', userdate($item->nextreview, get_string('strftimedate')));
        }
        if (!empty($item->commitmentdate)) {
            $meta .= ' · ' . get_string('commitment_date_short', 'local_pceinotifications', userdate($item->commitmentdate, get_string('strftimedate')));
        }
        echo html_writer::tag('div', s($meta), ['class' => 'followup-meta']);
        echo html_writer::end_div();
    }
} else {
    echo $OUTPUT->notification(get_string('no_followup_records', 'local_pceinotifications'), 'info');
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();
