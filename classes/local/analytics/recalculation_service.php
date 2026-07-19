<?php
namespace local_pceinotifications\local\analytics;

defined('MOODLE_INTERNAL') || die();

class recalculation_service {
    protected threshold_service $thresholdservice;
    protected source_data_service $sourceservice;
    protected risk_engine $riskengine;
    protected aggregate_engine $aggregateengine;

    public function __construct() {
        $this->thresholdservice = new threshold_service();
        $this->sourceservice = new source_data_service();
        $this->riskengine = new risk_engine();
        $this->aggregateengine = new aggregate_engine();
    }

    protected function table_exists(string $tablename): bool {
        global $DB;
        return $DB->get_manager()->table_exists(new \xmldb_table($tablename));
    }

    public function run_period_recalculation(string $periodtype, string $periodkey, array $filters = [], ?int $executedby = null): array {
        global $DB;

        if (!$this->table_exists('local_pceinotif_risk') || !$this->table_exists('local_pceinotif_dashagg') || !$this->table_exists('local_pceinotif_thresholds')) {
            throw new \moodle_exception('Analytical tables for V9 are missing. Please complete the plugin database upgrade first.');
        }

        $transaction = $DB->start_delegated_transaction();
        $runid = 0;
        $hasrunstable = $this->table_exists('local_pceinotif_runs');

        try {
            if ($hasrunstable) {
                $runid = $this->register_run([
                    'periodtype' => $periodtype,
                    'periodkey' => $periodkey,
                    'scopelevel' => 'institution',
                    'scopeid' => 0,
                    'recordsprocessed' => 0,
                    'status' => 'running',
                    'errormessage' => null,
                    'startedat' => time(),
                    'finishedat' => null,
                    'executedby' => $executedby,
                    'engineversion' => 'V9.4.2',
                ]);
            }

            $thresholds = $this->thresholdservice->get_active_thresholds($periodtype);
            $riskcount = $this->process_risk_layer($periodtype, $periodkey, $filters, $thresholds);
            $aggregatecount = $this->process_aggregate_layer($periodtype, $periodkey, $thresholds);

            if ($hasrunstable && $runid) {
                $this->mark_run_success($runid, $riskcount + $aggregatecount);
            }

            $transaction->allow_commit();
            dashboard_data_service::clear_session_cache();
            return ['status' => 'success', 'riskrecords' => $riskcount, 'aggregaterecords' => $aggregatecount, 'periodtype' => $periodtype, 'periodkey' => $periodkey];
        } catch (\Throwable $e) {
            if ($hasrunstable && $runid) {
                $this->mark_run_error($runid, $e->getMessage());
            }
            $transaction->rollback($e);
        }
    }

    public function process_risk_layer(string $periodtype, string $periodkey, array $filters, array $thresholds): int {
        global $DB;
        // Rebuild the selected period so users/enrolments that are no longer
        // eligible cannot remain as stale analytical records.
        $deletewhere = 'periodtype = :periodtype AND periodkey = :periodkey';
        $deleteparams = ['periodtype' => $periodtype, 'periodkey' => $periodkey];
        if (!empty($filters['courseid'])) {
            $deletewhere .= ' AND courseid = :courseid';
            $deleteparams['courseid'] = (int)$filters['courseid'];
        }
        if (!empty($filters['userid'])) {
            $deletewhere .= ' AND userid = :userid';
            $deleteparams['userid'] = (int)$filters['userid'];
        }
        $DB->delete_records_select('local_pceinotif_risk', $deletewhere, $deleteparams);
        $students = $this->sourceservice->get_students_for_period($periodtype, $periodkey, $filters);
        $processed = 0;
        foreach ($students as $student) {
            $snapshot = $this->sourceservice->get_student_snapshot((int)$student->userid, (int)$student->courseid, $periodtype, $periodkey);
            $previous = $this->sourceservice->get_previous_risk_record((int)$snapshot['userid'], (int)$snapshot['courseid'], $snapshot['cohortid'], $snapshot['tutorid'], $periodtype, $periodkey);
            $record = $this->riskengine->calculate_student_risk($snapshot, $thresholds, $previous);
            $this->upsert_risk_record($record);
            $processed++;
        }
        return $processed;
    }

