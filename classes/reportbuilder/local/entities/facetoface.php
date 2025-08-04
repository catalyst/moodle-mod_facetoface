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

use context_module;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use lang_string;
use stdClass;

/**
 * Facetoface entity class implementation
 *
 * @package     mod_facetoface
 * @copyright   2019 Moodle Pty Ltd <support@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class facetoface extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return ['facetoface' => 'ftf'];
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
        return new lang_string('facetoface', 'mod_facetoface');
    }

    /**
     * Initialise the entity
     *
     * @return base
     */
    public function initialise(): base {
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
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        global $DB;

        $facetoface = $this->get_table_alias('facetoface');

        // Column name.
        $columns[] = (new column(
            'name',
            new lang_string('name'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$facetoface}.name")
            ->set_is_sortable(true)
            ->add_callback(static function(string $value, stdClass $row): string {
                return format_string($value);
            });

        // Column description.
        $descriptionfieldsql = "{$facetoface}.intro";
        if ($DB->get_dbfamily() === 'oracle') {
            $descriptionfieldsql = $DB->sql_order_by_text($descriptionfieldsql, 1024);
        }
        $columns[] = (new column(
            'description',
            new lang_string('description'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_LONGTEXT)
            ->add_field($descriptionfieldsql, 'intro')
            ->add_fields("{$facetoface}.introformat, {$facetoface}.id")
            ->set_is_sortable(false)
            ->add_callback(static function(?string $intro, stdClass $facetoface): string {
                global $CFG;
                require_once("{$CFG->libdir}/filelib.php");

                if ($intro === null) {
                    return '';
                }

                [$course, $cm] = get_course_and_cm_from_instance($facetoface->id, 'facetoface');
                $context = context_module::instance($cm->id);

                $description = file_rewrite_pluginfile_urls($intro, 'pluginfile.php', $context->id, 'mod_facetoface',
                    'intro', null);

                return format_text($description, $facetoface->introformat, ['context' => $context]);
            });

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $facetoface = $this->get_table_alias('facetoface');

        // Filter name.
        $filters[] = (new filter(
            text::class,
            'name',
            new lang_string('name'),
            $this->get_entity_name(),
            "{$facetoface}.name"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
