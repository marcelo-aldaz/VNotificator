<?php
namespace local_pceinotifications\local\analytics;

use local_pceinotifications\util;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../../locallib.php');

class report_service {
    protected dashboard_data_service $dashboardservice;

    public function __construct() {
        $this->dashboardservice = new dashboard_data_service();
    }

    public function get_institutional_report_payload(string $periodtype, string $periodkey, array $filters = [], int $userid = 0): array {
        $payload = $this->dashboardservice->get_dashboard_payload($periodtype, $periodkey, $filters, $userid, true);
        $kpis = $payload['kpis'];
        $distribution = $payload['distribution'];
        $total = max(1, (int)($kpis['totalstudents'] ?? 0));

        $rows = [];
        foreach ([
            'green' => 'risk_green',
            'yellow' => 'risk_yellow',
            'orange' => 'risk_orange',
            'red' => 'risk_red',
            'recovered' => 'risk_recovered',
        ] as $key => $label) {
            $count = (int)($distribution[$key] ?? 0);
            $rows[] = [
                'labelkey' => $label,
                'count' => $count,
                'percent' => round(($count / $total) * 100, 1),
            ];
        }

        $payload['criticalcases'] = $this->normalize_critical_cases($payload['criticalcases'] ?? []);
        $payload['metadata']['criticalrecordsdisplayed'] = count($payload['criticalcases']);
        $payload['metadata']['criticaluniquestudentsdisplayed'] = count(array_unique(array_map(
            static fn($case): int => (int)($case->userid ?? 0),
            $payload['criticalcases']
        )));

        return [
            'payload' => $payload,
            'distributionrows' => $rows,
            'generatedat' => time(),
            'observations' => $this->build_observations($payload, $total),
        ];
    }


    protected function normalize_critical_cases(array $cases): array {
        foreach ($cases as $case) {
            if (isset($case->inactivitydays)) {
                $case->inactivitydays = $this->sanitize_inactivity_days($case->inactivitydays);
            }
        }
        return $cases;
    }

    protected function sanitize_inactivity_days($value): ?int {
        return util::sanitize_inactivity_days($value);
    }

    public function format_period_label(string $periodtype, string $periodkey): string {
        if ($periodtype === 'monthly' && preg_match('/^(\d{4})-(\d{2})$/', $periodkey, $m)) {
            $timestamp = mktime(12, 0, 0, (int)$m[2], 1, (int)$m[1]);
            return $this->translate_period_type($periodtype) . ' / ' . userdate($timestamp, get_string('strftimemonthyear'));
        }
        return $this->translate_period_type($periodtype) . ' / ' . $periodkey;
    }

    protected function build_observations(array $payload, int $total): array {
        $kpis = $payload['kpis'];
        $riskpercent = round(((int)($kpis['studentsatrisk'] ?? 0) / $total) * 100, 1);
        $coverage = round((float)($kpis['coveragepercent'] ?? 0), 1);
        $semaphore = $payload['semaphore']['value'] ?? 'green';
        $observations = [];
        switch ($semaphore) {
            case 'red':
                $observations[] = get_string('reportobs_red_formal', 'local_pceinotifications');
                break;
            case 'orange':
                $observations[] = get_string('reportobs_orange_formal', 'local_pceinotifications');
                break;
            case 'yellow':
                $observations[] = get_string('reportobs_yellow_formal', 'local_pceinotifications');
                break;
            default:
                $observations[] = get_string('reportobs_green_formal', 'local_pceinotifications');
        }
        $observations[] = get_string('reportobs_riskpercent_formal', 'local_pceinotifications', $riskpercent . '%');
        $observations[] = get_string('reportobs_coverage_formal', 'local_pceinotifications', $coverage . '%');
        return $observations;
    }

