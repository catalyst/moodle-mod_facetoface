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
 * Helper functions for plugin.
 *
 * @package    mod_facetoface
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Check if manager approval is required for a particular activity.
     *
     * @param \stdClass $instance DB record of a facetoface activity.
     * @return bool
     *
     * @throws \coding_exception
     */
    public static function is_approval_required(\stdClass $instance): bool {
        // Check the object contains expected data.
        if (!property_exists($instance, 'id') || !property_exists($instance, 'approvalreqd')) {
            throw new \coding_exception('Expected facetoface record to contain an id and approvalreqd property');
        }

        // Approvals must be enabled at site level and activity level.
        return get_config('facetoface', 'enableapprovals') && $instance->approvalreqd;
    }
}
