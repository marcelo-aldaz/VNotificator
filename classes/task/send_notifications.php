<?php
namespace local_pceinotifications\task;

defined('MOODLE_INTERNAL') || die();

class send_notifications extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('task_send_notifications', 'local_pceinotifications');
    }

    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/local/pceinotifications/locallib.php');
        require_once($CFG->libdir . '/completionlib.php');

        if (!(int)get_config('local_pceinotifications', 'enabled')) {
            \local_pceinotifications\util::log_debug('Motor deshabilitado.');
            return;
        }
        if (!\local_pceinotifications\util::within_sendhour()) {
            return;
        }

        $daysbefore = (int)get_config('local_pceinotifications', 'daysbefore');
        if ($daysbefore < 0 || $daysbefore > 30) $daysbefore = 2;

        $todaystart = (int)\local_pceinotifications\util::day_start(time());
        $todayend   = (int)\local_pceinotifications\util::day_end(time());
        $prevstart  = (int)\local_pceinotifications\util::day_start(time() + ($daysbefore * DAYSECS));
        $prevend    = (int)\local_pceinotifications\util::day_end(time() + ($daysbefore * DAYSECS));

        $blocks = $DB->get_records_select('local_pceinotif_blocks',
            'startdate > 0 AND blocktype IN (\'atpa\',\'tei\')',
            [], 'courseid ASC, sequenceindex ASC');

        $bycourse = [];
        foreach ($blocks as $b) $bycourse[$b->courseid][] = $b;

        foreach ($bycourse as $courseid => $courseblocks) {
            $course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
            if (!$course) continue;

            $context = \context_course::instance($courseid);
            $users = get_enrolled_users($context, 'local/pceinotifications:receive');
            if (empty($users)) continue;

            $modinfo = get_fast_modinfo($course);
            $completion = new \completion_info($course);

            foreach ($courseblocks as $block) {
                $types = [];
                if ($block->startdate >= $prevstart && $block->startdate <= $prevend) $types[] = 'previo';
                if ($block->startdate >= $todaystart && $block->startdate <= $todayend) $types[] = 'hoy';
                if (empty($types)) continue;

                foreach ($types as $notiftype) {
                    foreach ($users as $u) {
                        if ($DB->record_exists('local_pceinotif_log', [
                            'courseid' => $courseid, 'blockid' => $block->id, 'userid' => $u->id, 'notiftype' => $notiftype
                        ])) continue;

                        try {
                            $payload = $this->build_message($course, $block, $u, $notiftype, $modinfo, $completion, $daysbefore);
                            $success = $this->dispatch($u, $payload['subject'], $payload['html'], $courseid);
                            $this->log_send($courseid, $block->id, $u->id, $notiftype, $payload['subject'], $payload['html'], $success ? 1 : 0, null);
                            if ($success) {
                                $block->blockstate = 'notified';
                                $block->syncnote = 'Notificación enviada correctamente.';
                                $DB->update_record('local_pceinotif_blocks', $block);
                            }
                        } catch (\Throwable $e) {
                            $this->log_send($courseid, $block->id, $u->id, $notiftype, '', '', 0, $e->getMessage());
                            $block->blockstate = 'error';
                            $block->syncnote = 'Error: ' . $e->getMessage();
                            $DB->update_record('local_pceinotif_blocks', $block);
                        }
                    }
                }
            }
        }
    }

    private function build_message($course, $block, $user, $notiftype, $modinfo, $completion, int $daysbefore): array {
        global $DB;

        $fecha = userdate($block->startdate, get_string('strftimedatefullshort', 'langconfig'));
        $nombre = fullname($user);

        $section = $DB->get_record('course_sections', ['id' => $block->sectionid], 'id,section,name', MUST_EXIST);
        $sectionnum = (int)$section->section;

        $tag = ($notiftype === 'hoy') ? 'HOY' : ('EN ' . $daysbefore . ' DÍAS');

        if ($block->blocktype === 'atpa') {
            $subject = "[{$course->shortname}] ATPA {$tag} - {$fecha}";
            $html = $this->tpl_atpa($course, $block, $nombre, $fecha, $tag);
        } else {
            $subject = "[{$course->shortname}] TEI {$tag} - Actividades";
            $pend = $this->get_pending_by_section($course, $user->id, $sectionnum, $modinfo, $completion);
            $html = $this->tpl_tei($course, $block, $nombre, $fecha, $tag, $pend);
        }

        return ['subject' => $subject, 'html' => $html];
    }

    private function get_pending_by_section($course, int $userid, int $sectionnum, $modinfo, $completion): array {
        global $DB;
        if (!$completion->is_enabled()) return [];
        $pending = [];

        foreach ($modinfo->cms as $cm) {
            if (!$cm->uservisible) continue;
            if ((int)$cm->sectionnum !== $sectionnum) continue;
            if (empty($cm->completion)) continue;

            $data = $completion->get_data($cm, false, $userid);
            if ((int)$data->completionstate === COMPLETION_INCOMPLETE) {
                $mod = $DB->get_record('modules', ['id' => $cm->module], 'name', IGNORE_MISSING);
                $modname = $mod ? $mod->name : 'actividad';
                $url = (string)(new \moodle_url('/mod/' . $modname . '/view.php', ['id' => $cm->id]));
                $pending[] = ['name' => $cm->name, 'modname' => $modname, 'url' => $url];
            }
        }
        return $pending;
    }

    private function dispatch($user, string $subject, string $html, int $courseid): bool {
        $from = \core_user::get_noreply_user();
        $enableemail = (int)get_config('local_pceinotifications', 'enableemail');
        $enablepopup = (int)get_config('local_pceinotifications', 'enablepopup');

        $ok = true;

        if ($enableemail) {
            $txt = html_to_text($html);
            $emailok = email_to_user($user, $from, $subject, $txt, $html);
            $ok = $ok && (bool)$emailok;
        }

        if ($enablepopup) {
            $msg = new \core\message\message();
            $msg->component = 'local_pceinotifications';
            $msg->name = 'pcei_notice';
            $msg->userfrom = $from;
            $msg->userto = $user;
            $msg->subject = $subject;
            $msg->fullmessage = html_to_text($html);
            $msg->fullmessageformat = FORMAT_HTML;
            $msg->fullmessagehtml = $html;
            $msg->smallmessage = $subject;
            $msg->notification = 1;
            $msg->contexturl = new \moodle_url('/course/view.php', ['id' => $courseid]);
            $msg->contexturlname = $subject;
            message_send($msg);
        }

        return $ok;
    }

    private function log_send(int $courseid, int $blockid, int $userid, string $notiftype, string $subject, string $html, int $success, ?string $err): void {
        global $DB;
        $hash = sha1($subject . '|' . $html);
        $DB->insert_record('local_pceinotif_log', (object)[
            'courseid' => $courseid,
            'blockid' => $blockid,
            'userid' => $userid,
            'notiftype' => $notiftype,
            'subject' => $subject,
            'messagehash' => $hash,
            'success' => $success,
            'errormsg' => $err,
            'timesent' => time(),
        ]);
    }

    private function tpl_atpa($course, $block, $nombre, $fecha, $tag): string {
        $urlcourse = (new \moodle_url('/course/view.php', ['id' => $course->id]))->out();
        $desc = $block->sectionname ? s($block->sectionname) : '';
        $bbb = '';
        if (!empty($block->bbbcmid)) {
            $bbburl = (new \moodle_url('/mod/bigbluebuttonbn/view.php', ['id' => (int)$block->bbbcmid]))->out();
            $bbb = "<div style='text-align:center;margin:16px 0'>
              <a href='{$bbburl}' style='background:#1a4f7a;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none'>Ingresar a BBB</a>
            </div>";
        }

        return "<div style='font-family:Segoe UI,Arial,sans-serif;max-width:640px;margin:0 auto;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden'>
          <div style='background:#1a4f7a;padding:18px 22px'>
            <div style='color:#b7d5f0;font-size:11px;letter-spacing:1px;text-transform:uppercase'>UE PCEI NUEVO AMANECER</div>
            <div style='color:#fff;font-size:20px;font-weight:700;margin-top:6px'>ATPA / Sesión sincrónica — {$tag}</div>
            <div style='color:#b7d5f0;font-size:13px;margin-top:4px'>Fecha: <strong>{$fecha}</strong></div>
          </div>
          <div style='padding:20px 22px;color:#111827'>
            <p>Hola <strong>{$nombre}</strong>,</p>
            <p>Recordatorio institucional de su sesión sincrónica ATPA.</p>
            <p><strong>Curso:</strong> ".s($course->fullname)."</p>
            <p><strong>Bloque:</strong> {$desc}</p>
            {$bbb}
            <p style='margin-top:18px'><a href='{$urlcourse}' style='color:#1a4f7a'>Ver curso en Moodle</a></p>
            <hr style='border:none;border-top:1px solid #e5e7eb;margin:18px 0'>
            <p style='font-size:12px;color:#6b7280;margin:0'>Mensaje automático · PCEI Nuevo Amanecer</p>
          </div></div>";
    }

    private function tpl_tei($course, $block, $nombre, $fecha, $tag, array $pend): string {
        $urlcourse = (new \moodle_url('/course/view.php', ['id' => $course->id]))->out();
        $desc = $block->sectionname ? s($block->sectionname) : '';

        if (!empty($pend)) {
            $list = "<div style='background:#fff7ed;border:1px solid #fdba74;border-radius:10px;padding:12px 14px;margin:14px 0'>
              <div style='font-weight:700;color:#9a3412;margin-bottom:8px'>Actividades pendientes (esta sección TEI):</div>
              <ul style='margin:0;padding-left:18px'>";
            foreach ($pend as $a) {
                $list .= "<li style='margin:6px 0'><a href='".s($a['url'])."' style='color:#1a4f7a'>".s($a['name'])."</a>
                <span style='color:#6b7280;font-size:11px'>(".s($a['modname']).")</span></li>";
            }
            $list .= "</ul></div>";
        } else {
            $list = "<p style='color:#166534;background:#ecfdf5;border:1px solid #bbf7d0;border-radius:10px;padding:10px 12px'>No registra pendientes en esta sección. ¡Siga adelante!</p>";
        }

        return "<div style='font-family:Segoe UI,Arial,sans-serif;max-width:640px;margin:0 auto;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden'>
          <div style='background:#166534;padding:18px 22px'>
            <div style='color:#bbf7d0;font-size:11px;letter-spacing:1px;text-transform:uppercase'>UE PCEI NUEVO AMANECER</div>
            <div style='color:#fff;font-size:20px;font-weight:700;margin-top:6px'>TEI — {$tag}</div>
            <div style='color:#bbf7d0;font-size:13px;margin-top:4px'>Inicio: <strong>{$fecha}</strong></div>
          </div>
          <div style='padding:20px 22px;color:#111827'>
            <p>Hola <strong>{$nombre}</strong>,</p>
            <p>Inicia su fase TEI (Trabajo Estudiantil Independiente).</p>
            <p><strong>Curso:</strong> ".s($course->fullname)."</p>
            <p><strong>Bloque:</strong> {$desc}</p>
            {$list}
            <div style='text-align:center;margin:16px 0'>
              <a href='{$urlcourse}' style='background:#166534;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none'>Ir al curso en Moodle</a>
            </div>
            <hr style='border:none;border-top:1px solid #e5e7eb;margin:18px 0'>
            <p style='font-size:12px;color:#6b7280;margin:0'>Mensaje automático · PCEI Nuevo Amanecer</p>
          </div></div>";
    }
}