    public function get_summary_export_rows(string $periodtype, string $periodkey, array $filters = [], int $userid = 0): array {
        $report = $this->get_institutional_report_payload($periodtype, $periodkey, $filters, $userid);
        $p = $report['payload'];
        $k = $p['kpis'];
        return [[
            'Periodo' => $periodkey,
            'Tipo de periodo' => $this->translate_period_type($periodtype),
            'Estudiantes monitoreados' => $k['totalstudents'] ?? 0,
            'Registros estudiante-curso' => $k['studentcourseobservations'] ?? 0,
            'Sin riesgo actual' => $k['activestudents'] ?? 0,
            'Estudiantes en riesgo' => $k['studentsatrisk'] ?? 0,
            'Riesgo alto' => $k['highriskstudents'] ?? 0,
            'Recuperados' => $k['recoveredstudents'] ?? 0,
            'Casos abiertos' => $k['openalerts'] ?? 0,
            'Cobertura de seguimiento humano (%)' => round((float)($k['coveragepercent'] ?? 0), 1),
            'Semáforo institucional' => get_string('risk_' . ($p['semaphore']['value'] ?? 'green'), 'local_pceinotifications'),
            'Versión del motor' => $p['metadata']['sourceversion'] ?? '',
            'Requiere recálculo V9.4.2' => !empty($p['metadata']['requiresrecalculation']) ? 'Sí' : 'No',
            'Generado el' => userdate($report['generatedat']),
        ]];
    }

    public function get_distribution_export_rows(string $periodtype, string $periodkey, array $filters = [], int $userid = 0): array {
        $report = $this->get_institutional_report_payload($periodtype, $periodkey, $filters, $userid);
        $rows = [];
        foreach ($report['distributionrows'] as $row) {
            $rows[] = [
                'Categoría' => get_string($row['labelkey'], 'local_pceinotifications'),
                'Cantidad' => $row['count'],
                'Porcentaje (%)' => $row['percent'],
            ];
        }
        return $rows;
    }

    public function get_critical_cases_export_rows(string $periodtype, string $periodkey, array $filters = [], int $userid = 0, bool $identified = true): array {
        $report = $this->get_institutional_report_payload($periodtype, $periodkey, $filters, $userid);
        $rows = [];
        foreach ($report['payload']['criticalcases'] as $case) {
            $rows[] = [
                'Estudiante' => $identified
                    ? trim(fullname($case))
                    : 'Caso ' . strtoupper(substr(hash('sha256', $periodkey . '|' . (int)($case->userid ?? 0)), 0, 8)),
                'Curso' => $case->coursefullname ?? '',
                'Nivel de riesgo' => get_string('risk_' . ($case->risklevel ?? 'green'), 'local_pceinotifications'),
                'Días de inactividad' => ($case->inactivitydays ?? null) !== null ? $case->inactivitydays : get_string('notavailable', 'local_pceinotifications'),
                'Casos abiertos' => $case->openalerts ?? 0,
                'Tendencia' => !empty($case->trend) ? get_string($case->trend, 'local_pceinotifications') : get_string('notavailable', 'local_pceinotifications'),
                'Seguimiento' => get_string('followup_' . ($case->followupstatus ?? 'none'), 'local_pceinotifications'),
                'Acción sugerida' => $this->get_action_label($case->risklevel ?? 'green'),
            ];
        }
        return $rows;
    }

    public function get_course_summary_export_rows(string $periodtype, string $periodkey): array {
        return $this->get_scope_summary_rows('course', 'Curso', $periodtype, $periodkey);
    }

    public function get_tutor_summary_export_rows(string $periodtype, string $periodkey): array {
        return $this->get_scope_summary_rows('tutor', 'Tutor', $periodtype, $periodkey);
    }

    public function get_cohort_summary_export_rows(string $periodtype, string $periodkey): array {
        return $this->get_scope_summary_rows('cohort', 'Cohorte', $periodtype, $periodkey);
    }

