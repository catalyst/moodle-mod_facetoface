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
 * Copyright (C) 2007-2011 Catalyst IT (http://www.catalyst.net.nz)
 * Copyright (C) 2011-2013 Totara LMS (http://www.totaralms.com)
 * Copyright (C) 2014 onwards Catalyst IT (http://www.catalyst-eu.net)
 *
 * @package    mod
 * @subpackage facetoface
 * @copyright  2014 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @author     Alastair Munro <alastair.munro@totaralms.com>
 * @author     Aaron Barnes <aaron.barnes@totaralms.com>
 * @author     Francois Marier <francois@catalyst.net.nz>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/facetoface/classes/facetoface.class.php');

class mod_facetoface_renderer extends plugin_renderer_base {

    /**
     * Render a list of all Face-to-Face instances within
     * the course
     *
     * @param object $course the current course
     * @param array $instances Face-to-Face instances
     * @return string HTML
     */
    public function index_table($course, $instances) {
        $format     = course_get_format($course);
        $strmanager = get_string_manager();
        $context    = context_course::instance($course->id);

        $table = new html_table();
        $table->width = '100%';
        $table->head = array();
        $table->align = array();

        // If the format uses sections then display as first column.
        if ($format->uses_sections()) {
            if ($strmanager->string_exists('sectionname', 'format_' . $format->get_format())) {
                $table->head[] = $strmanager->get_string('sectionname', 'format_' . $format->get_format());
                $table->align[] = 'left';
            } else {
                $table->head[] = $strmanager->get_string('section');
                $table->align[] = 'left';
            }
        }
        $table->head[] = $strmanager->get_string('facetofacename', 'facetoface');
        $table->align[] = 'left';

        // If the user can view Face-to-Face attendees display as a final column.
        $viewattendees = has_capability('mod/facetoface:viewattendees', $context);
        if ($viewattendees) {
            $table->head[] = $strmanager->get_string('signups', 'facetoface');
            $table->align[] = 'center';
        }

        // Render Face-to-Face instances as rows.
        $timenow = time();
        foreach ($instances as $instance) {
            $data = array();
            $class = '';
            if (!$instance->visible) {
                $class = 'dimmed';
            }
            if ($format->uses_sections()) {
                if (isset($instance->section)) {
                    $data[] = $format->get_section_name($instance->section);
                } else {
                    $data[] = '';
                }
            }

            $viewurl = new moodle_url('/mod/facetoface/view.php', array('id' => $instance->coursemodule));
            $instancelink = html_writer::link($viewurl, $instance->name, array('class' => $class));
            $data[] = $instancelink;

            if ($viewattendees) {
                $facetoface = facetoface::get($instance->id);
                $totalattendees = 0;
                if ($sessions = $facetoface->get_sessions_list()) {
                    foreach ($sessions as $session) {
                        $totalattendees += $session->attendees;
                    }
                }
                $data[] = $totalattendees;
            }
            $table->data[] = $data;
        }

        return html_writer::table($table);
    }

    /**
     * Render the Face-to-Face introduction
     *
     * @param object $instance the current Face-to-Face instance
     * @param object $cm the Face-to-Face course module
     * @return string HTML
     */
    public function introduction($instance, $cm) {
        global $OUTPUT;

        $html = '';
        if ($instance->intro) {
            $html .= $OUTPUT->box_start('generalbox', 'intro');
            $html .= format_module_intro('facetoface', $instance, $cm->id);
            $html .= $OUTPUT->box_end();
        }

        return $html;
    }

    /**
     * Render a Face-to-Face location filter
     *
     * @param object $instance the Face-to-Face record instance
     * @param array $locations the locations menu for the instance
     * @param string $selected the currently selected location
     * @return string HTML
     */
    public function location_filter($instance, $locations, $selected=null) {
        global $OUTPUT, $PAGE;

        $html = '';
        if (count($locations) > 2) {
            $html  = $OUTPUT->single_select($PAGE->url, 'location', $locations, $selected, array());
        }

        return html_writer::tag('div', get_string('showbylocation', 'facetoface') . ": {$html}",
            array('class' => 'generalbox locationfilter clearfix'));
    }
}
