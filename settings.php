<?php
defined('MOODLE_INTERNAL') || die();
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_pceinotifications', get_string('pluginname', 'local_pceinotifications'));

    $settings->add(new admin_setting_heading('local_pceinotifications/general',
        get_string('settings_general', 'local_pceinotifications'),
        get_string('settings_general_desc', 'local_pceinotifications')));

    $settings->add(new admin_setting_configcheckbox('local_pceinotifications/enabled',
        get_string('cfg_enabled', 'local_pceinotifications'),
        get_string('cfg_enabled_help', 'local_pceinotifications'), 1));

    $settings->add(new admin_setting_configtext('local_pceinotifications/daysbefore',
        get_string('cfg_daysbefore', 'local_pceinotifications'),
        get_string('cfg_daysbefore_help', 'local_pceinotifications'), 2, PARAM_INT));

    $settings->add(new admin_setting_configtext('local_pceinotifications/sendhour',
        get_string('cfg_sendhour', 'local_pceinotifications'),
        get_string('cfg_sendhour_help', 'local_pceinotifications'), 7, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('local_pceinotifications/enableemail',
        get_string('cfg_enableemail', 'local_pceinotifications'),
        get_string('cfg_enableemail_help', 'local_pceinotifications'), 1));

    $settings->add(new admin_setting_configcheckbox('local_pceinotifications/enablepopup',
        get_string('cfg_enablepopup', 'local_pceinotifications'),
        get_string('cfg_enablepopup_help', 'local_pceinotifications'), 1));

    $settings->add(new admin_setting_configtextarea('local_pceinotifications/keywords_atpa',
        get_string('cfg_keywords_atpa', 'local_pceinotifications'),
        get_string('cfg_keywords_atpa_help', 'local_pceinotifications'),
        "ATPA\nSAV\nBBB\nSincrónica\nSincronica", PARAM_TEXT));

    $settings->add(new admin_setting_configtextarea('local_pceinotifications/keywords_tei',
        get_string('cfg_keywords_tei', 'local_pceinotifications'),
        get_string('cfg_keywords_tei_help', 'local_pceinotifications'),
        "TEI\nAsincrónica\nAsincronica\nTrabajo Independiente", PARAM_TEXT));



    $settings->add(new admin_setting_configtext('local_pceinotifications/warningdays',
        get_string('cfg_warningdays', 'local_pceinotifications'),
        get_string('cfg_warningdays_help', 'local_pceinotifications'), 3, PARAM_INT));

    $settings->add(new admin_setting_configtext('local_pceinotifications/topstudentslimit',
        get_string('cfg_topstudentslimit', 'local_pceinotifications'),
        get_string('cfg_topstudentslimit_help', 'local_pceinotifications'), 10, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('local_pceinotifications/debugmode',
        get_string('cfg_debugmode', 'local_pceinotifications'),
        get_string('cfg_debugmode_help', 'local_pceinotifications'), 0));

    $settings->add(new admin_setting_heading('local_pceinotifications/vtutor',
        get_string('settings_vtutor', 'local_pceinotifications'),
        get_string('settings_vtutor_desc', 'local_pceinotifications')));

    $settings->add(new admin_setting_configcheckbox('local_pceinotifications/vtutor_enabled',
        get_string('cfg_vtutor_enabled', 'local_pceinotifications'),
        get_string('cfg_vtutor_enabled_help', 'local_pceinotifications'), 0));

    $settings->add(new admin_setting_configtext('local_pceinotifications/vtutor_urltemplate',
        get_string('cfg_vtutor_urltemplate', 'local_pceinotifications'),
        get_string('cfg_vtutor_urltemplate_help', 'local_pceinotifications'),
        '', PARAM_RAW_TRIMMED));

    $settings->add(new admin_setting_configtext('local_pceinotifications/vtutor_label',
        get_string('cfg_vtutor_label', 'local_pceinotifications'),
        get_string('cfg_vtutor_label_help', 'local_pceinotifications'),
        'Abrir VTutor', PARAM_TEXT));

    $ADMIN->add('localplugins', $settings);

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_pceinotifications_admin_dashboard',
        get_string('admin_dashboard', 'local_pceinotifications'),
        new moodle_url('/local/pceinotifications/admin_dashboard.php'),
        'moodle/site:config'
    ));

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_pceinotifications_advanced_dashboard',
        get_string('dashboardadvanced', 'local_pceinotifications'),
        new moodle_url('/local/pceinotifications/advanced_dashboard.php'),
        'local/pceinotifications:viewadvanceddashboard'
    ));

}
