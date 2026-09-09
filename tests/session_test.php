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
require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

/**
 * Test the session helper class.
 *
 * @package    mod_facetoface
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_facetoface\session
 */
final class session_test extends \advanced_testcase {
    /** @var int $starttime */
    private $starttime;

    /**
     * This method runs before every test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->starttime = strtotime('01-01-2030 0900');
    }

    /**
     * Test getting session date.
     */
    public function test_get_readable_session_date_with_single_date(): void {
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
    public function test_get_readable_session_date_with_multiple_date(): void {
        $date = (object) [
            'timestart' => $this->starttime,
            'timefinish' => $this->starttime + 80 * HOURSECS,
        ];
        $expectedstring = '1 January 2030 - 4 January 2030';
        $this->assertEquals($expectedstring, session::get_readable_session_date($date));
    }

    /**
     * Data provider for test_get_readable_session_date_with_short_format.
     */
    public static function readable_session_date_short_provider(): array {
        $starttime = strtotime('01-01-2030 0900');
        $dateformat = get_string('strftimedatefullshort', 'langconfig');

        return [
            'single day' => [
                'timefinishoffset' => 8 * HOURSECS,
                'expectedstring' => userdate($starttime, $dateformat),
            ],
            'multiple days' => [
                'timefinishoffset' => 80 * HOURSECS,
                'expectedstring' => userdate($starttime, $dateformat) . ' - '
                    . userdate($starttime + 80 * HOURSECS, $dateformat),
            ],
        ];
    }

    /**
     * Test getting session date with a short date format.
     *
     * @dataProvider readable_session_date_short_provider
     */
    public function test_get_readable_session_date_with_short_format(int $timefinishoffset, string $expectedstring): void {
        $date = (object) [
            'timestart' => $this->starttime,
            'timefinish' => $this->starttime + $timefinishoffset,
        ];

        $this->assertEquals($expectedstring, session::get_readable_session_date($date, 'strftimedatefullshort'));
    }

    /**
     * Test getting session time.
     */
    public function test_get_readable_session_time(): void {
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
    public function test_get_readable_session_datetime_with_single_date(): void {
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
    public function test_get_readable_session_time_with_multiple_date(): void {
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
    public function test_get_readable_session_time_with_users_timezone(): void {
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
    public function test_email_notification(
        int $notifytype,
        string $confirmmessage,
        string $details,
        int $expectedemailcount,
        int $expectedicalamount,
        string $expectedmessage
    ) {

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
        $icalemails = array_filter($messages, function ($message) {
            return str_contains($message->body, 'Content-Disposition: attachment; filename=invite.ics');
        });
        $this->assertCount($expectedicalamount, $icalemails);

        // Do a very crude form of email multi-mime message parsing.
        // to extract the plaintext and html segments of the email.
        $messagessections = array_map(function ($message) {
            // Split on '--' which is the start of the separator in the email html multi-mime message.
            $sections = explode('--', $message->body);

            // Extract the html section.
            $htmlsection = current(array_filter($sections, function ($section) {
                return str_contains($section, 'text/html');
            }));
            $htmllines = explode("\n", $htmlsection);
            unset($htmllines[0]);
            $html = implode("\n", $htmllines);
            $html = quoted_printable_decode($html);

            // Do the same for the plaintext.
            $plaintextsection = current(array_filter($sections, function ($section) {
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

    /**
     * Data provider for test_cancellation_allowed
     *
     * @return array
     */
    public static function cancellation_allowed_provider(): array {
        return [
            'setting disabled' => [
                'configenabled' => false,
                'restriction' => DAYSECS,
                'sessiondates' => [
                    ['timestart' => DAYSECS, 'timefinish' => DAYSECS * 2],
                ],
                'expected' => true,
            ],
            'no restriction value set' => [
                'configenabled' => true,
                'restriction' => 0,
                'sessiondates' => [
                    ['timestart' => DAYSECS, 'timefinish' => DAYSECS * 2],
                ],
                'expected' => true,
            ],
            'within allowed time' => [
                'configenabled' => true,
                'restriction' => DAYSECS,
                'sessiondates' => [
                    ['timestart' => DAYSECS * 3, 'timefinish' => DAYSECS * 4],
                ],
                'expected' => true,
            ],
            'too close to session' => [
                'configenabled' => true,
                'restriction' => DAYSECS * 2,
                'sessiondates' => [
                    ['timestart' => DAYSECS, 'timefinish' => DAYSECS * 2],
                ],
                'expected' => false,
            ],
        ];
    }

    /**
     * Test the cancellation allowed check.
     *
     * @dataProvider cancellation_allowed_provider
     */
    public function test_cancellation_allowed(
        bool $configenabled,
        int $restriction,
        array $sessiondates,
        bool $expected
    ): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/facetoface/lib.php');

        // Configure settings.
        // Because of the admin setting we are using, there is an addition "_enabled" config we need to set.
        set_config('cancelrestriction_enabled', $configenabled, 'facetoface');
        set_config('cancelrestriction', $restriction, 'facetoface');

        // Setup course.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $generator->create_instance(['course' => $course->id]);

        // Create session.
        $now = time();
        $sessiondata = [
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 0,
            'duration' => 1.5,
            'normalcost' => 111,
            'discountcost' => 11,
            // We need to set session date unique to each provider as a unix timestamp.
            'sessiondates' => array_map(function ($date) use ($now) {
                return [
                    'timestart' => $now + $date['timestart'],
                    'timefinish' => $now + $date['timefinish'],
                ];
            }, $sessiondates),
        ];
        $session = $generator->create_session($sessiondata);

        // Check if provided user can cancel.
        $result = facetoface_cancellation_allowed($session);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for test_field_visibility
     *
     * @return array
     */
    public static function field_visibility_provider(): array {
        return [
            'visible to all, not teacher' => [
                'visibility' => MDL_F2F_FIELD_VISIBLETOALL,
                'isteacher' => false,
                'expected' => true,
            ],
            'visible to all, is teacher' => [
                'visibility' => MDL_F2F_FIELD_VISIBLETOALL,
                'isteacher' => true,
                'expected' => true,
            ],
            'visible to teachers only, not teacher' => [
                'visibility' => MDL_F2F_FIELD_VISIBLETOTEACHERS,
                'isteacher' => false,
                'expected' => false,
            ],
            'visible to teachers only, is teacher' => [
                'visibility' => MDL_F2F_FIELD_VISIBLETOTEACHERS,
                'isteacher' => true,
                'expected' => true,
            ],
            'not visible, not teacher' => [
                'visibility' => MDL_F2F_FIELD_NOTVISIBLE,
                'isteacher' => false,
                'expected' => false,
            ],
            'not visible, is teacher' => [
                'visibility' => MDL_F2F_FIELD_NOTVISIBLE,
                'isteacher' => true,
                'expected' => false,
            ],
        ];
    }

    /**
     * Test field visibility for different user roles and visibility settings.
     *
     * @dataProvider field_visibility_provider
     */
    public function test_field_visibility_helper(
        int $visibility,
        bool $isteacher,
        bool $expected
    ): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/facetoface/lib.php');

        // Setup course and participants.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $facetoface = $generator->create_instance(['course' => $course->id]);

        // Create custom field.
        $customfield = (object)[
            'name' => 'Test Field',
            'shortname' => 'testfield',
            'type' => 0,
            'possiblevalues' => '',
            'required' => 0,
            'defaultvalue' => '',
            'isfilter' => 0,
            'showinsummary' => 1,
            'visibleto' => $visibility,
        ];
        $DB->insert_record('facetoface_session_field', $customfield);

        // Test visibility.
        $this->setUser($isteacher ? $teacher : $student);
        $canedit = has_capability('mod/facetoface:editsessions', \context_course::instance($course->id));
        $result = facetoface_can_view_field($visibility, $canedit);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test field visibility for different user roles and visibility settings.
     *
     * @dataProvider field_visibility_provider
     */
    public function test_field_visibility(int $visibility, bool $isteacher, bool $expected): void {
        global $DB, $PAGE;
        $this->resetAfterTest();

        // Setup course and participants.
        $course = $this->getDataGenerator()->create_course();
        if ($isteacher) {
            $user = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        } else {
            $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        }
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $generator->create_instance(['course' => $course->id]);

        // Create custom field.
        $field = new \stdClass();
        $field->name = 'Test Field';
        $field->shortname = 'testfield';
        $field->type = 0;
        $field->required = 0;
        $field->isfilter = 1;
        $field->showinsummary = 1;
        $field->visibleto = $visibility;
        $field->id = $DB->insert_record('facetoface_session_field', $field);

        // Create a session.
        $session = $generator->create_session([
            'facetoface' => $facetoface->id,
            'capacity' => 10,
            'sessiondates' => [
                [
                    'timestart' => time() + DAYSECS,
                    'timefinish' => time() + DAYSECS + HOURSECS,
                ],
            ],
        ]);

        // Add data for the custom field.
        $data = new \stdClass();
        $data->sessionid = $session->id;
        $data->fieldid = $field->id;
        $data->data = "Test value";
        $DB->insert_record('facetoface_session_data', $data);

        // Set up session data as expected by renderer.
        $customfields = $DB->get_records('facetoface_session_field');
        $session->customfielddata = $DB->get_records(
            'facetoface_session_data',
            ['sessionid' => $session->id],
            '',
            'fieldid,data'
        );
        // Add bookedsession property required by renderer but not added by generator.
        $session->bookedsession = null;
        $sessions = [$session];
        $renderer = $PAGE->get_renderer('mod_facetoface');

        // Test visibility.
        $this->setUser($user);
        $canedit = has_capability('mod/facetoface:editsessions', \context_course::instance($course->id));
        $output = $renderer->print_session_list_table($customfields, $sessions, false, $canedit);
        if ($expected) {
            $this->assertStringContainsString('Test Field', $output);
            $this->assertStringContainsString('Test value', $output);
        } else {
            $this->assertStringNotContainsString('Test Field', $output);
            $this->assertStringNotContainsString('Test value', $output);
        }
    }

    /**
     * Test retrieving visible custom session field data for cm view on course page.
     */
    public function test_get_visiblefield_data(): void {
        global $DB;

        $this->resetAfterTest();

        /** @var \mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        // Setup course and participants.
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $generator->create_instance(['course' => $course->id]);

        // Create a custom field.
        $fieldid = $DB->insert_record('facetoface_session_field', [
            'name' => 'Location',
            'shortname' => 'location',
            'type' => 0,
            'required' => 0,
            'isfilter' => 0,
            'showinsummary' => 1,
            'visibleto' => MDL_F2F_FIELD_VISIBLETOALL, // Everyone
        ]);

        // Create a session.
        $session = $generator->create_session([
            'facetoface' => $facetoface->id,
            'capacity' => 10,
        ]);

        // Add custom field data to the session.
        $DB->insert_record('facetoface_session_data', [
            'sessionid' => $session->id,
            'fieldid' => $fieldid,
            'data' => 'Test Location',
        ]);

        // Ensure returns null when no visible field is configured.
        set_config('displaycustomfield', 0, 'facetoface');
        $result = facetoface_get_visiblefield_data($session);
        $this->assertNull($result);

        // Ensure returns valid values when visible field is configured.
        set_config('displaycustomfield', $fieldid, 'facetoface');
        $result = facetoface_get_visiblefield_data($session);
        $this->assertIsObject($result);
        $this->assertEquals('Location', $result->name);
        $this->assertEquals('Test Location', $result->value);

        // Ensure returns null when field doesn't exist for session.
        $newsession = $generator->create_session([
            'facetoface' => $facetoface->id,
            'capacity' => 10,
        ]);
        $result = facetoface_get_visiblefield_data($newsession);
        $this->assertNull($result);

        // Ensure returns null if custom field ID doesn't exist.
        set_config('displaycustomfield', 999, 'facetoface');
        $result = facetoface_get_visiblefield_data($session);
        $this->assertNull($result);
    }

    /**
     * Set up a course whose facetoface activity completes on attendance.
     *
     * @param int $completionattendance the status code attendance has to reach
     * @param array $sessiondates timestart/timefinish pairs for the session
     * @return array [course, facetoface, session, student]
     */
    private function setup_attendance_completion(int $completionattendance, array $sessiondates): array {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $facetoface = $generator->create_instance([
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionattendance' => $completionattendance,
        ]);
        $session = $generator->create_session([
            'facetoface' => $facetoface->id,
            'sessiondates' => $sessiondates,
        ]);

        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_TEXT, MDL_F2F_STATUS_BOOKED, $student->id);

        return [$course, $facetoface, $session, $student];
    }

    /**
     * Make the facetoface activity a course completion criterion.
     *
     * @param \stdClass $course
     * @param \stdClass $facetoface
     * @return void
     */
    private function add_activity_criterion(\stdClass $course, \stdClass $facetoface): void {
        $cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id);
        // update_config() takes its argument by reference, so it needs a variable.
        $criteriadata = (object) [
            'id' => $course->id,
            'criteria_activity' => [$cm->id => 1],
        ];
        $criterion = new \completion_criteria_activity();
        $criterion->update_config($criteriadata);
    }

    /**
     * Mark attendance the way the attendees screen and the CSV upload do.
     *
     * Both post through facetoface_take_attendance(), which maps the status code
     * to a grade before recording it. Calling the individual function directly
     * skips that mapping and so cannot reproduce what the plugin actually does.
     *
     * @param \stdClass $session
     * @param \stdClass $student
     * @param int $statuscode one of the MDL_F2F_STATUS_* attendance codes
     * @return void
     */
    private function take_attendance(\stdClass $session, \stdClass $student, int $statuscode): void {
        global $DB;

        $signup = $DB->get_record('facetoface_signups', ['sessionid' => $session->id, 'userid' => $student->id]);

        facetoface_take_attendance((object) [
            's' => $session->id,
            'submissionid_' . $signup->id => $statuscode,
        ]);
    }

    /**
     * The completion date of the activity, as the activity completion report reads it.
     *
     * @param \stdClass $course
     * @param \stdClass $facetoface
     * @param \stdClass $student
     * @return \stdClass
     */
    private function get_completion_data(\stdClass $course, \stdClass $facetoface, \stdClass $student): \stdClass {
        $cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id);
        $completion = new \completion_info($course);

        return (object) (array) $completion->get_data($cm, false, $student->id);
    }

    /**
     * With the setting enabled, the activity is dated by the session and not by the marking.
     *
     * Course completion criteria are optional, and this has to hold whether or
     * not the course uses them: the backdating used to sit inside the criteria
     * loop, so a course without them kept the time attendance was taken.
     */
    public function test_completion_date_uses_session_finish_without_course_criteria(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('sessioncompletiondate', 1, 'facetoface');

        $sessiondate = time() - WEEKSECS;
        [$course, $facetoface, $session, $student] = $this->setup_attendance_completion(
            MDL_F2F_STATUS_FULLY_ATTENDED,
            [['timestart' => $sessiondate, 'timefinish' => $sessiondate + HOURSECS]]
        );

        $this->take_attendance($session, $student, MDL_F2F_STATUS_FULLY_ATTENDED);

        $completiondata = $this->get_completion_data($course, $facetoface, $student);
        $this->assertEquals(COMPLETION_COMPLETE, $completiondata->completionstate);
        $this->assertEquals($sessiondate + HOURSECS, $completiondata->timemodified);
    }

    /**
     * The course completion criterion is dated by the session too.
     *
     * Core aggregates the course completion date from the criteria times, so
     * this is what keeps the course completion date and the activity completion
     * date telling the same story.
     */
    public function test_completion_date_uses_session_finish_with_course_criteria(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('sessioncompletiondate', 1, 'facetoface');

        $sessiondate = time() - WEEKSECS;
        [$course, $facetoface, $session, $student] = $this->setup_attendance_completion(
            MDL_F2F_STATUS_FULLY_ATTENDED,
            [['timestart' => $sessiondate, 'timefinish' => $sessiondate + HOURSECS]]
        );
        $this->add_activity_criterion($course, $facetoface);

        $this->take_attendance($session, $student, MDL_F2F_STATUS_FULLY_ATTENDED);

        $completiondata = $this->get_completion_data($course, $facetoface, $student);
        $this->assertEquals($sessiondate + HOURSECS, $completiondata->timemodified);

        $criteria = $DB->get_record('course_completion_crit_compl', ['userid' => $student->id, 'course' => $course->id]);
        $this->assertEquals($sessiondate + HOURSECS, $criteria->timecompleted);
    }

    /**
     * The course completion itself carries the session date, not the moment of marking.
     *
     * This is the record the course completion report and any certificate issued from it read,
     * and it is the one that used to disagree with the activity. Core builds the criterion from
     * {course_modules_completion}.timemodified and then aggregates {course_completions} from the
     * criteria, all inline while the attendance is being saved - so if the activity is dated
     * after that pass instead of before it, the course keeps the time attendance was taken and
     * cannot be corrected afterwards.
     */
    public function test_course_completion_date_uses_session_finish(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('sessioncompletiondate', 1, 'facetoface');

        $sessiondate = time() - WEEKSECS;
        [$course, $facetoface, $session, $student] = $this->setup_attendance_completion(
            MDL_F2F_STATUS_FULLY_ATTENDED,
            [['timestart' => $sessiondate, 'timefinish' => $sessiondate + HOURSECS]]
        );
        $this->add_activity_criterion($course, $facetoface);

        $this->take_attendance($session, $student, MDL_F2F_STATUS_FULLY_ATTENDED);

        $expected = $sessiondate + HOURSECS;

        $completiondata = $this->get_completion_data($course, $facetoface, $student);
        $this->assertEquals($expected, $completiondata->timemodified, 'activity completion date');

        $coursecompletion = $DB->get_record(
            'course_completions',
            ['userid' => $student->id, 'course' => $course->id]
        );
        $this->assertNotEmpty($coursecompletion, 'the course should be completed');
        $this->assertEquals($expected, $coursecompletion->timecompleted, 'course completion date');

        // The two dates telling the same story is the whole point of the setting.
        $this->assertEquals(
            $completiondata->timemodified,
            $coursecompletion->timecompleted,
            'course completion should match the activity completion'
        );
    }

    /**
     * With the setting off, the course completion is dated by the marking, as before.
     *
     * The deferred course-level pass must not change behaviour when the setting is not in use.
     */
    public function test_course_completion_date_without_setting_uses_marking_time(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('sessioncompletiondate', 0, 'facetoface');

        $sessiondate = time() - WEEKSECS;
        [$course, $facetoface, $session, $student] = $this->setup_attendance_completion(
            MDL_F2F_STATUS_FULLY_ATTENDED,
            [['timestart' => $sessiondate, 'timefinish' => $sessiondate + HOURSECS]]
        );
        $this->add_activity_criterion($course, $facetoface);

        $before = time();
        $this->take_attendance($session, $student, MDL_F2F_STATUS_FULLY_ATTENDED);

        $coursecompletion = $DB->get_record(
            'course_completions',
            ['userid' => $student->id, 'course' => $course->id]
        );
        $this->assertNotEmpty($coursecompletion, 'the course should be completed');
        $this->assertGreaterThanOrEqual($before, (int) $coursecompletion->timecompleted);
        $this->assertLessThan($sessiondate + HOURSECS + DAYSECS, (int) $coursecompletion->timecompleted);
    }

    /**
     * Partial attendance is backdated when partial attendance is what completes the activity.
     *
     * The rule is a status code comparison, but the guard used to compare the
     * grade: partial attendance grades 50 against a required status of 90, so
     * these users were completed at the time of marking while the fully attended
     * users beside them in the same session got the session date.
     */
    public function test_completion_date_for_partially_attended_user(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('sessioncompletiondate', 1, 'facetoface');

        $sessiondate = time() - WEEKSECS;
        [$course, $facetoface, $session, $student] = $this->setup_attendance_completion(
            MDL_F2F_STATUS_PARTIALLY_ATTENDED,
            [['timestart' => $sessiondate, 'timefinish' => $sessiondate + HOURSECS]]
        );

        $this->take_attendance($session, $student, MDL_F2F_STATUS_PARTIALLY_ATTENDED);

        $completiondata = $this->get_completion_data($course, $facetoface, $student);
        $this->assertEquals(COMPLETION_COMPLETE, $completiondata->completionstate);
        $this->assertEquals($sessiondate + HOURSECS, $completiondata->timemodified);
    }

    /**
     * A session held over several days is dated by the last of them.
     *
     * The finish time used to come from an unordered join against the dates
     * table, so which day won was left to the database. Postgres happens to
     * return them in insertion order, which is why this passed before the fix
     * as well - it guards the guarantee, not the symptom.
     */
    public function test_completion_date_uses_the_last_day_of_a_multi_date_session(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('sessioncompletiondate', 1, 'facetoface');

        $firstday = time() - WEEKSECS;
        $lastday = $firstday + (2 * DAYSECS);
        [$course, $facetoface, $session, $student] = $this->setup_attendance_completion(
            MDL_F2F_STATUS_FULLY_ATTENDED,
            [
                ['timestart' => $firstday, 'timefinish' => $firstday + HOURSECS],
                ['timestart' => $firstday + DAYSECS, 'timefinish' => $firstday + DAYSECS + HOURSECS],
                ['timestart' => $lastday, 'timefinish' => $lastday + HOURSECS],
            ]
        );
        // Configured so this isolates the choice of date: without a criterion
        // the backdating would not run at all and the assertion would pass for
        // the wrong reason.
        $this->add_activity_criterion($course, $facetoface);

        // The generator has to have produced a genuinely multi-date session,
        // otherwise this asserts nothing about which date is chosen.
        $this->assertEquals(3, $DB->count_records('facetoface_sessions_dates', ['sessionid' => $session->id]));

        $this->take_attendance($session, $student, MDL_F2F_STATUS_FULLY_ATTENDED);

        $completiondata = $this->get_completion_data($course, $facetoface, $student);
        $this->assertEquals($lastday + HOURSECS, $completiondata->timemodified);
    }

    /**
     * A user who did not attend is neither completed nor dated by the session.
     */
    public function test_no_show_is_not_completed_or_backdated(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('sessioncompletiondate', 1, 'facetoface');

        $sessiondate = time() - WEEKSECS;
        [$course, $facetoface, $session, $student] = $this->setup_attendance_completion(
            MDL_F2F_STATUS_FULLY_ATTENDED,
            [['timestart' => $sessiondate, 'timefinish' => $sessiondate + HOURSECS]]
        );
        $this->add_activity_criterion($course, $facetoface);

        $this->take_attendance($session, $student, MDL_F2F_STATUS_NO_SHOW);

        $completiondata = $this->get_completion_data($course, $facetoface, $student);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completiondata->completionstate);
        $this->assertFalse(
            $DB->record_exists('course_completion_crit_compl', ['userid' => $student->id, 'course' => $course->id])
        );
    }

    /**
     * With the setting off, completion is dated by the marking as it always was.
     */
    public function test_completion_date_is_the_marking_time_when_the_setting_is_off(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('sessioncompletiondate', 0, 'facetoface');

        $sessiondate = time() - WEEKSECS;
        [$course, $facetoface, $session, $student] = $this->setup_attendance_completion(
            MDL_F2F_STATUS_FULLY_ATTENDED,
            [['timestart' => $sessiondate, 'timefinish' => $sessiondate + HOURSECS]]
        );

        $before = time();
        $this->take_attendance($session, $student, MDL_F2F_STATUS_FULLY_ATTENDED);

        $completiondata = $this->get_completion_data($course, $facetoface, $student);
        $this->assertEquals(COMPLETION_COMPLETE, $completiondata->completionstate);
        $this->assertGreaterThanOrEqual($before, $completiondata->timemodified);
    }
}
