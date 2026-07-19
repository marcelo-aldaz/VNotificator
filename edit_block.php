<?php
require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');

$courseid = required_param('id', PARAM_INT);
$blockid  = required_param('blockid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/pceinotifications:manage', $context);

$block = $DB->get_record('local_pceinotif_blocks', ['id' => $blockid, 'courseid' => $courseid], '*', MUST_EXIST);

class pcei_block_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $block = $this->_customdata['block'];

        $mform->addElement('hidden', 'id', $this->_customdata['courseid']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'blockid', $block->id);
        $mform->setType('blockid', PARAM_INT);

        $mform->addElement('static', 'sectionname', get_string('col_section', 'local_pceinotifications'),
            format_string($block->sectionname));

        $mform->addElement('select', 'blocktype', get_string('col_type', 'local_pceinotifications'), [
            'atpa' => get_string('type_atpa', 'local_pceinotifications'),
            'tei' => get_string('type_tei', 'local_pceinotifications'),
            'other' => get_string('type_other', 'local_pceinotifications'),
        ]);

        $mform->addElement('date_selector', 'startdate', get_string('col_start', 'local_pceinotifications'));
        $mform->addElement('date_selector', 'enddate', get_string('col_end', 'local_pceinotifications'));

        $mform->addElement('text', 'bbbcmid', get_string('bbbcmid_label', 'local_pceinotifications'), ['size' => 10]);
        $mform->setType('bbbcmid', PARAM_INT);

        $this->add_action_buttons(true, get_string('save_changes', 'local_pceinotifications'));
    }

    public function validation($data, $files) {
        $errors = [];
        if (!empty($data['startdate']) && !empty($data['enddate']) && (int)$data['enddate'] < (int)$data['startdate']) {
            $errors['enddate'] = get_string('error_end_before_start', 'local_pceinotifications');
        }
        if (!empty($data['bbbcmid']) && (int)$data['bbbcmid'] < 0) {
            $errors['bbbcmid'] = get_string('error_bbbcmid_invalid', 'local_pceinotifications');
        }
        return $errors;
    }
}

$form = new pcei_block_form(null, ['courseid' => $courseid, 'block' => $block]);

$form->set_data((object)[
    'id' => $courseid,
    'blockid' => $blockid,
    'blocktype' => $block->blocktype,
    'startdate' => $block->startdate ?: time(),
    'enddate' => $block->enddate ?: time(),
    'bbbcmid' => (int)$block->bbbcmid,
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/pceinotifications/course.php', ['id' => $courseid]));
} else if ($data = $form->get_data()) {
    $block->blocktype = $data->blocktype;
    $block->startdate = (int)$data->startdate;
    $block->enddate = (int)$data->enddate;
    $block->bbbcmid = (int)$data->bbbcmid;
    $block->blockstate = !empty($block->startdate) ? 'notification_ready' : 'configured';
    $block->syncnote = !empty($block->startdate) ? 'Bloque configurado y listo para notificación.' : 'Bloque configurado; falta fecha de inicio para notificación.';
    $block->calendarstatus = 'pending';
    $block->calendarnote = !empty($block->startdate) ? 'Pendiente de sincronización con calendario.' : 'Falta fecha de inicio para crear evento.';
    $block->calendarupdated = time();
    $block->timemodified = time();
    $DB->update_record('local_pceinotif_blocks', $block);

    redirect(new moodle_url('/local/pceinotifications/course.php', ['id' => $courseid]),
        get_string('block_saved', 'local_pceinotifications'));
}

$PAGE->set_url('/local/pceinotifications/edit_block.php', ['id' => $courseid, 'blockid' => $blockid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('edit_block', 'local_pceinotifications'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('edit_block', 'local_pceinotifications'));
$form->display();
echo $OUTPUT->footer();
