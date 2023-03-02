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

use core_date;

/**
 * Test the session helper class.
 *
 * @package    mod_facetoface
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_facetoface\session
 */
class session_test extends \advanced_testcase {

    private $starttime;

    /**
     * This method runs before every test.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $this->starttime = strtotime('01-01-2030 0900');
    }

    /**
     * Test getting session date.
     */
    public function test_get_readable_session_date_with_single_date() {
        $date = (object) [
            'timestart' => $this->starttime,
            'timefinish' => $this->starttime + 8 * HOURSECS,
        ];
        $expectedstring = '1 January 2030';
        $this->assertEquals($expectedstring, session::get_readable_session_date($date));
    }

    /**
     * Test getting session dates.
     */
    public function test_get_readable_session_date_with_multiple_date() {
        $date = (object) [
            'timestart' => $this->starttime,
            'timefinish' => $this->starttime + 80 * HOURSECS,
        ];
        $expectedstring = '1 January 2030 - 4 January 2030';
        $this->assertEquals($expectedstring, session::get_readable_session_date($date));
    }

    /**
     * Test getting session time.
     */
    public function test_get_readable_session_time() {
        $date = (object) [
            'timestart' => $this->starttime,
            'timefinish' => $this->starttime + 80 * HOURSECS,
        ];
        $expectedstring = '9:00 AM - 5:00 PM';
        $this->assertEquals($expectedstring, session::get_readable_session_time($date));
    }

    /**
     * Test getting full session date and time.
     */
    public function test_get_readable_session_datetime_with_single_date() {
        $date = (object) [
            'timestart' => $this->starttime,
            'timefinish' => $this->starttime + 8 * HOURSECS,
        ];
        $expectedstring = '1 January 2030, 9:00 AM - 1 January 2030, 5:00 PM';
        $this->assertEquals($expectedstring, session::get_readable_session_datetime($date));
    }

    /**
     * Test getting full session dates and times.
     */
    public function test_get_readable_session_time_with_multiple_date() {
        $date = (object) [
            'timestart' => $this->starttime,
            'timefinish' => $this->starttime + 80 * HOURSECS,
        ];
        $expectedstring = '1 January 2030, 9:00 AM - 4 January 2030, 5:00 PM';
        $this->assertEquals($expectedstring, session::get_readable_session_datetime($date));
    }

    /**
     * Test getting full session dates and times with user's timezone.
     */
    public function test_get_readable_session_time_with_users_timezone() {
        set_config( 'displaysessiontimezones', 1, 'facetoface');
        $date = (object) [
            'timestart' => $this->starttime,
            'timefinish' => $this->starttime + 80 * HOURSECS,
        ];
        $expectedtimezone = core_date::get_localised_timezone(core_date::get_user_timezone());
        $expectedstring = "1 January 2030, 9:00 AM - 4 January 2030, 5:00 PM (time zone: $expectedtimezone)";
        $this->assertEquals($expectedstring, session::get_readable_session_datetime($date));
    }
}
