<?php
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

declare(strict_types=1);

namespace mod_facetoface\reportbuilder\datasource;

use core_reportbuilder_generator;
use core_reportbuilder_testcase;
use mod_facetoface_generator;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/reportbuilder/tests/helpers.php");

/**
 * Facetoface datasource tests.
 *
 * @covers     \mod_facetoface\reportbuilder\datasource\facetoface
 * @package    mod_facetoface
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class facetoface_test extends core_reportbuilder_testcase {

    /** @var core_reportbuilder_generator */
    protected $rbgenerator;
    /** @var mod_facetoface_generator */
    protected $generator;

    /**
     * setUp.
     */
    public function setUp(): void {
        parent::setUp();
        $this->rbgenerator = self::getDataGenerator()->get_plugin_generator('core_reportbuilder');
        $this->generator = self::getDataGenerator()->get_plugin_generator('mod_facetoface');
    }

    /**
     * Test facetoface datasource
     */
    public function test_facetoface_datasource(): void {
        $this->resetAfterTest();
        self::setAdminUser();

        $coursecategory = self::getDataGenerator()->create_category(['name' => 'tenant category']);
        $coursecategory2 = self::getDataGenerator()->create_category(['parent' => $coursecategory->id]);
        $course = self::getDataGenerator()->create_course(['category' => $coursecategory2->id, 'fullname' => 'Test course']);
        $student = self::getDataGenerator()->create_and_enrol($course);

        // Add facetoface to course.
        $facetoface = self::getDataGenerator()->create_module('facetoface',
            ['course' => $course->id, 'name' => 'My facetoface']);

        // Add session.
        $now = time();
        $session = $this->generator->create_session([
            'facetoface' => $facetoface->id,
            'capacity' => '3',
            'allowoverbook' => '0',
            'duration' => '1.5', // One and half hours.
            'normalcost' => '111',
            'discountcost' => '11',
            'allowcancellations' => '0',
            'visible' => '1',
            'sessiondates' => [
                ['timestart' => $now + 1 * DAYSECS, 'timefinish' => $now + 2 * DAYSECS],
            ],
        ]);

        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_TEXT, MDL_F2F_STATUS_BOOKED, (int) $student->id);

        $report = $this->rbgenerator->create_report([
            'name' => 'Facetoface',
            'source' => facetofaces::class,
            'default' => false,
        ]);

        // Add course fullname column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'course:fullname']);
        // Add facetoface name column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'facetoface:name']);
        // Add session date start column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'session_date:datestart']);
        // Add session visibility column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'session:visibility']);
        // Add user fullname column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'user:fullname']);

        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(1, $content);

        $contentrow = array_values(reset($content));
        $dateformat = get_string('strftimedaydate', 'core_langconfig');
        $this->assertEquals([
            'Test course', // Course fullname.
            'My facetoface', // Facetoface name.
            userdate($now + 1 * DAYSECS, $dateformat), // Session start date.
            'Yes', // Session visibility.
            fullname($student), // User full name.
        ], $contentrow);
    }

    /**
     * Test bookings and booking cancels on facetoface datasource
     */
    public function test_facetoface_datasource_bookings(): void {
        $this->resetAfterTest();
        self::setAdminUser();

        $coursecategory = self::getDataGenerator()->create_category(['name' => 'tenant category']);
        $coursecategory2 = self::getDataGenerator()->create_category(['parent' => $coursecategory->id]);
        $course = self::getDataGenerator()->create_course(['category' => $coursecategory2->id, 'fullname' => 'Test course']);
        $student = self::getDataGenerator()->create_and_enrol($course);

        // Add facetoface to course.
        $facetoface = self::getDataGenerator()->create_module('facetoface',
            ['course' => $course->id, 'name' => 'My facetoface']);

        // Add session.
        $now = time();
        $session = $this->generator->create_session([
            'facetoface' => $facetoface->id,
            'capacity' => '1',
            'allowcancellations' => '1',
            'sessiondates' => [
                ['timestart' => $now + 1 * DAYSECS, 'timefinish' => $now + 2 * DAYSECS],
            ],
        ]);

        $report = $this->rbgenerator->create_report([
            'name' => 'Facetoface',
            'source' => facetofaces::class,
            'default' => false,
        ]);

        // Add facetoface name column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'facetoface:name']);
        // Add seats booked column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'session:seatsbooked']);
        // Add booked vs capacity column to the report.
        $this->rbgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'session:bookedvscapacity']);

        // Make sure there is only one facetoface.
        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(1, $content);

        // Make sure there are no signups.
        $contentrow = array_values(reset($content));
        $this->assertEquals([
            'My facetoface', // Facetoface name.
            "0", // Seats booked.
            '0 / 1', // Booked VS Capacity.
        ], $contentrow);

        // Signup the user.
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_TEXT, MDL_F2F_STATUS_BOOKED, (int) $student->id);

        // Make sure there is still only one facetoface.
        $content1 = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(1, $content1);

        // Make sure there is one signup.
        $contentrow1 = array_values(reset($content1));
        $this->assertEquals([
            'My facetoface', // Facetoface name.
            "1", // Seats booked.
            '1 / 1', // Booked VS Capacity.
        ], $contentrow1);

        // Wait 1 second to make sure that book and cancel timestamps are different.
        $this->waitForSecond();

        // Cancel signup.
        facetoface_user_cancel($session, (int) $student->id, true);

        // Make sure there is still only one facetoface.
        $content2 = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(1, $content2);

        // Make sure there are no signups anymore.
        $contentrow2 = array_values(reset($content2));
        $this->assertEquals([
            'My facetoface', // Facetoface name.
            "0", // Seats booked.
            '0 / 1', // Booked VS Capacity.
        ], $contentrow2);
    }

    /**
     * Stress test datasource
     */
    public function test_stress_datasource(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $category = $this->getDataGenerator()->create_category();

        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Add facetoface to course.
        $facetoface = self::getDataGenerator()->create_module('facetoface',
            ['course' => $course->id, 'name' => 'My facetoface']);

        // Add session.
        $now = time();
        $session = $this->generator->create_session([
            'facetoface' => $facetoface->id,
            'sessiondates' => [
                ['timestart' => $now + 1 * DAYSECS, 'timefinish' => $now + 2 * DAYSECS],
            ],
        ]);

        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_TEXT, MDL_F2F_STATUS_BOOKED, (int) $user->id);

        $this->datasource_stress_test_columns(facetofaces::class);
        $this->datasource_stress_test_columns_aggregation(facetofaces::class);
        $this->datasource_stress_test_conditions(facetofaces::class, 'course:shortname');
    }
}
