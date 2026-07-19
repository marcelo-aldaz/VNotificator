<?php
namespace local_pceinotifications\local\analytics;

defined('MOODLE_INTERNAL') || die();

class followup_service {
    public function table_exists(): bool {
        global $DB;
        return $DB->get_manager()->table_exists(new \xmldb_table('local_pceinotif_followup'));
    }

    public function create_followup(int $courseid, int $userid, int $teacherid, string $status, string $contacttype, string $note, ?int $nextreview, string $commitment, string $responsible, ?int $commitmentdate, string $commitmentstatus, string $evidence): int {
        global $DB;

        if (!$this->table_exists()) {
            throw new \moodle_exception('analyticsnotready', 'local_pceinotifications');
        }

        $now = time();
        $record = (object)[
            'courseid' => $courseid,
            'userid' => $userid,
            'teacherid' => $teacherid,
            'status' => $status,
            'contacttype' => $contacttype,
            'note' => trim($note),
            'nextreview' => $nextreview,
            'commitment' => trim($commitment),
            'responsible' => trim($responsible),
            'commitmentdate' => $commitmentdate,
            'commitmentstatus' => $commitmentstatus,
            'evidence' => trim($evidence),
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $id = $DB->insert_record('local_pceinotif_followup', $record);
        $this->sync_risk_followup($courseid, $userid, $status, $now);
        return (int)$id;
    }

    public function get_latest_followup(int $courseid, int $userid): ?\stdClass {
        global $DB;
        if (!$this->table_exists()) {
            return null;
        }

        $records = $DB->get_records(
            'local_pceinotif_followup',
            ['courseid' => $courseid, 'userid' => $userid],
            'timemodified DESC, id DESC',
            '*',
            0,
            1
        );

        if (!$records) {
            return null;
        }

        return reset($records) ?: null;
    }

    public function get_followup_history(int $courseid, int $userid, int $limit = 10): array {
        global $DB;
        if (!$this->table_exists()) {
            return [];
        }
        return $DB->get_records('local_pceinotif_followup', ['courseid' => $courseid, 'userid' => $userid], 'timemodified DESC, id DESC', '*', 0, $limit);
    }

    protected function sync_risk_followup(int $courseid, int $userid, string $status, int $timestamp): void {
        global $DB;
        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_pceinotif_risk'))) {
            return;
        }

        $records = $DB->get_records('local_pceinotif_risk', ['courseid' => $courseid, 'userid' => $userid], 'timecalculated DESC', '*', 0, 1);
        if (!$records) {
            return;
        }

        $risk = reset($records);
        $risk->followupstatus = $status;
        $risk->lastintervention = $timestamp;
        $risk->interventionscount = (int)$risk->interventionscount + 1;
        $DB->update_record('local_pceinotif_risk', $risk);
    }
}
