<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');
require_once($CFG->libdir . '/tablelib.php');

$courseid = required_param('id', PARAM_INT);
$mode     = optional_param('mode', 'mine', PARAM_ALPHA); // mine | course
$blocktype = optional_param('blocktype', '', PARAM_ALPHA);
$notiftype = optional_param('notiftype', '', PARAM_ALPHANUMEXT);
$status    = optional_param('status', '', PARAM_ALPHA); // sent | failed | all
$q         = optional_param('q', '', PARAM_TEXT);
$from      = optional_param('from', 0, PARAM_INT); // date selector timestamp
$to        = optional_param('to', 0, PARAM_INT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);

$canviewlogs = has_capability('local/pceinotifications:viewlogs', $context) || has_capability('local/pceinotifications:manage', $context);
$canreceive  = has_capability('local/pceinotifications:receive', $context);

if ($mode === 'course') {
    require_capability('local/pceinotifications:viewlogs', $context);
} else {
    // Student view: requires receive capability.
    if (!$canreceive && !$canviewlogs) {
        require_capability('local/pceinotifications:receive', $context);
    }
}

$PAGE->set_url('/local/pceinotifications/notifications.php', ['id' => $courseid, 'mode' => $mode]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('notifications_center', 'local_pceinotifications'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('notifications_center', 'local_pceinotifications'));

if (\local_pceinotifications\util::is_vtutor_enabled()) {
    echo html_writer::start_div('p-3 mb-3 border rounded bg-light', ['id' => 'vtutor_notifications_panel']);
    echo html_writer::tag('h2', get_string('vtutor_support_title', 'local_pceinotifications'), ['class' => 'h5']);
    echo html_writer::tag('p', get_string('vtutor_notifications_desc', 'local_pceinotifications'), ['class' => 'mb-2']);
    echo \local_pceinotifications\util::get_vtutor_link_html($courseid, $USER->id, 0, 0, 'secondary');
    echo html_writer::end_div();
}

// Tabs / actions.
$tablinks = [];
if ($canreceive) {
    $tablinks[] = html_writer::link(new moodle_url($PAGE->url, ['mode' => 'mine']), get_string('my_notifications', 'local_pceinotifications'),
        ['class' => ($mode === 'mine' ? 'btn btn-primary me-2' : 'btn btn-outline-primary me-2')]);
}
if ($canviewlogs) {
    $tablinks[] = html_writer::link(new moodle_url($PAGE->url, ['mode' => 'course']), get_string('course_notifications', 'local_pceinotifications'),
        ['class' => ($mode === 'course' ? 'btn btn-primary me-2' : 'btn btn-outline-primary me-2')]);
}
echo html_writer::div(implode('', $tablinks), 'mb-3');

// Filter form (accessible).
$formurl = new moodle_url('/local/pceinotifications/notifications.php', ['id' => $courseid, 'mode' => $mode]);
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $formurl->out(false), 'class' => 'mb-3']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $courseid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'mode', 'value' => $mode]);

echo html_writer::start_div('row g-2 align-items-end');

// Blocktype
echo html_writer::start_div('col-md-3');
echo html_writer::label(get_string('filter_blocktype', 'local_pceinotifications'), 'f_blocktype', false, ['class' => 'form-label']);
$opts = [
    '' => get_string('filter_all', 'local_pceinotifications'),
    'atpa' => get_string('type_atpa', 'local_pceinotifications'),
    'tei'  => get_string('type_tei', 'local_pceinotifications'),
];
echo html_writer::select($opts, 'blocktype', $blocktype, false, ['id' => 'f_blocktype', 'class' => 'form-select']);
echo html_writer::end_div();

// Notiftype
echo html_writer::start_div('col-md-3');
echo html_writer::label(get_string('filter_notiftype', 'local_pceinotifications'), 'f_notiftype', false, ['class' => 'form-label']);
$opts2 = [
    '' => get_string('filter_all', 'local_pceinotifications'),
    'previo' => get_string('notif_pre', 'local_pceinotifications'),
    'hoy' => get_string('notif_today', 'local_pceinotifications'),
    'rezago' => get_string('notif_late', 'local_pceinotifications'),
    'manual' => get_string('notif_manual', 'local_pceinotifications'),
];
echo html_writer::select($opts2, 'notiftype', $notiftype, false, ['id' => 'f_notiftype', 'class' => 'form-select']);
echo html_writer::end_div();

// Status
echo html_writer::start_div('col-md-2');
echo html_writer::label(get_string('filter_status', 'local_pceinotifications'), 'f_status', false, ['class' => 'form-label']);
$opts3 = [
    '' => get_string('filter_all', 'local_pceinotifications'),
    'sent' => get_string('status_sent', 'local_pceinotifications'),
    'failed' => get_string('status_failed', 'local_pceinotifications'),
];
echo html_writer::select($opts3, 'status', $status, false, ['id' => 'f_status', 'class' => 'form-select']);
echo html_writer::end_div();

