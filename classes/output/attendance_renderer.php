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
 * @copyright  2016 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 */

namespace mod_facetoface\output;

defined('MOODLE_INTERNAL') || die();

use \html_writer;
use \html_table;
use \moodle_url;
use \context_module;

class attendance_renderer extends \plugin_renderer_base {

    /**
     * Render a form to export attendees for the Face-to-Face sessions
     *
     * @param object $instance the Face-to-face record instance
     * @param object $cm the Face-to-Face course module
     * @param string $location the location filter string if used
     * @return string HTML
     */
    public function attendees_export_form($instance, $cm, $location) {

        $html = '';
        $context = context_module::instance($cm->id);
        if (has_capability('mod/facetoface:viewattendees', $context)) {
            $formats = array(
                'excel' => get_string('excelformat', 'facetoface'),
                'ods'   => get_string('odsformat', 'facetoface')
            );

            $html .= $this->output->heading(get_string('exportattendance', 'facetoface'));
            $html .= html_writer::start_tag('form', array('action' => 'view.php', 'method' => 'get'));
            $html .= html_writer::start_tag('div');
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'f', 'value' => $instance->id));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'location', 'value' => $location));
            $html .= get_string('format', 'facetoface') . '&nbsp;';
            $html .= html_writer::select($formats, 'download', 'excel', '');
            $html .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('exporttofile', 'facetoface')));
            $html .= html_writer::end_tag('div'). html_writer::end_tag('form');
        }

        return $html;
    }

    /**
     * Render a list of session attendees (signed up users)
     *
     * @param object $instance the Face-to-face record instance
     * @param object $session the Face-to-face session instance
     * @param object $cm the Face-to-Face course module
     * @return string HTML
     */
    public function session_attendees($instance, $session, $cm) {
        $context = context_module::instance($cm->id);
        $viewfullnames = has_capability('moodle/site:viewfullnames', $context);

        $table = new html_table();
        $table->summary = get_string('attendeestablesummary', 'facetoface');
        $table->align = array('left');
        $table->size = array('100%');
        $this->session_attendees_table_head($table);

        // List attendees and any cost, discount and booking status details.
        foreach ($session->attendees as $attendee) {
            $data = array();
            $userurl = new moodle_url('/user/view.php', array('id' => $attendee->id, 'course' => $instance->course));
            $data[] = html_writer::link($userurl, format_string(fullname($attendee, $viewfullnames)));

            // Add the attendees cost and discount information if this hasn't been hidden
            // from display.
            if ($costdata = $this->session_attendees_cost_discount($instance, $session, $attendee)) {
                $data = array_merge($data, $costdata);
            }

            // Current booking status.
            $data[] = str_replace(' ', '&nbsp;', $instance->format_booking_status($attendee->statuscode));
            $table->data[] = $data;
        }

        return html_writer::table($table);
    }

    /**
     * Helper function to add the header to the session attendees table
     * includes cost and discount columns if not hidden from display
     *
     * @param object $table by reference the table to add header info
     */
    private function session_attendees_table_head(&$table) {
        $table->head = array(get_string('name'));

        // Check if we are listing the attendees booking cost.
        if (!get_config(null, 'facetoface_hidecost')) {
            $table->head[] = get_string('cost', 'facetoface');
            $table->align[] = 'center';

            // Additionally if this was discounted.
            if (!get_config(null, 'facetoface_hidediscount')) {
                $table->head[] = get_string('discountcode', 'facetoface');
                $table->align[] = 'center';
            }
        }

        // Attendance or booking status.
        $table->head[] = get_string('attendance', 'facetoface');
        $table->align[] = 'center';
    }

    /**
     * Helper function to add the attendees cost and discount information
     * to the table row if not hidden from display
     *
     * @param object $instance the Face-to-face record instance
     * @param object $session the current session instance
     * @param object $attendee the current attendee instance
     * @return false|array
     */
    private function session_attendees_cost_discount($instance, $session, $attendee) {
        if (!get_config(null, 'facetoface_hidecost')) {
            $data = array();
            $data[] = $instance->format_booking_cost($session, $attendee);
            if (!get_config(null, 'facetoface_hidediscount')) {
                $data[] = $attendee->discountcode;
            }

            return $data;
        }

        return false;
    }
}
