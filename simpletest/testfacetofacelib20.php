<?php

// Face-to-face module for Moodle
//
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

defined('MOODLE_INTERNAL') || die();

/**
* Unit tests for mod/facetoface/lib.php
*
* @author Alastair Munro <alastair.munro@totaralms.com>
*/
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

class quiz_lib_test extends UnitTestCase {
    public static $includecoverage = array('mod/quiz/lib.php');


    public function test_facetoface_get_status() {
        // test method - returns string

        // check for valid status codes
        $this->assertEqual(facetoface_get_status(10), 'user_cancelled');
        // SESSION_CANCELLED is not yet implemented
        //$this->assertEqual(facetoface_get_status(20), 'session_cancelled');
        $this->assertEqual(facetoface_get_status(30), 'declined');
        $this->assertEqual(facetoface_get_status(40), 'requested');
        $this->assertEqual(facetoface_get_status(50), 'approved');
        $this->assertEqual(facetoface_get_status(60), 'waitlisted');
        $this->assertEqual(facetoface_get_status(70), 'booked');
        $this->assertEqual(facetoface_get_status(80), 'no_show');
        $this->assertEqual(facetoface_get_status(90), 'partially_attended');
        $this->assertEqual(facetoface_get_status(100), 'fully_attended');
        //TODO error capture
        //check for invalid status code
//        $this->expectError(facetoface_get_status(17));
//        $this->expectError(facetoface_get_status('b'));
//        $this->expectError(facetoface_get_status('%'));
    }


    public function test_format_cost() {
        // test method - returns a string
        // test each method with the html parameter as true/ false /null

        //test for a valid value
        $this->assertEqual(format_cost(1000, true), '$1000');
        $this->assertEqual(format_cost(1000, false), '$1000');
        $this->assertEqual(format_cost(1000), '$1000');

        //test for a large negative value, html true/ false/ null
        $this->assertEqual(format_cost(-34000, true), '$-34000');
        $this->assertEqual(format_cost(-34000, false), '$-34000');
        $this->assertEqual(format_cost(-34000), '$-34000');

        //test for a large positive value
        $this->assertEqual(format_cost(100000000000, true), '$100000000000');
        $this->assertEqual(format_cost(100000000000, false), '$100000000000');
        $this->assertEqual(format_cost(100000000000), '$100000000000');

        //test for a decimal value
        $this->assertEqual(format_cost(32768.9045, true), '$32768.9045');
        $this->assertEqual(format_cost(32768.9045, false), '$32768.9045');
        $this->assertEqual(format_cost(32768.9045), '$32768.9045');

        //test for a null value
        $this->assertEqual(format_cost(null, true), '$');
        $this->assertEqual(format_cost(null, false), '$');
        $this->assertEqual(format_cost(null), '$');

        //test for a text string value
        $this->assertEqual(format_cost('string', true), '$string');
        $this->assertEqual(format_cost('string', false), '$string');
        $this->assertEqual(format_cost('string'), '$string');
    }

    public function test_facetoface_cost() {
        //test method - returns format_cost object

        //test variables case WITH discount
        /*$sessiondata1 = $this->sessiondata[0];

        $userid1 = 1;
        $sessionid1 = 1;

        $htmloutput1 = false; // forced to true in the function

        //variable for test case NO discount
        $sessiondata2 = $this->sessiondata[1];

        $userid2 = 2;
        $sessionid2 = 2;

        $htmloutput2 = false;

        //test WITH discount
        $this->assertEqual(facetoface_cost($userid1, $sessionid1, $sessiondata1, $htmloutput1), '$60');

        //test NO discount case
        $this->assertEqual(facetoface_cost($userid2, $sessionid2, $sessiondata2, $htmloutput2), '$90');*/
    }

    public function test_format_duration() {
        // test method - returns a string
        // ISSUES:
        //expects a space after hour/s but not minute/s
        //minutes > 59 are not being converted to hour values
        //negative values are not interpreted correctly

        //test for positive single hour value
        $this->assertEqual(format_duration('1:00'), '1 hour ');
        $this->assertEqual(format_duration('1.00'), '1 hour ');

        //test for positive multiple hours value
        $this->assertEqual(format_duration('3:00'), '3 hours ');
        $this->assertEqual(format_duration('3.00'), '3 hours ');

        //test for positive single minute value
        $this->assertEqual(format_duration('0:01'), '1 minute');
        $this->assertEqual(format_duration('0.1'), '6 minutes');

        //test for positive minutes value
        $this->assertEqual(format_duration('0:30'), '30 minutes');
        $this->assertEqual(format_duration('0.50'), '30 minutes');

        //test for out of range minutes value
        $this->assertEqual(format_duration('9:70'), '');

        //test for zero value
        $this->assertEqual(format_duration('0:00'), '');
        $this->assertEqual(format_duration('0.00'), '');

        //test for negative hour value
        $this->assertEqual(format_duration('-1:00'), '');
        $this->assertEqual(format_duration('-1.00'), '');

        //test for negative multiple hours value
        $this->assertEqual(format_duration('-7:00'), '');
        $this->assertEqual(format_duration('-7.00'), '');

        //test for negative single minute value
        $this->assertEqual(format_duration('-0:01'), '');
        $this->assertEqual(format_duration('-0.01'), '');

        //test for negative multiple minutes value
        $this->assertEqual(format_duration('-0:33'), '');
        $this->assertEqual(format_duration('-0.33'), '');

        //test for negative hours & minutes value
        $this->assertEqual(format_duration('-5:42'), '');
        $this->assertEqual(format_duration('-5.42'), '');

        //test for invalid characters value
        $this->assertEqual(format_duration('invalid_string'), '');
    }


    public function test_facetoface_minutes_to_hours() {
        // test method - returns a string

        //test for positive minutes value
        $this->assertEqual(facetoface_minutes_to_hours('11'), '0:11');

        //test for positive hours & minutes value
        $this->assertEqual(facetoface_minutes_to_hours('67'), '1:7');

        //test for negative minutes value
        $this->assertEqual(facetoface_minutes_to_hours('-42'), '-42');

        //test for negative hours and minutes value
        $this->assertEqual(facetoface_minutes_to_hours('-7:19'), '-7:19');

        //test for invalid characters value
        $this->assertEqual(facetoface_minutes_to_hours('invalid_string'), '0');
    }

    public function test_facetoface_hours_to_minutes() {
        // test method - returns a float
        // should negative values return 0 or a negative value?

        // test for positive hours value
        $this->assertEqual(facetoface_hours_to_minutes('10'), '600');

        // test for positive minutes and hours value
        $this->assertEqual(facetoface_hours_to_minutes('11:17'), '677');

        //test for negative hours value
        $this->assertEqual(facetoface_hours_to_minutes('-3'), '-180');

        //test for negative hours & minutes value
        $this->assertEqual(facetoface_hours_to_minutes('-2:1'), '-119');

        //test for invalid characters value
        $this->assertEqual(facetoface_hours_to_minutes('invalid_string'), '');
    }
}
