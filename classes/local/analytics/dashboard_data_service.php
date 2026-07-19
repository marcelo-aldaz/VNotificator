<?php
namespace local_pceinotifications\local\analytics;

defined('MOODLE_INTERNAL') || die();

class dashboard_data_service {
    protected const SESSION_CACHE_KEY = 'local_pceinotif_dashcache';
    protected const CACHE_TTL = 90;

    protected function table_exists(string $tablename): bool {
        global $DB;
        return $DB->get_manager()->table_exists(new \xmldb_table($tablename));
    }


    protected function get_cache_key(string $periodtype, string $periodkey, array $filters, int $userid, bool $loadcriticalcases): string {
        ksort($filters);
        return sha1(json_encode([
            'periodtype' => $periodtype,
            'periodkey' => $periodkey,
            'filters' => $filters,
            'userid' => $userid,
            'criticalcases' => (int)$loadcriticalcases,
        ]));
    }

    protected function get_cached_payload(string $cachekey): ?array {
        if (empty($_SESSION[self::SESSION_CACHE_KEY][$cachekey])) {
            return null;
        }
        $entry = $_SESSION[self::SESSION_CACHE_KEY][$cachekey];
        if (empty($entry['expires']) || $entry['expires'] < time() || empty($entry['payload'])) {
            unset($_SESSION[self::SESSION_CACHE_KEY][$cachekey]);
            return null;
        }
        return $entry['payload'];
    }

    protected function set_cached_payload(string $cachekey, array $payload): void {
        $_SESSION[self::SESSION_CACHE_KEY][$cachekey] = [
            'expires' => time() + self::CACHE_TTL,
            'payload' => $payload,
        ];
    }

    public static function clear_session_cache(): void {
        unset($_SESSION[self::SESSION_CACHE_KEY]);
    }

    public function get_dashboard_payload(string $periodtype, string $periodkey, array $filters = [], int $userid = 0, bool $loadcriticalcases = false): array {
        global $DB;

        $cachekey = $this->get_cache_key($periodtype, $periodkey, $filters, $userid, $loadcriticalcases);
        if ($cached = $this->get_cached_payload($cachekey)) {
            return $cached;
        }

        if (!$this->table_exists('local_pceinotif_dashagg') || !$this->table_exists('local_pceinotif_risk')) {
            $payload = $this->get_empty_payload($periodtype, $periodkey, true);
            $this->set_cached_payload($cachekey, $payload);
            return $payload;
        }

        $aggfields = 'totalstudents, activestudents, studentsatrisk, highriskstudents, recoveredstudents, openalerts, '
            . 'coveragepercent, institutionalsemaphore, trenddirection, green_count, yellow_count, orange_count, red_count, recovered_count, timecalculated, sourceversion';
        $agg = $DB->get_record('local_pceinotif_dashagg', [
            'scopelevel' => 'institution',
            'scopeid' => 0,
            'periodtype' => $periodtype,
            'periodkey' => $periodkey,
        ], $aggfields, IGNORE_MISSING);

        if (!$agg) {
            $payload = $this->get_empty_payload($periodtype, $periodkey, false);
            $this->set_cached_payload($cachekey, $payload);
            return $payload;
        }

        $criticalcases = [];
        $criticalcaselimit = 10;
        $studentcourseobservations = $DB->count_records('local_pceinotif_risk', [
            'periodtype' => $periodtype,
            'periodkey' => $periodkey,
        ]);
        if ($loadcriticalcases) {
            $params = ['periodtype' => $periodtype, 'periodkey' => $periodkey];
            $sql = "SELECT r.id AS rowid, r.userid, r.courseid, r.risklevel, r.inactivitydays, r.openalerts, r.trend, r.followupstatus,
                           u.firstname, u.lastname, u.middlename, u.lastnamephonetic, u.firstnamephonetic, u.alternatename,
                           c.fullname AS coursefullname
                      FROM {local_pceinotif_risk} r
                      JOIN {user} u ON u.id = r.userid
                      JOIN {course} c ON c.id = r.courseid
                     WHERE r.periodtype = :periodtype
                       AND r.periodkey = :periodkey
                       AND r.risklevel = 'red'
                  ORDER BY CASE r.risklevel WHEN 'red' THEN 1 WHEN 'orange' THEN 2 ELSE 3 END,
                           r.inactivitydays DESC,
                           r.openalerts DESC,
                           r.userid ASC";

            $criticalcases = $DB->get_records_sql($sql, $params, 0, $criticalcaselimit);
        }

        $payload = [
            'kpis' => [
                'totalstudents' => (int)($agg->totalstudents ?? 0),
                'activestudents' => (int)($agg->activestudents ?? 0),
                'studentsatrisk' => (int)($agg->studentsatrisk ?? 0),
                'highriskstudents' => (int)($agg->highriskstudents ?? 0),
                'recoveredstudents' => (int)($agg->recoveredstudents ?? 0),
                'openalerts' => (int)($agg->openalerts ?? 0),
                'coveragepercent' => (float)($agg->coveragepercent ?? 0),
                'studentcourseobservations' => (int)$studentcourseobservations,
            ],
            'semaphore' => [
                'value' => $agg->institutionalsemaphore ?? 'green',
                'trenddirection' => $agg->trenddirection ?? null,
            ],
            'distribution' => [
                'green' => (int)($agg->green_count ?? 0),
                'yellow' => (int)($agg->yellow_count ?? 0),
                'orange' => (int)($agg->orange_count ?? 0),
                'red' => (int)($agg->red_count ?? 0),
                'recovered' => (int)($agg->recovered_count ?? 0),
            ],
            'criticalcases' => $criticalcases,
            'metadata' => [
                'periodtype' => $periodtype,
                'periodkey' => $periodkey,
                'generatedat' => time(),
                'tablesmissing' => false,
                'criticalcaselimit' => $criticalcaselimit,
                'aggregatedat' => (int)($agg->timecalculated ?? 0),
                'criticalcasesloaded' => $loadcriticalcases,
                'unitofanalysis' => 'unique_students',
                'sourceversion' => (string)($agg->sourceversion ?? ''),
                'requiresrecalculation' => (string)($agg->sourceversion ?? '') !== 'V9.4.2',
            ],
        ];

        $this->set_cached_payload($cachekey, $payload);
        return $payload;
    }

    protected function get_empty_payload(string $periodtype, string $periodkey, bool $tablesmissing): array {
        $criticalcaselimit = 10;
        return [
            'kpis' => [
                'totalstudents' => 0,
                'activestudents' => 0,
                'studentsatrisk' => 0,
                'highriskstudents' => 0,
                'recoveredstudents' => 0,
                'openalerts' => 0,
                'coveragepercent' => 0,
                'studentcourseobservations' => 0,
            ],
            'semaphore' => [
                'value' => 'green',
                'trenddirection' => null,
            ],
            'distribution' => [
                'green' => 0,
                'yellow' => 0,
                'orange' => 0,
                'red' => 0,
                'recovered' => 0,
            ],
            'criticalcases' => [],
            'metadata' => [
                'periodtype' => $periodtype,
                'periodkey' => $periodkey,
                'generatedat' => time(),
                'tablesmissing' => $tablesmissing,
                'criticalcaselimit' => $criticalcaselimit,
                'aggregatedat' => 0,
                'criticalcasesloaded' => false,
                'unitofanalysis' => 'unique_students',
                'sourceversion' => '',
                'requiresrecalculation' => false,
            ],
        ];
    }
}
