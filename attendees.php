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
 *
 * This page is a bit of a special case in this respect as there are four
 * different uses; all relating to the session attendees list however.
 *
 * 1) Viewing session attendee list
 *   - Requires mod/facetoface:viewattendees capability in the course
 *
 * 2) Viewing session cancellation list
 *   - Requires mod/facetoface:viewcancellations capability in the course
 *
 * 3) taking session attendance
 *   - requires mod/facetoface:takeattendance capabilities in the course
 *
 * 3) Approving or denying booking requests
 *   - requires mod/facetoface:takeattendance capabilities in the course
 *
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

$s = required_param('s', PARAM_INT); // Session ID.
$takeattendance = optional_param('takeattendance', false, PARAM_BOOL); // Take attendance.

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
$pageurl = new moodle_url('/mod/facetoface/attendees.php', array('s' => $session->id));
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_cm($cm);

$pagetitle = "{$course->shortname}: " . format_string($facetoface->name);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

require_course_login($course, true, $cm);

// Require at least one of the following capabilities.
$capabilities = array(
    'viewattendees'     => 'mod/facetoface:viewattendees',
    'takeattendance'    => 'mod/facetoface:takeattendance',
    'viewcancellations' => 'mod/facetoface:viewcancellations',
);
if (!has_any_capability($capabilities, $context)) {
    print_error('nopermissions', '', "{$CFG->wwwroot}/mod/facetoface/view.php?id={$cm->id}", get_string('view'));
}

// Logging and events trigger.
$logrecord = $DB->get_record('facetoface', array('id' => $facetoface->id), '*', MUST_EXIST);
$logparams = array(
    'context'  => $context,
    'objectid' => $session->id
);
$event = \mod_facetoface\event\attendees_viewed::create($logparams);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('facetoface', $logrecord);
$event->trigger();

// Output depending on whether or not attendance is being taken.
$sessionrenderer    = $PAGE->get_renderer('mod_facetoface', 'session');
$attendancerenderer = $PAGE->get_renderer('mod_facetoface', 'attendance');
if (!$takeattendance) {
    if ($formdata = data_submitted()) {
        require_capability('mod/facetoface:takeattendance', $context);
        $returnurl = new moodle_url('/mod/facetoface/attendees.php', array('s' => $s));
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }
        if (isset($formdata->requests) && !empty($formdata->requests)) {
            if ($facetoface->session_approve_requests($session, $formdata->requests)) {
                $event = \mod_facetoface\event\approve_requests::create($logparams);
                $event->add_record_snapshot('facetoface_sessions', $session);
                $event->add_record_snapshot('facetoface', $logrecord);
                $event->trigger();
            }
        }

        redirect($returnurl);
        exit;
    }

    // Render the more detailed session information view.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('facetofacesession', 'facetoface'));
    echo $sessionrenderer->session($facetoface, $session, $cm);

    // Session attendees list.
    require_capability('mod/facetoface:viewattendees', $context);
    echo $OUTPUT->heading(get_string('attendees', 'facetoface'));
    echo $attendancerenderer->session_attendees($facetoface, $session, $cm);

    // If there are booking requests show these.
    if (has_capability('mod/facetoface:takeattendance', $context)) {
        $requests = $facetoface->get_session_requests($session->id);
        if ($requests && !empty($requests)) {
            echo $OUTPUT->heading(get_string('unapprovedrequests', 'facetoface'));
            echo $attendancerenderer->session_requests($facetoface, $session, $cm, $requests);
        }
    }

    // If there are any cancellations display these.
    if (has_capability('mod/facetoface:viewcancellations', $context)) {
        $cancellations = $facetoface->get_session_cancellations($session);
        if ($cancellations && !empty($cancellations)) {
            echo $attendancerenderer->session_cancellations($facetoface, $session, $cm, $cancellations);
        }
    }

    echo $attendancerenderer->user_action_links($facetoface, $session, $takeattendance);
} else {
    require_capability('mod/facetoface:takeattendance', $context);
    if ($formdata = data_submitted()) {
        $returnurl = new moodle_url('/mod/facetoface/attendees.php', array('s' => $s));
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }
        if (isset($formdata->cancelform)) {
            redirect($returnurl);
            exit;
        }

        // Identify the booking IDs and attendance
        // values from the submitted form data.
        $submissionids = array();
        foreach ($formdata as $key => $value) {
            $keycheck = substr($key, 0, 13);
            if ($keycheck == 'submissionid_') {
                $id = substr($key, 13);
                $submissionids[$id] = $value;
            }
        }

        // Take attendance returning if there are errors or not.
        if (!empty($submissionids)) {
            $errors = $facetoface->take_attendance($session, $submissionids);
            if (empty($errors)) {
                $event = \mod_facetoface\event\take_attendance::create($logparams);
            } else {
                $event = \mod_facetoface\event\take_attendance_failed::create($logparams);
            }
            $event->add_record_snapshot('course_modules', $cm);
            $event->add_record_snapshot('course', $course);
            $event->add_record_snapshot('facetoface_sessions', $session);
            $event->add_record_snapshot('facetoface', $logrecord);
            $event->trigger();
        }

        // ToDo: alert to attendance errors?
        // ToDo: redirect back to take attendance page or normal listing?
        $returnurl->param('takeattendance', true);
        redirect($returnurl);
        exit;
    }

    // Render the more detailed session information view.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('facetofacesession', 'facetoface'));
    echo $sessionrenderer->session($facetoface, $session, $cm);


    // Make sure the session has already started to take attendance.
    echo $OUTPUT->heading(get_string('takeattendance', 'facetoface'));
    if ($session->datetimeknown && !$facetoface->has_session_started($session)) {
        echo $OUTPUT->notification(get_string('error:canttakeattendanceforunstartedsession', 'facetoface'));
    } else {
        echo $attendancerenderer->session_attendees($facetoface, $session, $cm, $takeattendance);
    }
}

echo $OUTPUT->footer($course);
