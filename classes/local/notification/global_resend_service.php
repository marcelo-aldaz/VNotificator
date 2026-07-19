<?php
/**
 * Service to resend consolidated notifications across courses.
 *
 * This service encapsulates the logic for building and sending manual
 * reminders to participants of courses. The current implementation
 * consolidates basic information about open novelties (cases), the
 * latest follow‑up status and the most recent risk level. It is
 * intentionally lightweight and avoids expensive calculations to
 * ensure acceptable performance on large sites.
 *
 * Messages are sent through Moodle's messaging API. Each user receives
 * at most one message per course. If a user is not enrolled in a
 * course, or if there is no data for the selected content types, no
 * message is sent. The method returns the total number of messages
 * dispatched.
 *
 * @package   local_pceinotifications
 * @copyright 2026
 */

namespace local_pceinotifications\local\notification;

defined('MOODLE_INTERNAL') || die();

use context_course;
use core\message\message;
use local_pceinotifications\local\analytics\followup_service;
use local_pceinotifications\util;

class global_resend_service {
    /**
     * Send consolidated reminders to users.
     *
     * @param int $courseid Course ID or 0 for all courses
     * @param string $recipient Recipients: 'students', 'teachers' or 'both'
     * @param array $types List of content types: pending, progress, followup, risk
     * @return int Number of messages sent
     */
    public function resend_notifications(int $courseid, string $recipient, array $types): int {
        global $DB, $CFG, $USER;

        // Ensure necessary libs are loaded. user/lib.php provides fullname() and
        // enrolment helpers. completionlib.php provides completion_info.
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->libdir . '/completionlib.php');

        // Build list of courses.
        $courses = [];
        if ($courseid > 0) {
            $course = $DB->get_record('course', ['id' => $courseid, 'visible' => 1], 'id,fullname', IGNORE_MISSING);
            if ($course) {
                $courses[$course->id] = $course;
            }
        } else {
            $courses = $DB->get_records_select('course', 'id > 1 AND visible = 1', null, 'fullname ASC', 'id,fullname');
        }
        if (!$courses) {
            return 0;
        }

        // Determine which roles qualify as teachers or students. This uses
        // Moodle's get_enrolled_users() and has_capability() checks. If no
        // custom teacher/student capability is found we fall back to role
        // assignment names. This approach trades completeness for safety.

        require_once($CFG->dirroot . '/user/lib.php');
        $sentcount = 0;
        $followupservice = new followup_service();

