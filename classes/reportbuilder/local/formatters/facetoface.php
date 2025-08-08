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

namespace mod_facetoface\reportbuilder\local\formatters;

use context_module;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot. '/mod/facetoface/lib.php');

/**
 * Formatters for the facetoface entity
 *
 * @package    mod_facetoface
 * @copyright  2019 Moodle Pty Ltd <support@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class facetoface {

    /**
     * Returns session availability statuses for filter
     *
     * @return array
     */
    public static function get_sessionavailability_statuses(): array {
        return [
            1 => get_string('fullfilter', 'mod_facetoface'),
            2 => get_string('empty', 'mod_facetoface'),
            3 => get_string('partiallyfull', 'mod_facetoface'),
        ];
    }

    /**
     * Returns Session date and times
     *
     * @param string|null $value
     * @param stdClass $row
     * @return string
     */
    public static function sessiondatetime(?string $value, stdClass $row): string {
        if (!isset($row->timestart) || !isset($row->timefinish)) {
            return '';
        }
        $formatdate = get_string('strftimedaydate', 'langconfig');
        $formattime = get_string('strftimetime', 'langconfig');
        $dateobj = [];
        $dateobj['startdate'] = userdate($row->timestart, $formatdate);
        $dateobj['starttime'] = userdate($row->timestart, $formattime);
        $dateobj['endtime'] = userdate($row->timefinish, $formattime);

        return get_string('sessionstartdateandtimewithouttimezone', 'mod_facetoface', $dateobj);
    }

    /**
     * Returns status of the atendee.
     *
     * @param string|null $value
     * @param stdClass|null $row
     * @return string
     * @throws \coding_exception
     */
    public static function status(?string $value, ?stdClass $row): string {
        $statuses = facetoface_statuses();

        // Check code exists.
        if (!isset($statuses[$value])) {
            return '-';
        }
        return get_string('status_' . $statuses[$value], 'facetoface');
    }

    /**
     * Returns session statuses for filter
     *
     * @return array
     */
    public static function get_session_statuses(): array {
        return [
            1 => get_string('sessionnotstarted', 'mod_facetoface'),
            2 => get_string('inprogress'),
            3 => get_string('sessionfinished', 'mod_facetoface'),
        ];
    }

    /**
     * Returns list of facetoface statuses
     *
     * @return array
     */
    public static function get_facetoface_statuses(): array {
        $statuslist = [];
        // TODO: WP-1210 Removed excluded statuses when approval functionality is back.
        $excluded = [MDL_F2F_STATUS_DECLINED, MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_APPROVED];
        foreach (facetoface_statuses() as $key => $status) {
            if (!in_array($key, $excluded)) {
                $statuslist[$key] = get_string('status_' . $status, 'facetoface');
            }
        }
        return $statuslist;
    }

    /**
     * returns session status
     *
     * @param string|null $value
     * @param stdClass $row
     * @return string
     */
    public static function sessionstatus(?string $value, stdClass $row): string {
        if (!isset($row->sessionid)) {
            return '';
        }
        $session = facetoface_get_session($row->sessionid);
        return facetoface_get_session_info($session)['status'];
    }
}
