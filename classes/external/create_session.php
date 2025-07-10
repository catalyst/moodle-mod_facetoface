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

namespace mod_facetoface\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use context_module;
use stdClass;

/**
 * Create session webservice.
 *
 * @package    mod_facetoface
 * @copyright  2025 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_session extends external_api {
    /**
     * Possible parameters for crete_session.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'facetofaceid' => new external_value(PARAM_INT, 'Facetoface activity instance ID'),
            'details' => new external_value(PARAM_TEXT, 'Session details/description'),
            'capacity' => new external_value(PARAM_INT, 'Number of students who can enrol in the session'),
            'allowoverbook' => new external_value(PARAM_BOOL, 'Flag to turn on waitlisting for the session'),
            'datetimeknown' => new external_value(PARAM_INT, '0 means the date and time is unknown, 1 means that both are known.'),
            'duration' => new external_value(PARAM_INT, 'Total duration (in minutes) of the session.'),
            'normalcost' => new external_value(PARAM_INT, 'The normal cost of the session'),
            'discountcost' => new external_value(PARAM_INT, 'The discounted cost of the session'),
            'allowcancellations' => new external_value(PARAM_BOOL, 'Allow sign-up cancellation'),
            'sessiondates' => new external_multiple_structure(
                new external_single_structure([
                    'timestart' => new external_value(PARAM_INT, 'Unix timestamp for the start of the session'),
                    'timeend' => new external_value(PARAM_INT, 'Unix timestamp for the end of the session'),
                ]), 'Session dates', VALUE_OPTIONAL
            ),
            'customfields' => new external_multiple_structure(
                new external_single_structure([
                    'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Custom field shortname'),
                    'value' => new external_value(PARAM_TEXT, 'Custom field value'),
                ]), 'Custom field values', VALUE_OPTIONAL
            ),
        ]);
    }

    /**
     * Create Facetoface session.
     *
     * @param int $facetofaceid
     * @param int $capacity
     * @param int $allowoverbook
     * @param string $details
     * @param int $datetimeknown
     * @param int $duration
     * @param int $normalcost
     * @param int $discountcost
     * @param int $allowcancellations
     * @param array $sessiondates
     * @param array $customfields
     * @return array
     */
    public static function execute(int $facetofaceid, string $details, int $capacity = 0, bool $allowoverbook = false,
                                   int $datetimeknown = 0, int $duration = 0, int $normalcost = 0, int $discountcost = 0,
                                   bool $allowcancellations = false, array $sessiondates = [], array $customfields = []) {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), [
            'facetofaceid' => $facetofaceid,
            'capacity' => $capacity,
            'allowoverbook' => $allowoverbook,
            'details' => $details,
            'datetimeknown' => $datetimeknown,
            'duration' => $duration,
            'normalcost' => $normalcost,
            'discountcost' => $discountcost,
            'allowcancellations' => $allowcancellations,
            'sessiondates' => $sessiondates,
            'customfields' => $customfields,
        ]);

        $transaction = $DB->start_delegated_transaction();

        // Check permissions to add sessions to this facetoface activity.
        $facetoface = $DB->get_record('facetoface', ['id' => $facetofaceid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('facetoface', $facetofaceid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/facetoface:editsessions', $context);

        $session = new stdClass();
        $session->facetoface = $facetofaceid;
        $session->timecreated = time();
        $session->timemodified = $session->timecreated;
        $session->visible = 1;

        $fields = ['capacity', 'allowoverbook', 'details', 'datetimeknown', 'duration', 'normalcost',
                   'discountcost', 'allowcancellations'];

        foreach ($fields as $field) {
            if (isset($params[$field])) {
                $session->$field = $params[$field];
            } else {
                if ($field == 'details') {
                    $session->$field = '';
                } else {
                    $session->$field = 0;
                }
            }
        }

        // Insert new session.
        $session->id = $DB->insert_record('facetoface_sessions', $session);

        // Add session dates.
        $sessiondates = [];
        if (!empty($params['sessiondates'])) {
            foreach ($params['sessiondates'] as $sdate) {
                $date = new stdClass();
                $date->sessionid = $session->id;
                $date->timestart = $sdate['timestart'];
                $date->timefinish = $sdate['timeend'];
                $DB->insert_record('facetoface_sessions_dates', $date);
                $sessiondates[] = $date;
            }
        }
        // Face to face add_instance adds a dummy record when no specified date so do it here too.
        if (empty($sessiondates)) {
            // Insert a dummy date record.
            $date = new stdClass();
            $date->sessionid = $session->id;
            $date->timestart = 0;
            $date->timefinish = 0;
            $DB->insert_record('facetoface_sessions_dates', $date);
            $sessiondates[] = $date;
        }

        // Create any calendar entries.
        $session->sessiondates = $sessiondates;
        facetoface_update_calendar_entries($session);

        // Handle customfields.
        if (!empty($params['customfields'])) {
            $customfields = facetoface_get_session_customfields();
            foreach ($params['customfields'] as $field) {
                foreach ($customfields as $dbfield) {
                    if ($dbfield->shortname === $field['shortname']) {
                        facetoface_save_customfield_value($dbfield->id, $field['value'], $sessionid, 'session');
                    }
                }
            }
        }

        // Trigger event.
        $eventparams = [
            'objectid' => $session->id,
            'context' => $context,
        ];
        $event = \mod_facetoface\event\add_session::create($eventparams);
        $event->add_record_snapshot('facetoface_sessions', $session);
        $event->add_record_snapshot('facetoface', $facetoface);
        $event->trigger();

        $transaction->allow_commit();

        return [
            'sessionid' => $session->id,
        ];
    }

    /**
     * Returns function.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'sessionid' => new external_value(PARAM_INT, 'ID of the created session'),
        ]);
    }
}
