<?php
namespace local_pceinotifications\task;
defined('MOODLE_INTERNAL') || die();

class evaluate_notification_rules extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('task_evaluate_rules', 'local_pceinotifications');
    }

    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');

        if (!(int)get_config('local_pceinotifications', 'enabled')) {
            return;
        }

        $warningdays = \local_pceinotifications\util::cfg_int('warningdays', 3, 1, 30);
        $courses = $DB->get_records_select('course', 'id > 1', [], 'id ASC', 'id,fullname');

        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);
            $students = get_enrolled_users($context, 'local/pceinotifications:receive',
                0,
                'u.id,u.firstname,u.lastname,u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename,u.email');
            if (empty($students)) {
                continue;
            }

            $modinfo = get_fast_modinfo($course);
            $completion = new \completion_info($course);

            foreach ($students as $u) {
                $pending = 0;
                $soon = 0;
                $overdue = 0;

                foreach ($modinfo->get_cms() as $cm) {
                    if (!$cm->uservisible || in_array($cm->modname, ['label', 'resource', 'url', 'page', 'folder', 'book'], true)) {
                        continue;
                    }

                    $due = 0;
                    if ($cm->modname === 'assign') {
                        if ($rec = $DB->get_record('assign', ['id' => $cm->instance], 'duedate,cutoffdate')) {
                            $due = !empty($rec->duedate) ? (int)$rec->duedate : (!empty($rec->cutoffdate) ? (int)$rec->cutoffdate : 0);
                        }
                    } else if ($cm->modname === 'quiz') {
                        if ($rec = $DB->get_record('quiz', ['id' => $cm->instance], 'timeclose')) {
                            $due = !empty($rec->timeclose) ? (int)$rec->timeclose : 0;
                        }
                    }

                    $completed = false;
                    if (!empty($cm->completion) && (int)$cm->completion > 0 && $completion->is_enabled()) {
                        $data = $completion->get_data($cm, false, $u->id);
                        $state = (int)$data->completionstate;
                        $completed = ($state === COMPLETION_COMPLETE || $state === COMPLETION_COMPLETE_PASS);
                    }

                    if (!$completed && $cm->modname === 'assign') {
                        $completed = $DB->record_exists_select(
                            'assign_submission',
                            'assignment = :a AND userid = :u AND status IN (:s1,:s2,:s3)',
                            ['a' => $cm->instance, 'u' => $u->id, 's1' => 'submitted', 's2' => 'reopened', 's3' => 'graded']
                        );
                    }

                    if ($completed) {
                        continue;
                    }

                    $pending++;
                    if (!empty($due)) {
                        if ($due < time()) {
                            $overdue++;
                        } else if (($due - time()) <= ($warningdays * DAYSECS)) {
                            $soon++;
                        }
                    }
                }

                $notiftype = '';
                $subject = '';
                $html = '';

                if ($overdue > 0) {
                    $notiftype = 'vencida:student';
                    $subject = 'Tiene actividades vencidas en ' . $course->fullname;
                    $html = '<p>Tiene <strong>' . $overdue . '</strong> actividad(es) vencida(s) en el curso <strong>' . format_string($course->fullname) . '</strong>.</p>';
                } else if ($soon > 0) {
                    $notiftype = 'proxima_vencer:student';
                    $subject = 'Tiene actividades próximas a vencer en ' . $course->fullname;
                    $html = '<p>Tiene <strong>' . $soon . '</strong> actividad(es) próxima(s) a vencer en el curso <strong>' . format_string($course->fullname) . '</strong>.</p>';
                } else if ($pending > 0) {
                    $notiftype = 'resumen_pendientes:student';
                    $subject = 'Resumen de actividades pendientes en ' . $course->fullname;
                    $html = '<p>Tiene <strong>' . $pending . '</strong> actividad(es) pendiente(s) en el curso <strong>' . format_string($course->fullname) . '</strong>.</p>';
                }

                if ($notiftype === '') {
                    continue;
                }

                $hash = sha1($subject . '|' . $notiftype . '|' . $course->id . '|' . $u->id . '|' . date('Y-m-d'));
                if ($DB->record_exists('local_pceinotif_log', [
                    'courseid' => $course->id,
                    'userid' => $u->id,
                    'notiftype' => $notiftype,
                    'messagehash' => $hash
                ])) {
                    continue;
                }

                $from = \core_user::get_noreply_user();
                $success = 0;
                $err = null;

                try {
                    if (!empty((int)get_config('local_pceinotifications', 'enableemail'))) {
                        $success = email_to_user($u, $from, $subject, html_to_text($html), $html) ? 1 : 0;
                    }
                    if (!empty((int)get_config('local_pceinotifications', 'enablepopup'))) {
                        $msg = new \core\message\message();
                        $msg->component = 'local_pceinotifications';
                        $msg->name = 'pcei_notice';
                        $msg->userfrom = $from;
                        $msg->userto = $u;
                        $msg->subject = $subject;
                        $msg->fullmessage = html_to_text($html);
                        $msg->fullmessageformat = FORMAT_HTML;
                        $msg->fullmessagehtml = $html;
                        $msg->smallmessage = $subject;
                        $msg->notification = 1;
                        $msg->contexturl = new \moodle_url('/course/view.php', ['id' => $course->id]);
                        $msg->contexturlname = $course->fullname;
                        message_send($msg);
                        $success = 1;
                    }
                } catch (\Throwable $e) {
                    $err = $e->getMessage();
                }

                $DB->insert_record('local_pceinotif_log', (object)[
                    'courseid' => $course->id,
                    'blockid' => 0,
                    'userid' => $u->id,
                    'notiftype' => $notiftype,
                    'subject' => $subject,
                    'messagehash' => $hash,
                    'success' => $success,
                    'errormsg' => $err,
                    'timesent' => time(),
                ]);
            }
        }
    }
}
