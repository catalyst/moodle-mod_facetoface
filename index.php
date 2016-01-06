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

$id = required_param('id', PARAM_INT); // Course ID.

$course = get_course($id);
require_course_login($course);
$context = context_course::instance($course->id);
require_capability('mod/facetoface:view', $context);

// Logging and events trigger course viewed.
$params = array(
    'context'  => $context,
    'objectid' => $course->id
);
$event = \mod_facetoface\event\course_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_url('/mod/facetoface/index.php', array('id' => $course->id));
$PAGE->set_title("{$course->fullname}: " . get_string('modulename', 'facetoface'));
$PAGE->set_heading("{$course->fullname}: " . get_string('modulename', 'facetoface'));

echo $OUTPUT->header();
echo $OUTPUT->heading("{$course->fullname}: " . get_string('modulename', 'facetoface'));

// Fetch and render all instances in the course.
$instances = get_all_instances_in_course('facetoface', $course);
if ($instances) {
    $renderer = $PAGE->get_renderer('mod_facetoface');
    echo $renderer->index_table($course, $instances);
} else {
    $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
    notice(get_string('nofacetofaces', 'facetoface'), $courseurl);
}

echo $OUTPUT->footer($course);
