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
 * Helper class to get info about facetoface sessions.
 *
 * It's not a ORM/persistent object for a session as it should be due to the age of the plugin, and the effort to retrofit sessions data
 * into a class.
 *
 * @package    mod_facetoface
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session {

    /**
     * Get a human-readable string of the dates for a session instance.
     *
     * @param \stdClass $sessiondate Object containing a start and finish time for a session.
     * @return string Date string. If session is only over a day, it just returns one date, otherwise it will show a range.
     *
     * @throws \moodle_exception
     */
    public static function get_readable_session_date(\stdClass $sessiondate): string {
        if (!isset($sessiondate->timestart) || !isset($sessiondate->timefinish)) {
            throw new \moodle_exception('error:invalidsessiondate', 'facetoface');
        }
        $formatteddate = facetoface_format_session_times($sessiondate->timestart, $sessiondate->timefinish, null);
        $date = $formatteddate->startdate;
        // If start and finish date cover multiple days, append the finishing date.
        if ($formatteddate->startdate !== $formatteddate->enddate) {
            $date .= ' - ' . $formatteddate->enddate;
        }
        return $date;
    }

    /**
     * Get a human-readable string of the times for a session instance.
     *
     * @param \stdClass $sessiondate Object containing a start and finish time for a session.
     * @return string Time string. Shows start and finish time, which may be on different days.
     *
     * @throws \moodle_exception
     */
    public static function get_readable_session_time(\stdClass $sessiondate): string {
        if (!isset($sessiondate->timestart) || !isset($sessiondate->timefinish)) {
            throw new \moodle_exception('error:invalidsessiondate', 'facetoface');
        }
        $formatteddate = facetoface_format_session_times($sessiondate->timestart, $sessiondate->timefinish, null);
        return $formatteddate->starttime . ' - ' . $formatteddate->endtime;
    }

    /**
     * Get a human-readable string of the full date and times for a session instance.
     *
     * @param \stdClass $sessiondate Object containing a start and finish time for a session.
     * @param string|null $timezone Optional timezone to format the string.
     * @return string Date time string. Contains start date and time, to end date and time and a possible timezone
     * depending on settings.
     *
     * @throws \moodle_exception
     */
    public static function get_readable_session_datetime(\stdClass $sessiondate, string $timezone = null): string {
        if (!isset($sessiondate->timestart) || !isset($sessiondate->timefinish)) {
            throw new \moodle_exception('error:invalidsessiondate', 'facetoface');
        }
        $formatteddate = facetoface_format_session_times($sessiondate->timestart, $sessiondate->timefinish, $timezone);
        $datetime = $formatteddate->startdatetime . ' - ' . $formatteddate->enddatetime;
        if ($formatteddate->timezone) {
            // Use string for localization, as contains non-generated language.
            return get_string('sessiondateandtime', 'mod_facetoface', $formatteddate);
        }
        return $datetime;
    }
}
