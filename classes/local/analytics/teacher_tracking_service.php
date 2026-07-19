<?php
namespace local_pceinotifications\local\analytics;

defined('MOODLE_INTERNAL') || die();

class teacher_tracking_service {
    protected student_profile_service $profileservice;

    public function __construct() {
        $this->profileservice = new student_profile_service();
    }

    public function get_course_student_rows(int $courseid): array {
        global $DB;

        $context = \context_course::instance($courseid);
        $students = get_enrolled_users($context, 'local/pceinotifications:receive', 0, 'u.id,u.firstname,u.lastname,u.middlename,u.lastnamephonetic,u.firstnamephonetic,u.alternatename,u.email', 'u.lastname ASC, u.firstname ASC');

        $rows = [];
        foreach ($students as $student) {
            $payload = $this->profileservice->get_student_course_payload((int)$student->id, $courseid);
            $riskrecord = $payload['riskrecord'];
            $latestfollowup = $payload['latestfollowup'] ?? null;
            $nextreview = (!empty($latestfollowup->nextreview)) ? (int)$latestfollowup->nextreview : 0;
            $rows[] = [
                'student' => $student,
                'payload' => $payload,
                'riskweight' => $this->risk_weight($riskrecord->risklevel ?? 'green'),
                'priorityweight' => $this->priority_weight($payload['priority'] ?? 'ordinary'),
                'inactivitydays' => $this->profileservice->get_inactivity_display_value($riskrecord),
                'openalerts' => (int)($riskrecord->openalerts ?? 0),
                'lastsignal' => (int)($payload['lastsignal'] ?? 0),
                'latestfollowup' => $latestfollowup,
                'nextreview' => $nextreview,
                'nextreviewoverdue' => ($nextreview > 0 && $nextreview < strtotime('today')),
                'hasfollowup' => !empty($latestfollowup),
            ];
        }

        usort($rows, function(array $a, array $b): int {
            return [$b['riskweight'], $b['priorityweight'], ($b['inactivitydays'] ?? -1), $b['openalerts'], $a['lastsignal']] <=>
                   [$a['riskweight'], $a['priorityweight'], ($a['inactivitydays'] ?? -1), $a['openalerts'], $b['lastsignal']];
        });

        return $rows;
    }

    public function get_course_summary(array $rows): array {
        $summary = [
            'total' => count($rows),
            'red' => 0,
            'orange' => 0,
            'yellow' => 0,
            'green' => 0,
            'recovered' => 0,
            'high' => 0,
            'medium' => 0,
            'preventive' => 0,
            'ordinary' => 0,
            'followupoverdue' => 0,
            'withoutfollowup' => 0,
        ];

        foreach ($rows as $row) {
            $risk = $row['payload']['riskrecord']->risklevel ?? 'green';
            $priority = $row['payload']['priority'] ?? 'ordinary';
            if (array_key_exists($risk, $summary)) {
                $summary[$risk]++;
            }
            if (array_key_exists($priority, $summary)) {
                $summary[$priority]++;
            }
            if (!empty($row['nextreviewoverdue'])) {
                $summary['followupoverdue']++;
            }
            if (empty($row['hasfollowup'])) {
                $summary['withoutfollowup']++;
            }
        }

        return $summary;
    }

    protected function risk_weight(string $risk): int {
        return [
            'red' => 5,
            'orange' => 4,
            'yellow' => 3,
            'recovered' => 2,
            'green' => 1,
        ][$risk] ?? 0;
    }

    protected function priority_weight(string $priority): int {
        return [
            'high' => 4,
            'medium' => 3,
            'preventive' => 2,
            'ordinary' => 1,
        ][$priority] ?? 0;
    }
}
