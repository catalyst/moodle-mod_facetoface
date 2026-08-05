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
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use lang_string;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

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
            ->add_callback(static function (string $value, stdClass $row): string {
                return format_string($value);
            });

        // Column short name.
        $columns[] = (new column(
            'shortname',
            new lang_string('shortname', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$facetoface}.shortname")
            ->set_is_sortable(true);

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
            ->add_callback(static function (?string $intro, stdClass $facetoface): string {
                global $CFG;
                require_once("{$CFG->libdir}/filelib.php");

                if ($intro === null) {
                    return '';
                }

                [$course, $cm] = get_course_and_cm_from_instance($facetoface->id, 'facetoface');
                $context = context_module::instance($cm->id);

                $description = file_rewrite_pluginfile_urls(
                    $intro,
                    'pluginfile.php',
                    $context->id,
                    'mod_facetoface',
                    'intro',
                    null
                );

                return format_text($description, $facetoface->introformat, ['context' => $context]);
            });

        // Column third-party email addresses.
        $columns[] = (new column(
            'thirdparty',
            new lang_string('thirdpartyemailaddress', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$facetoface}.thirdparty");

        // Column third-party wait-list notifications.
        $columns[] = (new column(
            'thirdpartywaitlist',
            new lang_string('thirdpartywaitlist', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("{$facetoface}.thirdpartywaitlist");

        // Column reminder period.
        $columns[] = (new column(
            'reminderperiod',
            new lang_string('reminderperiod', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$facetoface}.reminderperiod")
            ->set_is_sortable(true);

        // Column calendar display setting.
        $columns[] = (new column(
            'showoncalendar',
            new lang_string('showoncalendar', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$facetoface}.showoncalendar")
            ->add_callback(static function (?int $value) {
                if ($value === F2F_CAL_NONE) {
                    return get_string('none', 'core');
                } elseif ($value === F2F_CAL_COURSE) {
                    return get_string('course', 'core');
                } elseif ($value === F2F_CAL_SITE) {
                    return get_string('site', 'core');
                }
                return $value;
            });

        // Column approval required.
        $columns[] = (new column(
            'approvalreqd',
            new lang_string('approvalreqd', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("{$facetoface}.approvalreqd");

        // Column user calendar entry.
        $columns[] = (new column(
            'usercalentry',
            new lang_string('usercalentry', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("{$facetoface}.usercalentry");

        // Column time created.
        $columns[] = (new column(
            'timecreated',
            new lang_string('timecreated', 'core_reportbuilder'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$facetoface}.timecreated")
            ->set_is_sortable(true);

        // Column time modified.
        $columns[] = (new column(
            'timemodified',
            new lang_string('timemodified', 'core_reportbuilder'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$facetoface}.timemodified")
            ->set_is_sortable(true);

        // Column signup type.
        $columns[] = (new column(
            'signuptype',
            new lang_string('signuptype', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$facetoface}.signuptype")
            ->add_callback(static function (?int $value) {
                if ($value === MOD_FACETOFACE_SIGNUP_SINGLE) {
                    return get_string('single', 'mod_facetoface');
                } elseif ($value === MOD_FACETOFACE_SIGNUP_MULTIPLE) {
                    return get_string('multiple', 'mod_facetoface');
                }
                return $value;
            });

        // Column multiple signup method.
        $columns[] = (new column(
            'multiplesignupmethod',
            new lang_string('multiplesignupmethod', 'mod_facetoface'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$facetoface}.multiplesignupmethod")
            ->add_callback(static function (?int $value) {
                if ($value === MOD_FACETOFACE_SIGNUP_MULTIPLE_PER_SESSION) {
                    return get_string('multiplesignuppersession', 'mod_facetoface');
                } elseif ($value === MOD_FACETOFACE_SIGNUP_MULTIPLE_PER_ACTIVITY) {
                    return get_string('multiplesignupperactivity', 'mod_facetoface');
                }
                return $value;
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

        // Filter short name.
        $filters[] = (new filter(
            text::class,
            'shortname',
            new lang_string('shortname', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$facetoface}.shortname"
        ))->add_joins($this->get_joins());

        // Filter description.
        $filters[] = (new filter(
            text::class,
            'description',
            new lang_string('description'),
            $this->get_entity_name(),
            "{$facetoface}.intro"
        ))->add_joins($this->get_joins());

        // Filter third-party email addresses.
        $filters[] = (new filter(
            text::class,
            'thirdparty',
            new lang_string('thirdpartyemailaddress', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$facetoface}.thirdparty"
        ))->add_joins($this->get_joins());

        // Filter third-party wait-list notifications.
        $filters[] = (new filter(
            boolean_select::class,
            'thirdpartywaitlist',
            new lang_string('thirdpartywaitlist', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$facetoface}.thirdpartywaitlist"
        ))->add_joins($this->get_joins());

        // Filter calendar display setting.
        $filters[] = (new filter(
            select::class,
            'showoncalendar',
            new lang_string('showoncalendar', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$facetoface}.showoncalendar"
        ))
            ->set_options([
                F2F_CAL_NONE => get_string('none', 'core'),
                F2F_CAL_COURSE => get_string('course', 'core'),
                F2F_CAL_SITE => get_string('site', 'core'),
            ])
            ->add_joins($this->get_joins());

        // Filter approval required.
        $filters[] = (new filter(
            boolean_select::class,
            'approvalreqd',
            new lang_string('approvalreqd', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$facetoface}.approvalreqd"
        ))->add_joins($this->get_joins());

        // Filter user calendar entry.
        $filters[] = (new filter(
            boolean_select::class,
            'usercalentry',
            new lang_string('usercalentry', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$facetoface}.usercalentry"
        ))->add_joins($this->get_joins());

        // Filter time created.
        $filters[] = (new filter(
            date::class,
            'timecreated',
            new lang_string('timecreated', 'core_reportbuilder'),
            $this->get_entity_name(),
            "{$facetoface}.timecreated"
        ))->add_joins($this->get_joins());

        // Filter time modified.
        $filters[] = (new filter(
            date::class,
            'timemodified',
            new lang_string('timemodified', 'core_reportbuilder'),
            $this->get_entity_name(),
            "{$facetoface}.timemodified"
        ))->add_joins($this->get_joins());

        // Filter signup type.
        $filters[] = (new filter(
            select::class,
            'signuptype',
            new lang_string('signuptype', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$facetoface}.signuptype"
        ))
            ->set_options([
                MOD_FACETOFACE_SIGNUP_SINGLE => get_string('single', 'mod_facetoface'),
                MOD_FACETOFACE_SIGNUP_MULTIPLE => get_string('multiple', 'mod_facetoface'),
            ])
            ->add_joins($this->get_joins());

        // Filter multiple signup method.
        $filters[] = (new filter(
            select::class,
            'multiplesignupmethod',
            new lang_string('multiplesignupmethod', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$facetoface}.multiplesignupmethod"
        ))
            ->set_options([
                MOD_FACETOFACE_SIGNUP_MULTIPLE_PER_SESSION => get_string('multiplesignuppersession', 'mod_facetoface'),
                MOD_FACETOFACE_SIGNUP_MULTIPLE_PER_ACTIVITY => get_string('multiplesignupperactivity', 'mod_facetoface'),
            ])
            ->add_joins($this->get_joins());

        // Filter reminder period.
        $filters[] = (new filter(
            number::class,
            'reminderperiod',
            new lang_string('reminderperiod', 'mod_facetoface'),
            $this->get_entity_name(),
            "{$facetoface}.reminderperiod"
        ))->add_joins($this->get_joins());

        return $filters;
    }
}
