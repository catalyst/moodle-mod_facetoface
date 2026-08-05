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

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\course;
use core_reportbuilder\local\entities\user;
use mod_facetoface\reportbuilder\local\entities\facetoface;
use mod_facetoface\reportbuilder\local\entities\session;
use mod_facetoface\reportbuilder\local\entities\session_date;
use mod_facetoface\reportbuilder\local\entities\signup;

/**
 * Signups datasource.
 *
 * @package    mod_facetoface
 * @copyright  2025 Murdoch University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class signups extends datasource {
    /**
     * Initialise the datasource.
     */
    protected function initialise(): void {
        $courseentity = new course();
        $facetofaceentity = new facetoface();
        $sessionentity = new session();
        $sessiondateentity = new session_date();
        $signupentity = new signup();
        $userentity = (new user())
            ->set_entity_title(new \lang_string('attendee', 'mod_facetoface'));

        $course = $courseentity->get_table_alias('course');
        $facetoface = $facetofaceentity->get_table_alias('facetoface');
        $session = $sessionentity->get_table_alias('facetoface_sessions');
        $sessiondate = $sessiondateentity->get_table_alias('facetoface_sessions_dates');
        $signup = $signupentity->get_table_alias('facetoface_signups');
        $user = $userentity->get_table_alias('user');

        $sessionentity->set_table_alias('course', $course);

        $this->set_main_table('facetoface', $facetoface);

        $this->add_entity($courseentity);
        $this->add_entity($facetofaceentity);
        $this->add_entity($sessionentity);
        $this->add_entity($sessiondateentity);
        $this->add_entity($signupentity);
        $this->add_entity($userentity);

        // TODO: Avoid unconditional date x signup joins because multi-date sessions multiply attendee rows.
        $this->add_join("JOIN {course} {$course}
                           ON {$facetoface}.course = {$course}.id");
        $this->add_join("LEFT JOIN {facetoface_sessions} {$session}
                                ON {$session}.facetoface = {$facetoface}.id");
        $this->add_join("LEFT JOIN {facetoface_sessions_dates} {$sessiondate}
                                ON {$sessiondate}.sessionid = {$session}.id");
        $this->add_join("LEFT JOIN {facetoface_signups} {$signup}
                                ON {$signup}.sessionid = {$session}.id");
        foreach ($signupentity->get_joins() as $join) {
            $this->add_join($join);
        }
        $this->add_join("LEFT JOIN {user} {$user}
                                ON {$user}.id = {$signup}.userid");

        $this->add_all_from_entity($courseentity->get_entity_name());
        $this->add_all_from_entity($facetofaceentity->get_entity_name());
        $this->add_all_from_entity($sessionentity->get_entity_name());
        $this->add_all_from_entity($sessiondateentity->get_entity_name());
        $this->add_all_from_entity($signupentity->get_entity_name());
        $this->add_all_from_entity($userentity->get_entity_name());
    }

    /**
     * Return the user-friendly datasource name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('signups', 'mod_facetoface');
    }

    /**
     * Return the default columns.
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'facetoface:name',
            'session:capacity',
            'session_date:timestart',
            'session_date:timefinish',
            'user:fullname',
            'signup:status',
        ];
    }

    /**
     * Return the default filters.
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [];
    }

    /**
     * Return the default conditions.
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }
}
