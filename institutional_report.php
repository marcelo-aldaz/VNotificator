<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');
require_login();

$context = context_system::instance();
require_capability('local/pceinotifications:viewreports', $context);
$canviewidentified = has_capability('local/pceinotifications:viewidentifiedreports', $context);

$periodtype = optional_param('periodtype', 'monthly', PARAM_ALPHA);
$periodkey = optional_param('periodkey', date('Y-m'), PARAM_TEXT);

$url = new moodle_url('/local/pceinotifications/institutional_report.php', ['periodtype' => $periodtype, 'periodkey' => $periodkey]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('institutionalreport', 'local_pceinotifications'));
$PAGE->set_heading(get_string('institutionalreport', 'local_pceinotifications'));

$service = new \local_pceinotifications\local\analytics\report_service();
$report = $service->get_institutional_report_payload($periodtype, $periodkey, [], $USER->id);
$payload = $report['payload'];
$kpis = $payload['kpis'];

$backurl = new moodle_url('/local/pceinotifications/advanced_dashboard.php', ['periodtype' => $periodtype, 'periodkey' => $periodkey]);
$exportsummary = new moodle_url('/local/pceinotifications/export_report.php', ['type' => 'summary', 'periodtype' => $periodtype, 'periodkey' => $periodkey, 'sesskey' => sesskey()]);
$exportcritical = new moodle_url('/local/pceinotifications/export_report.php', ['type' => 'criticalcases', 'periodtype' => $periodtype, 'periodkey' => $periodkey, 'sesskey' => sesskey()]);
$exportcriticalanon = new moodle_url('/local/pceinotifications/export_report.php', ['type' => 'criticalcasesanon', 'periodtype' => $periodtype, 'periodkey' => $periodkey, 'sesskey' => sesskey()]);
$exportdistribution = new moodle_url('/local/pceinotifications/export_report.php', ['type' => 'distribution', 'periodtype' => $periodtype, 'periodkey' => $periodkey, 'sesskey' => sesskey()]);
$exportcourse = new moodle_url('/local/pceinotifications/export_report.php', ['type' => 'course', 'periodtype' => $periodtype, 'periodkey' => $periodkey, 'sesskey' => sesskey()]);
$exporttutor = new moodle_url('/local/pceinotifications/export_report.php', ['type' => 'tutor', 'periodtype' => $periodtype, 'periodkey' => $periodkey, 'sesskey' => sesskey()]);
$exportcohort = new moodle_url('/local/pceinotifications/export_report.php', ['type' => 'cohort', 'periodtype' => $periodtype, 'periodkey' => $periodkey, 'sesskey' => sesskey()]);

$periodlabel = $service->format_period_label($periodtype, $periodkey);
$semaphorevalue = $payload['semaphore']['value'] ?? 'green';
$semaphorelabel = get_string('risk_' . $semaphorevalue, 'local_pceinotifications');
$generatedon = userdate($report['generatedat']);
$latestcalc = !empty($payload['metadata']['aggregatedat']) ? userdate($payload['metadata']['aggregatedat']) : get_string('notavailable', 'local_pceinotifications');


