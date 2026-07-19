<?php
namespace local_pceinotifications\local\analytics;

defined('MOODLE_INTERNAL') || die();

class source_data_service {
    public function get_students_for_period(string $periodtype, string $periodkey, array $filters = []): array {
        global $DB;

        [$periodstart, $periodend] = $this->resolve_period_dates($periodtype, $periodkey);
        $params = [
            'periodstartue' => $periodstart,
            'periodendue' => $periodend,
            'periodstartenrol' => $periodstart,
            'periodendenrol' => $periodend,
            'periodstartcourse' => $periodstart,
            'periodendcourse' => $periodend,
            'coursecontext' => CONTEXT_COURSE,
            'siteid' => SITEID,
            'studentarchetype' => 'student',
            'studentshortname' => 'student',
        ];
        $where = [
            "u.deleted = 0",
            "u.suspended = 0",
            "c.visible = 1",
            "c.id <> :siteid",
            "ue.status = 0",
            "e.status = 0",
            "(ue.timestart = 0 OR ue.timestart < :periodendue)",
            "(ue.timeend = 0 OR ue.timeend > :periodstartue)",
            "(e.enrolstartdate = 0 OR e.enrolstartdate < :periodendenrol)",
            "(e.enrolenddate = 0 OR e.enrolenddate > :periodstartenrol)",
            "(c.startdate = 0 OR c.startdate < :periodendcourse)",
            "(c.enddate = 0 OR c.enddate > :periodstartcourse)",
            "EXISTS (SELECT 1
                       FROM {role_assignments} ras
                       JOIN {context} ctxs ON ctxs.id = ras.contextid
                       JOIN {role} rs ON rs.id = ras.roleid
                      WHERE ras.userid = u.id
                        AND ctxs.contextlevel = :coursecontext
                        AND ctxs.instanceid = c.id
                        AND (rs.archetype = :studentarchetype OR rs.shortname = :studentshortname))",
        ];
        if (!empty($filters['courseid'])) {
            $where[] = "c.id = :courseid";
            $params['courseid'] = (int)$filters['courseid'];
        }
        if (!empty($filters['userid'])) {
            $where[] = "u.id = :userid";
            $params['userid'] = (int)$filters['userid'];
        }

        $sql = "SELECT MIN(ue.id) AS rowid, u.id AS userid, c.id AS courseid
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                 WHERE " . implode(' AND ', $where) . "
              GROUP BY u.id, c.id
              ORDER BY c.id, u.id";

