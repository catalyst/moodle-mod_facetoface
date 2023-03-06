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

/**
 * Test the plugin helper functions.
 *
 * @package    mod_facetoface
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper_test extends \advanced_testcase {

    /**
     * This method runs before every test.
     */
    public function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Test manager approval not required if approvals are disabled for plugin.
     */
    public function test_is_approval_required_when_disabled_for_plugin() {
        set_config('enableapprovals', '0', 'facetoface');
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->get_plugin_generator('mod_facetoface')->create_instance([
            'course' => $course,
            'approvalreqd' => 1,
        ]);
        $this->assertFalse(helper::is_approval_required($activity));
    }

    /**
     * Test manager approval not required if approvals are disabled for activity.
     */
    public function test_is_approval_required_when_disabled_for_activity() {
        set_config('enableapprovals', '1', 'facetoface');
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->get_plugin_generator('mod_facetoface')->create_instance([
            'course' => $course,
            'approvalreqd' => 0,
        ]);
        $this->assertFalse(helper::is_approval_required($activity));
    }

    /**
     * Test manager approval is required if approvals are enabled for plugin and activity.
     */
    public function test_is_approval_required_when_enabled() {
        set_config('enableapprovals', '1', 'facetoface');
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->get_plugin_generator('mod_facetoface')->create_instance([
            'course' => $course,
            'approvalreqd' => 1,
        ]);
        $this->assertTrue(helper::is_approval_required($activity));
    }
}