// Search
echo html_writer::start_div('col-md-3');
echo html_writer::label(get_string('filter_search', 'local_pceinotifications'), 'f_q', false, ['class' => 'form-label']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'q', 'id' => 'f_q', 'value' => s($q), 'class' => 'form-control', 'placeholder' => get_string('filter_search_ph', 'local_pceinotifications')]);
echo html_writer::end_div();

echo html_writer::start_div('col-md-1');
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-secondary w-100', 'value' => get_string('filter_apply', 'local_pceinotifications')]);
echo html_writer::end_div();

echo html_writer::end_div(); // row
echo html_writer::end_tag('form');

// Build SQL
$params = ['courseid' => $courseid];
$where = ["l.courseid = :courseid"];

if ($mode === 'mine' && !$canviewlogs) {
    $where[] = "l.userid = :userid";
    $params['userid'] = $USER->id;
} else if ($mode === 'mine' && $canviewlogs && $canreceive) {
    // In admin/teacher mode, "mine" still shows the current user's notifications.
    $where[] = "l.userid = :userid";
    $params['userid'] = $USER->id;
}

if ($blocktype === 'atpa' || $blocktype === 'tei') {
    $where[] = "b.blocktype = :blocktype";
    $params['blocktype'] = $blocktype;
}

if ($notiftype !== '') {
    $where[] = "l.notiftype = :notiftype";
    $params['notiftype'] = $notiftype;
}

if ($status === 'sent') {
    $where[] = "l.success = 1";
} else if ($status === 'failed') {
    $where[] = "l.success = 0";
}

if (trim($q) !== '') {
    $where[] = $DB->sql_like('l.subject', ':q', false, false);
    $params['q'] = '%' . $DB->sql_like_escape($q) . '%';
}

// Date range (timesent is unix ts)
if (!empty($from)) {
    $where[] = "l.timesent >= :fromts";
    $params['fromts'] = $from;
}
if (!empty($to)) {
    $where[] = "l.timesent <= :tots";
    $params['tots'] = $to;
}

$sql = "SELECT l.id, l.userid, l.notiftype, l.subject, l.success, l.errormsg, l.timesent,
               b.id AS blockid, b.sectionid, b.sectionname, b.blocktype, b.sequenceindex
          FROM {local_pceinotif_log} l
     LEFT JOIN {local_pceinotif_blocks} b
            ON b.id = l.blockid
         WHERE " . implode(' AND ', $where) . "
      ORDER BY l.timesent DESC";

$limit = \local_pceinotifications\util::cfg_int('topstudentslimit', 10, 1, 200);
$records = $DB->get_records_sql($sql, $params, 0, max(50, $limit * 20));

// Table
$table = new html_table();
$table->attributes['class'] = 'generaltable table table-striped table-hover';
$table->caption = get_string('notifications_table_caption', 'local_pceinotifications');

$head = [
    get_string('col_time', 'local_pceinotifications'),
    get_string('col_block', 'local_pceinotifications'),
    get_string('col_notiftype', 'local_pceinotifications'),
    get_string('col_subject', 'local_pceinotifications'),
    get_string('col_status', 'local_pceinotifications'),
    get_string('col_action', 'local_pceinotifications'),
];

if ($mode === 'course') {
    $head[] = get_string('col_user', 'local_pceinotifications');
}

$table->head = $head;

$rows = [];
foreach ($records as $r) {
    $time = $r->timesent ? userdate($r->timesent) : '-';
    $block = '';
    if (!empty($r->sectionname)) {
        $block = s($r->sectionname) . ' (' . s(strtoupper($r->blocktype ?: '')) . ')';
    } else {
        $block = '-';
    }

    $nt = s($r->notiftype);
    $subject = s($r->subject);

    if ((int)$r->success === 1) {
        $st = html_writer::span(get_string('status_sent', 'local_pceinotifications'), 'badge bg-success', ['aria-label' => get_string('status_sent', 'local_pceinotifications')]);
    } else {
        $st = html_writer::span(get_string('status_failed', 'local_pceinotifications'), 'badge bg-danger', ['aria-label' => get_string('status_failed', 'local_pceinotifications')]);
    }

    $vtaction = '-';
    if (\local_pceinotifications\util::is_vtutor_enabled()) {
        $vtaction = \local_pceinotifications\util::get_vtutor_link_html($courseid, $USER->id, (int)($r->blockid ?? 0), (int)($r->sectionid ?? 0), 'secondary');
        if ($vtaction === '') { $vtaction = '-'; }
    }
    $row = [$time, $block, $nt, $subject, $st, $vtaction];

    if ($mode === 'course') {
        $u = core_user::get_user($r->userid, 'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename,email', MUST_EXIST);
        $row[] = fullname($u) . ' (' . s($u->email) . ')';
    }

    $rows[] = $row;
}
$table->data = $rows;

if (empty($rows)) {
    echo $OUTPUT->notification(get_string('no_notifications', 'local_pceinotifications'), 'info');
} else {
    echo html_writer::table($table);
    echo html_writer::div(get_string('notifications_limit_note', 'local_pceinotifications'), 'mt-2 text-muted', ['style' => 'font-size:0.9em']);
}

echo $OUTPUT->footer();
