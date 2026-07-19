<?php
// This file is part of Moodle - http://moodle.org/.

namespace local_pceinotifications\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for VNotificator.
 *
 * @package   local_pceinotifications
 * @copyright 2026 Nelson Marcelo Aldaz Herrera
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    public static function get_metadata(\core_privacy\local\metadata\collection $collection)
    : \core_privacy\local\metadata\collection {
        $collection->add_database_table('local_pceinotif_log', [
            'userid' => 'privacy:metadata:userid',
            'courseid' => 'privacy:metadata:courseid',
            'blockid' => 'privacy:metadata:blockid',
            'notiftype' => 'privacy:metadata:notiftype',
            'subject' => 'privacy:metadata:subject',
            'success' => 'privacy:metadata:success',
            'errormsg' => 'privacy:metadata:errormsg',
            'timesent' => 'privacy:metadata:timesent',
        ], 'privacy:metadata:log');

        $collection->add_database_table('local_pceinotif_risk', [
            'userid' => 'privacy:metadata:userid',
            'courseid' => 'privacy:metadata:courseid',
            'tutorid' => 'privacy:metadata:tutorid',
            'lastactivity' => 'privacy:metadata:lastactivity',
            'inactivitydays' => 'privacy:metadata:inactivitydays',
            'risklevel' => 'privacy:metadata:risklevel',
            'followupstatus' => 'privacy:metadata:followupstatus',
            'timecalculated' => 'privacy:metadata:timecalculated',
        ], 'privacy:metadata:risk');

        $collection->add_database_table('local_pceinotif_followup', [
            'userid' => 'privacy:metadata:userid',
            'courseid' => 'privacy:metadata:courseid',
            'teacherid' => 'privacy:metadata:teacherid',
            'status' => 'privacy:metadata:followupstatus',
            'contacttype' => 'privacy:metadata:contacttype',
            'note' => 'privacy:metadata:note',
            'commitment' => 'privacy:metadata:commitment',
            'evidence' => 'privacy:metadata:evidence',
        ], 'privacy:metadata:followup');

        $collection->add_database_table('local_pceinotif_novelty', [
            'userid' => 'privacy:metadata:userid',
            'courseid' => 'privacy:metadata:courseid',
            'teacherid' => 'privacy:metadata:teacherid',
            'title' => 'privacy:metadata:noveltytitle',
            'detail' => 'privacy:metadata:noveltydetail',
            'risklevel' => 'privacy:metadata:risklevel',
            'studentresponse' => 'privacy:metadata:studentresponse',
            'teachervalidation' => 'privacy:metadata:teachervalidation',
        ], 'privacy:metadata:novelty');

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid)
    : \core_privacy\local\request\contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
        $params = ['courselevel' => CONTEXT_COURSE, 'userid' => $userid];
        $tables = [
            ['local_pceinotif_log', 'userid = :userid'],
            ['local_pceinotif_risk', ':userid IN (userid, tutorid)'],
            ['local_pceinotif_followup', ':userid IN (userid, teacherid)'],
            ['local_pceinotif_novelty', ':userid IN (userid, teacherid, closedby)'],
        ];
        foreach ($tables as [$table, $condition]) {
            $sql = "SELECT DISTINCT cctx.id
                      FROM {{$table}} p
                      JOIN {context} cctx
                        ON cctx.instanceid = p.courseid
                       AND cctx.contextlevel = :courselevel
                     WHERE {$condition}";
            $contextlist->add_from_sql($sql, $params);
        }
        return $contextlist;
    }

    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            $courseid = $context->instanceid;
            $data = (object) [
                'notifications' => array_values($DB->get_records('local_pceinotif_log', [
                    'courseid' => $courseid,
                    'userid' => $userid,
                ], 'timesent ASC')),
                'risk' => array_values($DB->get_records('local_pceinotif_risk', [
                    'courseid' => $courseid,
                    'userid' => $userid,
                ], 'timecalculated ASC')),
                'followup_as_student' => array_values($DB->get_records('local_pceinotif_followup', [
                    'courseid' => $courseid,
                    'userid' => $userid,
                ], 'timecreated ASC')),
                'followup_as_teacher' => array_values($DB->get_records('local_pceinotif_followup', [
                    'courseid' => $courseid,
                    'teacherid' => $userid,
                ], 'timecreated ASC')),
                'novelties_as_student' => array_values($DB->get_records('local_pceinotif_novelty', [
                    'courseid' => $courseid,
                    'userid' => $userid,
                ], 'timecreated ASC')),
                'novelties_as_teacher' => array_values($DB->get_records('local_pceinotif_novelty', [
                    'courseid' => $courseid,
                    'teacherid' => $userid,
                ], 'timecreated ASC')),
            ];
            \core_privacy\local\request\writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_pceinotifications')],
                $data
            );
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        foreach (self::course_tables() as $table) {
            $DB->delete_records($table, ['courseid' => $context->instanceid]);
        }
    }

    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            $courseid = $context->instanceid;
            $DB->delete_records('local_pceinotif_log', ['courseid' => $courseid, 'userid' => $userid]);
            $DB->delete_records('local_pceinotif_risk', ['courseid' => $courseid, 'userid' => $userid]);
            $DB->set_field('local_pceinotif_risk', 'tutorid', 0, ['courseid' => $courseid, 'tutorid' => $userid]);

            // Delete the subject's records but anonymise staff attribution so that
            // deleting a staff account does not erase another user's follow-up history.
            $DB->delete_records('local_pceinotif_followup', ['courseid' => $courseid, 'userid' => $userid]);
            $DB->set_field('local_pceinotif_followup', 'teacherid', 0, [
                'courseid' => $courseid,
                'teacherid' => $userid,
            ]);

            $DB->delete_records('local_pceinotif_novelty', ['courseid' => $courseid, 'userid' => $userid]);
            $DB->set_field('local_pceinotif_novelty', 'teacherid', 0, [
                'courseid' => $courseid,
                'teacherid' => $userid,
            ]);
            $DB->set_field('local_pceinotif_novelty', 'closedby', 0, [
                'courseid' => $courseid,
                'closedby' => $userid,
            ]);
        }
    }

    /**
     * Tables whose records are scoped directly to a Moodle course.
     *
     * @return string[]
     */
    private static function course_tables(): array {
        return [
            'local_pceinotif_log',
            'local_pceinotif_blocks',
            'local_pceinotif_events',
            'local_pceinotif_risk',
            'local_pceinotif_followup',
            'local_pceinotif_novelty',
        ];
    }
}
