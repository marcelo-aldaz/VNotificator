<?php
namespace local_pceinotifications\local\analytics;

use local_pceinotifications\util;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../../locallib.php');

class student_profile_service {
    protected followup_service $followupservice;
    protected source_data_service $sourcedataservice;
    protected risk_engine $riskengine;

    public function __construct() {
        $this->followupservice = new followup_service();
        $this->sourcedataservice = new source_data_service();
        $this->riskengine = new risk_engine();
    }
    public function get_student_course_payload(int $userid, int $courseid): array {
        global $DB;

        $risk = $DB->get_records('local_pceinotif_risk', [
            'userid' => $userid,
            'courseid' => $courseid,
        ], 'timecalculated DESC', '*', 0, 1);
        $riskrecord = $risk ? reset($risk) : null;
        $riskrecord = $this->normalize_riskrecord($riskrecord, $userid, $courseid);

        $notifstats = $this->get_notification_stats($userid, $courseid);
        $priority = $this->resolve_priority($riskrecord ? $riskrecord->risklevel : 'green', $riskrecord ? ($riskrecord->trend ?? null) : null);
        $recommendation = $this->resolve_recommendation($riskrecord ? $riskrecord->risklevel : 'green', $priority);
        $evidence = $this->resolve_evidence_level($riskrecord);
        $progress = $this->get_completion_summary($userid, $courseid);
        $latestfollowup = $this->followupservice->get_latest_followup($courseid, $userid);

        $followupstatus = $this->resolve_followup_status($riskrecord, $latestfollowup);

        return [
            'riskrecord' => $riskrecord,
            'risklabel' => $this->translate_risk($riskrecord ? $riskrecord->risklevel : 'green'),
            'priority' => $priority,
            'prioritylabel' => get_string('priority_' . $priority, 'local_pceinotifications'),
            'recommendation' => $recommendation,
            'followupstatus' => $followupstatus,
            'followuplabel' => get_string('followup_' . $followupstatus, 'local_pceinotifications'),
            'trendlabel' => !empty($riskrecord->trend) ? get_string($riskrecord->trend, 'local_pceinotifications') : get_string('notavailable', 'local_pceinotifications'),
            'evidence' => $evidence,
            'evidencelabel' => get_string('evidence_' . $evidence, 'local_pceinotifications'),
            'notifications' => $notifstats,
            'progress' => $progress,
            'lastsignal' => $this->resolve_last_signal($riskrecord, $notifstats, $latestfollowup),
            'latestfollowup' => $latestfollowup,
        ];
    }



    protected function normalize_riskrecord(?\stdClass $riskrecord, int $userid, int $courseid): ?\stdClass {
        if (!$riskrecord) {
            return null;
        }

        $normalized = clone $riskrecord;
        $storeddays = isset($normalized->inactivitydays) ? (int)$normalized->inactivitydays : 0;
        $needssanitizing = (util::sanitize_inactivity_days($storeddays) === null);

        if ($needssanitizing) {
            $recalculated = null;

            if (!empty($normalized->periodtype) && !empty($normalized->periodkey)) {
                $snapshot = $this->sourcedataservice->get_student_snapshot($userid, $courseid, (string)$normalized->periodtype, (string)$normalized->periodkey);
                $recalculated = $this->riskengine->calculate_inactivity_days(
                    $snapshot['lastactivity'] ?? null,
                    $snapshot['periodstart'] ?? null,
                    $snapshot['enrolstart'] ?? null,
                    (int)($snapshot['activitycount'] ?? 0)
                );
                if (isset($snapshot['lastactivity'])) {
                    $normalized->lastactivity = $snapshot['lastactivity'];
                }
                if (isset($snapshot['activitycount'])) {
                    $normalized->activitycount = (int)$snapshot['activitycount'];
                }
            } else {
                $recalculated = $this->riskengine->calculate_inactivity_days(
                    !empty($normalized->lastactivity) ? (int)$normalized->lastactivity : null,
                    null,
                    null,
                    (int)($normalized->activitycount ?? 0)
                );
            }

            $normalized->inactivitydays = max(0, (int)$recalculated);
        }

        if ((int)$normalized->inactivitydays < 0) {
            $normalized->inactivitydays = 0;
        }

        return $normalized;
    }

