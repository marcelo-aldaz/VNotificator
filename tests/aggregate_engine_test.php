<?php
// This file is part of Moodle - http://moodle.org/.

namespace local_pceinotifications;

use local_pceinotifications\local\analytics\aggregate_engine;

/**
 * Tests aggregation by unique student.
 *
 * @package local_pceinotifications
 * @covers  \local_pceinotifications\local\analytics\aggregate_engine
 */
final class aggregate_engine_test extends \advanced_testcase {
    public function test_collapse_by_user_keeps_highest_risk_and_accumulates_followup(): void {
        $engine = new aggregate_engine();
        $records = [
            (object)[
                'userid' => 7,
                'courseid' => 10,
                'risklevel' => 'yellow',
                'semaphore' => 'yellow',
                'inactivitydays' => 9,
                'openalerts' => 0,
                'closedalerts' => 0,
                'pendingnotifications' => 0,
                'attendednotifications' => 1,
                'interventionscount' => 0,
                'activitycount' => 1,
                'lastactivity' => 100,
                'lastintervention' => null,
                'followupstatus' => 'none',
                'trend' => null,
            ],
            (object)[
                'userid' => 7,
                'courseid' => 11,
                'risklevel' => 'red',
                'semaphore' => 'red',
                'inactivitydays' => 30,
                'openalerts' => 1,
                'closedalerts' => 0,
                'pendingnotifications' => 0,
                'attendednotifications' => 0,
                'interventionscount' => 1,
                'activitycount' => 0,
                'lastactivity' => 50,
                'lastintervention' => 90,
                'followupstatus' => 'inprogress',
                'trend' => 'worsening',
            ],
        ];

        $collapsed = $engine->collapse_by_user($records);
        $this->assertCount(1, $collapsed);
        $this->assertSame('red', $collapsed[0]->risklevel);
        $this->assertSame(1, $collapsed[0]->openalerts);
        $this->assertSame(1, $collapsed[0]->interventionscount);
        $this->assertSame('inprogress', $collapsed[0]->followupstatus);
        $this->assertSame(100, $collapsed[0]->lastactivity);
    }

    public function test_active_students_are_green_or_recovered(): void {
        $engine = new aggregate_engine();
        $records = [
            (object)['risklevel' => 'green'],
            (object)['risklevel' => 'recovered'],
            (object)['risklevel' => 'yellow'],
            (object)['risklevel' => 'red'],
        ];
        $this->assertSame(2, $engine->calculate_active_students($records));
    }
}
