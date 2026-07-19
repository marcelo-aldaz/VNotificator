<?php
namespace local_pceinotifications\task;

defined('MOODLE_INTERNAL') || die();

class recalculate_dashboard_metrics extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('task_recalculate_dashboard_metrics', 'local_pceinotifications');
    }

    public function execute() {
        if (!(int)get_config('local_pceinotifications', 'enabled')) {
            return;
        }
        $service = new \local_pceinotifications\local\analytics\recalculation_service();
        $service->run_period_recalculation('monthly', date('Y-m'), [], 0);
    }
}