    protected function resolve_followup_status(?\stdClass $riskrecord, ?\stdClass $latestfollowup = null): string {
        if ($latestfollowup && !empty($latestfollowup->status)) {
            return (string)$latestfollowup->status;
        }
        if ($riskrecord && !empty($riskrecord->followupstatus)) {
            return (string)$riskrecord->followupstatus;
        }
        return 'none';
    }

    public function get_inactivity_display_value(?\stdClass $riskrecord): ?int {
        if (!$riskrecord || !isset($riskrecord->inactivitydays)) {
            return null;
        }

        return util::sanitize_inactivity_days($riskrecord->inactivitydays);
    }

    protected function get_notification_stats(int $userid, int $courseid): array {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_pceinotif_log'))) {
            return ['sent' => 0, 'success' => 0, 'lastsent' => null];
        }
        $rows = $DB->get_records('local_pceinotif_log', ['userid' => $userid, 'courseid' => $courseid], 'timesent DESC');
        $sent = count($rows);
        $success = 0;
        $lastsent = null;
        foreach ($rows as $row) {
            if ((int)$row->success === 1) {
                $success++;
            }
            if ($lastsent === null && !empty($row->timesent)) {
                $lastsent = (int)$row->timesent;
            }
        }
        return ['sent' => $sent, 'success' => $success, 'lastsent' => $lastsent];
    }

    protected function get_completion_summary(int $userid, int $courseid): array {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        $info = new \completion_info(get_course($courseid));
        if (!$info->is_enabled()) {
            return ['enabled' => false, 'total' => 0, 'done' => 0, 'todo' => 0, 'percent' => 0];
        }
        $modinfo = get_fast_modinfo($courseid, $userid);
        $total = 0;
        $done = 0;
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible || empty($cm->completion)) {
                continue;
            }
            $total++;
            $data = $info->get_data($cm, false, $userid);
            if (!empty($data->completionstate)) {
                $done++;
            }
        }
        $todo = max(0, $total - $done);
        $percent = $total > 0 ? round(($done / $total) * 100, 1) : 0;
        return ['enabled' => true, 'total' => $total, 'done' => $done, 'todo' => $todo, 'percent' => $percent];
    }

    protected function resolve_priority(string $risklevel, ?string $trend): string {
        if ($risklevel === 'red') {
            return 'high';
        }
        if ($risklevel === 'orange' || ($risklevel === 'yellow' && $trend === 'worsening')) {
            return 'medium';
        }
        if ($risklevel === 'yellow' || $risklevel === 'recovered') {
            return 'preventive';
        }
        return 'ordinary';
    }

    protected function resolve_recommendation(string $risklevel, string $priority): string {
        if ($risklevel === 'red') {
            return get_string('recommendation_student_red', 'local_pceinotifications');
        }
        if ($risklevel === 'orange') {
            return get_string('recommendation_student_orange', 'local_pceinotifications');
        }
        if ($risklevel === 'yellow') {
            return get_string('recommendation_student_yellow', 'local_pceinotifications');
        }
        if ($risklevel === 'recovered') {
            return get_string('recommendation_student_recovered', 'local_pceinotifications');
        }
        return get_string('recommendation_student_green', 'local_pceinotifications');
    }

    protected function resolve_evidence_level(?\stdClass $riskrecord): string {
        if (!$riskrecord) {
            return 'low';
        }
        if (!empty($riskrecord->lastactivity) && (int)$riskrecord->activitycount > 0) {
            return 'high';
        }
        if (!empty($riskrecord->lastactivity) || (int)$riskrecord->openalerts > 0 || (int)$riskrecord->pendingnotifications > 0) {
            return 'medium';
        }
        return 'low';
    }

    protected function resolve_last_signal(?\stdClass $riskrecord, array $notifstats, ?\stdClass $latestfollowup = null): ?int {
        $candidates = [];
        if ($riskrecord && !empty($riskrecord->lastactivity)) {
            $candidates[] = (int)$riskrecord->lastactivity;
        }
        if ($riskrecord && !empty($riskrecord->lastintervention)) {
            $candidates[] = (int)$riskrecord->lastintervention;
        }
        if (!empty($notifstats['lastsent'])) {
            $candidates[] = (int)$notifstats['lastsent'];
        }
        if ($latestfollowup && !empty($latestfollowup->timemodified)) {
            $candidates[] = (int)$latestfollowup->timemodified;
        }
        return empty($candidates) ? null : max($candidates);
    }

    protected function translate_risk(string $risklevel): string {
        return get_string('risk_' . $risklevel, 'local_pceinotifications');
    }
}
