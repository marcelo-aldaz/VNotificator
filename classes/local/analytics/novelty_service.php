<?php
namespace local_pceinotifications\local\analytics;

defined('MOODLE_INTERNAL') || die();

class novelty_service {
    public function create_novelty(int $courseid, int $userid, int $teacherid, string $title, string $detail, string $status = 'open', string $visibility = 'internal', string $source = 'teacher_alert', ?string $risklevel = null, ?string $priority = null): int {
        global $DB;
        $now = time();
        $isclosed = $status === 'closed';
        $record = (object)[
            'courseid' => $courseid,
            'userid' => $userid,
            'teacherid' => $teacherid,
            'title' => trim($title),
            'detail' => trim($detail),
            'status' => $status,
            'visibility' => $visibility,
            'source' => $source,
            'risklevel' => $risklevel ?? '',
            'priority' => $priority ?? '',
            'studentresponse' => null,
            'teachervalidation' => null,
            'closedby' => $isclosed ? $teacherid : 0,
            'timeclosed' => $isclosed ? $now : 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        return (int)$DB->insert_record('local_pceinotif_novelty', $record);
    }

    public function get_student_novelties(int $courseid, int $userid, int $limit = 20): array {
        global $DB;
        return $DB->get_records('local_pceinotif_novelty', ['courseid' => $courseid, 'userid' => $userid], 'timemodified DESC, id DESC', '*', 0, $limit);
    }

    public function get_shared_student_novelties(int $courseid, int $userid, int $limit = 20): array {
        global $DB;
        return $DB->get_records('local_pceinotif_novelty', ['courseid' => $courseid, 'userid' => $userid, 'visibility' => 'shared'], 'timemodified DESC, id DESC', '*', 0, $limit);
    }

    public function count_student_novelties(int $courseid, int $userid): int {
        global $DB;
        return (int)$DB->count_records('local_pceinotif_novelty', ['courseid' => $courseid, 'userid' => $userid]);
    }

    public function get_recent_novelties(array $filters = [], int $limit = 100): array {
        global $DB;
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['courseid'])) {
            $where[] = 'n.courseid = :courseid';
            $params['courseid'] = (int)$filters['courseid'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'n.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['risklevel'])) {
            $where[] = 'n.risklevel = :risklevel';
            $params['risklevel'] = $filters['risklevel'];
        }
        $sql = "SELECT n.*
                  FROM {local_pceinotif_novelty} n
                 WHERE " . implode(' AND ', $where) . "
              ORDER BY n.timemodified DESC, n.id DESC";
        return $DB->get_records_sql($sql, $params, 0, $limit);
    }

    public function get_open_student_novelties(int $courseid, int $userid, int $limit = 20): array {
        global $DB;
        list($insql, $params) = $DB->get_in_or_equal(['open', 'reviewed']);
        $params = array_merge([$courseid, $userid], $params);
        $sql = "SELECT *
                  FROM {local_pceinotif_novelty}
                 WHERE courseid = ?
                   AND userid = ?
                   AND status {$insql}
              ORDER BY timemodified DESC, id DESC";
        return $DB->get_records_sql($sql, $params, 0, $limit);
    }

    public function update_case_resolution(int $noveltyid, string $status, string $studentresponse = '', string $teachervalidation = '', int $closedby = 0): void {
        global $DB;
        $record = $DB->get_record('local_pceinotif_novelty', ['id' => $noveltyid], '*', MUST_EXIST);
        $record->status = $status;
        $record->studentresponse = trim($studentresponse) === '' ? null : trim($studentresponse);
        $record->teachervalidation = trim($teachervalidation) === '' ? null : trim($teachervalidation);
        $record->timemodified = time();
        if ($status === 'closed') {
            $record->closedby = $closedby;
            $record->timeclosed = time();
        } else {
            $record->closedby = 0;
            $record->timeclosed = 0;
        }
        $DB->update_record('local_pceinotif_novelty', $record);
    }

    public function get_summary(): array {
        global $DB;
        $rows = $DB->get_records_sql("SELECT status, COUNT(*) AS total FROM {local_pceinotif_novelty} GROUP BY status");
        $summary = ['total' => 0, 'open' => 0, 'reviewed' => 0, 'closed' => 0];
        foreach ($rows as $row) {
            $summary['total'] += (int)$row->total;
            if (array_key_exists($row->status, $summary)) {
                $summary[$row->status] = (int)$row->total;
            }
        }
        return $summary;
    }
}
