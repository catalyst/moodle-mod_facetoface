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
    public function render_index_list($course, $instances) {
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
                if ($sessions = $facetoface->get_sessionslist()) {
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
    public function render_intro($instance, $cm) {
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
    public function render_locationfilter($instance, $locations, $selected=null) {
        global $OUTPUT;

        $html = '';
        if (count($locations) > 2) {
            $locationurl = new moodle_url('/mod/facetoface/view.php', array('f' => $instance->id));
            $html = $OUTPUT->single_select($locationurl, 'location', $locations, $selected, array());
        }

        return html_writer::tag('div', get_string('showbylocation', 'facetoface') . ": {$html}",
            array('class' => 'generalbox locationfilter'));
    }

    /**
     * Render all Face-to-Face sessions - current, upcoming and previous
     *
     * @param object $instance the Face-to-face record instance
     * @param array $sessions the Face-to-Face sessions
     * @param object $cm the Face-to-Face course module
     * @param string $location optional filter for location
     * @return string HTML
     */
    public function render_sessions($instance, $sessions, $cm, $location=null) {
        global $CFG, $OUTPUT, $PAGE;

        $html = '';
        if (empty($sessions) || !$sessions) {
            return $html;
        }
        $upcoming = array(
            'dates'   => array(),
            'nodates' => array(),
        );
        $current  = array();
        $previous = array();
        foreach ($sessions as $session) {
            switch ($session->status) {
                case FACETOFACE_FINISHED:
                    $previous[$session->id] = $session;
                    break;
                case FACETOFACE_NOT_STARTED:
                    $upcoming['dates'][$session->id] = $session;
                    break;
                case FACETOFACE_NO_DATES:
                    $upcoming['nodates'][$session->id] = $session;
                    break;
                case FACETOFACE_IN_PROGRESS:
                    $current[$session->id] = $session;
                    break;
                default:
                    $upcoming['nodates'][$session->id] = $session;
                    break;
            }
        }
        $html .= html_writer::empty_tag('hr');

        // Session location filter.
        $locations = $instance->get_locations();
        if (!empty($locations)) {
            $html .= $this->render_locationfilter($instance, $locations, $location);
        }

        // Render each organised session list.
        if (!empty($current)) {
            $html .= $OUTPUT->box_start('generalbox', 'facetoface-current-sessions');
            $html .= $OUTPUT->heading(get_string('inprogresssessions', 'facetoface'), '4');
            $html .= $this->render_sessions_list($instance, $current, $cm);
            $html .= $OUTPUT->box_end();
        }
        if (!empty($upcoming['dates']) || !empty($upcoming['nodates'])) {
            $dates = array_merge($upcoming['dates'], $upcoming['nodates']);
            $html .= $OUTPUT->box_start('generalbox', 'facetoface-upcoming-sessions');
            $html .= $OUTPUT->heading(get_string('upcomingsessions', 'facetoface'), '4');
            $html .= $this->render_sessions_list($instance, $dates, $cm);
            $html .= $OUTPUT->box_end();
        }
        if (!empty($previous)) {
            $html .= $OUTPUT->box_start('generalbox', 'facetoface-previous-sessions');
            $html .= $OUTPUT->heading(get_string('previoussessions', 'facetoface'), '4');
            $html .= $this->render_sessions_list($instance, $previous, $cm);
            $html .= $OUTPUT->box_end();
        }

        return $html;
    }

    /**
     * Render a link to add a new session to the Face-to-Face instance
     *
     * @param object $instance the Face-to-face record instance
     * @param object $cm the Face-to-Face course module
     * @return string HTML
     */
    public function add_session_link($instance, $cm) {
        global $OUTPUT;

        $context = context_module::instance($cm->id);
        if (has_capability('mod/facetoface:editsessions', $context)) {
            $addstr = get_string('addsession', 'facetoface');
            $addlink =new moodle_url('/mod/facetoface/sessions.php', array('f' => $instance->id));

            return html_writer::link($addlink, $addstr,
                array('class' => 'generalbox addbutton'));
        }

        return '';
    }

    /**
     * Render a list of all Face-to-Face sessions for the given instance
     *
     * @param object $instance the Face-to-face record instance
     * @param array $sessions the Face-to-Face sessions
     * @param object $cm the Face-to-Face course module
     * @return string HTML
     */
    public function render_sessions_list($instance, $sessions, $cm) {
        global $OUTPUT;

        $context = context_module::instance($cm->id);
        $viewattendees = has_capability('mod/facetoface:viewattendees', $context);
        $editsessions = has_capability('mod/facetoface:editsessions', $context);

        $table = new html_table();
        $table->width = '100%';
        $table->head  = array();
        $table->align = array();
        $table->data  = array();

        // Add customfields to header and mark which are shown.
        $shownfields = array();
        $customfields = $instance->get_customfields();
        foreach ($customfields as $field) {
            if (!empty($field->showinsummary) && $field->showinsummary) {
                $table->head[$field->shortname] = format_string($field->name);
                $shownfields[$field->shortname] = $field;
            }
        }

        // Core Face-to-Face session fields.
        $table->head['date'] = get_string('date', 'facetoface');
        $table->head['time'] = get_string('time', 'facetoface');
        if ($viewattendees) {
            $table->head['attendees'] = get_string('capacity', 'facetoface');
        } else {
            $table->head['attendees'] = get_string('seatsavailable', 'facetoface');
        }
        $table->head['status'] = get_string('status', 'facetoface');
        $table->head['options'] = get_string('options', 'facetoface');

        // Add each session, checking for shown customfields.
        foreach ($sessions as $session) {
            $row = array();

            // Add customdata to the correct spaces in the row.
            foreach ($shownfields as $key => $field) {
                if (isset($session->customdata[$key]) && !empty($session->customdata[$key])) {
                    $data = format_string($session->customdata[$key]->data);
                    if ($field->type === CUSTOMFIELD_TYPE_MULTISELECT) {
                        $row[$key] = $data;
                    } else {
                        $spacer = html_writer::empty_tag('br');
                        $row[$key] = str_replace(CUSTOMFIELD_DELIMITER, $spacer, $data);
                    }
                } else {
                    $row[$key] = '';
                }
            }

            // Dates/times of session.
            list($row['dates'], $row['times']) = $this->render_session_dates($session);

            // Capacity.
            $row['capacity'] = $this->render_session_capacity($session, $viewattendees);

            // Booking status.
            list($row['status'], $booking) = $this->render_session_booking_status($instance, $session);

            // Session options.
            $row['options'] = $this->render_session_options($instance, $session, $viewattendees, $editsessions);

            // Create the row and set the CSS class.
            $row = new html_table_row($row);
            if ($session->status == FACETOFACE_IN_PROGRESS || $session->status == FACETOFACE_FINISHED) {
                $row->attributes = array('class' => 'dimmed_text');
            } else if ($session->attendees >= $session->capacity && !$session->allowoverbook) {
                $row->attributes = array('class' => 'dimmed_text');
            } else if ($booking && $booking->sessionid == $session->id) {
                $row->attributes = array('class' => 'highlight');
            }
            $table->data[] = $row;
        }

        return html_writer::table($table);
    }

    /**
     * Render session lists date and times and return as an array
     * of content
     *
     * @param object $session the Face-to-Face session object
     * @return array
     */
    public function render_session_dates($session) {
        if (!$session->datetimeknown) {
            return array(get_string('wait-listed', 'facetoface'), get_string('wait-listed', 'facetoface'));
        } else {
            $dates = array();
            $times = array();
            foreach ($session->dates as $date) {
                $dates[] = userdate($date->timestart, get_string('strftimedate'));
                $times[] = userdate($date->timestart, get_string('strftimetime'))
                    . ' - ' . userdate($date->timefinish, get_string('strftimetime'));
            }

            // Render the strings and return to the
            $spacer = html_writer::empty_tag('br');
            $datestr = '';
            if (!empty($dates)) {
                $datestr = implode($spacer, $dates);
            }
            $timestr = '';
            if (!empty($times)) {
                $timestr = implode($spacer, $times);
            }
        }

        return array($timestr, $datestr);
    }

    /**
     * Render the capacity of the current
     *
     * @param object $session the Face-to-Face session object
     * @param bool $viewattendees capability check for viewing attendees
     * @return string
     */
    public function render_session_capacity($session, $viewattendees) {

        $capacity = '';
        if (!$session->attendees || $session->attendees == 0) {
            $capacity = $session->capacity;
        } else if ($session->attendees < $session->capacity) {
            if ($viewattendees) {
                $capacity = "{$session->attendees}/{$session->capacity}";
            } else {
                $stats = $session->capacity - $session->attendees;
                $capacity = max(0, $stats);
            }
        } else {
            $capacity = get_string('bookingfull', 'facetoface');
        }

        return $capacity;
    }

    /**
     * Render the users current booking status of the session
     *
     * @param object $instance the Face-to-face record instance
     * @param object $session the Face-to-Face session object
     * @return array
     */
    public function render_session_booking_status($instance, $session) {
        global $USER;

        // Get the users bookings on a session within this Face-to-Face instance.
        $submission = null;
        if ($submissions = $instance->user_booking_submissions($USER->id)) {
            $submission = array_shift($submissions);
        }

        $timenow = time();
        $status = get_string('bookingopen', 'facetoface');
        if ($session->datetimeknown && $session->status == FACETOFACE_IN_PROGRESS) {
            $status = get_string('sessioninprogress', 'facetoface');
        } else if ($session->datetimeknown && $session->status == FACETOFACE_FINISHED) {
            $status = get_string('sessionover', 'facetoface');
        } else if ($submission && $submission->sessionid == $session->id) {
            $status = $instance->format_booking_status($submission->statuscode);
        } else if ($session->attendees >= $session->capacity && !$session->allowoverbook) {
            $status = get_string('bookingfull', 'facetoface');
        }

        return array($status, $submission);
    }

    /**
     * Render the options the user can undertake for the current session
     *
     * @param object $instance the Face-to-face record instance
     * @param object $session the Face-to-Face session object
     * @param bool $viewattendees capability check for viewing attendees
     * @param bool $editsessions capability check for editing the session
     * @return string HTML
     */
    public function render_session_options($instance, $session, $viewattendees, $editsessions) {
        global $USER;

        $options = array();
        if ($editsessions) {

            // Edit.
            $editstr  = get_string('editsession', 'facetoface');
            $editicon = new pix_icon('t/edit', get_string('edit', 'facetoface'));
            $editurl  = new moodle_url('/mod/facetoface/sessions.php', array('s' => $session->id));
            $options[] = $this->output->action_icon($editurl, $editicon, null, array('title' => $editstr));

            // Copy.
            $copystr  = get_string('copysession', 'facetoface');
            $copyicon = new pix_icon('t/copy', get_string('copy', 'facetoface'));
            $copyurl  = new moodle_url('/mod/facetoface/sessions.php', array('s' => $session->id, 'c' => 1));
            $options[] = $this->output->action_icon($copyurl, $copyicon, null, array('title' => $copystr));

            // Delete.
            $deletestr  = get_string('deletesession', 'facetoface');
            $deleteicon = new pix_icon('t/delete', get_string('delete', 'facetoface'));
            $deleteurl  = new moodle_url('/mod/facetoface/sessions.php', array('s' => $session->id, 'd' => 1));
            $options[]  = $this->output->action_icon($deleteurl, $deleteicon, null, array('title' => $deletestr));
        }

        // Attendees.
        if ($viewattendees) {
            $usersstr  = get_string('attendees', 'facetoface');
            $usersicon = new pix_icon('i/users', get_string('attendees', 'facetoface'));
            $usersurl  = new moodle_url('/mod/facetoface/attendees.php',
                array('s' => $session->id, 'backtoallsessions' => $session->facetoface));
            $options[] = $this->output->action_icon($usersurl, $usersicon, null, array('title' => $usersstr));
        }

        $html = '';
        if (!empty($options)) {
            $html = implode(' ', $options);
        }

        // Get the users bookings on a session within this Face-to-Face instance.
        $timenow = time();
        $submission = null;
        $params = array('s' => $session->id, 'backtoallsessions' => $session->facetoface);
        $signupurl = new moodle_url('/mod/facetoface/signup.php', $params);
        if ($submissions = $instance->user_booking_submissions($USER->id)) {
            $submission = array_shift($submissions);
            if ($submission && $submission->sessionid == $session->id) {
                $html .= html_writer::empty_tag('br');

                // Sign-up information link.
                $signupstr = get_string('moreinfo', 'facetoface');
                $html .= html_writer::link($signupurl, $signupstr, array('title' => $signupstr));
                $html .= html_writer::empty_tag('br');

                // Cancel booking link.
                $cancelstr = get_string('cancelbooking', 'facetoface');
                $cancelurl = new moodle_url('/mod/facetoface/cancelsignup.php', $params);
                $html .= html_writer::link($cancelurl, $cancelstr, array('title' => $cancelstr));
            }
        } else if ($session->status != FACETOFACE_IN_PROGRESS && $session->status != FACETOFACE_FINISHED) {
            if ($session->attendees < $session->capacity || ($session->attendees >= $session->capacity && $session->allowoverbook)) {
                if (!empty($html)) {
                    $html .= html_writer::empty_tag('br');
                }
                $html .= html_writer::link($signupurl, get_string('signup', 'facetoface'));
            }
        }

        if (!empty($html)) {
            return $html;
        }

        return get_string('none', 'facetoface');
    }
}
