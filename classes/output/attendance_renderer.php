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
     * @param int $returnto the current return to page ID
     * @param bool $takeattendance if true the user is taking attendance
     * @return string HTML
     */
    public function session_attendees($instance, $session, $cm, $returnto=0, $takeattendance=false) {
        global $OUTPUT, $USER;

        if (empty($session->attendees) || !$session->attendees) {
            return $OUTPUT->notification(get_string('nosignedupusers', 'facetoface'));
        }
        $html = '';

        // Start take attendance form.
        if ($takeattendance) {
            $statusoptions = $instance->get_booking_status_options($takeattendance);
            if (!empty($statusoptions)) {
                $html .= $this->start_take_attendance_form($instance, $session, $returnto);
            }
        }

        $context = context_module::instance($cm->id);
        $viewfullnames = has_capability('moodle/site:viewfullnames', $context);
        $table = new html_table();
        $table->summary = get_string('attendeestablesummary', 'facetoface');
        $table->align = array('left');
        $table->size = array('100%');
        $this->session_attendees_table_head($table, $takeattendance);

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

            // Current booking status and attendance.
            $data[] = str_replace(' ', '&nbsp;', $instance->format_booking_status($attendee->statuscode));
            if ($takeattendance && !empty($statusoptions)) {
                $optionid = 'submissionid_' . $attendee->submissionid;
                $status = $attendee->statuscode;
                $select = html_writer::select($statusoptions, $optionid, $status);
                $data[] = $select;
            }
            $table->data[] = $data;
        }

        $html .= html_writer::table($table);

        // End attendance form.
        if ($takeattendance && !empty($statusoptions)) {
            $html .= $this->end_take_attendance_form();
        }

        return $html;
    }

    /**
     * Helper function to render the start of the take attendance form
     *
     * @param object $instance the Face-to-face record instance
     * @param object $session the Face-to-face session instance
     * @param int $returnto the current return to page ID
     * @return string
     */
    private function start_take_attendance_form($instance, $session, $returnto=0) {
        global $USER;

        if (!$returnto) {
            $returnto = $instance->id;
        }

        $url = new moodle_url('/mod/facetoface/attendees.php', array('s' => $session->id, 'takeattendance' => true));
        $html  = html_writer::start_tag('div', array('class' => 'saveattendance'));
        $html .= html_writer::start_tag('form', array('action' => $url, 'method' => 'post'));
        $html .= html_writer::tag('p', get_string('attendanceinstructions', 'facetoface'));
        $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => $USER->sesskey));
        $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 's', 'value' => $session->id));
        $html .= html_writer::empty_tag('input', array('type' => 'hidden', ' name' => 'backtoallsessions', 'value' => $returnto));

        return $html;
    }

    /**
     * Helper function to render the end of the take attendance form
     *
     * @return string
     */
    private function end_take_attendance_form() {
        $html  = html_writer::start_tag('div', array('class' => 'saveattendance-buttons'));
        $html .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('saveattendance', 'facetoface')));
        $html .= '&nbsp;';
        $html .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'cancelform', 'value' => get_string('cancel')));
        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_tag('div');

        return $html;
    }

    /**
     * Helper function to add the header to the session attendees table
     * includes cost and discount columns if not hidden from display
     *
     * @param object $table by reference the table to add header info
     * @param bool $takeattendance if true the user is taking attendance
     */
    private function session_attendees_table_head(&$table, $takeattendance=false) {
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
        if ($takeattendance) {
            $table->head[] = get_string('currentstatus', 'facetoface');
            $table->align[] = 'center';
            $table->head[] = get_string('attendedsession', 'facetoface');
            $table->align[] = 'center';
        } else {
            $table->head[] = get_string('attendance', 'facetoface');
            $table->align[] = 'center';
        }
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

    /**
     * Render a list of session attendees (signed up users)
     *
     * @param object $instance the Face-to-face record instance
     * @param object $session the Face-to-face session instance
     * @param object $cm the Face-to-Face course module
     * @param array $requests the list of signup requests to display
     * @param int $returnto the current return to page ID
     * @return string HTML
     */
    public function session_requests($instance, $session, $cm, $requests, $returnto=0) {
        global $USER, $OUTPUT;

        if (!$returnto) {
            $returnto = $instance->id;
        }

        $html = '';
        if (empty($requests)) {
            $html .= $OUTPUT->notification(get_string('noactionableunapprovedrequests', 'facetoface'));
        } else {
            $context = context_module::instance($cm->id);
            $viewfullnames = has_capability('moodle/site:viewfullnames', $context);

            $attendees = count($session->attendees);
            $canbookuser = $instance->session_has_capacity($session, $context);
            if ($canbookuser) {
                $action = new moodle_url('attendees.php', array('s' => $session->id));
                $html .= html_writer::start_tag('form', array('action' => $action->out(), 'method' => 'post'));
                $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => $USER->sesskey));
                $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 's', 'value' => $session->id));
                $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'backtoallsessions', 'value' => $returnto)) . html_writer::end_tag('p');
            } else {
                $html .= html_writer::tag('p', get_string('cannotapproveatcapacity', 'facetoface'));
            }

            $table = new html_table();
            $table->summary = get_string('requeststablesummary', 'facetoface');
            $table->head = array(get_string('name'), get_string('timerequested', 'facetoface'));
            $table->align = array('left', 'center');
            if ($canbookuser) {
                $table->head[] = get_string('decidelater', 'facetoface');
                $table->head[] = get_string('decline', 'facetoface');
                $table->head[] = get_string('approve', 'facetoface');
                $table->align[] = 'center';
                $table->align[] = 'center';
                $table->align[] = 'center';
            }

            foreach ($requests as $attendee) {
                $data = array();
                $link = new moodle_url('/user/view.php', array('id' => $attendee->id, 'course' => $instance->course));
                $data[] = html_writer::link($link, format_string(fullname($attendee, $viewfullnames)));
                $data[] = userdate($attendee->timerequested, get_string('strftimedatetime'));

                if ($canbookuser) {
                    $name = "requests[{$attendee->id}]";
                    $data[] = html_writer::empty_tag('input', array('type' => 'radio', 'name' => $name, 'value' => '0', 'checked' => 'checked'));
                    $data[] = html_writer::empty_tag('input', array('type' => 'radio', 'name' => $name, 'value' => '1'));
                    $data[] = html_writer::empty_tag('input', array('type' => 'radio', 'name' => $name, 'value' => '2'));
                }
                $table->data[] = $data;
            }
            $html .= html_writer::table($table);

            // End of requests form.
            if ($canbookuser) {
                $html .= html_writer::start_tag('div', array('class' => 'saverequests-buttons'));
                $html .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('updaterequests', 'facetoface')));
                $html .= html_writer::end_tag('div');
                $html .= html_writer::end_tag('form');
            }
        }

        return $html;
    }

    /**
     * Render a list of session cancellations
     *
     * @param object $instance the Face-to-face record instance
     * @param object $session the Face-to-face session instance
     * @param object $cm the Face-to-Face course module
     * @param array $cancellations the list of booking cancellations to display
     * @param int $returnto the current return to page ID
     * @return string HTML
     */
    public function session_cancellations($instance, $session, $cm, $cancellations, $returnto=0) {
        global $OUTPUT;

        $table = new html_table();
        $table->summary = get_string('cancellationstablesummary', 'facetoface');
        $table->head = array(
            get_string('name'),
            get_string('timesignedup', 'facetoface'),
            get_string('timecancelled', 'facetoface'),
            get_string('cancelreason', 'facetoface')
        );
        $table->align = array('left', 'center', 'center');

        $context = context_module::instance($cm->id);
        $viewfullnames = has_capability('moodle/site:viewfullnames', $context);
        foreach ($cancellations as $attendee) {
            $data = array();
            $link = new moodle_url('/user/view.php', array('id' => $attendee->id, 'course' => $instance->course));
            $data[] = html_writer::link($link, format_string(fullname($attendee, $viewfullnames)));
            $data[] = userdate($attendee->timesignedup, get_string('strftimedatetime'));
            $data[] = userdate($attendee->timecancelled, get_string('strftimedatetime'));
            $data[] = format_string($attendee->cancelreason);
            $table->data[] = $data;
        }

        $html = '';
        if (!empty($table->data)) {
            $html  = $OUTPUT->heading(get_string('cancellations', 'facetoface'));
            $html .= html_writer::table($table);
        }

        return $html;
    }
}