    protected function get_scope_summary_rows(string $scopelevel, string $scopeheader, string $periodtype, string $periodkey): array {
        global $DB;
        $records = $DB->get_records('local_pceinotif_dashagg', [
            'scopelevel' => $scopelevel,
            'periodtype' => $periodtype,
            'periodkey' => $periodkey,
        ], 'scopeid ASC');
        $rows = [];
        foreach ($records as $r) {
            $rows[] = [
                $scopeheader => $this->resolve_scope_name($scopelevel, (int)$r->scopeid),
                'Periodo' => $periodkey,
                'Estudiantes únicos' => (int)$r->totalstudents,
                'Registros estudiante-curso' => $this->count_scope_observations($scopelevel, (int)$r->scopeid, $periodtype, $periodkey),
                'Sin riesgo actual' => (int)$r->activestudents,
                'Estudiantes en riesgo' => (int)$r->studentsatrisk,
                'Riesgo alto' => (int)$r->highriskstudents,
                'Recuperados' => (int)$r->recoveredstudents,
                'Cobertura de seguimiento humano (%)' => round((float)$r->coveragepercent, 1),
                'Semáforo del ámbito' => get_string('risk_' . ($r->institutionalsemaphore ?: 'green'), 'local_pceinotifications'),
            ];
        }
        return $rows;
    }

    protected function resolve_scope_name(string $scopelevel, int $scopeid): string {
        global $DB;
        if ($scopeid === 0 && $scopelevel === 'tutor') {
            return get_string('unassignedtutor', 'local_pceinotifications');
        }
        if ($scopeid === 0 && $scopelevel === 'cohort') {
            return get_string('unassignedcohort', 'local_pceinotifications');
        }
        if ($scopelevel === 'course') {
            $course = $DB->get_record('course', ['id' => $scopeid], 'fullname', IGNORE_MISSING);
            return $course ? format_string($course->fullname) : (string)$scopeid;
        }
        if ($scopelevel === 'tutor') {
            $user = $DB->get_record('user', ['id' => $scopeid], '*', IGNORE_MISSING);
            return $user ? trim(fullname($user)) : (string)$scopeid;
        }
        if ($scopelevel === 'cohort' && $DB->get_manager()->table_exists(new \xmldb_table('cohort'))) {
            $cohort = $DB->get_record('cohort', ['id' => $scopeid], 'name', IGNORE_MISSING);
            return $cohort ? format_string($cohort->name) : (string)$scopeid;
        }
        return (string)$scopeid;
    }

    protected function count_scope_observations(string $scopelevel, int $scopeid, string $periodtype, string $periodkey): int {
        global $DB;
        $where = 'periodtype = :periodtype AND periodkey = :periodkey';
        $params = ['periodtype' => $periodtype, 'periodkey' => $periodkey];
        if ($scopelevel === 'course') {
            $where .= ' AND courseid = :scopeid';
            $params['scopeid'] = $scopeid;
        } else if ($scopelevel === 'tutor') {
            if ($scopeid === 0) {
                $where .= ' AND (tutorid IS NULL OR tutorid = 0)';
            } else {
                $where .= ' AND tutorid = :scopeid';
                $params['scopeid'] = $scopeid;
            }
        } else if ($scopelevel === 'cohort') {
            if ($scopeid === 0) {
                $where .= ' AND (cohortid IS NULL OR cohortid = 0)';
            } else {
                $where .= ' AND cohortid = :scopeid';
                $params['scopeid'] = $scopeid;
            }
        }
        return (int)$DB->count_records_select('local_pceinotif_risk', $where, $params);
    }

    protected function get_action_label(string $risklevel): string {
        switch ($risklevel) {
            case 'red': return get_string('action_immediate', 'local_pceinotifications');
            case 'orange': return get_string('action_priority', 'local_pceinotifications');
            case 'yellow': return get_string('action_preventive', 'local_pceinotifications');
            default: return get_string('action_normal', 'local_pceinotifications');
        }
    }

    protected function translate_period_type(string $periodtype): string {
        switch ($periodtype) {
            case 'monthly': return get_string('monthly', 'local_pceinotifications');
            case 'bimonthly': return get_string('bimonthly', 'local_pceinotifications');
            case 'finalcycle': return get_string('finalcycle', 'local_pceinotifications');
            default: return $periodtype;
        }
    }
}
