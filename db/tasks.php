<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_pceinotifications\\task\\sync_blocks',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_pceinotifications\\task\\send_notifications',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_pceinotifications\\task\\sync_calendar',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '*/2',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_pceinotifications\\task\\evaluate_notification_rules',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '*/4',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_pceinotifications\\task\\send_profile_summaries',
        'blocking' => 0,
        'minute' => '45',
        'hour' => '20',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_pceinotifications\task\recalculate_dashboard_metrics',
        'blocking' => 0,
        'minute' => '10',
        'hour' => '3',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ]
];
