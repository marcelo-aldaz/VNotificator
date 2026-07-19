<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');
require_once($CFG->dirroot . '/local/pceinotifications/classes/local/analytics/teacher_tracking_service.php');
require_once($CFG->dirroot . '/local/pceinotifications/classes/local/analytics/novelty_service.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
if (!(has_capability('local/pceinotifications:manage', $context) || has_capability('local/pceinotifications:viewlogs', $context))) {
    require_capability('local/pceinotifications:viewlogs', $context);
}

$riskfilter = optional_param('risk', '', PARAM_ALPHAEXT);
$priorityfilter = optional_param('priority', '', PARAM_ALPHAEXT);
$followupfilter = optional_param('followup', '', PARAM_ALPHAEXT);
$q = trim(optional_param('q', '', PARAM_TEXT));

$allowedrisk = ['', 'red', 'orange', 'yellow', 'green', 'recovered'];
$allowedpriority = ['', 'high', 'medium', 'preventive', 'ordinary'];
$allowedfollowup = ['', 'overdue', 'pendingreview', 'withoutfollowup', 'pending', 'inprogress', 'attended'];
if (!in_array($riskfilter, $allowedrisk, true)) {
    $riskfilter = '';
}
if (!in_array($priorityfilter, $allowedpriority, true)) {
    $priorityfilter = '';
}
if (!in_array($followupfilter, $allowedfollowup, true)) {
    $followupfilter = '';
}

$PAGE->set_url('/local/pceinotifications/teacher_dashboard.php', ['id' => $courseid, 'risk' => $riskfilter, 'priority' => $priorityfilter, 'followup' => $followupfilter, 'q' => $q]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('teacher_dashboard', 'local_pceinotifications'));
$PAGE->set_heading($course->fullname);

$notiftypelabels = [
    'vencida:student' => get_string('notification_type_overdue_student', 'local_pceinotifications'),
    'vencida:teacher' => get_string('notification_type_overdue_teacher', 'local_pceinotifications'),
    'proxima:student' => get_string('notification_type_upcoming_student', 'local_pceinotifications'),
    'proxima:teacher' => get_string('notification_type_upcoming_teacher', 'local_pceinotifications'),
    'resumen:student' => get_string('notification_type_summary_student', 'local_pceinotifications'),
    'resumen:teacher' => get_string('notification_type_summary_teacher', 'local_pceinotifications'),
];

$buildprofileurl = static function(int $studentid) use ($courseid): moodle_url {
    return new moodle_url('/local/pceinotifications/teacher_student_profile.php', ['id' => $courseid, 'userid' => $studentid]);
};

$trackingservice = new \local_pceinotifications\local\analytics\teacher_tracking_service();
$studentrows = $trackingservice->get_course_student_rows($courseid);
$summary = $trackingservice->get_course_summary($studentrows);

$filteredrows = array_values(array_filter($studentrows, function(array $row) use ($riskfilter, $priorityfilter, $followupfilter, $q): bool {
    $payload = $row['payload'];
    $studentname = fullname($row['student']);
    $risk = $payload['riskrecord']->risklevel ?? 'green';
    $priority = $payload['priority'] ?? 'ordinary';
    $followupstatus = $payload['followupstatus'] ?? 'none';

    if ($riskfilter !== '' && $risk !== $riskfilter) {
        return false;
    }
    if ($priorityfilter !== '' && $priority !== $priorityfilter) {
        return false;
    }
    if ($followupfilter !== '') {
        if ($followupfilter === 'overdue' && empty($row['nextreviewoverdue'])) {
            return false;
        }
        if ($followupfilter === 'pendingreview' && empty($row['pendingreview'])) {
            return false;
        }
        if ($followupfilter === 'withoutfollowup' && !empty($row['latestfollowup'])) {
            return false;
        }
        if (in_array($followupfilter, ['pending', 'inprogress', 'attended'], true) && $followupstatus !== $followupfilter) {
            return false;
        }
    }
    if ($q !== '' && core_text::strpos(core_text::strtolower($studentname), core_text::strtolower($q)) === false) {
        return false;
    }
    return true;
}));

$styles = \local_pceinotifications\util::page_styles();
$kpis = [
    ['label' => get_string('teacher_total_students', 'local_pceinotifications'), 'value' => $summary['total'], 'tone' => 'blue'],
    ['label' => get_string('teacher_high_priority_students', 'local_pceinotifications'), 'value' => $summary['high'], 'tone' => 'red'],
    ['label' => get_string('risk_red', 'local_pceinotifications'), 'value' => $summary['red'], 'tone' => 'red'],
    ['label' => get_string('risk_orange', 'local_pceinotifications'), 'value' => $summary['orange'], 'tone' => 'orange'],
    ['label' => get_string('risk_yellow', 'local_pceinotifications'), 'value' => $summary['yellow'], 'tone' => 'orange'],
    ['label' => get_string('risk_green', 'local_pceinotifications'), 'value' => $summary['green'], 'tone' => 'green'],
    ['label' => get_string('teacher_followup_overdue', 'local_pceinotifications'), 'value' => $summary['followupoverdue'], 'tone' => 'slate'],
    ['label' => get_string('teacher_without_followup', 'local_pceinotifications'), 'value' => $summary['withoutfollowup'], 'tone' => 'blue'],
];

$risktones = ['Crítico' => 'red', 'Prioritario' => 'orange', 'Preventivo' => 'orange', 'Normal' => 'green', 'Recuperado' => 'blue'];
$prioritytones = ['Alta' => 'red', 'Media' => 'orange', 'Preventiva' => 'blue', 'Ordinaria' => 'green'];
$followuptones = ['Sin seguimiento' => 'slate', 'Pendiente' => 'orange', 'En progreso' => 'blue', 'Atendido' => 'green'];

echo $OUTPUT->header();
echo html_writer::tag('style', $styles);
echo html_writer::start_div('vtn-shell');

echo html_writer::start_div('vtn-hero');
echo html_writer::tag('div', get_string('teacher_dashboard', 'local_pceinotifications'), ['class' => 'vtn-hero__title']);
echo html_writer::tag('p', get_string('teacher_dashboard_desc', 'local_pceinotifications'), ['class' => 'vtn-hero__text']);
echo html_writer::end_div();

echo html_writer::start_div('vtn-toolbar');
echo html_writer::link(new moodle_url('/local/pceinotifications/progress.php', ['id' => $courseid]), get_string('teacher_dashboard_go_progress', 'local_pceinotifications'), ['class' => 'btn btn-primary']);
echo html_writer::link(new moodle_url('/local/pceinotifications/notifications.php', ['id' => $courseid, 'mode' => 'course']), get_string('teacher_dashboard_go_notifications', 'local_pceinotifications'), ['class' => 'btn btn-outline-primary']);
echo html_writer::end_div();

echo html_writer::start_div('vtn-kpis');
foreach ($kpis as $card) {
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
echo html_writer::tag('div', get_string('teacher_filters_title', 'local_pceinotifications'), ['class' => 'vtn-section-title']);
echo html_writer::tag('p', get_string('teacher_filtered_results', 'local_pceinotifications', count($filteredrows) . '/' . count($studentrows)), ['class' => 'vtn-section-subtitle']);
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'vtn-filters']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $courseid]);
echo html_writer::start_div('row g-3');

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', get_string('filter_risk', 'local_pceinotifications'), ['for' => 'risk', 'class' => 'form-label']);
echo html_writer::start_tag('select', ['name' => 'risk', 'id' => 'risk', 'class' => 'form-select']);
foreach (['' => get_string('filter_all', 'local_pceinotifications'), 'red' => get_string('risk_red', 'local_pceinotifications'), 'orange' => get_string('risk_orange', 'local_pceinotifications'), 'yellow' => get_string('risk_yellow', 'local_pceinotifications'), 'green' => get_string('risk_green', 'local_pceinotifications'), 'recovered' => get_string('risk_recovered', 'local_pceinotifications')] as $value => $label) {
    echo html_writer::tag('option', s($label), ['value' => $value] + (($riskfilter === $value) ? ['selected' => 'selected'] : []));
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', get_string('filter_priority', 'local_pceinotifications'), ['for' => 'priority', 'class' => 'form-label']);
echo html_writer::start_tag('select', ['name' => 'priority', 'id' => 'priority', 'class' => 'form-select']);
foreach (['' => get_string('filter_all', 'local_pceinotifications'), 'high' => get_string('priority_high', 'local_pceinotifications'), 'medium' => get_string('priority_medium', 'local_pceinotifications'), 'preventive' => get_string('priority_preventive', 'local_pceinotifications'), 'ordinary' => get_string('priority_ordinary', 'local_pceinotifications')] as $value => $label) {
    echo html_writer::tag('option', s($label), ['value' => $value] + (($priorityfilter === $value) ? ['selected' => 'selected'] : []));
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', get_string('filter_followup', 'local_pceinotifications'), ['for' => 'followup', 'class' => 'form-label']);
echo html_writer::start_tag('select', ['name' => 'followup', 'id' => 'followup', 'class' => 'form-select']);
foreach (['' => get_string('filter_all', 'local_pceinotifications'), 'overdue' => get_string('filter_followup_overdue', 'local_pceinotifications'), 'pendingreview' => get_string('filter_followup_pendingreview', 'local_pceinotifications'), 'withoutfollowup' => get_string('filter_followup_withoutfollowup', 'local_pceinotifications'), 'pending' => get_string('followup_pending', 'local_pceinotifications'), 'inprogress' => get_string('followup_inprogress', 'local_pceinotifications'), 'attended' => get_string('followup_attended', 'local_pceinotifications')] as $value => $label) {
    echo html_writer::tag('option', s($label), ['value' => $value] + (($followupfilter === $value) ? ['selected' => 'selected'] : []));
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', get_string('filter_search_student', 'local_pceinotifications'), ['for' => 'q', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'q', 'id' => 'q', 'value' => $q, 'class' => 'form-control', 'placeholder' => get_string('filter_search_student_placeholder', 'local_pceinotifications')]);
echo html_writer::end_div();

echo html_writer::start_div('col-12');
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary me-2', 'value' => get_string('filter_apply', 'local_pceinotifications')]);
echo html_writer::link(new moodle_url('/local/pceinotifications/teacher_dashboard.php', ['id' => $courseid]), get_string('filter_clear', 'local_pceinotifications'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('vtn-card');
echo html_writer::start_div('vtn-card__body');
echo html_writer::tag('div', get_string('teacher_student_tracking_title', 'local_pceinotifications'), ['class' => 'vtn-section-title']);
echo html_writer::tag('p', get_string('teacher_student_tracking_desc', 'local_pceinotifications'), ['class' => 'vtn-section-subtitle']);
if ($filteredrows) {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover vtn-table';
    $table->head = [
        get_string('student', 'local_pceinotifications'),
        get_string('currentrisk', 'local_pceinotifications'),
        get_string('teacher_current_priority', 'local_pceinotifications'),
        get_string('followupstatus', 'local_pceinotifications'),
        get_string('inactivitydays', 'local_pceinotifications'),
        get_string('student_notifications_title', 'local_pceinotifications'),
        get_string('teacher_last_followup_recorded', 'local_pceinotifications'),
        get_string('teacher_next_review', 'local_pceinotifications'),
        get_string('col_action', 'local_pceinotifications'),
    ];
    foreach ($filteredrows as $row) {
        $student = $row['student'];
        $payload = $row['payload'];
        $profileurl = $buildprofileurl((int)$student->id);
        $notiftext = get_string('student_notifications_summary', 'local_pceinotifications', (object)[
            'sent' => $payload['notifications']['sent'],
            'success' => $payload['notifications']['success'],
        ]);
        $latestfollowup = $row['latestfollowup'];
        $lastfollowuptext = $latestfollowup && !empty($latestfollowup->timemodified) ? userdate($latestfollowup->timemodified) : get_string('notavailable', 'local_pceinotifications');
        $nextreviewtext = $latestfollowup && !empty($latestfollowup->nextreview) ? userdate($latestfollowup->nextreview, get_string('strftimedate')) : get_string('notavailable', 'local_pceinotifications');
        if (!empty($row['nextreviewoverdue'])) {
            $nextreviewtext .= ' · ' . get_string('followup_review_overdue_short', 'local_pceinotifications');
        }
        $table->data[] = [
            html_writer::link($profileurl, s(fullname($student)), ['class' => 'fw-semibold text-decoration-none']),
            \local_pceinotifications\util::badge($payload['risklabel'], $risktones[$payload['risklabel']] ?? 'blue'),
            \local_pceinotifications\util::badge($payload['prioritylabel'], $prioritytones[$payload['prioritylabel']] ?? 'blue'),
            \local_pceinotifications\util::badge($payload['followuplabel'], $followuptones[$payload['followuplabel']] ?? 'slate'),
            s($row['inactivitydays'] !== null ? (string)$row['inactivitydays'] : get_string('notavailable', 'local_pceinotifications')),
            s($notiftext),
            s($lastfollowuptext),
            !empty($row['nextreviewoverdue']) ? \local_pceinotifications\util::badge($nextreviewtext, 'red') : s($nextreviewtext),
            html_writer::link($profileurl, get_string('view_student_profile', 'local_pceinotifications'), ['class' => 'btn btn-sm btn-outline-primary']),
        ];
    }
    echo html_writer::table($table);
} else {
    echo html_writer::div(get_string('teacher_no_filtered_students', 'local_pceinotifications'), 'vtn-empty');
}
echo html_writer::end_div();
echo html_writer::end_div();

$logs = $DB->get_records('local_pceinotif_log', ['courseid' => $courseid], 'timesent DESC', '*', 0, 20);
echo html_writer::start_div('vtn-card');
echo html_writer::start_div('vtn-card__body');
echo html_writer::tag('div', get_string('teacher_recent_notifications', 'local_pceinotifications'), ['class' => 'vtn-section-title']);
echo html_writer::tag('p', get_string('teacher_recent_notifications_desc', 'local_pceinotifications'), ['class' => 'vtn-section-subtitle']);
if (!empty($logs)) {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover vtn-table';
    $table->head = [get_string('col_time', 'local_pceinotifications'), get_string('col_notiftype', 'local_pceinotifications'), get_string('col_subject', 'local_pceinotifications'), get_string('col_status', 'local_pceinotifications'), get_string('col_action', 'local_pceinotifications')];
    foreach ($logs as $l) {
        $type = $notiftypelabels[$l->notiftype] ?? get_string('notification_type_generic', 'local_pceinotifications');
        $profileurl = !empty($l->userid) ? $buildprofileurl((int)$l->userid) : null;
        $subject = s($l->subject);
        $action = '-';
        if ($profileurl) {
            $subject = html_writer::link($profileurl, $subject, ['class' => 'fw-semibold text-decoration-none']);
            $action = html_writer::link($profileurl, get_string('open_case_record', 'local_pceinotifications'), ['class' => 'btn btn-sm btn-outline-primary']);
        }
        $table->data[] = [
            !empty($l->timesent) ? userdate($l->timesent) : '-',
            \local_pceinotifications\util::badge($type, 'blue'),
            $subject,
            ((int)$l->success === 1) ? \local_pceinotifications\util::badge(get_string('status_sent', 'local_pceinotifications'), 'green') : \local_pceinotifications\util::badge(get_string('status_failed', 'local_pceinotifications'), 'red'),
            $action,
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
