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

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("$CFG->dirroot/mod/facetoface/lib.php");

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
        set_config('displaysessiontimezones', 1, 'facetoface');
        $date = (object) [
            'timestart' => $this->starttime,
            'timefinish' => $this->starttime + 80 * HOURSECS,
        ];
        $expectedtimezone = core_date::get_localised_timezone(core_date::get_user_timezone());
        $expectedstring = "1 January 2030, 9:00 AM - 4 January 2030, 5:00 PM (time zone: $expectedtimezone)";
        $this->assertEquals($expectedstring, session::get_readable_session_datetime($date));
    }

    /**
     * Provides values to test_email_notification
     * @return array
     */
    public static function email_notification_provider(): array {
        $htmlconfirmmessage = "
            <p> This is the confirm message </p>
            <p> Details: </p>
            [details]
        ";

        $htmldetails = "
            <p> This is a html message </p>
            <br />
            <ul>
                <li> Test1 </li>
                <li> Test2 </li>
            </ul>
        ";

        $expectedhtmlmessage = "<div class=\"text_to_html\"><br />
            <p> This is the confirm message </p><p> Details: </p>             This is a html message<br />
<br />
    * Test1<br />
    * Test2<br />
<br />
 <br />
        </div>";

        // Because moodle code standards specify spaces over tabs, editors will automatically insert spaces
        // into the string above instead of tabs.
        // However we need tabs there, because this is what html_to_text uses for converting <li> to lists with a '\t*'.
        $expectedhtmlmessage = str_replace('    *', "\t*", $expectedhtmlmessage);

        $plaintextmessage = 'This is a plain text message
        It has plain text stuff in it
        * This is a fake list
        * Another fake list item
        (test)[test]{test}!!@@##////
        [details]';

        $plaintextdetails = "This is a plain text detail
        It has plain text stuff in it";

        $expectedplaintextmessage = "This is a plain text message<br />
        It has plain text stuff in it<br />
        * This is a fake list<br />
        * Another fake list item<br />
        (test)[test]{test}!!@@##////<br />
        This is a plain text detail<br />
It has plain text stuff in it";

        // phpcs:ignore.
        $expectedhtmlandplainmessage = "<p> This is the confirm message </p><p> Details: </p>             This is a plain text detail<br />
It has plain text stuff in it<br />";

        // Generate a matrix of tests, with all the types, and all the message combinations.
        $types = [
            'both' => [
                'type' => MDL_F2F_BOTH,
                'emails' => 2,
                'icalemails' => 1,
            ],
            'ical only' => [
                'type' => MDL_F2F_ICAL,
                'emails' => 1,
                'icalemails' => 1,
            ],
            'text only' => [
                'type' => MDL_F2F_TEXT,
                'emails' => 1,
                'icalemails' => 0,
            ],
        ];
        $messages = [
            'html message and html details' => [
                'message' => $htmlconfirmmessage,
                'details' => $htmldetails,
                'expected' => $expectedhtmlmessage,
            ],
            'plain message and plain details' => [
                'message' => $plaintextmessage,
                'details' => $plaintextdetails,
                'expected' => $expectedplaintextmessage,
            ],
            'plain text message and html details' => [
                'message' => $htmlconfirmmessage,
                'details' => $plaintextdetails,
                'expected' => $expectedhtmlandplainmessage,
            ],
        ];

        $tests = [];
        foreach ($types as $typename => $typedata) {
            foreach ($messages as $messagename => $messagedata) {
                $testname = 'email with message: ' . $messagename . ' with type: ' . $typename;

                $tests[$testname] = [
                    'type' => $typedata['type'],
                    'confirmmessage' => $messagedata['message'],
                    'details' => $messagedata['details'],
                    'expectedcount' => $typedata['emails'],
                    'expectedicalamount' => $typedata['icalemails'],
                    'expectedmessage' => $messagedata['expected'],
                ];
            }
        }

        return $tests;
    }

    /**
     * Tests email notification construction.
     * @param int $notifytype type of notification
     * @param string $confirmmessage the confirmation message to set
     * @param string $details details to set in f2f settings
     * @param int $expectedemailcount expected amount of emails that should be sent
     * @param int $expectedicalamount expected amount of email with ical attachments that should be sent
     * @param string $expectedmessage a string that the output of all emails should contain
     * @dataProvider email_notification_provider
     */
    public function test_email_notification(int $notifytype, string $confirmmessage, string $details, int $expectedemailcount,
        int $expectedicalamount, string $expectedmessage) {

        /** @var \mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        // Setup course, f2f and user.
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $generator->create_instance([
            'course' => $course->id,
            'confirmationsubject' => 'Confirmation',
            'confirmationmessage' => $confirmmessage,
            'confirmationmessageformat' => FORMAT_HTML,
        ]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Create a session with start and end times.
        $now = time();
        $session = $generator->create_session([
            'facetoface' => $facetoface->id,
            'capacity' => '3',
            'allowoverbook' => '0',
            'details' => $details,
            'duration' => '1.5', // One and half hours.
            'normalcost' => '111',
            'discountcost' => '11',
            'allowcancellations' => '0',
            'sessiondates' => [
                ['timestart' => $now + 1 * DAYSECS, 'timefinish' => $now + 2 * DAYSECS],
            ],
        ]);

        // Sign user up to session, capturing emails.
        $sink = $this->redirectEmails();
        facetoface_user_signup($session, $facetoface, $course, '', $notifytype, MDL_F2F_STATUS_BOOKED, $user->id);
        $messages = $sink->get_messages();
        $this->assertCount($expectedemailcount, $messages);

        // Ensure number of ical attachment emails is same as expected.
        $icalemails = array_filter($messages, function($message) {
            return str_contains($message->body, 'Content-Disposition: attachment; filename=invite.ics');
        });
        $this->assertCount($expectedicalamount, $icalemails);

        // Do a very crude form of email multi-mime message parsing.
        // to extract the plaintext and html segments of the email.
        $messagessections = array_map(function($message) {
            // Split on '--' which is the start of the separator in the email html multi-mime message.
            $sections = explode('--', $message->body);

            // Extract the html section.
            $htmlsection = current(array_filter($sections, function($section) {
                return str_contains($section, 'text/html');
            }));
            $htmllines = explode("\n", $htmlsection);
            unset($htmllines[0]);
            $html = implode("\n", $htmllines);
            $html = quoted_printable_decode($html);

            // Do the same for the plaintext.
            $plaintextsection = current(array_filter($sections, function($section) {
                return str_contains($section, 'text/plain');
            }));
            $plaintextlines = explode("\n", $plaintextsection);
            unset($plaintextlines[0]);
            $plaintext = implode("\n", $plaintextlines);
            $plaintext = quoted_printable_decode($plaintext);

            return [
                'html' => $html,
                'plaintext' => $plaintext,
            ];
        }, $messages);

        $messagehtmls = array_column($messagessections, 'html');
        $messageplaintexts = array_column($messagessections, 'plaintext');

        // Ensure each message has both html and plaintext.
        $this->assertTrue(count($messagehtmls) == count($messageplaintexts));

        // Ensure all the HTML messages are the same
        // (note this is only applicable for 'both' because it's the only one that sends two emails).
        $this->assertCount(1, array_unique($messagehtmls), "Emails should have the same HTML message");

        // Ensure each message has the expected html.
        foreach ($messagehtmls as $messagehtml) {
            $this->assertStringContainsString($expectedmessage, $messagehtml);
        }
    }
}
