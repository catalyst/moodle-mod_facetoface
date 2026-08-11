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
 * Page to show users bookings across the site.
 *
 * @package     mod_facetoface
 * @author      Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright   2026 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_facetoface\my_bookings_table;

require_login(null, false);

$userid = optional_param('userid', $USER->id, PARAM_INT);
$sessiontype = optional_param('sessiontype', my_bookings_table::UPCOMING, PARAM_TEXT);

// Check that we have a valid user.
$user = core_user::get_user($userid, '*', MUST_EXIST);
$context = context_user::instance($user->id);

// If we are viewing certificates that are not for the currently logged in user then do a capability check.
if (($user->id != $USER->id) && !has_capability('mod/facetoface:viewuserbookings', $context)) {
    throw new moodle_exception('You are not allowed to view these bookings');
}

$sessiontypes = [
    my_bookings_table::UPCOMING,
    my_bookings_table::PREVIOUS,
    my_bookings_table::WAITLISTED,
];

if (!in_array($sessiontype, $sessiontypes)) {
    // Invalid session type, redirect to default page.
    redirect(new moodle_url('/mod/facetoface/my_bookings.php'));
}

$url = new moodle_url('/mod/facetoface/my_bookings.php', ['userid' => $user->id, 'sessiontype' => $sessiontype]);

$title = get_string('mybookings', 'mod_facetoface');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('profile'), new moodle_url('/user/profile.php'));
$PAGE->navbar->add(get_string('mybookings', 'facetoface'));

echo $OUTPUT->header();

$upcoming = new moodle_url('/mod/facetoface/my_bookings.php', [
    'userid' => $user->id,
    'sessiontype' => my_bookings_table::UPCOMING,
]);
$previous = new moodle_url('/mod/facetoface/my_bookings.php', [
    'userid' => $user->id,
    'sessiontype' => my_bookings_table::PREVIOUS,
]);
$waitlisted = new moodle_url('/mod/facetoface/my_bookings.php', [
    'userid' => $user->id,
    'sessiontype' => my_bookings_table::WAITLISTED,
]);
$options = [
    $upcoming->out(false)   => get_string('upcomingsessions', 'facetoface'),
    $previous->out(false)   => get_string('previoussessions', 'facetoface'),
    $waitlisted->out(false) => get_string('wait-listonlysessions', 'facetoface'),
];
$selectmenu = new core\output\select_menu('sessiontype', $options, $url->out(false));
$selectmenu->set_label(get_string('sessiontype', 'facetoface'), ['class' => 'sr-only']);
$tertiarynav = html_writer::tag(
    'div',
    $OUTPUT->render_from_template('core/tertiary_navigation_selector', $selectmenu->export_for_template($OUTPUT)),
    ['class' => 'navitem']
);
echo html_writer::div(
    $tertiarynav,
    'tertiary-navigation full-width-bottom-border',
    ['id' => 'tertiary-navigation']
);

$table = new mod_facetoface\my_bookings_table($user, $url, $sessiontype);
$table->out(20, false);

echo $OUTPUT->footer();
