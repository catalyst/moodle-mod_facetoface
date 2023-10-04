<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_facetoface\completion;

/**
 * Test for attendance completion in facetoface.
 *
 * @package   mod_facetoface
 * @copyright 2023 Open LMS (https://www.openlms.net/)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_facetoface_generator
 */
class generator_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_create_instance() {
        /** @var \mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $course = $this->getDataGenerator()->create_course();

        $facetoface = $generator->create_instance([
            'course' => $course->id,
        ]);
        $this->assertInstanceOf(\stdClass::class, $facetoface);
        $cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id);

        $expected = [
            'id' => $facetoface->id,
            'course' => $course->id,
            'name' => 'Face-to-Face 1',
            'intro' => 'Test facetoface 1',
            'introformat' => '0',
            'thirdparty' => '',
            'thirdpartywaitlist' => '0',
            'display' => '0',
            'confirmationsubject' => '',
            'confirmationinstrmngr' => null,
            'confirmationmessage' => '',
            'confirmationmessageformat' => '1',
            'waitlistedsubject' => '',
            'waitlistedmessage' => '',
            'cancellationsubject' => '',
            'cancellationinstrmngr' => null,
            'cancellationmessage' => '',
            'remindersubject' => '',
            'reminderinstrmngr' => null,
            'remindermessage' => '',
            'reminderperiod' => '0',
            'requestsubject' => '',
            'requestinstrmngr' => '',
            'requestmessage' => '',
            'timecreated' => '0',
            'timemodified' => $facetoface->timemodified,
            'shortname' => 'testfacetoface',
            'showoncalendar' => '1',
            'approvalreqd' => '0',
            'usercalentry' => '1',
            'allowcancellationsdefault' => '1',
            'signuptype' => '0',
            'multiplesignupmethod' => '0',
            'completionattendance' => '0',
            'cmid' => (int)$cm->id,
        ];
        $this->assertSame($expected, (array)$facetoface);
    }

    public function test_create_session() {
        /** @var \mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $course = $this->getDataGenerator()->create_course();
        $facetoface = $generator->create_instance(['course' => $course->id]);

        $this->setCurrentTimeStart();
        $session1 = $generator->create_session([
            'facetoface' => $facetoface->id,
        ]);
        $this->assertInstanceOf(\stdClass::class, $session1);
        $this->assertSame($facetoface->id, $session1->facetoface);
        $this->assertSame('10', $session1->capacity);
        $this->assertSame('0', $session1->allowoverbook);
        $this->assertSame(null, $session1->details);
        $this->assertSame('1', $session1->datetimeknown);
        $this->assertSame(0, $session1->duration);
        $this->assertSame('0', $session1->normalcost);
        $this->assertSame('0', $session1->discountcost);
        $this->assertSame('1', $session1->allowcancellations);
        $this->assertTimeCurrent($session1->timecreated);
        $this->assertSame('0', $session1->timemodified);
        $this->assertIsArray($session1->sessiondates);
        $this->assertCount(1, $session1->sessiondates);
        $this->assertSame($session1->id, $session1->sessiondates[0]->sessionid);
        $this->assertSame(true, $session1->sessiondates[0]->timestart <= time());
        $this->assertSame(true, $session1->sessiondates[0]->timefinish - 7 < time() + 2 * DAYSECS);
        $this->assertSame(true, $session1->sessiondates[0]->timefinish + 7 > time() + 2 * DAYSECS);

        $now = time();
        $this->setCurrentTimeStart();
        $session2 = $generator->create_session([
            'facetoface' => $facetoface->id,
            'capacity' => '17',
            'allowoverbook' => '1',
            'details' => 'xyz',
            'duration' => '1.5', // One and half hours.
            'normalcost' => '111',
            'discountcost' => '11',
            'allowcancellations' => '0',
            'sessiondates' => [
                ['timestart' => $now - 3 * DAYSECS, 'timefinish' => $now - 2 * DAYSECS]
            ]
        ]);
        $this->assertInstanceOf(\stdClass::class, $session2);
        $this->assertSame($facetoface->id, $session2->facetoface);
        $this->assertSame('17', $session2->capacity);
        $this->assertSame('1', $session2->allowoverbook);
        $this->assertSame('xyz', $session2->details);
        $this->assertSame('1', $session2->datetimeknown);
        $this->assertSame('1:30', $session2->duration);
        $this->assertSame('111', $session2->normalcost);
        $this->assertSame('11', $session2->discountcost);
        $this->assertSame('0', $session2->allowcancellations);
        $this->assertTimeCurrent($session2->timecreated);
        $this->assertSame('0', $session2->timemodified);
        $this->assertIsArray($session2->sessiondates);
        $this->assertCount(1, $session2->sessiondates);
        $this->assertSame($session2->id, $session2->sessiondates[0]->sessionid);
        $this->assertSame((string)($now - 3 * DAYSECS), $session2->sessiondates[0]->timestart);
        $this->assertSame((string)($now - 2 * DAYSECS), $session2->sessiondates[0]->timefinish);
    }
}