        $records = [];
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $record) {
            $records[] = (object)[
                'userid' => (int)$record->userid,
                'courseid' => (int)$record->courseid,
            ];
        }
        $rs->close();
        return $records;
    }

    public function get_student_snapshot(int $userid, int $courseid, string $periodtype, string $periodkey): array {
        [$periodstart, $periodend] = $this->resolve_period_dates($periodtype, $periodkey);
        $calculationtime = min(time(), $periodend - 1);
        $alerts = $this->get_alert_counts($userid, $courseid, $periodstart, $periodend);
        $notifications = $this->get_notification_counts($userid, $courseid, $periodstart, $periodend);
        $followup = $this->get_followup_snapshot($userid, $courseid, $periodtype, $periodkey);
        $activitycount = $this->get_activity_count($userid, $courseid, $periodtype, $periodkey);
        $cohortid = $this->resolve_student_cohort($userid, $courseid);
        $tutorid = $this->resolve_tutor_for_student($userid, $courseid, $periodstart, $periodend);

        return [
            'userid' => $userid,
            'courseid' => $courseid,
            'cohortid' => $cohortid,
            'tutorid' => $tutorid,
            'periodtype' => $periodtype,
            'periodkey' => $periodkey,
            'periodstart' => $periodstart,
            'periodend' => $periodend,
            'calculationtime' => $calculationtime,
            'enrolstart' => $this->get_enrolment_start($userid, $courseid),
            'lastactivity' => $this->get_last_activity($userid, $courseid, $calculationtime),
            'activitycount' => $activitycount,
            'openalerts' => $alerts['openalerts'],
            'closedalerts' => $alerts['closedalerts'],
            'pendingnotifications' => $notifications['pendingnotifications'],
            'attendednotifications' => $notifications['attendednotifications'],
            'interventionscount' => $followup['interventionscount'],
            'lastintervention' => $followup['lastintervention'],
            'followupstatus' => $followup['followupstatus'],
            'evidencelevel' => $this->resolve_evidence_level($activitycount, $notifications, $followup),
        ];
    }

    public function get_last_activity(int $userid, int $courseid, ?int $asof = null): ?int {
        global $DB;

        $asof = $asof ?? time();
        $r = $DB->get_record('user_lastaccess', ['userid' => $userid, 'courseid' => $courseid], 'timeaccess', IGNORE_MISSING);
        if ($r && !empty($r->timeaccess) && (int)$r->timeaccess <= $asof) {
            return (int)$r->timeaccess;
        }

        if (!$DB->get_manager()->table_exists(new \xmldb_table('logstore_standard_log'))) {
            return null;
        }

        $sql = "SELECT MAX(timecreated)
                 FROM {logstore_standard_log}
                 WHERE userid = :userid
                   AND courseid = :courseid
                   AND timecreated <= :asof";
        $value = $DB->get_field_sql($sql, ['userid' => $userid, 'courseid' => $courseid, 'asof' => $asof]);
        return $value ? (int)$value : null;
    }

    public function get_activity_count(int $userid, int $courseid, string $periodtype, string $periodkey): int {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('logstore_standard_log'))) {
            return 0;
        }
        [$start, $end] = $this->resolve_period_dates($periodtype, $periodkey);
        $sql = "SELECT COUNT(1)
                  FROM {logstore_standard_log}
                 WHERE userid = :userid
                   AND courseid = :courseid
                   AND timecreated >= :starttime
                   AND timecreated < :endtime";
        return (int)$DB->count_records_sql($sql, [
            'userid' => $userid,
            'courseid' => $courseid,
            'starttime' => $start,
            'endtime' => $end,
        ]);
    }

    public function get_alert_counts(int $userid, int $courseid, int $periodstart, int $periodend): array {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_pceinotif_novelty'))) {
            return ['openalerts' => 0, 'closedalerts' => 0];
        }

        $sql = "SELECT
                       SUM(CASE
                               WHEN timecreated < :opencreatedend
                                AND (status <> 'closed' OR (timeclosed > 0 AND timeclosed >= :openclosedend))
                               THEN 1 ELSE 0
                           END) AS openalerts,
                       SUM(CASE WHEN status = 'closed' AND timeclosed >= :closestart AND timeclosed < :closeend THEN 1 ELSE 0 END) AS closedalerts
                  FROM {local_pceinotif_novelty}
                 WHERE userid = :userid
                   AND courseid = :courseid
                   AND timecreated < :createdend";
        $row = $DB->get_record_sql($sql, [
            'opencreatedend' => $periodend,
            'openclosedend' => $periodend,
            'closestart' => $periodstart,
            'closeend' => $periodend,
            'createdend' => $periodend,
            'userid' => $userid,
            'courseid' => $courseid,
        ]);
        return [
            'openalerts' => (int)($row->openalerts ?? 0),
            'closedalerts' => (int)($row->closedalerts ?? 0),
        ];
    }

    public function get_notification_counts(int $userid, int $courseid, int $periodstart, int $periodend): array {
        $stats = $this->get_log_notification_stats($userid, $courseid, $periodstart, $periodend);
        return [
            // Legacy storage names retained for database compatibility.
            'pendingnotifications' => $stats['pending'],
            'attendednotifications' => $stats['attended'],
        ];
    }

    public function get_followup_snapshot(int $userid, int $courseid, string $periodtype, string $periodkey): array {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_pceinotif_followup'))) {
            return ['interventionscount' => 0, 'lastintervention' => null, 'followupstatus' => 'none'];
        }

        [$periodstart, $periodend] = $this->resolve_period_dates($periodtype, $periodkey);
        $select = 'userid = :userid AND courseid = :courseid AND timecreated >= :periodstart AND timecreated < :periodend';
        $params = [
            'userid' => $userid,
            'courseid' => $courseid,
            'periodstart' => $periodstart,
            'periodend' => $periodend,
        ];
        $total = $DB->count_records_select('local_pceinotif_followup', $select, $params);
        $records = $DB->get_records_select('local_pceinotif_followup', $select, $params, 'timemodified DESC, id DESC', '*', 0, 1);
        $latest = $records ? reset($records) : null;

        return [
            'interventionscount' => (int)$total,
            'lastintervention' => $latest ? (int)$latest->timemodified : null,
            'followupstatus' => $latest ? (string)$latest->status : 'none',
        ];
    }

    public function get_previous_risk_record(int $userid, int $courseid, ?int $cohortid, ?int $tutorid, string $periodtype, string $periodkey): ?\stdClass {
        global $DB;
        $sql = "SELECT *
                  FROM {local_pceinotif_risk}
                 WHERE userid = :userid
                   AND courseid = :courseid
                   AND periodtype = :periodtype
                   AND periodkey < :periodkey
              ORDER BY periodkey DESC, timecalculated DESC";
        return $DB->get_record_sql($sql, [
            'userid' => $userid,
            'courseid' => $courseid,
            'periodtype' => $periodtype,
            'periodkey' => $periodkey,
        ]) ?: null;
    }

    protected function resolve_student_cohort(int $userid, int $courseid = 0): ?int {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('cohort_members'))) {
            return null;
        }

        if ($courseid > 0) {
            $sql = "SELECT DISTINCT cm.cohortid
                      FROM {cohort_members} cm
                      JOIN {enrol} e ON e.enrol = 'cohort' AND e.customint1 = cm.cohortid
                      JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = cm.userid
                     WHERE cm.userid = :userid
                       AND e.courseid = :courseid
                       AND e.status = 0
                       AND ue.status = 0";
            $coursecohorts = array_values(array_unique(array_map('intval', $DB->get_fieldset_sql($sql, [
                'userid' => $userid,
                'courseid' => $courseid,
            ]))));
            if (count($coursecohorts) === 1) {
                return $coursecohorts[0];
            }
        }

        $cohorts = array_values(array_unique(array_map('intval', $DB->get_fieldset_select(
            'cohort_members',
            'cohortid',
            'userid = :userid',
            ['userid' => $userid]
        ))));
        return count($cohorts) === 1 ? $cohorts[0] : null;
    }

    protected function resolve_tutor_for_student(int $userid, int $courseid, int $periodstart, int $periodend): ?int {
        global $DB;

        if ($DB->get_manager()->table_exists(new \xmldb_table('local_pceinotif_followup'))) {
            $records = $DB->get_records_select(
                'local_pceinotif_followup',
                'userid = :userid AND courseid = :courseid AND timecreated >= :periodstart AND timecreated < :periodend',
                ['userid' => $userid, 'courseid' => $courseid, 'periodstart' => $periodstart, 'periodend' => $periodend],
                'timemodified DESC, id DESC',
                'teacherid',
                0,
                1
            );
            if ($records) {
                $latest = reset($records);
                if (!empty($latest->teacherid)) {
                    return (int)$latest->teacherid;
                }
            }
        }

        $sql = "SELECT DISTINCT ra.userid
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE ctx.contextlevel = :contextlevel
                   AND ctx.instanceid = :courseid
                   AND (r.archetype = 'editingteacher' OR r.shortname IN ('editingteacher', 'teacher'))";
        $tutors = array_values(array_unique(array_map('intval', $DB->get_fieldset_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'courseid' => $courseid,
        ]))));
        return count($tutors) === 1 ? $tutors[0] : null;
    }

    protected function get_enrolment_start(int $userid, int $courseid): ?int {
        global $DB;
        $sql = "SELECT MIN(CASE
                            WHEN ue.timestart IS NOT NULL AND ue.timestart > 0 THEN ue.timestart
                            WHEN ue.timecreated IS NOT NULL AND ue.timecreated > 0 THEN ue.timecreated
                            WHEN ue.timemodified IS NOT NULL AND ue.timemodified > 0 THEN ue.timemodified
                            ELSE NULL
                          END)
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :userid
                   AND e.courseid = :courseid";
        $value = $DB->get_field_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);
        return (!empty($value) && (int)$value > 0) ? (int)$value : null;
    }

    protected function get_log_notification_stats(int $userid, int $courseid, int $periodstart, int $periodend): array {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_pceinotif_log'))) {
            return ['total' => 0, 'pending' => 0, 'attended' => 0, 'lastsent' => null];
        }
        $sql = "SELECT COUNT(1) AS total,
                       SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) AS pending,
                       SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) AS attended,
                       MAX(timesent) AS lastsent
                 FROM {local_pceinotif_log}
                 WHERE userid = :userid
                   AND courseid = :courseid
                   AND timesent >= :periodstart
                   AND timesent < :periodend";
        $row = $DB->get_record_sql($sql, [
            'userid' => $userid,
            'courseid' => $courseid,
            'periodstart' => $periodstart,
            'periodend' => $periodend,
        ]);
        return [
            'total' => (int)($row->total ?? 0),
            'pending' => (int)($row->pending ?? 0),
            'attended' => (int)($row->attended ?? 0),
            'lastsent' => !empty($row->lastsent) ? (int)$row->lastsent : null,
        ];
    }

    protected function resolve_evidence_level(int $activitycount, array $notifications, array $followup): string {
        if ($activitycount > 0 || $followup['interventionscount'] > 0) {
            return 'high';
        }
        if ($notifications['attendednotifications'] > 0 || $notifications['pendingnotifications'] > 0) {
            return 'medium';
        }
        return 'low';
    }

    public function resolve_period_dates(string $periodtype, string $periodkey): array {
        if ($periodtype === 'monthly') {
            $start = strtotime($periodkey . '-01 00:00:00');
            $end = strtotime('+1 month', $start);
            return [$start, $end];
        }

        if ($periodtype === 'bimonthly' && preg_match('/^(\d{4})-B(\d+)$/', $periodkey, $m)) {
            $year = (int)$m[1];
            $block = (int)$m[2];
            $month = (($block - 1) * 2) + 1;
            $start = strtotime(sprintf('%04d-%02d-01 00:00:00', $year, $month));
            $end = strtotime('+2 months', $start);
            return [$start, $end];
        }

        if ($periodtype === 'finalcycle' && preg_match('/^(\d{4})-FC$/', $periodkey, $m)) {
            $year = (int)$m[1];
            $start = strtotime($year . '-01-01 00:00:00');
            $end = strtotime(($year + 1) . '-01-01 00:00:00');
            return [$start, $end];
        }

        $start = strtotime(date('Y-m-01 00:00:00'));
        $end = strtotime('+1 month', $start);
        return [$start, $end];
    }
}
