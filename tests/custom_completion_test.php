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
 * @covers \mod_facetoface\completion\custom_completion
 */
class custom_completion_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_completionattendance_disabled() {
        global $CFG;
        require_once("$CFG->dirroot/mod/facetoface/lib.php");

        /** @var \mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $facetoface = $generator->create_instance([
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionattendance' => 0]);
        $cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id);

        $this->setAdminUser();

        $cm = \cm_info::create($cm);
        $customcompletion = new custom_completion($cm, (int)$student->id);
        try {
            $customcompletion->get_state('completionattendance');
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertStringContainsString('error/Custom completion rule \'completionattendance\' is not used by this activity', $ex->getMessage());
        }
    }

    public function completionattendance_provider(): array {
        global $CFG, $DB;
        require_once("$CFG->dirroot/mod/facetoface/lib.php");

        return [
            'Full attendance' => [
                MDL_F2F_STATUS_FULLY_ATTENDED, COMPLETION_INCOMPLETE, COMPLETION_COMPLETE
            ],
            'Partial attendance' => [
                MDL_F2F_STATUS_PARTIALLY_ATTENDED, COMPLETION_COMPLETE, COMPLETION_COMPLETE
            ],
        ];
    }

    /**
     * Test completion.
     *
     * @dataProvider completionattendance_provider
     *
     * @param int $completionattendance
     * @param int $partiastate
     * @param int $fullstate
     * @return void
     */
    public function test_completionattendance($completionattendance, $partiastate, $fullstate) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/mod/facetoface/lib.php");

        /** @var \mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $facetoface = $generator->create_instance([
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionattendance' => $completionattendance]);
        $cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setAdminUser();

        $cm = \cm_info::create($cm);
        $customcompletion = new custom_completion($cm, (int)$student->id);
        $this->assertSame(COMPLETION_INCOMPLETE, $customcompletion->get_state('completionattendance'));

        $now = time();
        $session = $generator->create_session([
            'facetoface' => $facetoface->id,
            'sessiondates' => [
                ['timestart' => $now - 3 * DAYSECS, 'timefinish' => $now - 2 * DAYSECS]
            ]
        ]);

        $cm = \cm_info::create($cm);
        $customcompletion = new custom_completion($cm, (int)$student->id);
        $this->assertSame(COMPLETION_INCOMPLETE, $customcompletion->get_state('completionattendance'));

        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_BOTH, MDL_F2F_STATUS_BOOKED, $student->id, false);
        $signup = $DB->get_record('facetoface_signups', ['sessionid' => $session->id, 'userid' => $student->id], '*', MUST_EXIST);

        $cm = \cm_info::create($cm);
        $customcompletion = new custom_completion($cm, (int)$student->id);
        $this->assertSame(COMPLETION_INCOMPLETE, $customcompletion->get_state('completionattendance'));

        $result = facetoface_take_attendance((object)[
            's' => $session->id,
            'submissionid_' . $signup->id => MDL_F2F_STATUS_PARTIALLY_ATTENDED,
        ]);
        $this->assertTrue($result);

        $cm = \cm_info::create($cm);
        $customcompletion = new custom_completion($cm, (int)$student->id);
        $this->assertSame($partiastate, $customcompletion->get_state('completionattendance'));

        $result = facetoface_take_attendance((object)[
            's' => $session->id,
            'submissionid_' . $signup->id => MDL_F2F_STATUS_FULLY_ATTENDED,
        ]);
        $this->assertTrue($result);

        $cm = \cm_info::create($cm);
        $customcompletion = new custom_completion($cm, (int)$student->id);
        $this->assertSame($fullstate, $customcompletion->get_state('completionattendance'));
    }
}
