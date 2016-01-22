<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Copyright (C) 2007-2011 Catalyst IT (http://www.catalyst.net.nz)
 * Copyright (C) 2011-2013 Totara LMS (http://www.totaralms.com)
 * Copyright (C) 2014 onwards Catalyst IT (http://www.catalyst-eu.net)
 *
 * @package    mod
 * @subpackage facetoface
 * @copyright  2014 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @author     Alastair Munro <alastair.munro@totaralms.com>
 * @author     Aaron Barnes <aaron.barnes@totaralms.com>
 * @author     Francois Marier <francois@catalyst.net.nz>
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/facetoface/classes/facetoface.class.php');

$s = required_param('s', PARAM_INT); // Facetoface session ID.
$confirm = optional_param('confirm', false, PARAM_BOOL); // Form submission confirmation.

$fid = $DB->get_field('facetoface_sessions', 'facetoface', array('id' => $s));
if ($fid) {
    $facetoface = facetoface::get($fid);
    $course = get_course($facetoface->course);
    if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
        print_error('error:incorrectcoursemoduleid', 'facetoface');
    }
    if (!$session = $facetoface->get_session($s)) {
        print_error('error:incorrectcoursemodulesession', 'facetoface');
    }
} else {
    print_error('error:incorrectcoursemodulesession', 'facetoface');
}

$context = context_module::instance($cm->id);
$pageurl = new moodle_url('/mod/facetoface/cancelsignup.php', array('s' => $session->id, 'confirm' => $confirm));
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_cm($cm);

$pagetitle = "{$course->shortname}: " . format_string($facetoface->name);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

require_course_login($course, true, $cm);
require_capability('mod/facetoface:view', $context);

// Form definition and actions.
$mform     = new mod_facetoface_reason_form(null, compact('s'));
$returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $mform->get_data()) {
    if (empty($data->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'facetoface', $returnurl);
    }

    $success = $facetoface->cancel_user_booking($session, $data->reason);
    if ($success) {
        $message = get_string('bookingcancelled', 'facetoface');
        if ($session->datetimeknown) {
            // ToDo: implement cancellation notifications.
            //$notificationerrors = $facetoface->send_user_notification(...);
            /*if (empty($notificationerrors)) {
                if ($session->datetimeknown && $facetoface->cancellationinstrmngr) {
                    $message .= get_string('cancellationsentmgr', 'facetoface');
                } else {
                    $message .= get_string('cancellationsent', 'facetoface');
                }
            } else {
                print_error($error, 'facetoface');
            } */
        }
    }

    // Logging and events trigger data.
    $logrecord = $DB->get_record('facetoface', array('id' => $facetoface->id), '*', MUST_EXIST);
    $logparams = array(
        'context'  => $context,
        'objectid' => $session->id
    );

    // Could not cancel booking without errors.
    if (!$success) {
        $event = \mod_facetoface\event\cancel_booking_failed::create($logparams);
        $errorstr =  get_string('error:cancelbooking', 'facetoface');
        redirect($returnurl, $errorstr, 4);
        exit;
    } else {
        $event = \mod_facetoface\event\cancel_booking::create($logparams);
        redirect($returnurl, $message, 4);
    }

    // Trigger event.
    $event->add_record_snapshot('facetoface_sessions', $session);
    $event->add_record_snapshot('facetoface', $logrecord);
    $event->trigger();

    redirect($returnurl);
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('cancelbookingfor', 'facetoface', $facetoface->name));

$sessionrenderer = $PAGE->get_renderer('mod_facetoface', 'session');
echo $sessionrenderer->session($facetoface, $session);

// If the user has an active booking show the cancellation form
// otherwise a notificiation that they cannot cancel.
$submission = $facetoface->get_user_current_booking_submission();
if ($submission && $submission->sessionid == $session->id) {

    // Cancellation warning and form.
    echo $OUTPUT->heading(get_string('cancelbooking', 'facetoface'));
    echo $OUTPUT->notification(get_string('cancellationconfirm', 'facetoface'));
    $mform->display();
} else {
    echo $OUTPUT->notification(get_string('notsignedup', 'facetoface'));
    echo html_writer::link($returnurl, get_string('viewallsessions', 'facetoface'));
}

echo $OUTPUT->footer($course);
