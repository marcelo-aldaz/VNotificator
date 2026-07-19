<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/completionlib.php');

$courseid = required_param('id', PARAM_INT);
$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);

// Students: see own progress. Teachers/managers: see course summary.
$canviewlogs = has_capability('local/pceinotifications:viewlogs', $context) || has_capability('local/pceinotifications:manage', $context);
$canreceive  = has_capability('local/pceinotifications:receive', $context);
if (!$canreceive && !$canviewlogs) {
    require_capability('local/pceinotifications:receive', $context);
}

$PAGE->set_url('/local/pceinotifications/progress.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('progress_title', 'local_pceinotifications'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('progress_title', 'local_pceinotifications'));

if (\local_pceinotifications\util::is_vtutor_enabled()) {
    echo html_writer::start_div('p-3 mb-3 border rounded bg-light', ['id' => 'vtutorpanel']);
    echo html_writer::tag('h2', get_string('vtutor_support_title', 'local_pceinotifications'), ['class'=>'h5']);
    echo html_writer::tag('p', get_string('vtutor_support_desc', 'local_pceinotifications'), ['class'=>'mb-2']);
    echo \local_pceinotifications\util::get_vtutor_link_html($courseid, $USER->id, 0, 0, 'success');
    echo html_writer::end_div();
}

$modinfo = get_fast_modinfo($course);
$cms = $modinfo->get_cms();
$completion = new completion_info($course);
$warningdays = \local_pceinotifications\util::cfg_int('warningdays', 3, 1, 30);
$topstudentslimit = \local_pceinotifications\util::cfg_int('topstudentslimit', 10, 1, 100);

function pcei_get_activity_due($cm) {
    global $DB;
    switch ($cm->modname) {
        case 'assign':
            if ($rec = $DB->get_record('assign', ['id' => $cm->instance], 'duedate,cutoffdate')) {
                if (!empty($rec->duedate)) { return (int)$rec->duedate; }
                if (!empty($rec->cutoffdate)) { return (int)$rec->cutoffdate; }
            }
            break;
        case 'quiz':
            if ($rec = $DB->get_record('quiz', ['id' => $cm->instance], 'timeclose')) {
                if (!empty($rec->timeclose)) { return (int)$rec->timeclose; }
            }
            break;
        case 'forum':
            try {
                $fields = [];
                $columns = $DB->get_columns('forum');
                if (isset($columns['duedate'])) { $fields[] = 'duedate'; }
                if (isset($columns['cutoffdate'])) { $fields[] = 'cutoffdate'; }
                if (!empty($fields)) {
                    if ($rec = $DB->get_record('forum', ['id' => $cm->instance], implode(',', $fields))) {
                        if (!empty($rec->duedate)) { return (int)$rec->duedate; }
                        if (!empty($rec->cutoffdate)) { return (int)$rec->cutoffdate; }
                    }
                }
            } catch (Throwable $e) {}
            break;
        case 'workshop':
            if ($rec = $DB->get_record('workshop', ['id' => $cm->instance], 'submissionend,assessmentend')) {
                if (!empty($rec->submissionend)) { return (int)$rec->submissionend; }
                if (!empty($rec->assessmentend)) { return (int)$rec->assessmentend; }
            }
            break;
    }
    return 0;
}

function pcei_is_student_completed($course, $cm, $completion, $userid) {
    global $DB;
    if (!empty($cm->completion) && (int)$cm->completion > 0 && $completion->is_enabled()) {
        $data = $completion->get_data($cm, false, $userid);
        $state = (int)$data->completionstate;
        if ($state === COMPLETION_COMPLETE || $state === COMPLETION_COMPLETE_PASS) {
            return true;
        }
    }

    switch ($cm->modname) {
        case 'assign':
            $sub = $DB->get_record_sql(
                "SELECT id, status
                   FROM {assign_submission}
                  WHERE assignment = :aid AND userid = :uid
               ORDER BY timemodified DESC",
                ['aid' => $cm->instance, 'uid' => $userid],
                IGNORE_MULTIPLE
            );
            if ($sub && in_array($sub->status, ['submitted', 'reopened', 'graded'], true)) {
                return true;
            }
            break;
        case 'quiz':
            if ($DB->record_exists_select('quiz_attempts', 'quiz = :qid AND userid = :uid AND state IN (:s1, :s2)', [
                'qid' => $cm->instance, 'uid' => $userid, 's1' => 'finished', 's2' => 'graded'
            ])) {
                return true;
            }
            break;
    }

    return false;
}

function pcei_is_trackable_task($cm) {
    if (!$cm->uservisible) { return false; }
    if (in_array($cm->modname, ['label', 'resource', 'url', 'page', 'folder', 'book'], true)) {
        return false;
    }
    $due = pcei_get_activity_due($cm);
    if ($due > 0) { return true; }
    if (!empty($cm->completion) && (int)$cm->completion > 0) { return true; }
    if (in_array($cm->modname, ['assign', 'quiz', 'forum', 'workshop'], true)) { return true; }
    return false;
}

function pcei_task_temporal_status($completed, $due, $warningdays = 3) {
    if ($completed) {
        return 'completed';
    }
    if (empty($due)) {
        return 'pending_nodate';
    }
    $now = time();
    if ($due < $now) {
        return 'pending_overdue';
    }
    if (($due - $now) <= ($warningdays * DAYSECS)) {
        return 'pending_soon';
    }
    return 'pending_ontime';
}

function pcei_status_label($status) {
    switch ($status) {
        case 'completed': return get_string('taskstatus_completed', 'local_pceinotifications');
        case 'pending_nodate': return get_string('taskstatus_pending_nodate', 'local_pceinotifications');
        case 'pending_soon': return get_string('taskstatus_pending_soon', 'local_pceinotifications');
        case 'pending_overdue': return get_string('taskstatus_pending_overdue', 'local_pceinotifications');
        default: return get_string('taskstatus_pending_ontime', 'local_pceinotifications');
    }
}

function pcei_status_badge($status) {
    $label = pcei_status_label($status);
    $class = 'badge bg-secondary';
    if ($status === 'completed' || $status === 'pending_ontime') {
        $class = 'badge bg-success';
    } else if ($status === 'pending_soon') {
        $class = 'badge bg-warning text-dark';
    } else if ($status === 'pending_overdue') {
        $class = 'badge bg-danger';
    }
    return html_writer::span($label, $class, ['aria-label' => $label]);
}

function pcei_task_priority($status, $due) {
    if ($status === 'pending_overdue') { return 1; }
    if ($status === 'pending_soon') { return 2; }
    if ($status === 'pending_ontime') { return 3; }
    if ($status === 'pending_nodate') { return 4; }
    return 5;
}

function pcei_global_risk($counts) {
    if (!empty($counts['pending_overdue'])) {
        return 'high';
    }
    if (!empty($counts['pending_soon']) || (($counts['pending_nodate'] + $counts['pending_ontime']) >= 3)) {
        return 'medium';
    }
    return 'low';
}

function pcei_risk_badge($risk) {
    if ($risk === 'high') {
        return html_writer::span(get_string('risk_high', 'local_pceinotifications'), 'badge bg-danger');
    } else if ($risk === 'medium') {
        return html_writer::span(get_string('risk_medium', 'local_pceinotifications'), 'badge bg-warning text-dark');
    }
    return html_writer::span(get_string('risk_low', 'local_pceinotifications'), 'badge bg-success');
}

function pcei_donut_svg($done, $todo) {
    $total = max(1, ($done + $todo));
    $pct = ($done / $total) * 100.0;
    $r = 44;
    $circ = 2 * M_PI * $r;
    $dashDone = ($pct / 100.0) * $circ;
    $dashTodo = $circ - $dashDone;

    $title = get_string('progress_chart_title', 'local_pceinotifications', (int)round($pct));
    $desc  = get_string('progress_chart_desc', 'local_pceinotifications', (object)['done'=>$done,'todo'=>$todo,'total'=>$total]);

    return '
<svg width="140" height="140" viewBox="0 0 120 120" role="img" aria-label="'.s($title).'" xmlns="http://www.w3.org/2000/svg">
  <title>'.s($title).'</title>
  <desc>'.s($desc).'</desc>
  <g transform="rotate(-90 60 60)">
    <circle cx="60" cy="60" r="'.$r.'" fill="none" stroke="#e9ecef" stroke-width="12"></circle>
    <circle cx="60" cy="60" r="'.$r.'" fill="none" stroke="#198754" stroke-width="12"
      stroke-dasharray="'.sprintf('%.2f %.2f', $dashDone, $circ - $dashDone).'" stroke-linecap="round"></circle>
  </g>
  <text x="60" y="64" text-anchor="middle" font-size="16" fill="#212529">'.((int)round($pct)).'%</text>
</svg>';
}

function pcei_collect_user_tasks($course, $cms, $completion, $userid, $warningdays = 3) {
    $rows = [];
    $counts = [
        'completed' => 0,
        'pending_nodate' => 0,
        'pending_ontime' => 0,
        'pending_soon' => 0,
        'pending_overdue' => 0,
    ];

    foreach ($cms as $cm) {
        if (!pcei_is_trackable_task($cm)) { continue; }

        $due = pcei_get_activity_due($cm);
        $completed = pcei_is_student_completed($course, $cm, $completion, $userid);
        $status = pcei_task_temporal_status($completed, $due, $warningdays);
        $counts[$status]++;

        $rows[] = [
            'name' => $cm->get_formatted_name(),
            'modname' => $cm->modplural ?: $cm->modname,
            'due' => $due,
            'status' => $status,
            'url' => (string)$cm->url,
            'priority' => pcei_task_priority($status, $due),
        ];
    }

    usort($rows, function($a, $b) {
        if ($a['priority'] !== $b['priority']) {
            return $a['priority'] <=> $b['priority'];
        }
        $av = empty($a['due']) ? PHP_INT_MAX : $a['due'];
        $bv = empty($b['due']) ? PHP_INT_MAX : $b['due'];
        return $av <=> $bv;
    });

    return [$counts, $rows];
}

function pcei_collect_course_activity_summary($course, $cms, $completion, $users, $warningdays = 3) {
    $summary = [];
    foreach ($cms as $cm) {
        if (!pcei_is_trackable_task($cm)) { continue; }

        $due = pcei_get_activity_due($cm);
        $completed = 0;
        $pending = 0;
        foreach ($users as $u) {
            if (pcei_is_student_completed($course, $cm, $completion, $u->id)) {
                $completed++;
            } else {
                $pending++;
            }
        }

        $status = 'pending_nodate';
        if (!empty($due)) {
            $status = pcei_task_temporal_status(false, $due, $warningdays);
        }
        if ($pending === 0 && ($completed > 0 || count($users) > 0)) {
            $status = 'completed';
        }

        $summary[] = [
            'name' => $cm->get_formatted_name(),
            'modname' => $cm->modplural ?: $cm->modname,
            'due' => $due,
            'completed' => $completed,
            'pending' => $pending,
            'status' => $status,
            'url' => (string)$cm->url,
            'priority' => pcei_task_priority($status, $due),
        ];
    }

    usort($summary, function($a, $b) {
        if ($a['priority'] !== $b['priority']) {
            return $a['priority'] <=> $b['priority'];
        }
        $av = empty($a['due']) ? PHP_INT_MAX : $a['due'];
        $bv = empty($b['due']) ? PHP_INT_MAX : $b['due'];
        return $av <=> $bv;
    });

    return $summary;
}

function pcei_render_alerts($counts) {
    $alerts = [];
    if (!empty($counts['pending_overdue'])) {
        $alerts[] = html_writer::div(get_string('alert_overdue', 'local_pceinotifications', $counts['pending_overdue']), 'alert alert-danger mb-2');
    }
    if (!empty($counts['pending_soon'])) {
        $alerts[] = html_writer::div(get_string('alert_due_soon', 'local_pceinotifications', $counts['pending_soon']), 'alert alert-warning mb-2');
    }
    if (empty($counts['pending_overdue']) && empty($counts['pending_soon']) && empty($counts['pending_ontime']) && empty($counts['pending_nodate'])) {
        $alerts[] = html_writer::div(get_string('alert_no_pending', 'local_pceinotifications'), 'alert alert-success mb-2');
    }
    return implode('', $alerts);
}

// Student view: own task tracking by course.
if (!$canviewlogs) {
    list($counts, $tasks) = pcei_collect_user_tasks($course, $cms, $completion, $USER->id, $warningdays);
    $done = $counts['completed'];
    $todo = $counts['pending_nodate'] + $counts['pending_ontime'] + $counts['pending_soon'] + $counts['pending_overdue'];
    $total = $done + $todo;
    $risk = pcei_global_risk($counts);

    echo pcei_render_alerts($counts);

    echo html_writer::start_div('p-3 mb-3 border rounded');
    echo html_writer::tag('h2', get_string('progress_my_course', 'local_pceinotifications'), ['class'=>'h4']);
    echo html_writer::start_div('d-flex flex-wrap gap-3 align-items-center');

    echo html_writer::start_div('');
    echo html_writer::tag('div', get_string('progress_total', 'local_pceinotifications', $total), ['class'=>'mb-1']);
    echo html_writer::tag('div', get_string('progress_done', 'local_pceinotifications', $done), ['class'=>'mb-1']);
    echo html_writer::tag('div', get_string('progress_todo', 'local_pceinotifications', $todo), ['class'=>'mb-1']);
    echo html_writer::tag('div', get_string('taskcount_nodate', 'local_pceinotifications', $counts['pending_nodate']), ['class'=>'mb-1']);
    echo html_writer::tag('div', get_string('taskcount_ontime', 'local_pceinotifications', $counts['pending_ontime']), ['class'=>'mb-1']);
    echo html_writer::tag('div', get_string('taskcount_soon', 'local_pceinotifications', $counts['pending_soon']), ['class'=>'mb-1']);
    echo html_writer::tag('div', get_string('taskcount_overdue', 'local_pceinotifications', $counts['pending_overdue']), ['class'=>'mb-1']);
    echo html_writer::tag('div', get_string('progress_risk', 'local_pceinotifications') . ': ' . pcei_risk_badge($risk), ['class'=>'mb-1']);
    echo html_writer::end_div();

    echo html_writer::div(pcei_donut_svg($done, $todo), '');
    echo html_writer::end_div();
    echo html_writer::end_div();

    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-striped table-hover';
    $table->caption = get_string('task_table_mine_caption', 'local_pceinotifications');
    $table->head = [
        get_string('col_priority', 'local_pceinotifications'),
        get_string('col_activity', 'local_pceinotifications'),
        get_string('col_module', 'local_pceinotifications'),
        get_string('col_duedate', 'local_pceinotifications'),
        get_string('col_taskstatus', 'local_pceinotifications'),
    ];
    foreach ($tasks as $t) {
        $activity = html_writer::link($t['url'], format_string($t['name']));
        $due = !empty($t['due']) ? userdate($t['due']) : get_string('task_duedate_none', 'local_pceinotifications');
        $table->data[] = [
            (int)$t['priority'],
            $activity,
            s($t['modname']),
            $due,
            pcei_status_badge($t['status']),
        ];
    }
    if (!empty($tasks)) {
        echo html_writer::table($table);
    } else {
        echo $OUTPUT->notification(get_string('task_table_empty', 'local_pceinotifications'), 'info');
    }

    echo $OUTPUT->footer();
    exit;
}

// Teacher/manager view: course summary + top students.
$namefields = 'u.id,u.firstname,u.lastname,u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename,u.email';
$users = get_enrolled_users($context, 'local/pceinotifications:receive', 0, $namefields);
$tot_total = 0; $tot_done = 0; $tot_todo = 0; $n = 0;
$rank = [];
$coursecounts = [
    'completed' => 0,
    'pending_nodate' => 0,
    'pending_ontime' => 0,
    'pending_soon' => 0,
    'pending_overdue' => 0,
];

foreach ($users as $u) {
    list($ucounts, $utasks) = pcei_collect_user_tasks($course, $cms, $completion, $u->id, $warningdays);
    foreach ($coursecounts as $k => $v) {
        $coursecounts[$k] += $ucounts[$k];
    }
    $u_done = $ucounts['completed'];
    $u_todo = $ucounts['pending_nodate'] + $ucounts['pending_ontime'] + $ucounts['pending_soon'] + $ucounts['pending_overdue'];
    $u_total = $u_done + $u_todo;

    $tot_total += $u_total;
    $tot_done += $u_done;
    $tot_todo += $u_todo;
    $n++;
    $rank[] = ['user' => $u, 'todo' => $u_todo, 'done' => $u_done, 'total' => $u_total];
}

$avg_total = ($n > 0) ? round($tot_total / $n) : 0;
$avg_done  = ($n > 0) ? round($tot_done / $n) : 0;
$avg_todo  = ($n > 0) ? round($tot_todo / $n) : 0;
$risk = pcei_global_risk($coursecounts);

usort($rank, function($a,$b){ return $b['todo'] <=> $a['todo']; });
$top = array_slice($rank, 0, $topstudentslimit);

echo pcei_render_alerts($coursecounts);

echo html_writer::start_div('p-3 mb-3 border rounded');
echo html_writer::tag('h2', get_string('progress_course_summary', 'local_pceinotifications'), ['class'=>'h4']);
echo html_writer::start_div('d-flex flex-wrap gap-3 align-items-center');

echo html_writer::start_div('');
echo html_writer::tag('div', get_string('progress_students', 'local_pceinotifications', $n), ['class'=>'mb-1']);
echo html_writer::tag('div', get_string('progress_avg_total', 'local_pceinotifications', $avg_total), ['class'=>'mb-1']);
echo html_writer::tag('div', get_string('progress_avg_done', 'local_pceinotifications', $avg_done), ['class'=>'mb-1']);
echo html_writer::tag('div', get_string('progress_avg_todo', 'local_pceinotifications', $avg_todo), ['class'=>'mb-1']);
echo html_writer::tag('div', get_string('progress_risk', 'local_pceinotifications') . ': ' . pcei_risk_badge($risk), ['class'=>'mb-1']);
echo html_writer::end_div();

echo html_writer::div(pcei_donut_svg($avg_done, $avg_todo), '');
echo html_writer::end_div();
echo html_writer::end_div();

$summary = pcei_collect_course_activity_summary($course, $cms, $completion, $users, $warningdays);

$atable = new html_table();
$atable->attributes['class'] = 'generaltable table table-striped table-hover';
$atable->caption = get_string('task_table_course_caption', 'local_pceinotifications');
$atable->head = [
    get_string('col_priority', 'local_pceinotifications'),
    get_string('col_activity', 'local_pceinotifications'),
    get_string('col_module', 'local_pceinotifications'),
    get_string('col_duedate', 'local_pceinotifications'),
    get_string('col_completed_count', 'local_pceinotifications'),
    get_string('col_pending_count', 'local_pceinotifications'),
    get_string('col_taskstatus', 'local_pceinotifications'),
];

foreach ($summary as $s) {
    $activity = html_writer::link($s['url'], format_string($s['name']));
    $due = !empty($s['due']) ? userdate($s['due']) : get_string('task_duedate_none', 'local_pceinotifications');
    $atable->data[] = [
        (int)$s['priority'],
        $activity,
        s($s['modname']),
        $due,
        (int)$s['completed'],
        (int)$s['pending'],
        pcei_status_badge($s['status']),
    ];
}
if (!empty($summary)) {
    echo html_writer::table($atable);
}

$table = new html_table();
$table->attributes['class'] = 'generaltable table table-striped table-hover';
$table->caption = get_string('progress_top_caption', 'local_pceinotifications');
$table->head = [
    get_string('col_user', 'local_pceinotifications'),
    get_string('progress_todo_short', 'local_pceinotifications'),
    get_string('progress_done_short', 'local_pceinotifications'),
    get_string('progress_total_short', 'local_pceinotifications'),
];

$rows = [];
foreach ($top as $r) {
    $u = $r['user'];
    $rows[] = [
        fullname($u) . ' (' . s($u->email) . ')',
        (int)$r['todo'],
        (int)$r['done'],
        (int)$r['total'],
    ];
}
$table->data = $rows;
if (!empty($rows)) {
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