    public function process_aggregate_layer(string $periodtype, string $periodkey, array $thresholds): int {
        global $DB;
        $DB->delete_records('local_pceinotif_dashagg', ['periodtype' => $periodtype, 'periodkey' => $periodkey]);

        $processed = 0;
        $this->upsert_aggregate_record($this->build_scope_record('institution', 0, $periodtype, $periodkey, $thresholds));
        $processed++;

        $courses = $DB->get_records_sql("SELECT DISTINCT courseid AS scopeid FROM {local_pceinotif_risk} WHERE periodtype = :periodtype AND periodkey = :periodkey AND courseid > 0 ORDER BY courseid ASC", ['periodtype' => $periodtype, 'periodkey' => $periodkey]);
        foreach ($courses as $scope) {
            $this->upsert_aggregate_record($this->build_scope_record('course', (int)$scope->scopeid, $periodtype, $periodkey, $thresholds));
            $processed++;
        }

        $tutors = $DB->get_records_sql("SELECT DISTINCT tutorid AS scopeid FROM {local_pceinotif_risk} WHERE periodtype = :periodtype AND periodkey = :periodkey AND tutorid IS NOT NULL AND tutorid > 0 ORDER BY tutorid ASC", ['periodtype' => $periodtype, 'periodkey' => $periodkey]);
        foreach ($tutors as $scope) {
            $this->upsert_aggregate_record($this->build_scope_record('tutor', (int)$scope->scopeid, $periodtype, $periodkey, $thresholds));
            $processed++;
        }
        if ($DB->record_exists_select('local_pceinotif_risk', 'periodtype = :periodtype AND periodkey = :periodkey AND (tutorid IS NULL OR tutorid = 0)', ['periodtype' => $periodtype, 'periodkey' => $periodkey])) {
            $this->upsert_aggregate_record($this->build_scope_record('tutor', 0, $periodtype, $periodkey, $thresholds));
            $processed++;
        }

        $cohorts = $DB->get_records_sql("SELECT DISTINCT cohortid AS scopeid FROM {local_pceinotif_risk} WHERE periodtype = :periodtype AND periodkey = :periodkey AND cohortid IS NOT NULL AND cohortid > 0 ORDER BY cohortid ASC", ['periodtype' => $periodtype, 'periodkey' => $periodkey]);
        foreach ($cohorts as $scope) {
            $this->upsert_aggregate_record($this->build_scope_record('cohort', (int)$scope->scopeid, $periodtype, $periodkey, $thresholds));
            $processed++;
        }
        if ($DB->record_exists_select('local_pceinotif_risk', 'periodtype = :periodtype AND periodkey = :periodkey AND (cohortid IS NULL OR cohortid = 0)', ['periodtype' => $periodtype, 'periodkey' => $periodkey])) {
            $this->upsert_aggregate_record($this->build_scope_record('cohort', 0, $periodtype, $periodkey, $thresholds));
            $processed++;
        }

        return $processed;
    }

    protected function build_scope_record(string $scopelevel, int $scopeid, string $periodtype, string $periodkey, array $thresholds): array {
        global $DB;
        [$where, $params] = $this->get_scope_where_params($scopelevel, $scopeid, $periodtype, $periodkey);
        $riskrecords = $DB->get_records_select('local_pceinotif_risk', $where, $params);
        if ($scopelevel !== 'course') {
            $riskrecords = $this->aggregateengine->collapse_by_user($riskrecords);
        }

        $d = $this->aggregateengine->calculate_distribution($riskrecords);
        $total = count($riskrecords);
        $atrisk = $this->aggregateengine->calculate_students_at_risk($d);
        $active = $this->aggregateengine->calculate_active_students($riskrecords, $thresholds);
        $coverage = $this->aggregateengine->calculate_coverage_percent($riskrecords);
        $open = 0; $closed = 0;
        foreach ($riskrecords as $r) { $open += (int)$r->openalerts; $closed += (int)$r->closedalerts; }

        return [
            'scopelevel' => $scopelevel,
            'scopeid' => $scopeid,
            'periodtype' => $periodtype,
            'periodkey' => $periodkey,
            'totalstudents' => $total,
            'activestudents' => $active,
            'studentsatrisk' => $atrisk,
            'highriskstudents' => $d['red'],
            'recoveredstudents' => $d['recovered'],
            'openalerts' => $open,
            'closedalerts' => $closed,
            'green_count' => $d['green'],
            'yellow_count' => $d['yellow'],
            'orange_count' => $d['orange'],
            'red_count' => $d['red'],
            'recovered_count' => $d['recovered'],
            'coveragepercent' => $coverage,
            'interventioneffectiveness' => 0,
            'institutionalsemaphore' => $this->aggregateengine->resolve_institutional_semaphore($total, $atrisk, $thresholds),
            'trenddirection' => $this->resolve_scope_trend(
                $scopelevel,
                $scopeid,
                $periodtype,
                $periodkey,
                $this->aggregateengine->resolve_institutional_semaphore($total, $atrisk, $thresholds),
                $d['red'],
                $total
            ),
            'timecalculated' => time(),
            'sourceversion' => 'V9.4.2',
        ];
    }

