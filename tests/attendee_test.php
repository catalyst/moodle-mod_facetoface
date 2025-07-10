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

namespace mod_facetoface;

use mod_facetoface\booking_manager;
use lang_string;

/**
 * Test attendee related functions.
 *
 * @package    mod_facetoface
 * @author     Djarran Cotleanu <djarrancotleanu@catalyst-au.net>
 * @copyright  Catalyst IT, 2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_facetoface\booking_manager
 */
class attendee_test extends \advanced_testcase {

    /**
     * This method runs before every test.
     */
    public function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Test attendees are returned in alphabetical order.
     */
    public function test_attendees_sorted_alphabetically() {
        global $DB;

        /** @var \mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        // Setup course and participants.
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $generator->create_instance(['course' => $course->id]);
        $users = [
            $this->getDataGenerator()->create_and_enrol($course, 'student', ['firstname' => 'Charlie', 'lastname' => 'Brown']),
            $this->getDataGenerator()->create_and_enrol($course, 'student', ['firstname' => 'Alice', 'lastname' => 'Smith']),
            $this->getDataGenerator()->create_and_enrol($course, 'student', ['firstname' => 'Bob', 'lastname' => 'Jones'])
        ];

        // Create a session.
        $now = time();
        $session = $generator->create_session([
            'facetoface' => $facetoface->id,
            'capacity' => count($users),
            'allowoverbook' => '0',
            'sessiondates' => [
                ['timestart' => $now + 3 * DAYSECS, 'timefinish' => $now + 4 * DAYSECS],
            ],
        ]);

        // Sign up users for the session in non-alphabetical order.
        foreach ($users as $user) {
            facetoface_user_signup($session, $facetoface, $course, '',
                MDL_F2F_TEXT, MDL_F2F_STATUS_BOOKED, $user->id);
        }

        // Get attendees.
        $attendees = facetoface_get_attendees($session->id);

        // Verify sorting order.
        $this->assertCount(count($users), $attendees);
        $this->assertEquals('Alice', reset($attendees)->firstname);
        $this->assertEquals('Bob', next($attendees)->firstname);
        $this->assertEquals('Charlie', next($attendees)->firstname);
    }
}
