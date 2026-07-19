<?php
namespace local_pceinotifications\task;

defined('MOODLE_INTERNAL') || die();

class sync_blocks extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('task_sync_blocks', 'local_pceinotifications');
    }

    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');

        if (!(int)get_config('local_pceinotifications', 'enabled')) {
            \local_pceinotifications\util::log_debug('Motor deshabilitado.');
            return;
        }

        $courses = $DB->get_records_select('course', 'id > 1', [], 'id ASC', 'id,fullname,shortname');
        foreach ($courses as $course) {
            $stats = \local_pceinotifications\util::sync_course_blocks($course);
            \local_pceinotifications\util::log_debug('Curso ' . $course->id . ' sync: analysed=' . $stats['analysed'] . ', created=' . $stats['created'] . ', updated=' . $stats['updated'] . ', removed=' . $stats['removed'] . ', ignored=' . $stats['ignored']);
        }
    }
}
