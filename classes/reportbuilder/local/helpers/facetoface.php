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

namespace mod_facetoface\reportbuilder\local\helpers;

require_once("$CFG->dirroot/mod/facetoface/lib.php");

/**
 * Helpers for the facetoface RB entities
 *
 * @package    mod_facetoface
 * @copyright  2024 Moodle Pty Ltd <support@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class facetoface {

    /**
     * Returns a query to retrieve not cancelled session bookings.
     *
     * @param string $tablealias
     * @return string
     */
    public static function get_bookings_query(string $tablealias): string {
        $excludestatuses = [
            MDL_F2F_STATUS_USER_CANCELLED,
            MDL_F2F_STATUS_DECLINED,
            MDL_F2F_STATUS_REQUESTED,
            MDL_F2F_STATUS_WAITLISTED,
        ];
        $excludesql = 'NOT IN (' . implode(',', $excludestatuses) . ')';

        // Bookings query.
        return "(
            SELECT COUNT(fsu.id)
              FROM {facetoface_signups} fsu
              JOIN {facetoface_signups_status} fsus
                ON fsu.id = fsus.signupid
              JOIN (
                SELECT signupid, max(timecreated) AS timecreated
                  FROM {facetoface_signups_status}
              GROUP BY signupid
                 ) fsus2
                ON fsus.signupid = fsus2.signupid AND fsus.timecreated = fsus2.timecreated
             WHERE fsus.statuscode {$excludesql} AND fsu.sessionid = {$tablealias}.id)";
    }
}
