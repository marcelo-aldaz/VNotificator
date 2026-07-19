<?php
namespace local_pceinotifications\local\analytics;

defined('MOODLE_INTERNAL') || die();

class threshold_service {
    public function get_active_thresholds(?string $periodtype = null): array {
        global $DB;
        $sql = "isactive = :isactive";
        $params = ['isactive' => 1];
        if ($periodtype !== null) {
            $sql .= " AND (periodtype = :periodtype OR periodtype IS NULL)";
            $params['periodtype'] = $periodtype;
        }
        $record = $DB->get_record_select('local_pceinotif_thresholds', $sql, $params, '*', IGNORE_MULTIPLE);
        if (!$record) {
            return $this->get_default_thresholds();
        }
        return $this->normalize_thresholds($record);
    }

    public function get_default_thresholds(): array {
        return [
            'yellow_inactivity_min' => 8,
            'orange_inactivity_min' => 15,
            'red_inactivity_min' => 22,
            'yellow_alerts_min' => 1,
            'orange_alerts_min' => 2,
            'red_alerts_min' => 3,
            'green_risk_max_percent' => 9.99,
            'yellow_risk_max_percent' => 20.00,
            'orange_risk_max_percent' => 35.00,
            'red_risk_min_percent' => 35.01,
            'recovered_requires_activity_days' => 7,
            'recovered_max_openalerts' => 0,
        ];
    }

    public function normalize_thresholds(\stdClass $record): array {
        return [
            'yellow_inactivity_min' => (int)$record->yellow_inactivity_min,
            'orange_inactivity_min' => (int)$record->orange_inactivity_min,
            'red_inactivity_min' => (int)$record->red_inactivity_min,
            'yellow_alerts_min' => (int)$record->yellow_alerts_min,
            'orange_alerts_min' => (int)$record->orange_alerts_min,
            'red_alerts_min' => (int)$record->red_alerts_min,
            'green_risk_max_percent' => (float)$record->green_risk_max_percent,
            'yellow_risk_max_percent' => (float)$record->yellow_risk_max_percent,
            'orange_risk_max_percent' => (float)$record->orange_risk_max_percent,
            'red_risk_min_percent' => (float)$record->red_risk_min_percent,
            'recovered_requires_activity_days' => (int)$record->recovered_requires_activity_days,
            'recovered_max_openalerts' => (int)$record->recovered_max_openalerts,
        ];
    }
}
