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

declare(strict_types=1);

namespace mod_facetoface\reportbuilder\datasource;

use core_course\reportbuilder\local\entities\course_category;
use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\course;
use mod_facetoface\reportbuilder\local\entities\facetoface;
use mod_facetoface\reportbuilder\local\entities\attendee;
use mod_facetoface\reportbuilder\local\entities\session;
use mod_facetoface\reportbuilder\local\entities\session_date;
use core_reportbuilder\local\entities\user;

/**
 * Facetoface datasource
 *
 * @package   mod_facetoface
 * @copyright 2019 Moodle Pty Ltd <support@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class facetofaces extends datasource {

    /**
     * Initialise report
     *
     */
    protected function initialise(): void {
        // Add course entity as main entity.
        $courseentity = new course();
        $course = $courseentity->get_table_alias('course');
        $this->add_entity($courseentity);

        $this->set_main_table('course', $course);

        // Add course category entity.
        $coursecatentity = new course_category();
        $coursecategories = $coursecatentity->get_table_alias('course_categories');
        $this->add_entity($coursecatentity
            ->add_join("JOIN {course_categories} {$coursecategories} ON {$coursecategories}.id = {$course}.category"));

        // Add facetoface entity.
        $facetofaceentity = new facetoface();
        $facetoface = $facetofaceentity->get_table_alias('facetoface');
        $facetofacejoin = "JOIN {facetoface} {$facetoface} ON {$facetoface}.course = {$course}.id";
        $facetofaceentity->add_join($facetofacejoin);
        $this->add_entity($facetofaceentity);

        // Add facetoface session entity.
        $sessionentity = new session();
        $sessionentity->set_table_alias('course', $course);
        $session = $sessionentity->get_table_alias('facetoface_sessions');
        $sessionjoin = "JOIN {facetoface_sessions} {$session} ON {$session}.facetoface = {$facetoface}.id";
        $sessionentity->add_joins([$facetofacejoin, $sessionjoin]);
        $this->add_entity($sessionentity);

        // Add facetoface session dates entity.
        $sessiondateentity = new session_date();
        $sessiondate = $sessiondateentity->get_table_alias('facetoface_sessions_dates');
        $sessiondatejoin = "JOIN {facetoface_sessions_dates} {$sessiondate} ON {$sessiondate}.sessionid = {$session}.id";
        $sessiondateentity->add_joins([$facetofacejoin, $sessionjoin, $sessiondatejoin]);
        $this->add_entity($sessiondateentity);

        // Add facetoface attendee entity.
        $attendeeentity = new attendee();
        $attendee = $attendeeentity->get_table_alias('facetoface_signups');
        $attendeestatus = $attendeeentity->get_table_alias('facetoface_signups_status');
        $attendeeentityjoin = "LEFT JOIN {facetoface_signups} {$attendee} ON {$attendee}.sessionid = {$session}.id";
        $attendeestatusentityjoin = "
            LEFT JOIN {facetoface_signups_status} {$attendeestatus}
            ON {$attendeestatus}.signupid = {$attendee}.id
        ";
        $attendeeentity->add_joins([$facetofacejoin, $sessionjoin, $attendeeentityjoin, $attendeestatusentityjoin]);
        $this->add_entity($attendeeentity);

        // Add user entity.
        $userentity = new user();
        $user = $userentity->get_table_alias('user');
        $userjoin = "JOIN {user} {$user} ON {$user}.id = {$attendee}.userid";
        $userentity->add_joins([$facetofacejoin, $sessionjoin, $attendeeentityjoin, $attendeestatusentityjoin, $userjoin]);
        $this->add_entity($userentity);

        // Add enrol entity.
        $enrolentity = new \core_course\reportbuilder\local\entities\enrolment();
        $enrol = $enrolentity->get_table_alias('enrol');
        $userenrolment = $enrolentity->get_table_alias('user_enrolments');
        $enroljoins = [
            "JOIN {user_enrolments} {$userenrolment} ON {$userenrolment}.userid = {$user}.id",
            "JOIN {enrol} {$enrol} ON {$enrol}.id = {$userenrolment}.enrolid AND {$enrol}.courseid = {$course}.id",
        ];
        $enrolentity
            ->add_joins([$facetofacejoin, $sessionjoin, $attendeeentityjoin, $attendeestatusentityjoin, $userjoin])
            ->add_joins($enroljoins);
        $this->add_entity($enrolentity);

        // Add course completion entity.
        $completionentity = new \core_course\reportbuilder\local\entities\completion();
        $completionentity->set_table_alias('user', $user);
        $completionentity->set_table_alias('course', $course);
        $completion = $completionentity->get_table_alias('course_completion');
        $completionjoins = [
            "LEFT JOIN {course_completions} {$completion} ON $completion.course = {$course}.id AND $completion.userid = {$user}.id",
        ];
        $completionentity
            ->add_joins([$facetofacejoin, $sessionjoin, $attendeeentityjoin, $attendeestatusentityjoin, $userjoin])
            ->add_joins($completionjoins);
        $this->add_entity($completionentity);

        // Add all entities columns/filters/conditions.
        $this->add_all_from_entities();
    }

    /**
     * Return user friendly name of the datasource
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('facetoface', 'mod_facetoface');
    }

    /**
     * Return the columns that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'course:fullname',
            'facetoface:name',
            'session_date:datestart',
            'session_date:starttime',
            'session_date:finishtime',
            'session:bookedvscapacity',
            'session:status',
        ];
    }

    /**
     * Return the filters that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'course:fullname',
            'facetoface:name',
            'session:capacity',
            'session:allowoverbook',
            'session:sessionavailability',
        ];
    }

    /**
     * Return the conditions that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }
}
