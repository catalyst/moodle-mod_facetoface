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

    /**
     * Test facetoface_enrol_user function
     */
    public function test_facetoface_enrol_user(): void {
        global $DB;

        $this->resetAfterTest();

        // Setup course.
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        // Test student that is already enrolled.
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $result = facetoface_enrol_user($context, $course->id, $student1->id);
        $this->assertTrue($result);
        $this->assertTrue(is_enrolled($context, $student1->id));

        // Test student that isn't enrolled.
        $student2 = $this->getDataGenerator()->create_user();
        $result = facetoface_enrol_user($context, $course->id, $student2->id);
        $this->assertTrue($result);
        $this->assertTrue(is_enrolled($context, $student2->id));

        // Test admin user with moodle/course:view capability.
        $admin = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign('manager', $admin->id);
        $result = facetoface_enrol_user($context, $course->id, $admin->id);
        $this->assertTrue($result);
        $this->assertTrue(is_enrolled($context, $admin->id));

        // Test student that isn't enrolled and enrol_try_internal_enrol fails
        // when manual enrol is disabled.
        $manualinstance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, ['id' => $manualinstance->id]);

        $student3 = $this->getDataGenerator()->create_user();
        $result = facetoface_enrol_user($context, $course->id, $student3->id);
        $this->assertFalse($result);
        $this->assertFalse(is_enrolled($context, $student3->id));
    }
}
