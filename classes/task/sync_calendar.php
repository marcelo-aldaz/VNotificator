<?php
namespace local_pceinotifications\task;

defined('MOODLE_INTERNAL') || die();

class sync_calendar extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('task_sync_calendar', 'local_pceinotifications');
    }

    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');

        if (!(int)get_config('local_pceinotifications', 'enabled')) {
            \local_pceinotifications\util::log_debug('Motor deshabilitado. Calendario omitido.');
            return;
        }

        $courses = $DB->get_records_select('course', 'id > 1', [], 'id ASC', 'id,fullname,shortname');
        foreach ($courses as $course) {
            $stats = \local_pceinotifications\util::sync_course_calendar($course);
            \local_pceinotifications\util::log_debug('Curso ' . $course->id . ' calendario: procesados=' . $stats['processed'] . ', creados=' . $stats['created'] . ', actualizados=' . $stats['updated'] . ', eliminados=' . $stats['removed'] . ', omitidos=' . $stats['skipped'] . ', errores=' . $stats['errors']);
        }
    }
}
