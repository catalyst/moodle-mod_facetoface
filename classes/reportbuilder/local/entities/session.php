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
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\helpers\custom_fields;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\helpers\database;
use lang_string;
use mod_facetoface\reportbuilder\local\formatters\facetoface as facetoface_formatter;
use mod_facetoface\reportbuilder\local\helpers\facetoface as facetoface_helper;
use stdClass;

/**
 * Facetoface session entity class implementation
 *
 * @package     mod_facetoface
 * @copyright   2019 Moodle Pty Ltd <support@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session extends base {

    /** @var custom_fields */
    protected $customfields;

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'facetoface_sessions' => 'fs',
            'facetoface_sessions_dates' => 'fsd',
            'course' => 'c',
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
     * The default title for this entity in the list of columns/conditions/filters in the report builder
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('facetofacesession', 'mod_facetoface');
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
        global $DB;

        $dbfamily = $DB->get_dbfamily();
        $session = $this->get_table_alias('facetoface_sessions');
        $coursealias = $this->get_table_alias('course');

        // Column details.
        $detailsfieldsql = "{$session}.details";
        if ($dbfamily === 'oracle') {
            $detailsfieldsql = $DB->sql_order_by_text($detailsfieldsql, 1024);
        }
        $columns[] = (new column(
            'details',
            new lang_string('description'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join("LEFT JOIN {modules} m ON m.name = 'facetoface'")
            ->add_join("
                LEFT JOIN {course_modules} cm
                       ON {$session}.facetoface = cm.instance AND cm.course = {$coursealias}.id AND cm.module = m.id
            ")
            ->set_type(column::TYPE_LONGTEXT)
            ->add_field($detailsfieldsql, 'details')
            ->add_field("{$session}.facetoface", 'facetofaceid')
            ->add_field('cm.id')
            ->set_is_sortable(false);

        // Column capacity.
        $columns[] = (new column(
            'capacity',
            new lang_string('capacity', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$session}.capacity")
            ->set_is_sortable(true);

        // Column allow wait listing.
        $columns[] = (new column(
            'allowoverbook',
            new lang_string('allowoverbook', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("{$session}.allowoverbook")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'boolean_as_text']);

        // Column allow cancellations.
        $columns[] = (new column(
            'allowcancellations',
            new lang_string('allowcancellations', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("{$session}.allowcancellations")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'boolean_as_text']);

        // Bookings query.
        $bookingsquery = facetoface_helper::get_bookings_query($session);

        // Column seatsbooked.
        $column = (new column(
            'seatsbooked',
            new lang_string('seatsbooked', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field($bookingsquery, 'seatsbooked');

        // TODO: See WP-1397 - seats booked is already an aggregate function, so can't be grouped by in MSSQL/Oracle.
        if ($dbfamily === 'mssql' || $dbfamily === 'oracle') {
            $column->set_groupby_sql("{$session}.id");
        }
        // MSSQL can not aggregate columns with sub-query, so we'll disable all aggregation methods for them.
        if ($dbfamily === 'mssql') {
            $column->set_disabled_aggregation_all();
        }
        $columns[] = $column;

        // Column bookedvscapacity.
        $column = (new column(
            'bookedvscapacity',
            new lang_string('bookedvscapacity', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field($bookingsquery, 'seatsbooked')
            ->add_field("{$session}.capacity", 'capacity')
            ->set_is_sortable(true)
            ->add_callback(static function(string $value, stdClass $row): string {
                return $row->seatsbooked . ' / ' . $row->capacity;
            });

        // TODO: See WP-1397 - booked vs capacity contains an aggregate function, so can't be grouped by in MSSQL/Oracle.
        if ($dbfamily === 'mssql' || $dbfamily === 'oracle') {
            $column->set_groupby_sql("{$session}.id, {$session}.capacity");
        }
        // MSSQL can not aggregate columns with sub-query, so we'll disable all aggregation methods for them.
        if ($dbfamily === 'mssql') {
            $column->set_disabled_aggregation_all();
        }
        $columns[] = $column;

        // Column session visibility.
        $columns[] = (new column(
            'visibility',
            new lang_string('sessionvisibility', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("{$session}.visible")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'boolean_as_text']);

        // Column session status.
        $columns[] = (new column(
            'status',
            new lang_string('sessionstatus', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$session}.id", 'sessionid')
            ->set_is_sortable(true)
            ->add_callback([facetoface_formatter::class, 'sessionstatus']);

        foreach ($this->get_custom_fields() as $field) {
            $fieldalias = $field->alias;
            $columns[] = (new column(
                'customfield_' . $field->shortname,
                new lang_string('customfieldcolumn', 'core_reportbuilder', $field->name),
                $this->get_entity_name()
            ))
                ->add_joins($this->get_joins())
                ->add_join($this->get_custom_field_join($field))
                ->add_field("{$fieldalias}.data")
                ->set_type(column::TYPE_TEXT);
        }

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $session = $this->get_table_alias('facetoface_sessions');
        $filters = [];
        // Filter capacity.
        $filters[] = (new filter(
            number::class,
            'capacity',
            new lang_string('capacity', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$session}.capacity"
        ))
            ->add_joins($this->get_joins());

        // Filter allowwaitlist.
        $filters[] = (new filter(
            boolean_select::class,
            'allowoverbook',
            new lang_string('allowoverbook', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$session}.allowoverbook"
        ))
            ->add_joins($this->get_joins());

        // Filter allowcancellations.
        $filters[] = (new filter(
            boolean_select::class,
            'allowcancellations',
            new lang_string('allowcancellations', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$session}.allowcancellations"
        ))
            ->add_joins($this->get_joins());

        // Bookings query.
        $bookingsquery = facetoface_helper::get_bookings_query($session);

        // Filter seatsbooked.
        $filters[] = (new filter(
            number::class,
            'seatsbooked',
            new lang_string('seatsbooked', 'mod_facetoface'),
            $this->get_entity_name(),
            $bookingsquery
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            select::class,
            'sessionavailability',
            new lang_string('sessionavailability', 'mod_facetoface'),
            $this->get_entity_name(),
        ))
            ->add_joins($this->get_joins())
            ->set_field_sql("CASE WHEN
                " . $bookingsquery . " >= {$session}.capacity
                THEN 1
                WHEN
                " . $bookingsquery . " = 0
                THEN 2
                WHEN
                (" . $bookingsquery . " > 0 AND
                " . $bookingsquery . " < {$session}.capacity)
                THEN 3
                ELSE 0
            END")
            ->set_options_callback([facetoface_formatter::class, 'get_sessionavailability_statuses']);

        // Fully booked sessions.
        $filters[] = (new filter(
            boolean_select::class,
            'fullybooked',
            new lang_string('fullybooked', 'mod_facetoface'),
            $this->get_entity_name(),
        ))
            ->add_joins($this->get_joins())
            ->set_field_sql("CASE WHEN ({$bookingsquery} >= {$session}.capacity) THEN 1 ELSE 0 END");


        // Filter session visibility.
        $filters[] = (new filter(
            boolean_select::class,
            'visible',
            new lang_string('sessionvisibility', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$session}.visible"
        ))
            ->add_joins($this->get_joins());

        foreach ($this->customfields as $field) {
            $fieldalias = $field->alias;
            $filters[] = (new filter(
                text::class,
                'customfield_' . $field->shortname,
                new lang_string('customfieldcolumn', 'core_reportbuilder', $field->name),
                $this->get_entity_name(),
                "{$fieldalias}.data"
            ))
                ->add_joins($this->get_joins())
                ->add_join($this->get_custom_field_join($field));
        }

        return $filters;
    }

    /**
     * Get a customfield join.
     *
     * @param stdClass $field The custom field.
     * @return string
     */
    protected function get_custom_field_join($field): string {
        $table = $this->get_table_alias('facetoface_sessions');

        $fieldalias = $field->alias;
        $join = "LEFT JOIN {facetoface_session_data} {$fieldalias}
                        ON {$fieldalias}.sessionid = {$table}.id
                       AND {$fieldalias}.fieldid = {$field->id}";

        return $join;
    }

    /**
     * Get the custom fields.
     *
     * @return array
     */
    protected function get_custom_fields(): array {
        global $DB;
        if (!isset($this->customfields)) {
            $this->customfields = array_map(function($record) {
                return (object) [
                    'id' => $record->id,
                    'shortname' => $record->shortname,
                    'name' => $record->name,
                    'alias' => database::generate_alias(),
                ];
            }, $DB->get_records('facetoface_session_field', [], 'name ASC'));
        }
        return $this->customfields;
    }
}
