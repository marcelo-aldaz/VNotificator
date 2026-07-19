<?php
defined('MOODLE_INTERNAL') || die();

function local_pceinotifications_extend_navigation_course($navigation, $course, $context) {
    $canmanage   = has_capability('local/pceinotifications:manage', $context);
    $canviewlogs = has_capability('local/pceinotifications:viewlogs', $context);
    $canreceive  = has_capability('local/pceinotifications:receive', $context);

    if (!($canmanage || $canviewlogs || $canreceive)) {
        return;
    }

    // Parent node.
    $parenturl = new moodle_url('/local/pceinotifications/notifications.php', ['id' => $course->id, 'mode' => ($canviewlogs ? 'course' : 'mine')]);
    $parent = navigation_node::create(
        get_string('pceinotifications', 'local_pceinotifications'),
        $parenturl,
        navigation_node::TYPE_SETTING,
        null,
        'local_pceinotifications',
        new pix_icon('i/notifications', '')
    );
    $navigation->add_node($parent);

    // Child: Panel del estudiante.
    if ($canreceive) {
        $surl = new moodle_url('/local/pceinotifications/student_dashboard.php', ['id' => $course->id]);
        $parent->add(get_string('student_dashboard', 'local_pceinotifications'), $surl, navigation_node::TYPE_SETTING, null, 'local_pceinotifications_student_dashboard');
    }

    // Child: Panel docente.
    if ($canviewlogs || $canmanage) {
        $turl = new moodle_url('/local/pceinotifications/teacher_dashboard.php', ['id' => $course->id]);
        $parent->add(get_string('teacher_dashboard', 'local_pceinotifications'), $turl, navigation_node::TYPE_SETTING, null, 'local_pceinotifications_teacher_dashboard');
    }

    // Child: Dashboard del curso.
    $dashurl = new moodle_url('/local/pceinotifications/course_dashboard.php', ['id' => $course->id]);
    $parent->add(get_string('course_dashboard', 'local_pceinotifications'), $dashurl, navigation_node::TYPE_SETTING, null, 'local_pceinotifications_course_dashboard');

    // Child: Progress dashboard (per course).
    $purl = new moodle_url('/local/pceinotifications/progress.php', ['id' => $course->id]);
    $parent->add(get_string('progress_title', 'local_pceinotifications'), $purl, navigation_node::TYPE_SETTING, null, 'local_pceinotifications_progress');

    // Child: My notifications (students).
    if ($canreceive || $canmanage || $canviewlogs) {
        $url = new moodle_url('/local/pceinotifications/notifications.php', ['id' => $course->id, 'mode' => 'mine']);
        $parent->add(get_string('my_notifications', 'local_pceinotifications'), $url, navigation_node::TYPE_SETTING, null, 'local_pceinotifications_mine');
    }

    // Child: Course center (teachers/managers).
    if ($canviewlogs || $canmanage) {
        $url = new moodle_url('/local/pceinotifications/notifications.php', ['id' => $course->id, 'mode' => 'course']);
        $parent->add(get_string('course_notifications', 'local_pceinotifications'), $url, navigation_node::TYPE_SETTING, null, 'local_pceinotifications_course');
    }

    // Child: Manage/sync blocks (only managers/editingteachers).
    if ($canmanage) {
        $url = new moodle_url('/local/pceinotifications/course.php', ['id' => $course->id]);
        $parent->add(get_string('manage_blocks', 'local_pceinotifications'), $url, navigation_node::TYPE_SETTING, null, 'local_pceinotifications_manage');
    }
}


function local_pceinotifications_extend_settings_navigation($settingsnav, $context) {
    if (!has_capability('moodle/site:config', context_system::instance())) {
        return;
    }

    $url = new moodle_url('/local/pceinotifications/admin_dashboard.php');
    $settingsnav->add(
        get_string('admin_dashboard', 'local_pceinotifications'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_pceinotifications_admin_dashboard'
    );

    $advurl = new moodle_url('/local/pceinotifications/advanced_dashboard.php');
    $settingsnav->add(
        get_string('dashboardadvanced', 'local_pceinotifications'),
        $advurl,
        navigation_node::TYPE_SETTING,
        null,
        'local_pceinotifications_advanced_dashboard'
    );
}
