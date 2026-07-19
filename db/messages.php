<?php
defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    'pcei_notice' => [
        'defaults' => [
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
        ],
    ],
];
