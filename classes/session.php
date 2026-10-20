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
 * It's not a ORM/persistent object for a session as it should be due to the
 * age of the plugin, and the effort to retrofit sessions data into a class.
 *
 * @package    mod_facetoface
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session {
    /**
     * Get the language string key explaining why self-service signup is unavailable.
     *
     * @param \stdClass $session Session record including its dates.
     * @param int $timenow Current timestamp.
     * @param bool $checkdates Whether to check dates; bulk entry forms check each session separately.
     * @return string|null Error string key, or null when signup is available.
     */
    public static function get_self_signup_error(\stdClass $session, int $timenow, bool $checkdates = true): ?string {
        if (empty($session->visible)) {
            return 'error:sessionclosed';
        }

        if ($checkdates && facetoface_has_session_started($session, $timenow)) {
            return facetoface_is_session_in_progress($session, $timenow)
                ? 'cannotsignupsessioninprogress' : 'cannotsignupsessionover';
        }

        return null;
    }

    /**
     * Get a human-readable string of the dates for a session instance.
     *
     * @param \stdClass $sessiondate Object containing a start and finish time for a session.
     * @param string $dateformat Lang string key for the date format.
     * @return string Date string. If session is only over a day, it just returns one date, otherwise it will show a range.
     *
     * @throws \moodle_exception
     */
    public static function get_readable_session_date(\stdClass $sessiondate, string $dateformat = 'strftimedate'): string {
        if (!isset($sessiondate->timestart) || !isset($sessiondate->timefinish)) {
            throw new \moodle_exception('error:invalidsessiondate', 'facetoface');
        }

        $targettz = \core_date::get_user_timezone();
        $format = get_string($dateformat, 'langconfig');
        $date = userdate($sessiondate->timestart, $format, $targettz);
        $enddate = userdate($sessiondate->timefinish, $format, $targettz);

        // If start and finish date cover multiple days, append the finishing date.
        if ($date !== $enddate) {
            $date .= ' - ' . $enddate;
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
