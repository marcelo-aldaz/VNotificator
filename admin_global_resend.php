<?php
/**
 * Global resend notifications page for VNotificator.
 *
 * This page provides a simple interface to send a consolidated reminder to
 * teachers, students or both across one or more courses. It allows an
 * administrator to trigger a manual resend of pending notifications,
 * progress summaries, follow‑ups and risk information. Messages are
 * consolidated per user and per course.
 *
 * The implementation here intentionally avoids any expensive analytics
 * processing. It uses existing data stored by the plugin (novelties,
 * follow‑ups and risk snapshots) to construct a short summary. It will
 * gracefully skip users or courses without data. All UI strings are
 * defined in the language packs (es, es_419 and en) to support Spanish
 * Latin American deployments.
 *
 * @package   local_pceinotifications
 * @copyright 2026
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');

// Require site administrator capability.
require_login();
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

// Optional parameters for the form.
$courseid = optional_param('courseid', 0, PARAM_INT);
$recipient = optional_param('recipient', 'both', PARAM_ALPHA);
$types    = optional_param_array('types', [], PARAM_ALPHAEXT);
$confirm  = optional_param('confirm', 0, PARAM_BOOL);

// Build page.
$PAGE->set_url(new moodle_url('/local/pceinotifications/admin_global_resend.php', ['courseid' => $courseid]));
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('globalresend', 'local_pceinotifications'));
$PAGE->set_heading(get_string('globalresend', 'local_pceinotifications'));

// Process submission.
if ($confirm && confirm_sesskey()) {
    require_once($CFG->dirroot . '/local/pceinotifications/classes/local/notification/global_resend_service.php');
    $service = new \local_pceinotifications\local\notification\global_resend_service();
    // Default to all types if none selected.
    if (empty($types)) {
        $types = ['pending', 'progress', 'followup', 'risk'];
    }
    $count = $service->resend_notifications($courseid, $recipient, $types);
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('globalresenddone', 'local_pceinotifications', $count), 'notifysuccess');
    echo $OUTPUT->continue_button(new moodle_url('/local/pceinotifications/admin_dashboard.php'));
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->header();
// Get list of courses for the select element. 0 means all courses.
$courses = [0 => get_string('filter_all', 'local_pceinotifications')];
foreach ($DB->get_records_select('course', 'id > 1', null, 'fullname ASC', 'id,fullname') as $c) {
    $courses[(int)$c->id] = format_string($c->fullname);
}

// Recipient options.
$recipientoptions = [
    'students' => get_string('recipients_students', 'local_pceinotifications'),
    'teachers' => get_string('recipients_teachers', 'local_pceinotifications'),
    'both'     => get_string('recipients_both', 'local_pceinotifications'),
];

// Types options (checkboxes).
$typeoptions = [
    'pending'  => get_string('content_pending', 'local_pceinotifications'),
    'progress' => get_string('content_progress', 'local_pceinotifications'),
    'followup' => get_string('content_followup', 'local_pceinotifications'),
    'risk'     => get_string('content_risk', 'local_pceinotifications'),
];

$styles = \local_pceinotifications\util::page_styles();
echo html_writer::tag('style', $styles . '
.vtn-admin-filters .form-select{min-height:56px;line-height:1.35;padding-top:.95rem;padding-bottom:.95rem;padding-right:2.8rem;font-size:1rem;white-space:normal;background-position:right .9rem center;}
.vtn-admin-filters .form-label{font-weight:700;color:#12344d;margin-bottom:.45rem;}
.vtn-admin-filters .col-lg-6{display:flex;flex-direction:column;}
.vtn-admin-filters .form-select option{white-space:normal;}
.vtn-admin-filters__hint{margin-top:.15rem;color:#5b7083;font-size:.88rem;}');

echo html_writer::start_div('vtn-shell');
echo html_writer::start_div('vtn-hero');
echo html_writer::tag('div', get_string('globalresend', 'local_pceinotifications'), ['class' => 'vtn-hero__title']);
echo html_writer::tag('p', get_string('globalresenddesc', 'local_pceinotifications'), ['class' => 'vtn-hero__text']);
echo html_writer::end_div();

// Build the form.
echo html_writer::start_div('vtn-card');
echo html_writer::start_div('vtn-card__body');
echo html_writer::tag('div', get_string('globalresendheading', 'local_pceinotifications'), ['class' => 'vtn-section-title']);
echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'vtn-filters vtn-admin-filters']);
echo html_writer::start_div('row g-3');
// Course select.
echo html_writer::start_div('col-lg-6 col-md-12');
echo html_writer::tag('label', get_string('choosecourse', 'local_pceinotifications'), ['for' => 'courseid', 'class' => 'form-label']);
echo html_writer::select($courses, 'courseid', $courseid, false, ['id' => 'courseid', 'class' => 'form-select']);
echo html_writer::div(get_string('choosecourse_desc', 'local_pceinotifications'), 'vtn-admin-filters__hint');
echo html_writer::end_div();
// Recipient select.
echo html_writer::start_div('col-lg-6 col-md-12');
echo html_writer::tag('label', get_string('recipient', 'local_pceinotifications'), ['for' => 'recipient', 'class' => 'form-label']);
echo html_writer::select($recipientoptions, 'recipient', $recipient, false, ['id' => 'recipient', 'class' => 'form-select']);
echo html_writer::div(get_string('recipient_desc', 'local_pceinotifications'), 'vtn-admin-filters__hint');
echo html_writer::end_div();
echo html_writer::end_div(); // row
// Types checkboxes.
echo html_writer::start_div('row g-3 mt-3');
echo html_writer::start_div('col-12');
echo html_writer::tag('label', get_string('content', 'local_pceinotifications'), ['class' => 'form-label']);
foreach ($typeoptions as $typevalue => $typelabel) {
    $checked = in_array($typevalue, $types);
    echo html_writer::start_div('form-check');
    echo html_writer::checkbox('types[' . $typevalue . ']', $typevalue, $checked, $typelabel, ['id' => 'type_' . $typevalue, 'class' => 'form-check-input']);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();
// Submit button.
echo html_writer::start_div('row g-3 mt-4');
echo html_writer::start_div('col-12');
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'confirm', 'value' => 1]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary me-2', 'value' => get_string('resend', 'local_pceinotifications')]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();