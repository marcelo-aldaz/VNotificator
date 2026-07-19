<?php
require_once('../../config.php');
require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/pceinotifications:exportreports', $context);

$type = required_param('type', PARAM_ALPHA);
$periodtype = optional_param('periodtype', 'monthly', PARAM_ALPHA);
$periodkey = optional_param('periodkey', date('Y-m'), PARAM_TEXT);

$service = new \local_pceinotifications\local\analytics\report_service();
$validationreport = $service->get_institutional_report_payload($periodtype, $periodkey, [], $USER->id);
if (!empty($validationreport['payload']['metadata']['requiresrecalculation'])) {
    throw new moodle_exception('analyticsrecalculationrequired', 'local_pceinotifications');
}

switch ($type) {
    case 'summary':
        $rows = $service->get_summary_export_rows($periodtype, $periodkey, [], $USER->id);
        $filename = 'vnotificator_resumen_institucional_' . $periodkey . '.csv';
        break;
    case 'criticalcases':
        require_capability('local/pceinotifications:viewidentifiedreports', $context);
        $rows = $service->get_critical_cases_export_rows($periodtype, $periodkey, [], $USER->id);
        $filename = 'vnotificator_casos_criticos_' . $periodkey . '.csv';
        break;
    case 'criticalcasesanon':
        $rows = $service->get_critical_cases_export_rows($periodtype, $periodkey, [], $USER->id, false);
        $filename = 'vnotificator_casos_criticos_seudonimizados_' . $periodkey . '.csv';
        break;
    case 'distribution':
        $rows = $service->get_distribution_export_rows($periodtype, $periodkey, [], $USER->id);
        $filename = 'vnotificator_distribucion_riesgo_' . $periodkey . '.csv';
        break;
    case 'course':
        $rows = $service->get_course_summary_export_rows($periodtype, $periodkey);
        $filename = 'vnotificator_resumen_por_curso_' . $periodkey . '.csv';
        break;
    case 'tutor':
        require_capability('local/pceinotifications:viewidentifiedreports', $context);
        $rows = $service->get_tutor_summary_export_rows($periodtype, $periodkey);
        $filename = 'vnotificator_resumen_por_tutor_' . $periodkey . '.csv';
        break;
    case 'cohort':
        $rows = $service->get_cohort_summary_export_rows($periodtype, $periodkey);
        $filename = 'vnotificator_resumen_por_cohorte_' . $periodkey . '.csv';
        break;
    default:
        throw new moodle_exception('invalidreporttype', 'local_pceinotifications');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF");
if (!empty($rows)) {
    fputcsv($out, array_keys(reset($rows)), ';');
    foreach ($rows as $row) {
        fputcsv($out, $row, ';');
    }
} else {
    fputcsv($out, [get_string('nodatatoreport', 'local_pceinotifications')], ';');
}
fclose($out);
exit;
