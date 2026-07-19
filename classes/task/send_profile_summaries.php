<?php
namespace local_pceinotifications\task;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../locallib.php');

class send_profile_summaries extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('task_profile_summaries', 'local_pceinotifications');
    }

    public function execute() {
        \local_pceinotifications\util::log_debug('Resumen por perfiles ejecutado sin acciones adicionales.');
    }
}
