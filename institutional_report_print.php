<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');
require_login();

$context = context_system::instance();
require_capability('local/pceinotifications:viewreports', $context);
$canviewidentified = has_capability('local/pceinotifications:viewidentifiedreports', $context);

$periodtype = optional_param('periodtype', 'monthly', PARAM_ALPHA);
$periodkey = optional_param('periodkey', date('Y-m'), PARAM_TEXT);

$url = new moodle_url('/local/pceinotifications/institutional_report_print.php', ['periodtype' => $periodtype, 'periodkey' => $periodkey]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('embedded');

$ls = static function(string $key, $a = null): string {
    $manager = get_string_manager();
    if ($manager->string_exists($key, 'local_pceinotifications')) {
        return get_string($key, 'local_pceinotifications', $a);
    }
    $fallbacks = [
        'analyticreading' => 'Lectura analítica',
        'prioritycases' => 'Casos priorizados',
        'institutionaltracking' => 'Seguimiento institucional',
        'institutionalreportprint' => 'Reporte institucional para impresión',
        'institutionalreportprint_eyebrow' => 'Versión profesional para impresión y PDF',
        'institutionalreport' => 'Reporte institucional',
        'institutionalprintsummary' => 'Documento ejecutivo preparado para impresión institucional, archivo PDF y respaldo documental con conservación visual de colores, tarjetas, gráficos y tablas.',
        'reportperiodlabel' => 'Periodo: {$a}',
        'latestconsolidatedlabel' => 'Último consolidado: {$a}',
        'generatedon' => 'Generado el: {$a}',
        'institutionalsemaphore' => 'Semáforo institucional',
        'trenddirection' => 'Tendencia',
        'criticalcasesreport' => 'Reporte de casos críticos',
        'institutionalprintdocumentlabel' => 'Tipo de documento',
        'institutionalprintdocumentvalue' => 'Reporte institucional profesional listo para impresión',
        'printreporthint_professional' => 'Use esta vista para imprimir o guardar como PDF. El diseño conserva colores, tarjetas, gráficos y tablas en una estructura preparada para hoja A4.',
        'backtoreportscreen' => 'Volver al reporte',
        'printreportnow' => 'Imprimir ahora',
        'executivesummary' => 'Resumen ejecutivo',
        'riskdistributionreport' => 'Reporte de distribución de riesgo',
        'reportobservations' => 'Observaciones automáticas',
        'printfooter' => 'Reporte generado por VNotificator.',
    ];
    $template = $fallbacks[$key] ?? $key;
    if ($a !== null) {
        return str_replace('{$a}', (string)$a, $template);
    }
    return $template;
};

$PAGE->set_title($ls('institutionalreportprint'));
$PAGE->set_heading($ls('institutionalreportprint'));

$service = new \local_pceinotifications\local\analytics\report_service();
$report = $service->get_institutional_report_payload($periodtype, $periodkey, [], $USER->id);
$payload = $report['payload'];
$kpis = $payload['kpis'];

$backurl = new moodle_url('/local/pceinotifications/institutional_report.php', ['periodtype' => $periodtype, 'periodkey' => $periodkey]);
$periodlabel = $service->format_period_label($periodtype, $periodkey);
$semaphorevalue = $payload['semaphore']['value'] ?? 'green';
$semaphorelabel = get_string('risk_' . $semaphorevalue, 'local_pceinotifications');
$generatedon = userdate($report['generatedat']);
$latestcalc = !empty($payload['metadata']['aggregatedat']) ? userdate($payload['metadata']['aggregatedat']) : get_string('notavailable', 'local_pceinotifications');
$PAGE->requires->js_init_code("window.localPceiPrintReportNow = function() { window.print(); };");


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
html,body{background:#edf4fb}
.vtn-linechart{width:100%;height:auto;display:block}
.vtn-linechart__grid{stroke:#d7e3f0;stroke-width:1}
.vtn-linechart__label{font-size:11px;fill:#54667a;font-weight:700}
.pcei-printreport{max-width:1080px;margin:0 auto;padding:1rem .7rem 1.4rem}
.pcei-print-toolbar{display:flex;flex-wrap:wrap;gap:.75rem;justify-content:flex-end;margin:0 0 1rem}
.pcei-sheet{background:#fff;border:1px solid #dbe8f7;border-radius:24px;box-shadow:0 24px 60px rgba(15,76,129,.16);padding:1rem 1rem 1.15rem;overflow:hidden}
.pcei-header{display:grid;grid-template-columns:1.15fr .85fr;gap:.8rem;align-items:stretch;margin-bottom:.85rem}
.pcei-brand{background:linear-gradient(135deg,#0f4c81 0%,#1967c8 48%,#0d87c8 100%);color:#fff;border-radius:28px;padding:1.35rem 1.4rem;box-shadow:0 18px 42px rgba(15,76,129,.22)}
.pcei-brand__eyebrow{font-size:.84rem;text-transform:uppercase;letter-spacing:.08em;font-weight:800;opacity:.9;margin-bottom:.35rem}
.pcei-brand__title{font-size:1.8rem;font-weight:900;line-height:1.05;margin:0 0 .5rem}
.pcei-brand__desc{margin:0;opacity:.96;max-width:52rem}
.pcei-meta{display:grid;gap:.8rem}
.pcei-meta-box{background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);border:1px solid #dbe8f7;border-radius:24px;padding:1rem 1.05rem;box-shadow:0 12px 28px rgba(31,66,115,.08)}
.pcei-meta-box__label{font-size:.84rem;color:#667085;font-weight:800;text-transform:uppercase;letter-spacing:.05em}
.pcei-meta-box__value{font-size:1.08rem;color:#12344d;font-weight:800;margin-top:.25rem}
.pcei-chipbar{display:flex;flex-wrap:wrap;gap:.55rem;margin-top:.95rem}.pcei-chip{display:inline-flex;align-items:center;padding:.48rem .88rem;border-radius:999px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.24);font-weight:700}
.pcei-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem;margin:0 0 .9rem}
.pcei-kpi{border-radius:24px;padding:1rem 1rem;box-shadow:0 16px 34px rgba(31,66,115,.12);color:#fff;min-height:124px;display:flex;flex-direction:column;justify-content:space-between;page-break-inside:avoid}
.pcei-kpi--blue{background:linear-gradient(135deg,#0f4c81,#2d77da)}.pcei-kpi--green{background:linear-gradient(135deg,#18794e,#28a36a)}.pcei-kpi--orange{background:linear-gradient(135deg,#cd6d08,#f59e0b)}.pcei-kpi--red{background:linear-gradient(135deg,#b42318,#ef4444)}.pcei-kpi--slate{background:linear-gradient(135deg,#475467,#667085)}
.pcei-kpi__value{font-size:1.85rem;font-weight:900;line-height:1}.pcei-kpi__label{font-size:.92rem;font-weight:800}.pcei-kpi__hint{font-size:.79rem;opacity:.92;line-height:1.25}
.pcei-section{margin-bottom:1rem;page-break-inside:avoid}.pcei-avoid-break{page-break-inside:avoid;break-inside:avoid}.pcei-break-before{page-break-before:always;break-before:page}
.pcei-section-card{background:linear-gradient(180deg,#fff 0%,#f8fbff 100%);border:1px solid #dbe8f7;border-radius:26px;box-shadow:0 16px 34px rgba(31,66,115,.08);padding:1.1rem 1.15rem}
.pcei-section-title{font-size:1.1rem;font-weight:900;color:#12344d;margin:0 0 .25rem}.pcei-section-subtitle{font-size:.92rem;color:#5b7083;margin:0 0 .9rem}
.pcei-columns{display:grid;grid-template-columns:1.05fr .95fr;gap:.8rem}
.pcei-semaphore{display:flex;align-items:center;gap:.9rem;padding:1rem;border-radius:22px;background:#fff;border:1px solid #dbe8f7;box-shadow:inset 0 0 0 1px rgba(255,255,255,.55)}
.pcei-dot{width:18px;height:18px;border-radius:50%;display:inline-block}.pcei-dot.green{background:#2e7d32}.pcei-dot.yellow{background:#f9a825}.pcei-dot.orange{background:#ef6c00}.pcei-dot.red{background:#c62828}.pcei-dot.recovered{background:#1565c0}
.pcei-semaphore__value{font-size:1.15rem;font-weight:900;color:#12344d}.pcei-semaphore__text{color:#5b7083}
.pcei-chart{background:#fff;border:1px solid #dbe8f7;border-radius:20px;padding:.85rem;box-shadow:0 10px 24px rgba(31,66,115,.06);page-break-inside:avoid;overflow:hidden}.pcei-chart + .pcei-chart{margin-top:.75rem}
.pcei-chart__meaning,.pcei-chart__caption{margin-top:.7rem;border-radius:16px;padding:.8rem .9rem;background:#f6faff;border:1px solid #d7e7fb;color:#4f687e}
.pcei-distribution{display:grid;grid-template-columns:minmax(160px,220px) 1fr 120px;gap:10px;align-items:center;margin:.65rem 0}
.pcei-bar-wrap{background:#eef2f7;border-radius:999px;overflow:hidden;height:16px}.pcei-bar{height:16px;border-radius:999px}
.pcei-bar.green{background:linear-gradient(90deg,#2e7d32,#49a95c)}.pcei-bar.yellow{background:linear-gradient(90deg,#f0b429,#ffd76b)}.pcei-bar.orange{background:linear-gradient(90deg,#e67e22,#f6ad55)}.pcei-bar.red{background:linear-gradient(90deg,#c62828,#ef5350)}.pcei-bar.recovered{background:linear-gradient(90deg,#1565c0,#42a5f5)}
.pcei-table-wrap{background:#fff;border:1px solid #dbe8f7;border-radius:26px;box-shadow:0 16px 34px rgba(31,66,115,.08);padding:1rem 1rem 1.1rem;page-break-inside:auto}
.pcei-table{width:100%;border-collapse:separate;border-spacing:0;font-size:.82rem;table-layout:fixed}.pcei-table thead th{background:#eef5ff;color:#12344d;font-weight:900;border-bottom:1px solid #dbe8f7;padding:.72rem .58rem;word-break:normal;overflow-wrap:anywhere;vertical-align:top;line-height:1.2}.pcei-table thead th:first-child{border-top-left-radius:16px}.pcei-table thead th:last-child{border-top-right-radius:16px}.pcei-table tbody td{padding:.68rem .55rem;border-bottom:1px solid #edf2f8;vertical-align:top;overflow-wrap:anywhere;line-height:1.28}.pcei-table tbody tr:nth-child(even){background:#fbfdff}
.pcei-badge{display:inline-flex;align-items:center;padding:.34rem .68rem;border-radius:999px;font-size:.82rem;font-weight:800}.pcei-badge--red{background:#fdeceb;color:#b42318}.pcei-badge--orange{background:#fff2df;color:#b76600}.pcei-badge--yellow{background:#fff8d9;color:#9a6700}.pcei-badge--green{background:#eaf7ef;color:#18794e}.pcei-badge--blue{background:#e7f0ff;color:#0f4c81}.pcei-badge--slate{background:#f2f4f7;color:#475467}
.pcei-notes{display:grid;gap:.75rem}.pcei-note{padding:.9rem 1rem;border-radius:18px;border:1px solid #d7e7fb;background:linear-gradient(180deg,#f7fbff 0%,#fff 100%);box-shadow:0 10px 24px rgba(31,66,115,.05);color:#4f687e;page-break-inside:avoid}
.pcei-footer{margin-top:1rem;padding-top:.85rem;border-top:2px solid #dbe8f7;color:#667085;font-size:.9rem;display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.pcei-empty{padding:1rem;border:1px dashed #bfd7ff;background:#f7fbff;border-radius:18px;color:#49657d}
.pcei-screen-note{background:#ecf5ff;border:1px solid #cfe0f6;border-radius:16px;padding:.8rem .9rem;color:#335b7d;margin-bottom:1rem}
@page{size:A4 portrait;margin:11mm 10mm 12mm 10mm}
@media (max-width: 991px){.pcei-header,.pcei-columns,.pcei-grid{grid-template-columns:1fr}.pcei-distribution{grid-template-columns:1fr}.pcei-kpi{min-height:auto}}
.pcei-table th:nth-child(1),.pcei-table td:nth-child(1){width:17%}.pcei-table th:nth-child(2),.pcei-table td:nth-child(2){width:25%}.pcei-table th:nth-child(3),.pcei-table td:nth-child(3){width:12%}.pcei-table th:nth-child(4),.pcei-table td:nth-child(4){width:14%}.pcei-table th:nth-child(5),.pcei-table td:nth-child(5){width:14%}.pcei-table th:nth-child(6),.pcei-table td:nth-child(6){width:18%}.vtn-linechart{width:100%;height:auto;display:block}.vtn-linechart__label{font-size:11px;font-weight:700;fill:#4f687e}.pcei-table-wrap .pcei-section-subtitle{margin-bottom:.85rem}.pcei-table-wrap{overflow:hidden}
.pcei-summary-strip{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.7rem;margin:0 0 .9rem}.pcei-summary-box{background:#f7fbff;border:1px solid #d7e7fb;border-radius:18px;padding:.8rem .9rem;box-shadow:0 8px 18px rgba(31,66,115,.05)}.pcei-summary-box__label{font-size:.8rem;font-weight:800;color:#5b7083;text-transform:uppercase;letter-spacing:.04em}.pcei-summary-box__value{font-size:1rem;font-weight:900;color:#12344d;margin-top:.2rem}.pcei-section-marker{display:inline-flex;align-items:center;padding:.28rem .65rem;border-radius:999px;background:#e7f0ff;color:#0f4c81;font-size:.76rem;font-weight:800;margin-bottom:.55rem}.pcei-table--critical th,.pcei-table--critical td{text-align:left}.pcei-table--critical td:nth-child(4),.pcei-table--critical td:nth-child(6){line-height:1.25}.pcei-print-note{font-size:.83rem;color:#5b7083;margin-top:.55rem}.pcei-chart svg{max-width:100%;height:auto;display:block}
@media print{html,body{background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}.pagelayout-embedded #page{margin:0;padding:0}.pcei-print-toolbar,.btn,.paging,.skip-block,.breadcrumb-nav,.page-header-headings,.secondary-navigation,.navbar,footer,.drawer-toggles,.header-actions-container{display:none !important}.pcei-printreport{max-width:none;padding:0}.pcei-sheet{box-shadow:none;border:none;border-radius:0;padding:0}.pcei-section-card,.pcei-table-wrap,.pcei-chart,.pcei-note,.pcei-meta-box,.pcei-semaphore,.pcei-kpi{box-shadow:none;break-inside:avoid;page-break-inside:avoid}.pcei-grid{grid-template-columns:repeat(3,1fr)}.pcei-kpi{break-inside:avoid;page-break-inside:avoid}.pcei-table thead{display:table-header-group}.pcei-table tr,img,svg{break-inside:avoid;page-break-inside:avoid}.pcei-footer{position:fixed;left:0;right:0;bottom:0;background:#fff}.pcei-screen-note{display:none}}
</style>
HTML;

$riskbadgetones = ['green' => 'green', 'yellow' => 'yellow', 'orange' => 'orange', 'red' => 'red', 'recovered' => 'blue'];

$summarycards = [
    ['key' => 'totalstudents', 'tone' => 'blue', 'hint' => $ls('executivesummary')],
    ['key' => 'activestudents', 'tone' => 'green', 'hint' => $ls('institutionalsemaphore')],
    ['key' => 'studentsatrisk', 'tone' => 'orange', 'hint' => $ls('riskdistributionreport')],
    ['key' => 'highriskstudents', 'tone' => 'red', 'hint' => $ls('criticalcasesreport')],
    ['key' => 'openalerts', 'tone' => 'slate', 'hint' => $ls('reportobservations')],
    ['key' => 'coveragepercent', 'tone' => 'blue', 'hint' => get_string('followupstatus', 'local_pceinotifications')],
];

$distributionchart = \local_pceinotifications\util::simple_bar_chart($distributionpoints, ['N','P','Pr','C','R']);
$activitychart = \local_pceinotifications\util::simple_bar_chart($activitypoints, ['A','R','RA','Ca','Rec'], '#0f9d58');

echo $OUTPUT->header();
echo $style;
echo html_writer::start_div('pcei-printreport');
echo html_writer::start_div('pcei-print-toolbar');
echo html_writer::link($backurl, $ls('backtoreportscreen'), ['class' => 'btn btn-secondary']);
echo html_writer::link('#', $ls('printreportnow'), ['class' => 'btn btn-primary', 'onclick' => 'window.localPceiPrintReportNow(); return false;']);
echo html_writer::end_div();
echo html_writer::tag('div', $ls('printreporthint_professional'), ['class' => 'pcei-screen-note']);
echo html_writer::start_div('pcei-sheet');
if (!empty($payload['metadata']['requiresrecalculation'])) {
    echo html_writer::tag('div', get_string('analyticsrecalculationrequired', 'local_pceinotifications'), ['class' => 'pcei-print-note']);
}

echo html_writer::start_div('pcei-header');
echo html_writer::start_div('pcei-brand');
echo html_writer::start_div();
echo html_writer::tag('div', $ls('institutionalreportprint_eyebrow'), ['class' => 'pcei-brand__eyebrow']);
echo html_writer::tag('div', $ls('institutionalreport'), ['class' => 'pcei-brand__title']);
echo html_writer::tag('p', $ls('institutionalprintsummary'), ['class' => 'pcei-brand__desc']);
echo html_writer::start_div('pcei-chipbar');
echo html_writer::tag('span', $ls('reportperiodlabel', $periodlabel), ['class' => 'pcei-chip']);
echo html_writer::tag('span', $ls('latestconsolidatedlabel', $latestcalc), ['class' => 'pcei-chip']);
echo html_writer::tag('span', $ls('generatedon', $generatedon), ['class' => 'pcei-chip']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('pcei-meta');
echo html_writer::start_div('pcei-meta-box');
echo html_writer::tag('div', $ls('institutionalsemaphore'), ['class' => 'pcei-meta-box__label']);
echo html_writer::tag('div', $semaphorelabel, ['class' => 'pcei-meta-box__value']);
echo html_writer::tag('div', $ls('trenddirection') . ': ' . s(!empty($payload['semaphore']['trenddirection']) ? get_string($payload['semaphore']['trenddirection'], 'local_pceinotifications') : get_string('notavailable', 'local_pceinotifications')), ['class' => 'pcei-meta-box__value']);
echo html_writer::tag('div', $ls('criticalcasesreport') . ': ' . $criticalcount, ['class' => 'pcei-meta-box__value']);
echo html_writer::tag('div', get_string('studentcourseobservations', 'local_pceinotifications') . ': ' . (int)($kpis['studentcourseobservations'] ?? 0), ['class' => 'pcei-meta-box__value']);
echo html_writer::end_div();
echo html_writer::start_div('pcei-meta-box');
echo html_writer::tag('div', $ls('institutionalprintdocumentlabel'), ['class' => 'pcei-meta-box__label']);
echo html_writer::tag('div', $ls('institutionalprintdocumentvalue'), ['class' => 'pcei-meta-box__value']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('pcei-grid');
foreach ($summarycards as $card) {
    $value = $kpis[$card['key']] ?? 0;
    if ($card['key'] === 'coveragepercent') {
        $value = round((float)$value, 1) . '%';
    }
    echo html_writer::start_div('pcei-kpi pcei-kpi--' . $card['tone']);
    echo html_writer::tag('div', s((string)$value), ['class' => 'pcei-kpi__value']);
    echo html_writer::tag('div', get_string($card['key'], 'local_pceinotifications'), ['class' => 'pcei-kpi__label']);
    echo html_writer::tag('div', $card['hint'], ['class' => 'pcei-kpi__hint']);
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('pcei-section');
echo html_writer::start_div('pcei-columns');
echo html_writer::start_div('pcei-section-card pcei-avoid-break');
echo html_writer::tag('span', $ls('executivesummary'), ['class' => 'pcei-section-marker']);
echo html_writer::tag('div', $ls('institutionalsemaphore'), ['class' => 'pcei-section-title']);
echo html_writer::tag('p', 'Lectura ejecutiva del estado institucional y distribución de criticidad.', ['class' => 'pcei-section-subtitle']);
echo html_writer::start_div('pcei-semaphore');
echo html_writer::tag('span', '', ['class' => 'pcei-dot ' . s($semaphorevalue)]);
echo html_writer::start_div();
echo html_writer::tag('div', $semaphorelabel, ['class' => 'pcei-semaphore__value']);
echo html_writer::tag('div', $ls('trenddirection') . ': ' . s(!empty($payload['semaphore']['trenddirection']) ? get_string($payload['semaphore']['trenddirection'], 'local_pceinotifications') : get_string('notavailable', 'local_pceinotifications')), ['class' => 'pcei-semaphore__text']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('pcei-chart mt-3');
echo html_writer::tag('div', $ls('riskdistributionreport'), ['class' => 'pcei-section-title']);
echo $distributionchart;
echo html_writer::tag('div', get_string('riskprofilemeaning', 'local_pceinotifications'), ['class' => 'pcei-chart__meaning']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('pcei-section-card pcei-avoid-break');
echo html_writer::tag('span', $ls('analyticreading'), ['class' => 'pcei-section-marker']);
echo html_writer::tag('div', 'Indicadores operativos del periodo', ['class' => 'pcei-section-title']);
echo html_writer::tag('p', 'Comparación de magnitudes del mismo periodo; no constituye una serie temporal.', ['class' => 'pcei-section-subtitle']);
echo html_writer::start_div('pcei-chart');
echo $activitychart;
echo html_writer::tag('div', 'A = sin riesgo actual, R = en riesgo, RA = riesgo alto, Ca = casos abiertos, Rec = recuperados.', ['class' => 'pcei-chart__caption']);
echo html_writer::tag('div', 'Los indicadores representan conceptos diferentes y deben interpretarse por separado.', ['class' => 'pcei-chart__meaning']);
echo html_writer::end_div();
echo html_writer::start_div('pcei-chart mt-3');
echo html_writer::tag('div', 'Distribución detallada', ['class' => 'pcei-section-title']);
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
echo html_writer::end_div();

echo html_writer::start_div('pcei-table-wrap pcei-break-before');
echo html_writer::tag('div', $ls('criticalcasesreport'), ['class' => 'pcei-section-title']);
echo html_writer::tag('span', $ls('prioritycases'), ['class' => 'pcei-section-marker']);
echo html_writer::tag('p', get_string('criticalrecordsnote', 'local_pceinotifications', (object)[
    'records' => $criticalcount,
    'students' => $criticalunique,
    'total' => (int)($kpis['highriskstudents'] ?? 0),
]), ['class' => 'pcei-section-subtitle']);
echo html_writer::start_tag('table', ['class' => 'pcei-table pcei-table--critical']);
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

echo html_writer::start_div('pcei-section-card pcei-avoid-break');
echo html_writer::tag('span', $ls('institutionaltracking'), ['class' => 'pcei-section-marker']);
echo html_writer::tag('div', $ls('reportobservations'), ['class' => 'pcei-section-title']);
echo html_writer::tag('p', 'Interpretación ejecutiva para revisión directiva, acompañamiento y respaldo documental.', ['class' => 'pcei-section-subtitle']);
echo html_writer::start_div('pcei-notes');
foreach ($report['observations'] as $obs) {
    echo html_writer::tag('div', s($obs), ['class' => 'pcei-note']);
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('pcei-footer');
echo html_writer::tag('div', $ls('printfooter') . html_writer::tag('div', $ls('printreporthint_professional'), ['class' => 'pcei-print-note']));
echo html_writer::tag('div', $ls('generatedon', $generatedon));
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