$distributionpoints = [];
$distributionlabels = [];
foreach ($report['distributionrows'] as $row) {
    $distributionpoints[] = (float)$row['percent'];
    $distributionlabels[] = get_string($row['labelkey'], 'local_pceinotifications');
}
$activitypoints = [
    (int)($kpis['activestudents'] ?? 0),
    (int)($kpis['studentsatrisk'] ?? 0),
    (int)($kpis['highriskstudents'] ?? 0),
    (int)($kpis['openalerts'] ?? 0),
    (int)($kpis['recoveredstudents'] ?? 0),
];
$activitylabels = [
    get_string('activestudents', 'local_pceinotifications'),
    get_string('studentsatrisk', 'local_pceinotifications'),
    get_string('highriskstudents', 'local_pceinotifications'),
    get_string('openalerts', 'local_pceinotifications'),
    get_string('recoveredstudents', 'local_pceinotifications'),
];
$criticalcount = count($payload['criticalcases'] ?? []);
$criticalunique = (int)($payload['metadata']['criticaluniquestudentsdisplayed'] ?? 0);
$style = <<<HTML
<style>
.pcei-report{max-width:1280px;margin:0 auto;padding-bottom:1.5rem}
.pcei-hero{background:linear-gradient(135deg,#0f4c81 0%,#1967c8 52%,#0d87c8 100%);border-radius:28px;padding:1.45rem 1.5rem;box-shadow:0 20px 48px rgba(15,76,129,.22);color:#fff;display:grid;grid-template-columns:1.35fr .65fr;gap:1.1rem;margin-bottom:1rem}
.pcei-hero__title{font-size:1.7rem;font-weight:800;line-height:1.1;margin:0 0 .45rem}
.pcei-hero__text{margin:0;opacity:.96;max-width:860px}
.pcei-meta-card{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);border-radius:22px;padding:1rem 1.1rem;backdrop-filter:blur(6px)}
.pcei-meta-line{margin:0 0 .45rem;font-size:.95rem}.pcei-meta-line:last-child{margin-bottom:0}
.pcei-chipbar{display:flex;flex-wrap:wrap;gap:.6rem;margin-top:.95rem}.pcei-chip{display:inline-flex;align-items:center;padding:.5rem .9rem;border-radius:999px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.24);font-weight:700}
.pcei-toolbar{display:flex;flex-wrap:wrap;gap:.65rem;margin:1rem 0 1.15rem}
.pcei-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin:0 0 1.2rem}
.pcei-card{background:#fff;border:1px solid #dbe8f7;border-radius:22px;box-shadow:0 14px 34px rgba(31,66,115,.08);padding:1rem 1.05rem}
.pcei-card--blue{background:linear-gradient(135deg,#0f4c81,#2d77da);color:#fff;border-color:transparent}.pcei-card--green{background:linear-gradient(135deg,#18794e,#28a36a);color:#fff;border-color:transparent}.pcei-card--orange{background:linear-gradient(135deg,#cd6d08,#f59e0b);color:#fff;border-color:transparent}.pcei-card--red{background:linear-gradient(135deg,#b42318,#ef4444);color:#fff;border-color:transparent}.pcei-card--slate{background:linear-gradient(135deg,#475467,#667085);color:#fff;border-color:transparent}
.pcei-card__value{font-size:1.72rem;font-weight:800;line-height:1.05;margin-bottom:.35rem}.pcei-card__label{font-size:.93rem;opacity:.95}.pcei-card__hint{margin-top:.35rem;font-size:.82rem;opacity:.92}
.pcei-layout{display:grid;grid-template-columns:1.1fr .9fr;gap:1rem;margin-bottom:1rem}
.pcei-panel{background:linear-gradient(180deg,#fff 0%,#f8fbff 100%);border:1px solid #dbe8f7;border-radius:24px;box-shadow:0 14px 34px rgba(31,66,115,.08);padding:1.1rem 1.15rem}
.pcei-panel__title{font-size:1.08rem;font-weight:800;color:#12344d;margin:0 0 .25rem}.pcei-panel__subtitle{font-size:.92rem;color:#5b7083;margin:0 0 .9rem}
.pcei-semaphore{display:flex;align-items:center;gap:.9rem;padding:1rem;border-radius:20px;border:1px solid #dbe8f7;background:#fff}
.pcei-dot{width:18px;height:18px;border-radius:50%;display:inline-block;box-shadow:0 0 0 4px rgba(255,255,255,.65)}
.pcei-dot.green{background:#2e7d32}.pcei-dot.yellow{background:#f9a825}.pcei-dot.orange{background:#ef6c00}.pcei-dot.red{background:#c62828}.pcei-dot.recovered{background:#1565c0}
.pcei-semaphore__value{font-size:1.14rem;font-weight:800;color:#12344d}.pcei-semaphore__text{color:#5b7083}
.pcei-chart{padding:1rem;border-radius:22px;background:#fff;border:1px solid #dbe8f7;box-shadow:0 10px 24px rgba(31,66,115,.06)}
.pcei-chart + .pcei-chart{margin-top:1rem}
.pcei-chart__caption{font-size:.86rem;color:#5b7083;margin-top:.55rem}.pcei-chart__meaning{margin-top:.7rem;padding:.85rem 1rem;border-radius:16px;background:#f5f9ff;border:1px solid #d7e7fb;color:#4f687e}
.pcei-distribution{display:grid;grid-template-columns:minmax(170px,220px) 1fr 110px;gap:12px;align-items:center;margin-bottom:.8rem}
.pcei-bar-wrap{background:#eef2f7;border-radius:999px;overflow:hidden;height:16px}.pcei-bar{height:16px;border-radius:999px}
.pcei-bar.green{background:linear-gradient(90deg,#2e7d32,#49a95c)}.pcei-bar.yellow{background:linear-gradient(90deg,#f0b429,#ffd76b)}.pcei-bar.orange{background:linear-gradient(90deg,#e67e22,#f6ad55)}.pcei-bar.red{background:linear-gradient(90deg,#c62828,#ef5350)}.pcei-bar.recovered{background:linear-gradient(90deg,#1565c0,#42a5f5)}
.pcei-table-wrap{background:#fff;border:1px solid #dbe8f7;border-radius:24px;box-shadow:0 14px 34px rgba(31,66,115,.08);padding:1rem 1.1rem;margin-bottom:1rem}
.pcei-table{width:100%;border-collapse:separate;border-spacing:0;table-layout:fixed}.pcei-table thead th{background:#eef5ff;color:#12344d;font-weight:800;border-bottom:1px solid #dbe8f7;padding:.88rem .8rem}.pcei-table thead th:first-child{border-top-left-radius:16px}.pcei-table thead th:last-child{border-top-right-radius:16px}.pcei-table tbody td{padding:.82rem .8rem;border-bottom:1px solid #edf2f8;vertical-align:middle}.pcei-table tbody tr:nth-child(even){background:#fbfdff}.pcei-table tbody tr:hover{background:#f5f9ff}
.pcei-badge{display:inline-flex;align-items:center;padding:.34rem .68rem;border-radius:999px;font-size:.83rem;font-weight:700}.pcei-badge--red{background:#fdeceb;color:#b42318}.pcei-badge--orange{background:#fff2df;color:#b76600}.pcei-badge--yellow{background:#fff8d9;color:#9a6700}.pcei-badge--green{background:#eaf7ef;color:#18794e}.pcei-badge--blue{background:#e7f0ff;color:#0f4c81}.pcei-badge--slate{background:#f2f4f7;color:#475467}
.pcei-notes{display:grid;gap:.8rem}.pcei-note{padding:.95rem 1rem;border-radius:18px;border:1px solid #d7e7fb;background:linear-gradient(180deg,#f7fbff 0%,#fff 100%);box-shadow:0 10px 24px rgba(31,66,115,.05);color:#4f687e}.pcei-mini-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.8rem;margin:0 0 1rem}.pcei-mini{background:#f7fbff;border:1px solid #d7e7fb;border-radius:18px;padding:.85rem .95rem}.pcei-mini__label{font-size:.8rem;font-weight:800;color:#5b7083;text-transform:uppercase;letter-spacing:.04em}.pcei-mini__value{font-size:1.02rem;font-weight:900;color:#12344d;margin-top:.2rem}
.pcei-pill{display:inline-flex;align-items:center;padding:.58rem .95rem;border-radius:999px;background:#eef5ff;color:#0f4c81;font-weight:800;border:1px solid #cfe0f6}
.pcei-empty{padding:1rem;border:1px dashed #bfd7ff;background:#f7fbff;border-radius:18px;color:#49657d}
.pcei-printonly{display:none}
@media (max-width: 991px){.pcei-hero,.pcei-layout{grid-template-columns:1fr}.pcei-distribution{grid-template-columns:1fr}.pcei-card__value{font-size:1.5rem}}
@media print{#page-header,.secondary-navigation,.navbar,.drawer,.block,footer,.pcei-toolbar,.breadcrumb-nav{display:none !important}.pcei-report{max-width:none}.pcei-panel,.pcei-card,.pcei-table-wrap,.pcei-chart,.pcei-note{box-shadow:none}.pcei-printonly{display:block}}
</style>
HTML;

$riskbadgetones = ['green' => 'green', 'yellow' => 'yellow', 'orange' => 'orange', 'red' => 'red', 'recovered' => 'blue'];

$summarycards = [
    ['key' => 'totalstudents', 'tone' => 'blue', 'hint' => get_string('executivesummary', 'local_pceinotifications')],
    ['key' => 'activestudents', 'tone' => 'green', 'hint' => get_string('institutionalsemaphore', 'local_pceinotifications')],
    ['key' => 'studentsatrisk', 'tone' => 'orange', 'hint' => get_string('riskdistributionreport', 'local_pceinotifications')],
    ['key' => 'highriskstudents', 'tone' => 'red', 'hint' => get_string('criticalcasesreport', 'local_pceinotifications')],
    ['key' => 'openalerts', 'tone' => 'slate', 'hint' => get_string('reportobservations', 'local_pceinotifications')],
    ['key' => 'coveragepercent', 'tone' => 'blue', 'hint' => get_string('followupstatus', 'local_pceinotifications')],
];

$distributionchart = \local_pceinotifications\util::simple_bar_chart($distributionpoints, ['N','P','Pr','C','R']);
$activitychart = \local_pceinotifications\util::simple_bar_chart($activitypoints, ['A','R','RA','Ca','Rec'], '#0f9d58');

echo $OUTPUT->header();
echo $style;
echo html_writer::start_div('pcei-report');
if (!empty($payload['metadata']['requiresrecalculation'])) {
    echo $OUTPUT->notification(get_string('analyticsrecalculationrequired', 'local_pceinotifications'), 'notifywarning');
}

echo html_writer::start_div('pcei-hero');
echo html_writer::start_div();
echo html_writer::tag('div', get_string('institutionalreport', 'local_pceinotifications'), ['class' => 'pcei-hero__title']);
echo html_writer::tag('p', get_string('reportsummary_formal', 'local_pceinotifications'), ['class' => 'pcei-hero__text']);
echo html_writer::start_div('pcei-chipbar');
echo html_writer::tag('span', get_string('reportperiodlabel', 'local_pceinotifications', $periodlabel), ['class' => 'pcei-chip']);
echo html_writer::tag('span', get_string('latestconsolidatedlabel', 'local_pceinotifications', $latestcalc), ['class' => 'pcei-chip']);
echo html_writer::tag('span', get_string('generatedon', 'local_pceinotifications', $generatedon), ['class' => 'pcei-chip']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('pcei-meta-card');
echo html_writer::tag('div', get_string('institutionalsemaphore', 'local_pceinotifications'), ['class' => 'pcei-meta-line']);
echo html_writer::tag('div', $semaphorelabel, ['class' => 'pcei-hero__title', 'style' => 'font-size:1.32rem;margin-bottom:.2rem;']);
echo html_writer::tag('div', get_string('trenddirection', 'local_pceinotifications') . ': ' . s(!empty($payload['semaphore']['trenddirection']) ? get_string($payload['semaphore']['trenddirection'], 'local_pceinotifications') : get_string('notavailable', 'local_pceinotifications')), ['class' => 'pcei-meta-line']);
echo html_writer::tag('div', get_string('criticalcasesreport', 'local_pceinotifications') . ': ' . $criticalcount, ['class' => 'pcei-meta-line']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('pcei-mini-grid');
echo html_writer::tag('div', html_writer::tag('div', get_string('institutionalsemaphore', 'local_pceinotifications'), ['class' => 'pcei-mini__label']) . html_writer::tag('div', $semaphorelabel, ['class' => 'pcei-mini__value']), ['class' => 'pcei-mini']);
echo html_writer::tag('div', html_writer::tag('div', get_string('criticalcasesreport', 'local_pceinotifications'), ['class' => 'pcei-mini__label']) . html_writer::tag('div', (string)$criticalcount, ['class' => 'pcei-mini__value']), ['class' => 'pcei-mini']);
echo html_writer::tag('div', html_writer::tag('div', get_string('coveragepercent', 'local_pceinotifications'), ['class' => 'pcei-mini__label']) . html_writer::tag('div', round((float)($kpis['coveragepercent'] ?? 0), 1) . '%', ['class' => 'pcei-mini__value']), ['class' => 'pcei-mini']);
echo html_writer::end_div();

echo html_writer::start_div('pcei-toolbar');
echo html_writer::link($backurl, get_string('backtodashboard', 'local_pceinotifications'), ['class' => 'btn btn-secondary']);
echo html_writer::link($exportsummary, get_string('exportsummarycsv', 'local_pceinotifications'), ['class' => 'btn btn-primary']);
if ($canviewidentified) {
    echo html_writer::link($exportcritical, get_string('exportcriticalcsv', 'local_pceinotifications'), ['class' => 'btn btn-primary']);
}
echo html_writer::link($exportcriticalanon, get_string('exportcriticalanoncsv', 'local_pceinotifications'), ['class' => 'btn btn-primary']);
echo html_writer::link($exportdistribution, get_string('exportdistributioncsv', 'local_pceinotifications'), ['class' => 'btn btn-outline-primary']);
echo html_writer::link($exportcourse, get_string('exportcoursecsv', 'local_pceinotifications'), ['class' => 'btn btn-outline-primary']);
if ($canviewidentified) {
    echo html_writer::link($exporttutor, get_string('exporttutorcsv', 'local_pceinotifications'), ['class' => 'btn btn-outline-primary']);
}
echo html_writer::link($exportcohort, get_string('exportcohortcsv', 'local_pceinotifications'), ['class' => 'btn btn-outline-primary']);
$printurl = new moodle_url('/local/pceinotifications/institutional_report_print.php', ['periodtype' => $periodtype, 'periodkey' => $periodkey]);
echo html_writer::link($printurl, get_string('printreportprofessional', 'local_pceinotifications'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::end_div();

echo html_writer::tag('div', get_string('printreporthint_professional', 'local_pceinotifications'), ['class' => 'pcei-note']);
echo html_writer::tag('div', get_string('reportunitnote', 'local_pceinotifications'), ['class' => 'pcei-note']);

echo html_writer::start_div('pcei-grid');
foreach ($summarycards as $card) {
    $value = $kpis[$card['key']] ?? 0;
    if ($card['key'] === 'coveragepercent') {
        $value = round((float)$value, 1) . '%';
    }
    echo html_writer::start_div('pcei-card pcei-card--' . $card['tone']);
    echo html_writer::tag('div', s((string)$value), ['class' => 'pcei-card__value']);
    echo html_writer::tag('div', get_string($card['key'], 'local_pceinotifications'), ['class' => 'pcei-card__label']);
    echo html_writer::tag('div', $card['hint'], ['class' => 'pcei-card__hint']);
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('pcei-layout');
echo html_writer::start_div('pcei-panel');
echo html_writer::tag('div', get_string('institutionalsemaphore', 'local_pceinotifications'), ['class' => 'pcei-panel__title']);
echo html_writer::tag('p', 'Lectura ejecutiva del estado institucional y distribución de criticidad.', ['class' => 'pcei-panel__subtitle']);
echo html_writer::start_div('pcei-semaphore');
echo html_writer::tag('span', '', ['class' => 'pcei-dot ' . s($semaphorevalue)]);
echo html_writer::start_div();
echo html_writer::tag('div', $semaphorelabel, ['class' => 'pcei-semaphore__value']);
echo html_writer::tag('div', get_string('trenddirection', 'local_pceinotifications') . ': ' . s(!empty($payload['semaphore']['trenddirection']) ? get_string($payload['semaphore']['trenddirection'], 'local_pceinotifications') : get_string('notavailable', 'local_pceinotifications')), ['class' => 'pcei-semaphore__text']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('pcei-chart mt-3');
echo html_writer::tag('div', get_string('riskdistributionreport', 'local_pceinotifications'), ['class' => 'pcei-panel__title']);
echo $distributionchart;
echo html_writer::tag('div', get_string('riskprofilemeaning', 'local_pceinotifications'), ['class' => 'pcei-chart__meaning']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('pcei-panel');
echo html_writer::tag('div', 'Indicadores operativos del periodo', ['class' => 'pcei-panel__title']);
echo html_writer::tag('p', 'Comparación de magnitudes del mismo periodo; no constituye una serie temporal.', ['class' => 'pcei-panel__subtitle']);
echo html_writer::start_div('pcei-chart');
echo $activitychart;
echo html_writer::tag('div', 'A = sin riesgo actual, R = en riesgo, RA = riesgo alto, Ca = casos abiertos, Rec = recuperados.', ['class' => 'pcei-chart__caption']);
echo html_writer::tag('div', 'Los indicadores utilizan escalas de conteo comparables, pero representan conceptos diferentes y deben interpretarse por separado.', ['class' => 'pcei-chart__meaning']);
echo html_writer::end_div();
echo html_writer::start_div('pcei-chart mt-3');
echo html_writer::tag('div', 'Distribución detallada', ['class' => 'pcei-panel__title']);
foreach ($report['distributionrows'] as $row) {
    $class = str_replace('risk_', '', $row['labelkey']);
    echo html_writer::start_div('pcei-distribution');
    echo html_writer::tag('div', get_string($row['labelkey'], 'local_pceinotifications'));
    echo html_writer::tag('div', html_writer::tag('div', '', ['class' => 'pcei-bar ' . s($class), 'style' => 'width:' . max(2, (float)$row['percent']) . '%;']), ['class' => 'pcei-bar-wrap']);
    echo html_writer::tag('div', $row['count'] . ' (' . $row['percent'] . '%)');
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('pcei-table-wrap');
echo html_writer::tag('div', get_string('criticalcasesreport', 'local_pceinotifications'), ['class' => 'pcei-panel__title']);
echo html_writer::tag('p', get_string('criticalrecordsnote', 'local_pceinotifications', (object)[
    'records' => $criticalcount,
    'students' => $criticalunique,
    'total' => (int)($kpis['highriskstudents'] ?? 0),
]), ['class' => 'pcei-panel__subtitle']);
echo html_writer::tag('p', 'Casos con mayor necesidad de actuación institucional, priorizados por riesgo y seguimiento.', ['class' => 'pcei-panel__subtitle']);
echo html_writer::start_tag('table', ['class' => 'pcei-table']);
echo html_writer::start_tag('thead');
echo html_writer::tag('tr',
    html_writer::tag('th', get_string('student', 'local_pceinotifications')) .
    html_writer::tag('th', get_string('course')) .
    html_writer::tag('th', get_string('risklevel', 'local_pceinotifications')) .
    html_writer::tag('th', get_string('inactivitydays', 'local_pceinotifications')) .
    html_writer::tag('th', get_string('followupstatus', 'local_pceinotifications')) .
    html_writer::tag('th', get_string('suggestedaction', 'local_pceinotifications'))
);
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');
if (empty($payload['criticalcases'])) {
    echo html_writer::tag('tr', html_writer::tag('td', get_string('nocriticalcases', 'local_pceinotifications'), ['colspan' => 6]));
} else {
    foreach ($payload['criticalcases'] as $case) {
        $riskkey = (string)($case->risklevel ?? 'green');
        $risklabel = get_string('risk_' . $riskkey, 'local_pceinotifications');
        $followupkey = (string)($case->followupstatus ?? 'none');
        $followup = get_string('followup_' . $followupkey, 'local_pceinotifications');
        $followuptone = \local_pceinotifications\util::tone_from_followup($followupkey);
        $action = get_string('action_normal', 'local_pceinotifications');
        if ($riskkey === 'red') {
            $action = get_string('action_immediate', 'local_pceinotifications');
        } else if ($riskkey === 'orange') {
            $action = get_string('action_priority', 'local_pceinotifications');
        } else if ($riskkey === 'yellow') {
            $action = get_string('action_preventive', 'local_pceinotifications');
        }
        $caseidentifier = $canviewidentified
            ? trim(fullname($case))
            : 'Caso ' . strtoupper(substr(hash('sha256', $periodkey . '|' . (int)($case->userid ?? 0)), 0, 8));
        echo html_writer::tag('tr',
            html_writer::tag('td', s($caseidentifier)) .
            html_writer::tag('td', s($case->coursefullname ?? '')) .
            html_writer::tag('td', html_writer::span($risklabel, 'pcei-badge pcei-badge--' . ($riskbadgetones[$riskkey] ?? 'slate'))) .
            html_writer::tag('td', s(($case->inactivitydays ?? null) !== null ? (string)$case->inactivitydays : get_string('notavailable', 'local_pceinotifications'))) .
            html_writer::tag('td', html_writer::span($followup, 'pcei-badge pcei-badge--' . ($followuptone === 'slate' ? 'slate' : $followuptone))) .
            html_writer::tag('td', s($action))
        );
    }
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');
echo html_writer::end_div();

echo html_writer::start_div('pcei-panel');
echo html_writer::tag('div', get_string('reportobservations', 'local_pceinotifications'), ['class' => 'pcei-panel__title']);
echo html_writer::tag('p', 'Interpretación ejecutiva para revisión directiva, acompañamiento y respaldo documental.', ['class' => 'pcei-panel__subtitle']);
echo html_writer::start_div('pcei-notes');
foreach ($report['observations'] as $obs) {
    echo html_writer::tag('div', s($obs), ['class' => 'pcei-note']);
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div(get_string('printfooter', 'local_pceinotifications'), 'pcei-printonly');
echo html_writer::end_div();
echo $OUTPUT->footer();
