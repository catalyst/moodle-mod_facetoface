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

use core_reportbuilder\local\filters\duration;
use core_reportbuilder\local\filters\select;
use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use mod_facetoface\reportbuilder\local\formatters\facetoface as facetoface_formatter;

/**
 * Session date entity
 *
 * @package     mod_facetoface
 * @copyright   2022 Moodle Pty Ltd <support@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session_date extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'facetoface_sessions_dates' => 'fd',
        ];
    }

    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return array_keys($this->get_default_table_aliases());
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('facetofacesessiondates', 'mod_facetoface');
    }

    /**
     * Initialise the entity
     *
     * @return base
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $sessiondates = $this->get_table_alias('facetoface_sessions_dates');
        $dateformat = get_string('strftimedaydate', 'core_langconfig');
        $timeformat = get_string('strftimetime', 'core_langconfig');

        // Date start.
        $columns[] = (new column(
            'datestart',
            new lang_string('sessionstartdate', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$sessiondates}.timestart")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $dateformat);

        // Date start time.
        $columns[] = (new column(
            'starttime',
            new lang_string('sessionstarttime', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$sessiondates}.timestart")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $timeformat);

        // Date finish time.
        $columns[] = (new column(
            'finishtime',
            new lang_string('sessionfinishtime', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$sessiondates}.timefinish")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], $timeformat);

        // Column sessiondatetime: date (timestart - timefinish).
        $columns[] = (new column(
            'sessiondatetime',
            new lang_string('sessiondatetime', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("$sessiondates.timestart")
            ->add_field("$sessiondates.timefinish")
            ->add_callback([facetoface_formatter::class, 'sessiondatetime']);

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $sessiondates = $this->get_table_alias('facetoface_sessions_dates');

        // Date start.
        $filters[] = (new filter(
            date::class,
            'datestart',
            new lang_string('facetofacesessiondates', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$sessiondates}.timestart"
        ))
            ->add_joins($this->get_joins())
            ->set_limited_operators([
                date::DATE_ANY,
                date::DATE_RANGE,
                date::DATE_CURRENT,
                date::DATE_LAST,
                date::DATE_NEXT,
            ]);

        // Duration filter.
        $filters[] = (new filter(
            duration::class,
            'duration',
            new lang_string('duration', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$sessiondates}.timefinish - {$sessiondates}.timestart"
        ))
            ->add_joins($this->get_joins());

        // Filter session status.
        $now = time();
        $filters[] = (new filter(
            select::class,
            'status',
            new lang_string('sessionstatus', 'mod_facetoface'),
            $this->get_entity_name(),
        ))
            ->add_joins($this->get_joins())
            ->set_field_sql("CASE WHEN
                {$sessiondates}.timestart > " . $now . "
                THEN 1
                WHEN
                ({$sessiondates}.timestart <= " . $now . " AND
                {$sessiondates}.timefinish > " . $now . ")
                THEN 2
                WHEN
                {$sessiondates}.timefinish <= " . $now . "
                THEN 3
                ELSE 0
            END")
            ->set_options_callback([facetoface_formatter::class, 'get_session_statuses']);

        return $filters;
    }
}
