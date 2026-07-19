<?php
namespace local_pceinotifications\local\analytics;

defined('MOODLE_INTERNAL') || die();

class risk_engine {
    public function calculate_student_risk(array $snapshot, array $thresholds, ?\stdClass $previousrecord = null): array {
        $inactivitydays = $this->calculate_inactivity_days(
            $snapshot['lastactivity'] ?? null,
            $snapshot['periodstart'] ?? null,
            $snapshot['enrolstart'] ?? null,
            $snapshot['activitycount'] ?? 0,
            $snapshot['calculationtime'] ?? null
        );
        $risklevel = $this->resolve_risk_level($snapshot, $inactivitydays, $thresholds, $previousrecord);
        $semaphore = $this->resolve_semaphore($risklevel);
        $trend = $this->detect_trend($risklevel, $previousrecord);

        return [
            'userid' => $snapshot['userid'],
            'courseid' => $snapshot['courseid'],
            'cohortid' => $snapshot['cohortid'],
            'tutorid' => $snapshot['tutorid'],
            'periodtype' => $snapshot['periodtype'],
            'periodkey' => $snapshot['periodkey'],
            'lastactivity' => $snapshot['lastactivity'],
            'inactivitydays' => $inactivitydays,
            'activitycount' => $snapshot['activitycount'],
            'openalerts' => $snapshot['openalerts'],
            'closedalerts' => $snapshot['closedalerts'],
            'pendingnotifications' => $snapshot['pendingnotifications'],
            'attendednotifications' => $snapshot['attendednotifications'],
            'interventionscount' => $snapshot['interventionscount'],
            'lastintervention' => $snapshot['lastintervention'],
            'followupstatus' => $snapshot['followupstatus'],
            'risklevel' => $risklevel,
            'semaphore' => $semaphore,
            'trend' => $trend,
            'timecalculated' => time(),
            'sourceversion' => 'V9.4.2',
        ];
    }

    public function calculate_inactivity_days(?int $lastactivity, ?int $periodstart = null, ?int $enrolstart = null, int $activitycount = 0, ?int $asof = null): int {
        $asof = $asof ?? time();
        if (!empty($lastactivity)) {
            return max(0, (int)floor(($asof - $lastactivity) / DAYSECS));
        }
        if ($activitycount > 0) {
            return 0;
        }

        $reference = null;
        if (!empty($enrolstart) && !empty($periodstart)) {
            $reference = max((int)$enrolstart, (int)$periodstart);
        } else if (!empty($enrolstart)) {
            $reference = (int)$enrolstart;
        } else if (!empty($periodstart)) {
            $reference = (int)$periodstart;
        }

        if (empty($reference)) {
            return 0;
        }

        return max(0, (int)floor(($asof - $reference) / DAYSECS));
    }

    public function resolve_risk_level(array $snapshot, int $inactivitydays, array $thresholds, ?\stdClass $previousrecord = null): string {
        if ($this->is_recovered($snapshot, $thresholds, $previousrecord)) {
            return 'recovered';
        }

        if ($inactivitydays >= (int)$thresholds['red_inactivity_min'] || (int)$snapshot['openalerts'] >= (int)$thresholds['red_alerts_min']) {
            return 'red';
        }
        if ($inactivitydays >= (int)$thresholds['orange_inactivity_min'] || (int)$snapshot['openalerts'] >= (int)$thresholds['orange_alerts_min']) {
            return 'orange';
        }
        if ($inactivitydays >= (int)$thresholds['yellow_inactivity_min']
            || (int)$snapshot['openalerts'] >= (int)$thresholds['yellow_alerts_min']) {
            return 'yellow';
        }
        return 'green';
    }

    public function is_recovered(array $snapshot, array $thresholds, ?\stdClass $previousrecord = null): bool {
        if (!$previousrecord || !in_array($previousrecord->risklevel, ['yellow', 'orange', 'red'], true)) {
            return false;
        }
        $days = $this->calculate_inactivity_days(
            $snapshot['lastactivity'] ?? null,
            $snapshot['periodstart'] ?? null,
            $snapshot['enrolstart'] ?? null,
            $snapshot['activitycount'] ?? 0,
            $snapshot['calculationtime'] ?? null
        );
        return $days <= (int)$thresholds['recovered_requires_activity_days']
            && (int)$snapshot['openalerts'] <= (int)$thresholds['recovered_max_openalerts'];
    }

    public function resolve_semaphore(string $risklevel): string {
        return $risklevel;
    }

    public function detect_trend(string $currentrisk, ?\stdClass $previousrecord = null): ?string {
        if (!$previousrecord) {
            return null;
        }
        $map = ['green' => 1, 'recovered' => 1, 'yellow' => 2, 'orange' => 3, 'red' => 4];
        $current = $map[$currentrisk] ?? 1;
        $previous = $map[$previousrecord->risklevel] ?? 1;
        if ($current < $previous) {
            return 'improving';
        }
        if ($current > $previous) {
            return 'worsening';
        }
        return 'stable';
    }
}
