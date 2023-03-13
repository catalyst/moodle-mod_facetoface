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

/**
 * Generate a test activity for unit testing.
 *
 * @package    mod_facetoface
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_generator extends testing_module_generator {

    /**
     * Create a new instance of the facetoface activity.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, array $options = null) {
        $record = (array) $record;

        $defaultsettings = [
            'thirdparty' => '',
            'thirdpartywaitlist' => 0,
            'display' => 0,
            'confirmationsubject' => '',
            'confirmationinstrmngr' => '',
            'confirmationmessage' => ['text' => ''],
            'confirmationmessageformat' => 0,
            'waitlistedsubject' => '',
            'waitlistedmessage' => '',
            'cancellationsubject' => '',
            'cancellationinstrmngr' => '',
            'cancellationmessage' => '',
            'remindersubject' => '',
            'reminderinstrmngr' => '',
            'remindermessage' => '',
            'reminderperiod' => 0,
            'requestsubject' => '',
            'requestinstrmngr' => '',
            'requestmessage' => '',
            'timecreated' => 0,
            'timemodified' => 0,
            'shortname' => 'testfacetoface',
            'showoncalendar' => 1,
            'approvalreqd' => 0,
            'usercalentry' => 1,
            'allowcancellationsdefault' => 1,
            'signuptype' => 0,
            'multiplesignupmethod' => 0,
        ];

        $record = (object) array_merge($defaultsettings, $record);

        return parent::create_instance($record, (array) $options);
    }
}
