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

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
$f  = optional_param('f', 0, PARAM_INT); // Facetoface ID.
$location = optional_param('location', null, PARAM_TEXT); // Location.
$download = optional_param('download', '', PARAM_ALPHA); // Download attendance.

if ($id) {
    if (!$cm = get_coursemodule_from_id('facetoface', $id)) {
        print_error('error:incorrectcoursemoduleid', 'facetoface');
    }
    $facetoface = facetoface::get($cm->instance);
    $course = get_course($facetoface->course);
} else if ($f) {
    $facetoface = facetoface::get($f);
    $course = get_course($facetoface->course);
    if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
        print_error('error:incorrectcoursemoduleid', 'facetoface');
    }
} else {
    print_error('error:mustspecifycoursemodulefacetoface', 'facetoface');
}

$context = context_module::instance($cm->id);
$pageurl = new moodle_url('/mod/facetoface/view.php', array('id' => $cm->id));
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_cm($cm);

$pagetitle = "{$course->shortname}: " . format_string($facetoface->name);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->set_button(update_module_button($cm->id, '', get_string('modulename', 'facetoface')));

require_capability('mod/facetoface:view', $context);
require_course_login($course, true, $cm);

// Exporting attendance.
if (!empty($download)) {
    require_capability('mod/facetoface:viewattendees', $context);
    $facetoface->export_attendance($download, $location);
    exit;
}

// Logging and events trigger.
$logrecord = $DB->get_record('facetoface', array('id' => $facetoface->id), '*', MUST_EXIST);
$params = array(
    'context'  => $context,
    'objectid' => $facetoface->id
);
$event = \mod_facetoface\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('facetoface', $logrecord);
$event->trigger();

// Module completion call.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

if (empty($cm->visible) and !has_capability('mod/facetoface:viewemptyactivities', $context)) {
    echo $OUTPUT->header();
    notice(get_string('activityiscurrentlyhidden'));
    echo $OUTPUT->footer($course);
    exit;
}
$renderer = $PAGE->get_renderer('mod_facetoface');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('allsessionsin', 'facetoface', $facetoface->name), 2);
echo $renderer->introduction($facetoface, $cm);

// Add new session link.
$sessionrenderer = $PAGE->get_renderer('mod_facetoface', 'session');
echo $sessionrenderer->add_session_link($facetoface, $cm);

// Session location filter.
$locations = $facetoface->get_locations();
if (!empty($locations)) {
    echo $renderer->location_filter($facetoface, $locations, $location);
}

// Session listing.
$sessions = $facetoface->get_sessions_list($location);
$sortedsessions = $facetoface->sort_sessions_list($sessions);
echo $sessionrenderer->view_sessions_list($facetoface, $sortedsessions, $cm);

// Attendance export form.
if (!empty($sortedsessions)) {
    $attendancerenderer = $PAGE->get_renderer('mod_facetoface', 'attendance');
    echo $attendancerenderer->attendees_export_form($facetoface, $cm, $location);
}

echo $OUTPUT->footer($course);