    protected function get_scope_where_params(string $scopelevel, int $scopeid, string $periodtype, string $periodkey): array {
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
        return [$where, $params];
    }

    protected function resolve_scope_trend(
        string $scopelevel,
        int $scopeid,
        string $periodtype,
        string $periodkey,
        string $currentsemaphore,
        int $currenthighrisk,
        int $currenttotal
    ): ?string {
        global $DB;
        $sql = "SELECT *
                  FROM {local_pceinotif_dashagg}
                 WHERE scopelevel = :scopelevel
                   AND scopeid = :scopeid
                   AND periodtype = :periodtype
                   AND periodkey < :periodkey
                   AND sourceversion = :sourceversion
              ORDER BY periodkey DESC, timecalculated DESC";
        $previous = $DB->get_record_sql($sql, [
            'scopelevel' => $scopelevel,
            'scopeid' => $scopeid,
            'periodtype' => $periodtype,
            'periodkey' => $periodkey,
            'sourceversion' => 'V9.4.2',
        ]);
        if (!$previous) {
            return null;
        }

        $rank = ['green' => 1, 'yellow' => 2, 'orange' => 3, 'red' => 4];
        $current = $rank[$currentsemaphore] ?? 1;
        $prior = $rank[$previous->institutionalsemaphore ?? 'green'] ?? 1;
        if ($current < $prior) {
            return 'improving';
        }
        if ($current > $prior) {
            return 'worsening';
        }

        // When both periods remain in the same semaphore band, retain useful
        // directionality by comparing the proportion of unique high-risk
        // students. A five-point margin avoids reporting minor rounding noise
        // as institutional change.
        $previoustotal = (int)($previous->totalstudents ?? 0);
        if ($currenttotal > 0 && $previoustotal > 0) {
            $currentrate = ($currenthighrisk / $currenttotal) * 100;
            $previousrate = ((int)($previous->highriskstudents ?? 0) / $previoustotal) * 100;
            if ($currentrate <= $previousrate - 5.0) {
                return 'improving';
            }
            if ($currentrate >= $previousrate + 5.0) {
                return 'worsening';
            }
        }
        return 'stable';
    }

    protected function upsert_risk_record(array $record): void {
        global $DB;
        $existing = $DB->get_record('local_pceinotif_risk', ['userid' => $record['userid'], 'courseid' => $record['courseid'], 'periodtype' => $record['periodtype'], 'periodkey' => $record['periodkey']]);
        if ($existing) { $record['id'] = $existing->id; $DB->update_record('local_pceinotif_risk', (object)$record); }
        else { $DB->insert_record('local_pceinotif_risk', (object)$record); }
    }

    protected function upsert_aggregate_record(array $record): void {
        global $DB;
        $existing = $DB->get_record('local_pceinotif_dashagg', ['scopelevel' => $record['scopelevel'], 'scopeid' => $record['scopeid'], 'periodtype' => $record['periodtype'], 'periodkey' => $record['periodkey']]);
        if ($existing) { $record['id'] = $existing->id; $DB->update_record('local_pceinotif_dashagg', (object)$record); }
        else { $DB->insert_record('local_pceinotif_dashagg', (object)$record); }
    }

    protected function register_run(array $runinfo): int { global $DB; return (int)$DB->insert_record('local_pceinotif_runs', (object)$runinfo); }
    protected function mark_run_success(int $runid, int $recordsprocessed): void { global $DB; $DB->update_record('local_pceinotif_runs', (object)['id' => $runid, 'recordsprocessed' => $recordsprocessed, 'status' => 'success', 'finishedat' => time()]); }
    protected function mark_run_error(int $runid, string $errormessage): void { global $DB; $DB->update_record('local_pceinotif_runs', (object)['id' => $runid, 'status' => 'error', 'errormessage' => $errormessage, 'finishedat' => time()]); }
}
