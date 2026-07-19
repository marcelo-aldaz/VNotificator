<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');
require_login();

$context = context_system::instance();
require_capability('local/pceinotifications:viewadvanceddashboard', $context);

if (!empty($_SESSION['local_pceinotif_recalc_ok'])) {
    unset($_SESSION['local_pceinotif_recalc_ok']);
    \core\notification::add(get_string('metricsrecalculated', 'local_pceinotifications'), \core\output\notification::NOTIFY_SUCCESS);
}

$periodtype = optional_param('periodtype', 'monthly', PARAM_ALPHA);
$periodkey = optional_param('periodkey', date('Y-m'), PARAM_TEXT);
$loadcriticalcases = optional_param('loadcriticalcases', 0, PARAM_BOOL);

$url = new moodle_url('/local/pceinotifications/advanced_dashboard.php', ['periodtype' => $periodtype, 'periodkey' => $periodkey]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('dashboardadvanced', 'local_pceinotifications'));
$PAGE->set_heading(get_string('dashboardadvanced', 'local_pceinotifications'));

$service = new \local_pceinotifications\local\analytics\dashboard_data_service();
$payload = $service->get_dashboard_payload($periodtype, $periodkey, [], $USER->id, (bool)$loadcriticalcases);
$kpis = $payload['kpis'];
$distribution = $payload['distribution'];
$totalstudents = (int)($kpis['totalstudents'] ?? 0);
$total = max(1, $totalstudents);
$activestudents = (int)($kpis['activestudents'] ?? 0);
$atrisk = (int)($kpis['studentsatrisk'] ?? 0);
$highriskstudents = (int)($kpis['highriskstudents'] ?? 0);
$recoveredstudents = (int)($kpis['recoveredstudents'] ?? 0);
$openalerts = (int)($kpis['openalerts'] ?? 0);
$coveragepercent = round((float)($kpis['coveragepercent'] ?? 0), 1);
$riskpercent = round(($atrisk / $total) * 100, 1);
$activepercent = round(($activestudents / $total) * 100, 1);
$greenpct = round(((int)($distribution['green'] ?? 0) / $total) * 100, 1);
$yellowpct = round(((int)($distribution['yellow'] ?? 0) / $total) * 100, 1);
$orangepct = round(((int)($distribution['orange'] ?? 0) / $total) * 100, 1);
$redpct = round(((int)($distribution['red'] ?? 0) / $total) * 100, 1);
$recoveredpct = round(((int)($distribution['recovered'] ?? 0) / $total) * 100, 1);
$semaphorevalue = $payload['semaphore']['value'] ?? 'green';
$trendkey = !empty($payload['semaphore']['trenddirection']) ? $payload['semaphore']['trenddirection'] : 'notavailable';
$aggregatedat = !empty($payload['metadata']['aggregatedat']) ? userdate($payload['metadata']['aggregatedat']) : get_string('notavailable', 'local_pceinotifications');

$periodlabels = [
    'monthly' => 'Periodo mensual',
    'bimonthly' => 'Periodo bimestral',
    'finalcycle' => 'Cierre de ciclo',
];
$periodlabel = $periodlabels[$periodtype] ?? 'Periodo';
$periodpill = $periodlabel . ' · ' . s($periodkey);

$toggleurl = new moodle_url('/local/pceinotifications/advanced_dashboard.php', [
    'periodtype' => $periodtype,
    'periodkey' => $periodkey,
    'loadcriticalcases' => $loadcriticalcases ? 0 : 1,
]);
$reporturl = new moodle_url('/local/pceinotifications/institutional_report.php', ['periodtype' => $periodtype, 'periodkey' => $periodkey]);
$exporturl = new moodle_url('/local/pceinotifications/export_report.php', ['type' => 'summary', 'periodtype' => $periodtype, 'periodkey' => $periodkey, 'sesskey' => sesskey()]);
$recalcurl = new moodle_url('/local/pceinotifications/recalculate_metrics.php', ['periodtype' => $periodtype, 'periodkey' => $periodkey, 'sesskey' => sesskey()]);