        foreach ($courses as $course) {
            $context = context_course::instance($course->id, IGNORE_MISSING);
            if (!$context) {
                continue;
            }
            // Get all enrolled users in this course.
            $enrolled = get_enrolled_users($context, '', 0, 'u.id,u.firstname,u.lastname,u.email');
            if (!$enrolled) {
                continue;
            }
            foreach ($enrolled as $user) {
                // Determine if user is teacher or student.
                $isstudent = has_capability('moodle/course:view', $context, $user->id) && !has_capability('moodle/course:update', $context, $user->id);
                $isteacher = has_capability('moodle/course:update', $context, $user->id);
                // Skip according to recipient filter.
                if ($recipient === 'students' && !$isstudent) {
                    continue;
                }
                if ($recipient === 'teachers' && !$isteacher) {
                    continue;
                }
                // Build message components depending on selected types.
                $parts = [];
                // Pending novelties: count open novelties for this user in this course.
                if (in_array('pending', $types)) {
                    $pendingcount = (int)$DB->count_records('local_pceinotif_novelty', ['courseid' => $course->id, 'userid' => $user->id, 'status' => 'open']);
                    if ($pendingcount > 0) {
                        if ($isstudent) {
                            $parts[] = get_string('resend_message_pending_student', 'local_pceinotifications', $pendingcount);
                        } else if ($isteacher) {
                            $parts[] = get_string('resend_message_pending_teacher', 'local_pceinotifications', $pendingcount);
                        }
                    }
                }
                // Progress: simple completion summary if completion is enabled.
                if (in_array('progress', $types)) {
                    // Check if completion is enabled for this course.
                    $completioninfo = new \completion_info($course);
                    if ($completioninfo->is_enabled()) {
                        $modinfo = get_fast_modinfo($course->id);
                        $activities = 0;
                        $done = 0;
                        foreach ($modinfo->get_cms() as $cm) {
                            if (!$cm->uservisible || !$completioninfo->is_enabled($cm)) {
                                continue;
                            }
                            $activities++;
                            $data = $completioninfo->get_data($cm, false, $user->id);
                            if ($data && (int)$data->completionstate === COMPLETION_COMPLETE) {
                                $done++;
                            }
                        }
                        if ($activities > 0) {
                            $pend = $activities - $done;
                            $parts[] = get_string('resend_message_progress', 'local_pceinotifications', [
                                'done' => $done,
                                'total' => $activities,
                                'pending' => $pend,
                            ]);
                        }
                    }
                }
                // Follow‑up: latest status.
                if (in_array('followup', $types)) {
                    $latest = $followupservice->get_latest_followup($course->id, $user->id);
                    if ($latest) {
                    // Map follow‑up status to existing language keys. The follow‑up
                    // status values are stored without a prefix; e.g. 'pending',
                    // 'inprogress' or 'attended'. The language strings use
                    // 'followup_pending', 'followup_inprogress' and
                    // 'followup_attended'.
                    $statuskey = 'followup_' . $latest->status;
                    $statusstr = get_string($statuskey, 'local_pceinotifications');
                    $parts[] = get_string('resend_message_followup', 'local_pceinotifications', $statusstr);
                    }
                }
                // Risk: current risk level (if any).
                if (in_array('risk', $types)) {
                    $risks = $DB->get_records('local_pceinotif_risk', ['courseid' => $course->id, 'userid' => $user->id], 'timecalculated DESC', '*', 0, 1);
                    if ($risks) {
                        $risk = reset($risks);
                        $riskstr = get_string('risk_' . $risk->risklevel, 'local_pceinotifications');
                        $parts[] = get_string('resend_message_risk', 'local_pceinotifications', $riskstr);
                    }
                }
                // If there is nothing to send, skip this user.
                if (empty($parts)) {
                    continue;
                }
                // Construct full message.
                $fullname = fullname($user);
                $coursename = format_string($course->fullname);
                // Build plain text and HTML bodies. HTML uses simple <p> tags.
                $plaintext = get_string('resend_message_greeting', 'local_pceinotifications', $fullname) . "\n";
                $htmltext  = '<p>' . format_text(get_string('resend_message_greeting', 'local_pceinotifications', $fullname), FORMAT_HTML) . '</p>';
                foreach ($parts as $p) {
                    $plaintext .= "\n- " . strip_tags($p);
                    $htmltext  .= '<p>' . format_text($p, FORMAT_HTML) . '</p>';
                }
                $plaintext .= "\n\n" . get_string('resend_message_signature', 'local_pceinotifications');
                $htmltext  .= '<p>' . format_text(get_string('resend_message_signature', 'local_pceinotifications'), FORMAT_HTML) . '</p>';
                // Construct message object.
                $msg = new message();
                $msg->courseid         = $course->id;
                $msg->component        = 'local_pceinotifications';
                $msg->name             = 'global_resend';
                $msg->userfrom         = $USER;
                $msg->userto           = $user;
                $msg->subject          = get_string('resend_message_subject', 'local_pceinotifications', $coursename);
                $msg->fullmessage      = $plaintext;
                $msg->fullmessageformat = FORMAT_PLAIN;
                $msg->fullmessagehtml  = $htmltext;
                $msg->smallmessage     = get_string('resend_message_small', 'local_pceinotifications');
                $msg->notification     = 1;
                // Send message. Ignore failures to continue processing others.
                message_send($msg);
                $sentcount++;
            }
        }
        return $sentcount;
    }
}