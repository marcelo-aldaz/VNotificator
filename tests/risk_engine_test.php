<?php
// This file is part of Moodle - http://moodle.org/.

namespace local_pceinotifications;

use local_pceinotifications\local\analytics\risk_engine;

/**
 * Tests deterministic risk classification behaviour.
 *
 * @package local_pceinotifications
 * @covers  \local_pceinotifications\local\analytics\risk_engine
 */
final class risk_engine_test extends \advanced_testcase {
    private function thresholds(): array {
        return [
            'yellow_inactivity_min' => 8,
            'orange_inactivity_min' => 15,
            'red_inactivity_min' => 22,
            'yellow_alerts_min' => 1,
            'orange_alerts_min' => 2,
            'red_alerts_min' => 3,
            'recovered_requires_activity_days' => 7,
            'recovered_max_openalerts' => 0,
        ];
    }

    public function test_resolve_risk_level_from_alert_counts(): void {
        $engine = new risk_engine();
        $base = [
            'lastactivity' => time(),
            'activitycount' => 1,
            'attendednotifications' => 0,
            'interventionscount' => 0,
            'pendingnotifications' => 0,
            'openalerts' => 0,
        ];
        $this->assertSame('green', $engine->resolve_risk_level($base, 0, $this->thresholds()));
        $this->assertSame('yellow', $engine->resolve_risk_level(array_replace($base, ['openalerts' => 1]), 0, $this->thresholds()));
        $this->assertSame('orange', $engine->resolve_risk_level(array_replace($base, ['openalerts' => 2]), 0, $this->thresholds()));
        $this->assertSame('red', $engine->resolve_risk_level(array_replace($base, ['openalerts' => 3]), 0, $this->thresholds()));
    }

    public function test_detect_trend(): void {
        $engine = new risk_engine();
        $previous = (object) ['risklevel' => 'red'];
        $this->assertSame('improving', $engine->detect_trend('yellow', $previous));
        $this->assertSame('stable', $engine->detect_trend('red', $previous));
        $this->assertNull($engine->detect_trend('green'));
    }

    public function test_inactivity_uses_explicit_period_cutoff(): void {
        $engine = new risk_engine();
        $cutoff = 1722470400; // 2024-08-01 00:00:00 UTC.
        $lastactivity = $cutoff - (10 * DAYSECS);
        $this->assertSame(10, $engine->calculate_inactivity_days($lastactivity, null, null, 0, $cutoff));
    }

    public function test_failed_delivery_does_not_create_pedagogical_risk(): void {
        $engine = new risk_engine();
        $snapshot = [
            'lastactivity' => time(),
            'activitycount' => 1,
            'attendednotifications' => 0,
            'interventionscount' => 0,
            'pendingnotifications' => 99,
            'openalerts' => 0,
        ];
        $this->assertSame('green', $engine->resolve_risk_level($snapshot, 0, $this->thresholds()));
    }
}