$bars = [
    ['key' => 'green', 'label' => get_string('risk_green', 'local_pceinotifications'), 'count' => (int)($distribution['green'] ?? 0), 'pct' => $greenpct, 'color' => '#1f8f5f'],
    ['key' => 'yellow', 'label' => get_string('risk_yellow', 'local_pceinotifications'), 'count' => (int)($distribution['yellow'] ?? 0), 'pct' => $yellowpct, 'color' => '#d49b18'],
    ['key' => 'orange', 'label' => get_string('risk_orange', 'local_pceinotifications'), 'count' => (int)($distribution['orange'] ?? 0), 'pct' => $orangepct, 'color' => '#e46a11'],
    ['key' => 'red', 'label' => get_string('risk_red', 'local_pceinotifications'), 'count' => (int)($distribution['red'] ?? 0), 'pct' => $redpct, 'color' => '#c63c3c'],
    ['key' => 'recovered', 'label' => get_string('risk_recovered', 'local_pceinotifications'), 'count' => (int)($distribution['recovered'] ?? 0), 'pct' => $recoveredpct, 'color' => '#2d77da'],
];

$trendseries = [$greenpct, $yellowpct, $orangepct, $redpct, $recoveredpct];
$maxseries = max(1, max($trendseries));
$chartwidth = 540;
$chartheight = 180;
$leftpad = 26;
$rightpad = 18;
$toppad = 20;
$bottompad = 34;
$usablew = $chartwidth - $leftpad - $rightpad;
$usableh = $chartheight - $toppad - $bottompad;
$points = [];
foreach ($trendseries as $i => $value) {
    $x = $leftpad + ($usablew / max(1, (count($trendseries) - 1))) * $i;
    $y = $toppad + $usableh - (($value / $maxseries) * $usableh);
    $points[] = sprintf('%.1f,%.1f', $x, $y);
}
$polyline = implode(' ', $points);

$meaning = 'La tendencia institucional se mantiene controlada.';
if ($redpct >= 30) {
    $meaning = 'El tablero evidencia concentración alta de casos críticos y requiere intervención directiva inmediata.';
} else if (($redpct + $orangepct) >= 35) {
    $meaning = 'El tablero muestra presión relevante en riesgo alto y prioritario; conviene reforzar seguimiento docente.';
} else if ($yellowpct >= 30) {
    $meaning = 'Predominan señales preventivas; la institución debe actuar antes de que los casos escalen.';
} else if ($greenpct >= 50) {
    $meaning = 'La mayor parte de estudiantes se mantiene en estado estable o controlado durante el periodo analizado.';
}

