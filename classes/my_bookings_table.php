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

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/../lib.php');

use html_writer;
use table_sql;

/**
 * Table to display users bookings across the site.
 *
 * @package     mod_facetoface
 * @author      Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright   2026 Catalyst IT Australia Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class my_bookings_table extends table_sql {
    /** @var string display upcoming bookings */
    const UPCOMING = 'upcoming';

    /** @var string display previous bookings */
    const PREVIOUS = 'previous';

    /** @var string display wait-listed bookings */
    const WAITLISTED = 'waitlisted';

    /** @var \context_user the table context */
    protected ?\context_user $context = null;

    /** @var string the session type to display (upcoming or previous) */
    protected ?string $sessiontype = null;

    /** @var array an array of course visibility as course id key and true/false value */
    protected array $coursevisible = [];

    /** @var array an array of activity visibility as facetoface id key and true/false value */
    protected array $activityvisible = [];

    /**
     * Sets up the table.
     *
     * @param object $user The user to display bookings for.
     * @param \moodle_url $url The base URL.
     * @param ?string $sessiontype The session type to display (upcoming or previous).
     */
    public function __construct(object $user, \moodle_url $url, ?string $sessiontype = null) {
        global $DB;

        parent::__construct('facetoface_mybookings');
        $this->context = \context_user::instance($user->id);

        // Define columns in the table.
        $columns = [
            'coursename' => get_string('course'),
            'facetoface' => get_string('session', 'facetoface'),
            'sessiontime' => get_string('time', 'facetoface'),
            'status' => get_string('status', 'facetoface'),
        ];

        $this->define_columns(array_keys($columns));
        $this->define_headers(array_values($columns));
        $this->define_baseurl($url);
        $this->no_sorting('status');

        $uniqueid = $DB->sql_concat('s.id', '\'-\'', 'sd.id');
        $fields = $uniqueid . ',
                   s.id AS sessionid,
                   s.datetimeknown,
                   c.id AS courseid,
                   c.fullname AS coursename,
                   c.visible AS coursevisible,
                   f.id AS facetofaceid,
                   f.name AS facetoface,
                   sd.timestart AS sessiontime,
                   sd.timestart,
                   sd.timefinish,
                   ss.statuscode';
        $from = 'FROM {facetoface_signups} su
                 JOIN {facetoface_signups_status} ss ON ss.signupid = su.id AND ss.superceded = 0
                 JOIN {facetoface_sessions} s ON s.id = su.sessionid
                 JOIN {facetoface} f ON f.id = s.facetoface
                 JOIN {course} c ON c.id = f.course
            LEFT JOIN {facetoface_sessions_dates} sd ON sd.sessionid = s.id';
        $where = 'WHERE su.userid = :userid';
        $params = ['userid' => $user->id];
        $order = 'ORDER BY ss.statuscode DESC, f.name ASC';

        if ($sessiontype) {
            if ($sessiontype !== self::WAITLISTED) {
                $order = 'ORDER BY sessiontime DESC';
            }
            $where .= match ($sessiontype) {
                self::UPCOMING   => " AND s.datetimeknown = 1 AND sd.timefinish >= :now",
                self::PREVIOUS   => " AND s.datetimeknown = 1 AND sd.timefinish < :now",
                self::WAITLISTED => " AND s.datetimeknown = 0",
            };
            $params['now'] = time();
        }

        $this->sql = (object) [
            'fields' => $fields,
            'from' => $from,
            'where' => $where,
            'params' => $params,
            'order' => $order,
        ];
    }

    /**
     * Query the db. Store results in the table object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar. Bar
     * will only be used if there is a fullname column defined for the table.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        // Fetch the sessions. We have to fetch all of them to get the total count of visible sessions for the user,
        // this is a performance loss vs proper pagination but is better than doing a complex query to include visibility.
        $sort = $this->get_sql_sort();
        $order = $sort ? "ORDER BY $sort" : $this->sql->order;
        $sql = "SELECT {$this->sql->fields}
                {$this->sql->from}
                {$this->sql->where}
                {$order}";
        $records = $DB->get_records_sql($sql, $this->sql->params);
        $this->rawdata = array_filter($records, [$this, 'check_visibility']);

        if (!$this->is_downloading()) {
            // We are not downloading so we include the page size. While we don't get a performance gain
            // from this since we already fetched all the records, it is still needed to reduce the amount of
            // table rows displayed on the page.
            $this->totalrows = count($this->rawdata);
            $this->pagesize($pagesize, $this->totalrows);
            $this->rawdata = array_slice($this->rawdata, $this->get_page_start(), $pagesize);
        }
    }

    /**
     * Check if the user can see the course and activity.
     *
     * @param object $record The record to check.
     * @return bool True if the user can see the course and activity, false otherwise.
     */
    private function check_visibility(object $record): bool {
        if (!isset($this->activityvisible[$record->facetofaceid])) {
            // We don't know if this activity is visible for the user yet,
            // so let's check if the user can see it.
            if (!isset($this->coursevisible[$record->courseid])) {
                // We don't know the visibility of this course yet, so let's figure that out first.
                $visible = true;
                if (!$record->coursevisible) {
                    $coursectx = \context_course::instance($record->courseid);
                    if (!has_capability('moodle/course:viewhiddencourses', $coursectx)) {
                        $visible = false;
                    }
                }
                $this->coursevisible[$record->courseid] = $visible;
            }

            $activityvisible = true;

            if (!$this->coursevisible[$record->courseid]) {
                // User cannot see this course, which means they cannot see the activity.
                $activityvisible = false;
            } else {
                // User can see the course, finally check if they can see the activity.
                $modinfo = get_fast_modinfo($record->courseid);
                $cminstances = $modinfo->get_instances();
                if (
                    !isset($cminstances['facetoface'][$record->facetofaceid]) ||
                    !$cminstances['facetoface'][$record->facetofaceid]->uservisible
                ) {
                    // User cannot see this activity or the activity is missing.
                    $activityvisible = false;
                }
            }

            $this->activityvisible[$record->facetofaceid] = $activityvisible;
        }

        return $this->activityvisible[$record->facetofaceid];
    }

    /**
     * Display course name column as link
     *
     * @param \stdClass $record
     * @return string
     */
    public function col_coursename(\stdClass $record): string {
        return html_writer::link(
            new \moodle_url('/course/view.php', ['id' => $record->courseid]),
            format_string($record->coursename, true)
        );
    }

    /**
     * Display F2F column as activity name and link
     *
     * @param \stdClass $record
     * @return string
     */
    public function col_facetoface(\stdClass $record): string {
        return html_writer::link(
            new \moodle_url('/mod/facetoface/view.php', ['f' => $record->facetofaceid]),
            format_string($record->facetoface, true)
        );
    }

    /**
     * Display session date/times
     *
     * @param \stdClass $record
     * @return string
     */
    public function col_sessiontime(\stdClass $record): string {
        if ($record->datetimeknown) {
            $date = session::get_readable_session_date($record);
            $time = session::get_readable_session_time($record);
            return $date . ', ' . $time;
        } else {
            return get_string('wait-listed', 'facetoface');
        }
    }

    /**
     * Display status column
     *
     * @param \stdClass $record
     * @return string
     */
    public function col_status(\stdClass $record): string {
        if ($record->datetimeknown) {
            $now = time();
            if ($record->timefinish < $now) {
                // Session is past it's finished time.
                return get_string('sessionover', 'facetoface');
            } else if ($record->timestart <= $now) {
                // Session is not past it's finish time but is after the start time, so it is in progress.
                return get_string('inprogress');
            }
        }

        $signupstatus = facetoface_get_status($record->statuscode);
        return get_string('status_' . $signupstatus, 'facetoface');
    }
}
