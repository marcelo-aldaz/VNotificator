<?php
namespace local_pceinotifications\local\analytics;

defined('MOODLE_INTERNAL') || die();

class aggregate_engine {
    /**
     * Collapse course-level observations into one institutional observation per user.
     * The highest risk level is retained while operational counters are accumulated.
     */
    public function collapse_by_user(array $riskrecords): array {
        $collapsed = [];
        $riskrank = ['green' => 0, 'recovered' => 1, 'yellow' => 2, 'orange' => 3, 'red' => 4];
        $followuprank = ['none' => 0, 'pending' => 1, 'inprogress' => 2, 'attended' => 3];

        foreach ($riskrecords as $record) {
            $userid = (int)$record->userid;
            if (!isset($collapsed[$userid])) {
                $collapsed[$userid] = clone $record;
                foreach (['openalerts', 'closedalerts', 'pendingnotifications', 'attendednotifications', 'interventionscount', 'activitycount'] as $field) {
                    $collapsed[$userid]->$field = 0;
                }
                $collapsed[$userid]->lastactivity = null;
                $collapsed[$userid]->lastintervention = null;
                $collapsed[$userid]->followupstatus = 'none';
            }

            $target = $collapsed[$userid];
            $currentrank = $riskrank[$target->risklevel ?? 'green'] ?? 0;
            $recordrank = $riskrank[$record->risklevel ?? 'green'] ?? 0;
            if ($recordrank > $currentrank || ($recordrank === $currentrank && (int)$record->inactivitydays > (int)$target->inactivitydays)) {
                $target->risklevel = $record->risklevel;
                $target->semaphore = $record->semaphore;
                $target->inactivitydays = $record->inactivitydays;
                $target->trend = $record->trend;
                $target->courseid = $record->courseid;
            }

            foreach (['openalerts', 'closedalerts', 'pendingnotifications', 'attendednotifications', 'interventionscount', 'activitycount'] as $field) {
                $target->$field += (int)($record->$field ?? 0);
            }
            $target->lastactivity = max((int)($target->lastactivity ?? 0), (int)($record->lastactivity ?? 0)) ?: null;
            $target->lastintervention = max((int)($target->lastintervention ?? 0), (int)($record->lastintervention ?? 0)) ?: null;

            $targetstatus = (string)($target->followupstatus ?? 'none');
            $recordstatus = (string)($record->followupstatus ?? 'none');
            if (($followuprank[$recordstatus] ?? 0) > ($followuprank[$targetstatus] ?? 0)) {
                $target->followupstatus = $recordstatus;
            }
        }

        return array_values($collapsed);
    }

    public function calculate_distribution(array $riskrecords): array {
        $d = ['green' => 0, 'yellow' => 0, 'orange' => 0, 'red' => 0, 'recovered' => 0];
        foreach ($riskrecords as $record) {
            if (isset($d[$record->risklevel])) $d[$record->risklevel]++;
        }
        return $d;
    }
    public function calculate_students_at_risk(array $distribution): int { return $distribution['yellow'] + $distribution['orange'] + $distribution['red']; }
    public function calculate_active_students(array $riskrecords, array $thresholds = []): int {
        $count = 0;
        foreach ($riskrecords as $record) {
            if (in_array((string)$record->risklevel, ['green', 'recovered'], true)) {
                $count++;
            }
        }
        return $count;
    }
    public function calculate_coverage_percent(array $riskrecords): float {
        $total = count($riskrecords); if (!$total) return 0;
        $covered = 0; foreach ($riskrecords as $record) { if ((int)$record->interventionscount > 0 || (!empty($record->followupstatus) && $record->followupstatus !== 'none')) $covered++; }
        return round(($covered / $total) * 100, 2);
    }
    public function resolve_institutional_semaphore(int $totalstudents, int $studentsatrisk, array $thresholds): string {
        if ($totalstudents <= 0) return 'green';
        $riskpercent = ($studentsatrisk / $totalstudents) * 100;
        if ($riskpercent >= $thresholds['red_risk_min_percent']) return 'red';
        if ($riskpercent <= $thresholds['green_risk_max_percent']) return 'green';
        if ($riskpercent <= $thresholds['yellow_risk_max_percent']) return 'yellow';
        return 'orange';
    }
}
