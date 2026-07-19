<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_pceinotifications_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026030700) {
        $table = new xmldb_table('local_pceinotif_log');
        $field = new xmldb_field('messagehash');
        $field->set_type(XMLDB_TYPE_CHAR);
        $field->set_length(40);
        $field->set_notnull(false);

        if ($dbman->table_exists($table) && $dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026030700, 'local', 'pceinotifications');
    }


    if ($oldversion < 2026031601) {
        $table = new xmldb_table('local_pceinotif_blocks');

        $field = new xmldb_field('blockstate', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'detected', 'bbbcmid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('syncsource', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'blockstate');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('syncnote', XMLDB_TYPE_TEXT, null, null, null, null, null, 'syncsource');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('lastsync', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'syncnote');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026031601, 'local', 'pceinotifications');
    }


    if ($oldversion < 2026031801) {
        $table = new xmldb_table('local_pceinotif_blocks');

        $field = new xmldb_field('calendarstatus', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'pending', 'lastsync');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('calendarnote', XMLDB_TYPE_TEXT, null, null, null, null, null, 'calendarstatus');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('calendarupdated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'calendarnote');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('local_pceinotif_events');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('blockid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('eventid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('eventtype', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'course');
            $table->add_field('status', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'created');
            $table->add_field('errormsg', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('uq_blockid', XMLDB_INDEX_UNIQUE, ['blockid']);
            $table->add_index('idx_courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
            $table->add_index('idx_eventid', XMLDB_INDEX_NOTUNIQUE, ['eventid']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026031801, 'local', 'pceinotifications');
    }


    if ($oldversion < 2026040202) {
        // 8C analytical tables for upgrades from sprint8B.

        $table = new xmldb_table('local_pceinotif_risk');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('tutorid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('periodtype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
            $table->add_field('periodkey', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
            $table->add_field('lastactivity', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('inactivitydays', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('activitycount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('openalerts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('closedalerts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('pendingnotifications', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('attendednotifications', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('interventionscount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('lastintervention', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('followupstatus', XMLDB_TYPE_CHAR, '30', null, null, null, null);
            $table->add_field('risklevel', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'green');
            $table->add_field('semaphore', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'green');
            $table->add_field('trend', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $table->add_field('timecalculated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('sourceversion', XMLDB_TYPE_CHAR, '30', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('idx_user', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $table->add_index('idx_course', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
            $table->add_index('idx_period', XMLDB_INDEX_NOTUNIQUE, ['periodtype', 'periodkey']);
            $table->add_index('idx_risklevel', XMLDB_INDEX_NOTUNIQUE, ['risklevel']);
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_pceinotif_dashagg');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('scopelevel', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
            $table->add_field('scopeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('periodtype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
            $table->add_field('periodkey', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
            $table->add_field('totalstudents', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('activestudents', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('studentsatrisk', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('highriskstudents', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('recoveredstudents', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('openalerts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('closedalerts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('green_count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('yellow_count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('orange_count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('red_count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('recovered_count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('coveragepercent', XMLDB_TYPE_NUMBER, '10', '2', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('interventioneffectiveness', XMLDB_TYPE_NUMBER, '10', '2', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('institutionalsemaphore', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'green');
            $table->add_field('trenddirection', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $table->add_field('timecalculated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('sourceversion', XMLDB_TYPE_CHAR, '30', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('idx_scopeperiod', XMLDB_INDEX_NOTUNIQUE, ['scopelevel', 'scopeid', 'periodtype', 'periodkey']);
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_pceinotif_thresholds');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('isactive', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('periodtype', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $table->add_field('yellow_inactivity_min', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '8');
            $table->add_field('orange_inactivity_min', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '15');
            $table->add_field('red_inactivity_min', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '22');
            $table->add_field('yellow_alerts_min', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('orange_alerts_min', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '2');
            $table->add_field('red_alerts_min', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '3');
            $table->add_field('green_risk_max_percent', XMLDB_TYPE_NUMBER, '10', '2', null, XMLDB_NOTNULL, null, '9.99');
            $table->add_field('yellow_risk_max_percent', XMLDB_TYPE_NUMBER, '10', '2', null, XMLDB_NOTNULL, null, '20.00');
            $table->add_field('orange_risk_max_percent', XMLDB_TYPE_NUMBER, '10', '2', null, XMLDB_NOTNULL, null, '35.00');
            $table->add_field('red_risk_min_percent', XMLDB_TYPE_NUMBER, '10', '2', null, XMLDB_NOTNULL, null, '35.01');
            $table->add_field('recovered_requires_activity_days', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '7');
            $table->add_field('recovered_max_openalerts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_pceinotif_runs');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('periodtype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
            $table->add_field('periodkey', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
            $table->add_field('scopelevel', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'institution');
            $table->add_field('scopeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('recordsprocessed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'success');
            $table->add_field('errormessage', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('startedat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('finishedat', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('executedby', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('engineversion', XMLDB_TYPE_CHAR, '30', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        if (!$DB->record_exists('local_pceinotif_thresholds', ['isactive' => 1])) {
            $record = (object)[
                'name' => 'Default institutional thresholds',
                'isactive' => 1,
                'periodtype' => null,
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
                'timecreated' => time(),
                'timemodified' => time(),
                'createdby' => 0,
            ];
            $DB->insert_record('local_pceinotif_thresholds', $record);
        }

        upgrade_plugin_savepoint(true, 2026040202, 'local', 'pceinotifications');
    }


    if ($oldversion < 2026040204) {
        // Build 4: force a new upgrade step after fixing missing analytical tables handling.
        upgrade_plugin_savepoint(true, 2026040204, 'local', 'pceinotifications');
    }


    if ($oldversion < 2026040226) {
        $table = new xmldb_table('local_pceinotif_followup');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
            $table->add_field('contacttype', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'review_only');
            $table->add_field('note', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('nextreview', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('idx_course_user', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'userid']);
            $table->add_index('idx_teacher', XMLDB_INDEX_NOTUNIQUE, ['teacherid']);
            $table->add_index('idx_status', XMLDB_INDEX_NOTUNIQUE, ['status']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026040226, 'local', 'pceinotifications');
    }


    if ($oldversion < 2026040229) {
        $table = new xmldb_table('local_pceinotif_followup');

        $field = new xmldb_field('commitment', XMLDB_TYPE_TEXT, null, null, null, null, null, 'nextreview');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('responsible', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'commitment');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('commitmentdate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'responsible');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('commitmentstatus', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'notstarted', 'commitmentdate');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('evidence', XMLDB_TYPE_TEXT, null, null, null, null, null, 'commitmentstatus');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026040229, 'local', 'pceinotifications');
    }



    if ($oldversion < 2026040231) {
        $table = new xmldb_table('local_pceinotif_novelty');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('detail', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'open');
            $table->add_field('visibility', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'internal');
            $table->add_field('source', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'teacher_alert');
            $table->add_field('risklevel', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $table->add_field('priority', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('idx_course_user', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'userid']);
            $table->add_index('idx_status', XMLDB_INDEX_NOTUNIQUE, ['status']);
            $table->add_index('idx_teacher', XMLDB_INDEX_NOTUNIQUE, ['teacherid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026040231, 'local', 'pceinotifications');
    }


    if ($oldversion < 2026040237) {
        $table = new xmldb_table('local_pceinotif_novelty');

        $field = new xmldb_field('studentresponse', XMLDB_TYPE_TEXT, null, null, null, null, null, 'priority');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('teachervalidation', XMLDB_TYPE_TEXT, null, null, null, null, null, 'studentresponse');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('closedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'teachervalidation');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timeclosed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'closedby');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026040237, 'local', 'pceinotifications');
    }

    if ($oldversion < 2026071801) {
        // V9.4.2: semantic correction of analytics and reports; no schema change.
        upgrade_plugin_savepoint(true, 2026071801, 'local', 'pceinotifications');
    }

    if ($oldversion < 2026071802) {
        // RC2: database-portable enrolment date field correction.
        upgrade_plugin_savepoint(true, 2026071802, 'local', 'pceinotifications');
    }

    if ($oldversion < 2026071803) {
        // RC3: case-resolution UI and comparable-engine trend correction; no schema change.
        upgrade_plugin_savepoint(true, 2026071803, 'local', 'pceinotifications');
    }

    if ($oldversion < 2026071804) {
        // RC4: closed-case temporal consistency correction; no schema change.
        upgrade_plugin_savepoint(true, 2026071804, 'local', 'pceinotifications');
    }

    if ($oldversion < 2026071805) {
        // RC5: dashboard usability, text, and percentage-sensitive trend correction.
        upgrade_plugin_savepoint(true, 2026071805, 'local', 'pceinotifications');
    }

    if ($oldversion < 2026071806) {
        // Stable promotion after production UI acceptance; no schema change.
        upgrade_plugin_savepoint(true, 2026071806, 'local', 'pceinotifications');
    }


    return true;
}
