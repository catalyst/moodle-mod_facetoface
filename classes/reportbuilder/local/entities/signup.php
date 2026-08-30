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

namespace mod_facetoface\reportbuilder\local\entities;

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\autocomplete;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use lang_string;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

/**
 * Current signup entity.
 *
 * This combines signups with the current, non-superceded signup status.
 *
 * @package    mod_facetoface
 * @copyright  2025 Murdoch University
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class signup extends base {
    /**
     * Database tables that this entity uses and their default aliases.
     *
     * @return string[]
     */
    protected function get_default_table_aliases(): array {
        return [
            'facetoface_signups' => 'fsu',
            'facetoface_signups_status' => 'fss',
        ];
    }

    /**
     * Database tables that this entity uses.
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return array_keys($this->get_default_table_aliases());
    }

    /**
     * Default entity title.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('signup', 'mod_facetoface');
    }

    /**
     * Initialise the entity.
     *
     * @return base
     */
    public function initialise(): base {
        $signup = $this->get_table_alias('facetoface_signups');
        $status = $this->get_table_alias('facetoface_signups_status');

        $this->add_join("LEFT JOIN {facetoface_signups_status} {$status}
                                ON {$status}.signupid = {$signup}.id
                               AND {$status}.superceded = 0");

        foreach ($this->get_all_columns() as $column) {
            $this->add_column($column);
        }

        foreach ($this->get_all_filters() as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Return all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $signup = $this->get_table_alias('facetoface_signups');
        $status = $this->get_table_alias('facetoface_signups_status');
        $columns = [];

        $columns[] = (new column(
            'mailedreminder',
            new lang_string('mailedreminder', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_field("{$signup}.mailedreminder")
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN);

        $columns[] = (new column(
            'discountcode',
            new lang_string('discountcode', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_field("{$signup}.discountcode")
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT);

        $columns[] = (new column(
            'status',
            new lang_string('status', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_field("{$status}.statuscode")
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_callback(static function (?int $value) {
                if ($value === MDL_F2F_STATUS_BOOKED) {
                    return get_string('status_booked', 'mod_facetoface');
                } elseif ($value === MDL_F2F_STATUS_WAITLISTED) {
                    return get_string('status_waitlisted', 'mod_facetoface');
                } elseif ($value === MDL_F2F_STATUS_REQUESTED) {
                    return get_string('status_requested', 'mod_facetoface');
                } elseif ($value === MDL_F2F_STATUS_APPROVED) {
                    return get_string('status_approved', 'mod_facetoface');
                } elseif ($value === MDL_F2F_STATUS_DECLINED) {
                    return get_string('status_declined', 'mod_facetoface');
                } elseif ($value === MDL_F2F_STATUS_USER_CANCELLED) {
                    return get_string('status_user_cancelled', 'mod_facetoface');
                } elseif ($value === MDL_F2F_STATUS_SESSION_CANCELLED) {
                    return get_string('status_session_cancelled', 'mod_facetoface');
                } elseif ($value === MDL_F2F_STATUS_NO_SHOW) {
                    return get_string('status_no_show', 'mod_facetoface');
                } elseif ($value === MDL_F2F_STATUS_PARTIALLY_ATTENDED) {
                    return get_string('status_partially_attended', 'mod_facetoface');
                } elseif ($value === MDL_F2F_STATUS_FULLY_ATTENDED) {
                    return get_string('status_fully_attended', 'mod_facetoface');
                }
                return $value;
            });

        $columns[] = (new column(
            'grade',
            new lang_string('gradenoun', 'core'),
            $this->get_entity_name()
        ))
            ->add_field("{$status}.grade")
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true);

        $columns[] = (new column(
            'note',
            new lang_string('note', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_field("{$status}.note")
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT);

        $columns[] = (new column(
            'timecreated',
            new lang_string('timecreated', 'core_reportbuilder'),
            $this->get_entity_name()
        ))
            ->add_field("{$status}.timecreated")
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true);

        return $columns;
    }

    /**
     * Return all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $signup = $this->get_table_alias('facetoface_signups');
        $status = $this->get_table_alias('facetoface_signups_status');
        $filters = [];

        $filters[] = (new filter(
            boolean_select::class,
            'mailedreminder',
            new lang_string('mailedreminder', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$signup}.mailedreminder"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'discountcode',
            new lang_string('discountcode', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$signup}.discountcode"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            autocomplete::class,
            'status',
            new lang_string('status', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$status}.statuscode"
        ))
            ->set_options([
                MDL_F2F_STATUS_BOOKED => get_string('status_booked', 'mod_facetoface'),
                MDL_F2F_STATUS_WAITLISTED => get_string('status_waitlisted', 'mod_facetoface'),
                MDL_F2F_STATUS_REQUESTED => get_string('status_requested', 'mod_facetoface'),
                MDL_F2F_STATUS_APPROVED => get_string('status_approved', 'mod_facetoface'),
                MDL_F2F_STATUS_DECLINED => get_string('status_declined', 'mod_facetoface'),
                MDL_F2F_STATUS_USER_CANCELLED => get_string('status_user_cancelled', 'mod_facetoface'),
                MDL_F2F_STATUS_SESSION_CANCELLED => get_string('status_session_cancelled', 'mod_facetoface'),
                MDL_F2F_STATUS_NO_SHOW => get_string('status_no_show', 'mod_facetoface'),
                MDL_F2F_STATUS_PARTIALLY_ATTENDED => get_string('status_partially_attended', 'mod_facetoface'),
                MDL_F2F_STATUS_FULLY_ATTENDED => get_string('status_fully_attended', 'mod_facetoface'),
            ])
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'grade',
            new lang_string('gradenoun', 'core'),
            $this->get_entity_name(),
            "{$status}.grade"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'note',
            new lang_string('note', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$status}.note"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            date::class,
            'timecreated',
            new lang_string('timecreated', 'core_reportbuilder'),
            $this->get_entity_name(),
            "{$status}.timecreated"
        ))->add_joins($this->get_joins());

        return $filters;
    }
}
