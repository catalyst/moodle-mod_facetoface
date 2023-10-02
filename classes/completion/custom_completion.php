<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_facetoface\completion;

/**
 * Activity custom completion subclass for the facetoface activity.
 *
 * Class for defining mod_facetoface's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given facetoface instance and a user.
 *
 * @package   mod_facetoface
 * @copyright 2023 Open LMS (https://www.openlms.net/)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends \core_completion\activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $params = ['userid' => $this->userid, 'cmid' => $this->cm->id];
        $sql = "SELECT 1
                  FROM {facetoface_sessions} fs
                  JOIN {facetoface} f ON f.id = fs.facetoface AND f.completionattendance > 0
                  JOIN {course_modules} cm ON cm.instance = f.id
                  JOIN {modules} m ON m.name = 'facetoface' AND m.id = cm.module
                  JOIN {facetoface_signups} fsu ON fsu.sessionid = fs.id
                  JOIN {facetoface_signups_status} fsus ON fsus.signupid = fsu.id AND fsus.superceded = 0
                 WHERE fsu.userid = :userid AND cm.id = :cmid
                       AND fsus.statuscode >= f.completionattendance";

        if ($DB->record_exists_sql($sql, $params)) {
            return COMPLETION_COMPLETE;
        } else {
            return COMPLETION_INCOMPLETE;
        }
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionattendance'];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        global $CFG;
        require_once("$CFG->dirroot/mod/facetoface/lib.php");

        $text = get_string('completiondetail:attendance', 'facetoface');

        if (isset($this->cm->customdata['customcompletionrules']['completionattendance'])) {
            if ($this->cm->customdata['customcompletionrules']['completionattendance'] == MDL_F2F_STATUS_PARTIALLY_ATTENDED) {
                $text = get_string('completiondetail:attendance_partial', 'facetoface');
            } else if ($this->cm->customdata['customcompletionrules']['completionattendance'] == MDL_F2F_STATUS_FULLY_ATTENDED) {
                $text = get_string('completiondetail:attendance_full', 'facetoface');
            }
        }
        return [
            'completionattendance' => $text,
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionusegrade',
            'completionpassgrade',
            'completionattendance',
        ];
    }
}