$semaphoretone = \local_pceinotifications\util::tone_from_risk($semaphorevalue);
$styles = \local_pceinotifications\util::page_styles() . <<<CSS
.vtn-shell .vtn-hero--advanced{padding:1.55rem 1.6rem;background:linear-gradient(135deg,#0b4273 0%,#165fb8 58%,#0f8ac8 100%)}
.vtn-period-pill{display:inline-flex;align-items:center;gap:.45rem;padding:.45rem .8rem;border-radius:999px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);font-weight:600;margin-top:.55rem}
.vtn-dashboard-actions{display:flex;flex-wrap:wrap;gap:.65rem;margin-top:1rem}
.vtn-dashboard-actions .btn{border-radius:999px;padding:.72rem 1rem;font-weight:600}
.vtn-enterprise-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:1rem}
.vtn-chart-card{background:linear-gradient(180deg,#fff 0%,#f7fbff 100%);border:1px solid #dbe8f8;border-radius:22px;box-shadow:0 16px 40px rgba(15,76,129,.08)}
.vtn-chart-card__body{padding:1.15rem 1.2rem}
.vtn-chart-title{font-size:1.05rem;font-weight:700;color:#12344d;margin:0 0 .25rem 0}
.vtn-chart-subtitle{margin:0 0 1rem 0;color:#5b7083;font-size:.92rem}
.vtn-trend-legend{display:flex;flex-wrap:wrap;gap:.55rem;margin-top:.9rem}
.vtn-legend-item{display:inline-flex;align-items:center;gap:.35rem;padding:.34rem .6rem;border-radius:999px;background:#f6f9fc;color:#35506b;border:1px solid #e1ebf6;font-size:.84rem}
.vtn-legend-dot{width:10px;height:10px;border-radius:50%}
.vtn-insight-list{display:grid;gap:.8rem}
.vtn-insight{border:1px solid #e5eef8;border-radius:18px;padding:1rem 1.05rem;background:#fff;box-shadow:0 10px 26px rgba(31,66,115,.06)}
.vtn-insight__label{font-size:.84rem;font-weight:700;letter-spacing:.02em;text-transform:uppercase;color:#5b7083;margin-bottom:.3rem}
.vtn-insight__value{font-size:1.2rem;font-weight:700;color:#12344d}
.vtn-insight__text{margin-top:.35rem;color:#5b7083}
.vtn-bar-list{display:grid;gap:.95rem}
.vtn-bar{display:grid;gap:.35rem}
.vtn-bar__head{display:flex;justify-content:space-between;gap:.8rem;color:#12344d;font-weight:600}
.vtn-bar__track{height:14px;border-radius:999px;background:#edf3fa;overflow:hidden;box-shadow:inset 0 1px 2px rgba(15,23,42,.05)}
.vtn-bar__fill{height:100%;border-radius:999px}
.vtn-report-table{margin-bottom:0;border-collapse:separate;border-spacing:0}
.vtn-report-table thead th{background:#eff6ff;color:#12344d;border-bottom:1px solid #d8e8fb;font-weight:700}
.vtn-report-table tbody tr:nth-child(even){background:#fbfdff}
.vtn-report-table tbody tr:hover{background:#f2f8ff}
.vtn-shell .vtn-filters .form-select{min-height:56px;line-height:1.35;padding-top:.95rem;padding-bottom:.95rem;padding-right:2.8rem;font-size:1rem;white-space:normal;background-position:right .9rem center;overflow:visible;text-overflow:clip}
.vtn-shell .vtn-filters .form-select option{white-space:normal}
.vtn-shell .vtn-filters .col-lg-4,.vtn-shell .vtn-filters .col-md-6,.vtn-shell .vtn-filters .col-md-12{min-width:0}
.vtn-shell .vtn-filters label.form-label{font-weight:700;color:#12344d}
.vtn-trend-svg{width:100%;height:auto;display:block}
.vtn-trend-svg text{font-size:11px;fill:#5b7083}
.vtn-trend-svg .grid{stroke:#dce7f4;stroke-width:1}
.vtn-trend-svg .axis{stroke:#9fb7d3;stroke-width:1.2}
.vtn-trend-svg .line{fill:none;stroke:#165fb8;stroke-width:3.5;stroke-linecap:round;stroke-linejoin:round}
.vtn-trend-svg .point{fill:#165fb8;stroke:#fff;stroke-width:3}
.vtn-trend-svg .area{fill:url(#trendFill)}
@media (max-width: 991px){.vtn-enterprise-grid{grid-template-columns:1fr}.vtn-dashboard-actions{margin-top:.9rem}}
CSS;

echo $OUTPUT->header();
echo html_writer::tag('style', $styles);
echo html_writer::start_div('vtn-shell');

echo html_writer::start_div('vtn-hero vtn-hero--advanced');
echo html_writer::tag('div', get_string('dashboardadvanced', 'local_pceinotifications'), ['class' => 'vtn-hero__title']);
echo html_writer::tag('p', get_string('dashboardadvancedsubtitle', 'local_pceinotifications') . ' Este tablero resume evolución, distribución de riesgo y foco de intervención institucional.', ['class' => 'vtn-hero__text']);
echo html_writer::tag('div', s($periodpill), ['class' => 'vtn-period-pill']);
echo html_writer::start_div('vtn-dashboard-actions');
echo html_writer::link($reporturl, get_string('viewreport', 'local_pceinotifications'), ['class' => 'btn btn-light']);
echo html_writer::link($exporturl, get_string('exportcsv', 'local_pceinotifications'), ['class' => 'btn btn-outline-light']);
if (has_capability('local/pceinotifications:recalculatemetrics', $context)) {
    echo html_writer::link($recalcurl, get_string('recalculatemetrics', 'local_pceinotifications'), ['class' => 'btn btn-outline-light']);
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('vtn-card');
echo html_writer::start_div('vtn-card__body');
echo html_writer::tag('div', 'Filtros ejecutivos del tablero', ['class' => 'vtn-section-title']);
echo html_writer::tag('p', 'Seleccione el periodo de análisis y regenere la vista institucional desde este mismo panel.', ['class' => 'vtn-section-subtitle']);
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $url->out(false), 'class' => 'vtn-filters']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::start_div('row g-3 align-items-end');
echo html_writer::start_div('col-lg-4 col-md-6');
echo html_writer::tag('label', get_string('periodtype', 'local_pceinotifications'), ['for' => 'id_periodtype', 'class' => 'form-label']);
echo html_writer::select(['monthly' => 'Mensual', 'bimonthly' => 'Bimestral', 'finalcycle' => 'Cierre de ciclo'], 'periodtype', $periodtype, false, ['id' => 'id_periodtype', 'class' => 'form-select']);
echo html_writer::end_div();
echo html_writer::start_div('col-lg-4 col-md-6');
echo html_writer::tag('label', get_string('periodkey', 'local_pceinotifications'), ['for' => 'id_periodkey', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'periodkey', 'id' => 'id_periodkey', 'value' => s($periodkey), 'class' => 'form-control', 'placeholder' => '2026-03']);
echo html_writer::end_div();
echo html_writer::start_div('col-lg-4 col-md-12');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('applyfilters', 'local_pceinotifications'), 'class' => 'btn btn-primary']);
if (has_capability('local/pceinotifications:recalculatemetrics', $context)) {
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('applyandrecalculate', 'local_pceinotifications'),
        'class' => 'btn btn-outline-primary ms-2',
        'formaction' => (new moodle_url('/local/pceinotifications/recalculate_metrics.php'))->out(false),
    ]);
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

$kpicards = [
    ['label' => get_string('totalstudents', 'local_pceinotifications'), 'value' => $totalstudents, 'hint' => 'Base total de estudiantes monitoreados.', 'tone' => 'blue'],
    ['label' => get_string('activestudents', 'local_pceinotifications'), 'value' => $activestudents, 'hint' => $activepercent . '% del total con actividad reciente.', 'tone' => 'green'],
    ['label' => get_string('studentsatrisk', 'local_pceinotifications'), 'value' => $atrisk, 'hint' => $riskpercent . '% del total bajo observación.', 'tone' => 'orange'],
    ['label' => get_string('highriskstudents', 'local_pceinotifications'), 'value' => $highriskstudents, 'hint' => 'Casos críticos o de alta urgencia.', 'tone' => 'red'],
    ['label' => get_string('openalerts', 'local_pceinotifications'), 'value' => $openalerts, 'hint' => 'Alertas activas pendientes de intervención.', 'tone' => 'slate'],
    ['label' => get_string('coveragepercent', 'local_pceinotifications'), 'value' => $coveragepercent . '%', 'hint' => 'Cobertura institucional de seguimiento.', 'tone' => 'blue'],
];
echo html_writer::start_div('vtn-metric-grid');
foreach ($kpicards as $card) {
    echo html_writer::start_div('vtn-metric-panel vtn-metric-panel--' . $card['tone']);
    echo html_writer::tag('div', s((string)$card['label']), ['class' => 'vtn-metric-panel__label']);
    echo html_writer::tag('div', s((string)$card['value']), ['class' => 'vtn-metric-panel__value']);
    echo html_writer::tag('div', s((string)$card['hint']), ['class' => 'vtn-metric-panel__hint']);
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('vtn-enterprise-grid');
echo html_writer::start_div('vtn-chart-card');
echo html_writer::start_div('vtn-chart-card__body');
echo html_writer::tag('div', get_string('riskprofiletitle', 'local_pceinotifications'), ['class' => 'vtn-chart-title']);
echo html_writer::tag('p', get_string('riskprofilesubtitle', 'local_pceinotifications'), ['class' => 'vtn-chart-subtitle']);
echo '<svg class="vtn-trend-svg" viewBox="0 0 ' . $chartwidth . ' ' . $chartheight . '" role="img" aria-label="Tendencia institucional de riesgo">';
echo '<defs><linearGradient id="trendFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#9ec5fe" stop-opacity=".35" /><stop offset="100%" stop-color="#9ec5fe" stop-opacity=".02" /></linearGradient></defs>';
foreach ([25, 50, 75, 100] as $tick) {
    $y = $toppad + $usableh - (($tick / 100) * $usableh);
    echo '<line class="grid" x1="' . $leftpad . '" y1="' . round($y,1) . '" x2="' . ($chartwidth - $rightpad) . '" y2="' . round($y,1) . '"></line>';
    echo '<text x="2" y="' . round($y + 4,1) . '">' . $tick . '%</text>';
}
echo '<line class="axis" x1="' . $leftpad . '" y1="' . ($chartheight - $bottompad) . '" x2="' . ($chartwidth - $rightpad) . '" y2="' . ($chartheight - $bottompad) . '"></line>';
echo '<path class="area" d="M ' . $leftpad . ' ' . ($chartheight - $bottompad) . ' L ' . $polyline . ' L ' . ($chartwidth - $rightpad) . ' ' . ($chartheight - $bottompad) . ' Z"></path>';
echo '<polyline class="line" points="' . $polyline . '"></polyline>';
$labels = ['Normal', 'Preventivo', 'Prioritario', 'Crítico', 'Recuperado'];
foreach ($points as $i => $point) {
    [$px, $py] = array_map('floatval', explode(',', $point));
    echo '<circle class="point" cx="' . round($px,1) . '" cy="' . round($py,1) . '" r="5"></circle>';
    echo '<text x="' . round($px - 24,1) . '" y="' . ($chartheight - 12) . '">' . s($labels[$i]) . '</text>';
}
echo '</svg>';
echo html_writer::start_div('vtn-trend-legend');
foreach ($bars as $bar) {
    echo html_writer::tag('span', html_writer::span('', 'vtn-legend-dot', ['style' => 'background:' . $bar['color']]) . s($bar['label']) . ' · ' . $bar['pct'] . '%', ['class' => 'vtn-legend-item']);
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('vtn-chart-card');
echo html_writer::start_div('vtn-chart-card__body');
echo html_writer::tag('div', 'Lectura ejecutiva del tablero', ['class' => 'vtn-chart-title']);
echo html_writer::tag('p', 'Interpretación rápida para revisión directiva y toma de decisiones.', ['class' => 'vtn-chart-subtitle']);
echo html_writer::start_div('vtn-insight-list');
echo html_writer::start_div('vtn-insight');
echo html_writer::tag('div', 'Semáforo institucional', ['class' => 'vtn-insight__label']);
echo html_writer::tag('div', \local_pceinotifications\util::badge(get_string('risk_' . $semaphorevalue, 'local_pceinotifications'), $semaphoretone), ['class' => 'vtn-insight__value']);
echo html_writer::tag('div', 'Tendencia: ' . get_string($trendkey, 'local_pceinotifications') . '. Último consolidado: ' . s($aggregatedat), ['class' => 'vtn-insight__text']);
echo html_writer::end_div();
echo html_writer::start_div('vtn-insight');
echo html_writer::tag('div', 'Lectura institucional', ['class' => 'vtn-insight__label']);
echo html_writer::tag('div', s($meaning), ['class' => 'vtn-insight__value']);
echo html_writer::tag('div', 'La lectura combina volumen de riesgo, estudiantes activos y cobertura de seguimiento.', ['class' => 'vtn-insight__text']);
echo html_writer::end_div();
echo html_writer::start_div('vtn-insight');
echo html_writer::tag('div', 'Estado de seguimiento', ['class' => 'vtn-insight__label']);
echo html_writer::tag('div', $coveragepercent . '% de cobertura', ['class' => 'vtn-insight__value']);
echo html_writer::tag('div', 'A mayor cobertura, menor probabilidad de casos sin intervención documentada.', ['class' => 'vtn-insight__text']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('vtn-grid vtn-grid--equal');
echo html_writer::start_div('vtn-chart-card');
echo html_writer::start_div('vtn-chart-card__body');
echo html_writer::tag('div', get_string('riskdistribution', 'local_pceinotifications'), ['class' => 'vtn-chart-title']);
echo html_writer::tag('p', 'Distribución porcentual y volumen de estudiantes por nivel de riesgo.', ['class' => 'vtn-chart-subtitle']);
echo html_writer::start_div('vtn-bar-list');
foreach ($bars as $bar) {
    echo html_writer::start_div('vtn-bar');
    echo html_writer::tag('div', html_writer::span(s($bar['label'])) . html_writer::span($bar['count'] . ' · ' . $bar['pct'] . '%'), ['class' => 'vtn-bar__head']);
    echo html_writer::tag('div', html_writer::tag('div', '', ['class' => 'vtn-bar__fill', 'style' => 'width:' . max(0, min(100, $bar['pct'])) . '%;background:' . $bar['color']]), ['class' => 'vtn-bar__track']);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('vtn-chart-card');
echo html_writer::start_div('vtn-chart-card__body');
echo html_writer::tag('div', 'Resumen numérico institucional', ['class' => 'vtn-chart-title']);
echo html_writer::tag('p', 'Cuadro consolidado para exportación, análisis y contraste entre porcentajes y cantidades.', ['class' => 'vtn-chart-subtitle']);
$summary = new html_table();
$summary->attributes['class'] = 'vtn-table vtn-report-table';
$summary->head = [get_string('category', 'local_pceinotifications'), get_string('count', 'local_pceinotifications'), get_string('percentage', 'local_pceinotifications'), 'Lectura'];
foreach ($bars as $bar) {
    $reading = 'Nivel estable.';
    if ($bar['key'] === 'red') { $reading = 'Requiere atención institucional inmediata.'; }
    else if ($bar['key'] === 'orange') { $reading = 'Conviene seguimiento prioritario.'; }
    else if ($bar['key'] === 'yellow') { $reading = 'Demanda monitoreo preventivo.'; }
    else if ($bar['key'] === 'recovered') { $reading = 'Muestra recuperación o contención.'; }
    $summary->data[] = [$bar['label'], $bar['count'], $bar['pct'] . '%', $reading];
}
echo html_writer::table($summary);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('vtn-chart-card');
echo html_writer::start_div('vtn-chart-card__body');
echo html_writer::tag('div', get_string('criticalcases', 'local_pceinotifications'), ['class' => 'vtn-chart-title']);
echo html_writer::tag('p', 'Casos de alta sensibilidad para revisión directiva y acompañamiento intensivo.', ['class' => 'vtn-chart-subtitle']);
echo html_writer::div(html_writer::link($toggleurl, $loadcriticalcases ? get_string('hidecriticalcases', 'local_pceinotifications') : get_string('loadcriticalcases', 'local_pceinotifications'), ['class' => 'btn btn-outline-secondary']), 'mb-3');
if ($loadcriticalcases && !empty($payload['criticalcases'])) {
    $table = new html_table();
    $table->attributes['class'] = 'vtn-table vtn-report-table';
    $table->head = [get_string('student', 'local_pceinotifications'), get_string('col_course', 'local_pceinotifications'), get_string('risklevel', 'local_pceinotifications'), get_string('inactivitydays', 'local_pceinotifications'), get_string('openalerts', 'local_pceinotifications'), get_string('trenddirection', 'local_pceinotifications'), get_string('followupstatus', 'local_pceinotifications'), get_string('suggestedaction', 'local_pceinotifications')];
    foreach ($payload['criticalcases'] as $case) {
        $action = get_string('action_normal', 'local_pceinotifications');
        if ($case->risklevel === 'red') {
            $action = get_string('action_immediate', 'local_pceinotifications');
        } else if ($case->risklevel === 'orange') {
            $action = get_string('action_priority', 'local_pceinotifications');
        } else if ($case->risklevel === 'yellow') {
            $action = get_string('action_preventive', 'local_pceinotifications');
        }
        $followupkey = !empty($case->followupstatus) ? 'followup_' . $case->followupstatus : 'notavailable';
        $riskbadge = \local_pceinotifications\util::badge(get_string('risk_' . $case->risklevel, 'local_pceinotifications'), \local_pceinotifications\util::tone_from_risk($case->risklevel));
        $table->data[] = [
            s(fullname($case)),
            s(format_string($case->coursefullname)),
            $riskbadge,
            s(($case->inactivitydays ?? null) !== null ? (string)$case->inactivitydays : get_string('notavailable', 'local_pceinotifications')),
            s($case->openalerts),
            s(!empty($case->trend) ? get_string($case->trend, 'local_pceinotifications') : get_string('notavailable', 'local_pceinotifications')),
            s(get_string($followupkey, 'local_pceinotifications')),
            s($action),
        ];
    }
    echo html_writer::table($table);
} else if ($loadcriticalcases) {
    echo $OUTPUT->notification(get_string('nocriticalcases', 'local_pceinotifications'), 'notifymessage');
} else {
    echo html_writer::div(get_string('criticalcaseshint', 'local_pceinotifications'), 'vtn-empty');
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();
