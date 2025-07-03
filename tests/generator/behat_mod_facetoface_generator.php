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
 * Behat data generator for mod_facetoface.
 *
 * @package    mod_facetoface
 * @copyright  Copyright (C) 2023 Open LMS (https://www.openlms.net)
 * @author     Chris Tranel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_facetoface_generator extends behat_generator_base {

    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'sessions' => [
                'singular' => 'session',
                'datagenerator' => 'session',
                'required' => ['facetoface'],
                'switchids' => ['facetoface' => 'facetofaceid'],
            ],
            'customfields' => [
                'singular' => 'customfield',
                'datagenerator' => 'customfield',
                'required' => ['name'],
            ],
        ];
    }

    /**
     * Get the facetoface id using an activity idnumber.
     *
     * @param string $identifier
     * @return int The facetoface id
     */
    protected function get_facetoface_id(string $identifier): int {
        $cm = $this->get_cm_by_activity_name('facetoface', $identifier);

        return $cm->instance;
    }
}
