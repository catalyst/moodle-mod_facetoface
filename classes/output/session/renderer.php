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
use \html_table_row;
use \moodle_url;
use \context_module;
use \context_course;
use \pix_icon;

class session_renderer extends \plugin_renderer_base {

    /**
     * Render a link to add a new session to the Face-to-Face instance
     *
     * @param object $instance the Face-to-face record instance
     * @return string HTML
     */
    public function add_session_link($instance) {
        global $OUTPUT;

        $context = $instance->get_context();
        if (has_capability('mod/facetoface:editsessions', $context)) {
            $addstr = get_string('addsession', 'facetoface');
            $addlink =new moodle_url('/mod/facetoface/sessions.php', array('f' => $instance->id));

            return html_writer::link($addlink, $addstr,
                array('class' => 'generalbox addbutton'));
        }

        return '';
    }

    /**
     * Render a single session from a Face-to-Face instance. This is a
     * two column table with each session data displayed with key and value
     * on a single row rather than by column.
     *
     * @param object $instance the Face-to-face record instance
     * @param object $session the Face-to-Face session object
     * @return string HTML
     */
    public function session($instance, $session) {
        $context = $instance->get_context();

        $table = new html_table();
        $table->summary = get_string('sessionsdetailstablesummary', 'facetoface');
        $table->attributes['class'] = 'generaltable facetoface-session';
        $table->align = array('right', 'left');

        // Session customfields.
        $customfields = $instance->get_custom_fields();
        foreach ($customfields as $field) {
            if (!empty($session->customdata[$field->shortname])) {
                $key   = $field->shortname;
                $value = $session->customdata[$key]->data;
                if (CUSTOMFIELD_TYPE_MULTISELECT == $field->type) {
                    $values = explode(CUSTOMFIELD_DELIMITER, format_string($value));
                    $data = implode(html_writer::empty_tag('br'), $values);
                } else {
                    $data = format_string($value);
                }
                $table->data[] = array(str_replace(' ', '&nbsp;', format_string($field->name)), $data);
            }
        }

        // Dates and times.
        $datetimestr = str_replace(' ', '&nbsp;', get_string('sessiondatetime', 'facetoface'));
        $table->data[] = array($datetimestr, $this->session_dates_times($session));

        // Capacity and if the session allows overbooking at all.
        $viewattendees = has_capability('mod/facetoface:viewattendees', $context);
        $capacitystr = get_string('seatsavailable', 'facetoface');
        $allowoverbookstr = '';
        if ($viewattendees) {
            $capacitystr = get_string('capacity', 'facetoface');
            if ($session->allowoverbook) {
                $allowoverbookstr = html_writer::empty_tag('br')
                    . ' (' . strtolower(get_string('allowoverbook', 'facetoface')) . ')';
            }
        }
        $table->data[] = array($capacitystr, $this->session_capacity($session, $viewattendees) . $allowoverbookstr);

        // Display requires approval notification.
        if ($instance->approvalreqd) {
            $table->data[] = array('', get_string('sessionrequiresmanagerapproval', 'facetoface'));
        }
        if (!empty($session->duration)) {
            $table->data[] = array(get_string('duration', 'facetoface'), $instance->format_duration($session->duration));
        }
        if (!empty($session->normalcost)) {
            $table->data[] = array(get_string('normalcost', 'facetoface'), $instance->format_cost($session->normalcost));
        }
        if (!empty($session->discountcost)) {
            $table->data[] = array(get_string('discountcost', 'facetoface'), $instance->format_cost($session->discountcost));
        }
        if (!empty($session->details)) {
            $details = clean_text($session->details, FORMAT_HTML);
            $table->data[] = array(get_string('details', 'facetoface'), $details);
        }

        // Session trainers.
        echo $this->session_trainers($instance, $session, $table);

        return html_writer::table($table);
    }

    /**
     * Render all Face-to-Face sessions for the view page,
     * sorted into current, upcoming and previous groups of content
     *
     * @param object $instance the Face-to-face record instance
     * @param array $sortedsessions the Face-to-Face sessions
     * @return string HTML
     */
    public function view_sessions_list($instance, $sortedsessions) {
        global $CFG, $OUTPUT, $PAGE;

        $html = '';
        if (empty($sortedsessions) || !$sortedsessions) {
            return $html;
        }

        // Display using the key as the value for the CSS ID and heading string.
        foreach ($sortedsessions as $key => $sessions) {
            if (!empty($sessions)) {
                $html .= $OUTPUT->box_start('generalbox', "facetoface-{$key}-sessions");
                $html .= $OUTPUT->heading(get_string("{$key}sessions", 'facetoface'), '4');
                $html .= $this->sessions_table($instance, $sessions);
                $html .= $OUTPUT->box_end();
            }
        }

        return $html;
    }

    /**
     * Render a table of Face-to-Face sessions and their data
     *
     * @param object $instance the Face-to-face record instance
     * @param array $sessions the Face-to-Face sessions
     * @return string HTML
     */
    public function sessions_table($instance, $sessions) {
        global $OUTPUT;

        $context = $instance->get_context();
        $viewattendees = has_capability('mod/facetoface:viewattendees', $context);
        $editsessions = has_capability('mod/facetoface:editsessions', $context);

        $table = new html_table();
        $table->width = '100%';
        $table->head  = array();
        $table->align = array();
        $table->data  = array();

        // Add customfields to header and mark which are shown.
        $shownfields = array();
        $customfields = $instance->get_custom_fields();
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

            // Core session details.
            $row['dates']    = $this->session_dates($session);
            $row['times']    = $this->session_times($session);
            $row['capacity'] = $this->session_capacity($session, $viewattendees);
            $row['status']   = $this->session_booking_status($instance, $session);
            $row['options']  = $this->session_options($instance, $session, $viewattendees, $editsessions);

            // Create the row and set the CSS class.
            $submission = $instance->get_user_current_booking_submission();
            $row = new html_table_row($row);
            if ($session->status == FACETOFACE_IN_PROGRESS || $session->status == FACETOFACE_FINISHED) {
                $row->attributes = array('class' => 'dimmed_text');
            } else if (count($session->attendees) >= $session->capacity && !$session->allowoverbook) {
                $row->attributes = array('class' => 'dimmed_text');
            } else if ($submission && $submission->sessionid == $session->id) {
                $row->attributes = array('class' => 'highlight');
            }
            $table->data[] = $row;
        }

        return html_writer::table($table);
    }
    /**
     * Render the given sessions dates and times together
     *
     * @param object $session the Face-to-Face session object
     * @return array
     */
    public function session_dates_times($session) {

        $datestr = '';
        if (!$session->datetimeknown) {
            $datestr = get_string('wait-listed', 'facetoface');
        } else {
            $datetimes = array();
            foreach ($session->dates as $date) {
                $datetime  = userdate($date->timestart, get_string('strftimedate'));
                $datetime .= ' ';
                $datetime .= userdate($date->timestart, get_string('strftimetime'))
                    . ' - ' . userdate($date->timefinish, get_string('strftimetime'));

                $datetimes[] = $datetime;
            }

            // Render the strings and return to the
            $spacer = html_writer::empty_tag('br');
            if (!empty($datetimes)) {
                $datestr = implode($spacer, $datetimes);
            }
        }

        return $datestr;
    }

    /**
     * Render the given sessions dates
     *
     * @param object $session the Face-to-Face session object
     * @return array
     */
    public function session_dates($session) {

        $datestr = '';
        if (!$session->datetimeknown) {
            $datestr = get_string('wait-listed', 'facetoface');
        } else {
            $dates = array();
            foreach ($session->dates as $date) {
                $dates[] = userdate($date->timestart, get_string('strftimedate'));
            }

            // Render the strings and return to the
            $spacer = html_writer::empty_tag('br');
            if (!empty($dates)) {
                $datestr = implode($spacer, $dates);
            }
        }

        return $datestr;
    }

    /**
     * Render the given sessions times
     *
     * @param object $session the Face-to-Face session object
     * @return array
     */
    public function session_times($session) {

        $timestr = '';
        if (!$session->datetimeknown) {
            $timestr = get_string('wait-listed', 'facetoface');
        } else {
            $times = array();
            foreach ($session->dates as $date) {
                $times[] = userdate($date->timestart, get_string('strftimetime'))
                    . ' - ' . userdate($date->timefinish, get_string('strftimetime'));
            }

            // Render the strings and return to the
            $spacer = html_writer::empty_tag('br');
            if (!empty($times)) {
                $timestr = implode($spacer, $times);
            }
        }

        return $timestr;
    }

    /**
     * Render the capacity of the current
     *
     * @param object $session the Face-to-Face session object
     * @param bool $viewattendees capability check for viewing attendees
     * @return string
     */
    public function session_capacity($session, $viewattendees) {

        $attendees = count($session->attendees);
        $capacity = '';
        if (!$attendees || $session->attendees == 0) {
            $capacity = $session->capacity;
        } else if ($attendees < $session->capacity) {
            if ($viewattendees) {
                $capacity = "{$attendees}/{$session->capacity}";
            } else {
                $stats = $session->capacity - $attendees;
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
    public function session_booking_status($instance, $session) {
        global $USER;

        // Get the users current booking submission on a session within
        // this Face-to-Face instance.
        $submission = $instance->get_user_current_booking_submission($USER->id);

        $timenow = time();
        $status = get_string('bookingopen', 'facetoface');
        if ($session->datetimeknown && $session->status == FACETOFACE_IN_PROGRESS) {
            $status = get_string('sessioninprogress', 'facetoface');
        } else if ($session->datetimeknown && $session->status == FACETOFACE_FINISHED) {
            $status = get_string('sessionover', 'facetoface');
        } else if ($submission && $submission->sessionid == $session->id) {
            $status = $instance->format_booking_status($submission->statuscode);
        } else if (count($session->attendees) >= $session->capacity && !$session->allowoverbook) {
            $status = get_string('bookingfull', 'facetoface');
        }

        return $status;
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
    public function session_options($instance, $session, $viewattendees, $editsessions) {
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
        if ($submissions = $instance->get_user_booking_submissions()) {
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
            if (count($session->attendees) < $session->capacity || (count($session->attendees) >= $session->capacity && $session->allowoverbook)) {
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

    /**
     * Render a list of session trainers for the given instance
     * sorted by the role type they have within the course
     *
     * @param object $instance the Face-to-face record instance
     * @param object $session the Face-to-Face session object
     * @param array $table (optional) table reference to add trainers to
     * @return string
     *
     */
    public function session_trainers($instance, $session, &$table=null) {
        $context = $instance->get_context();
        $viewfullnames = has_capability('moodle/site:viewfullnames', $context);

        $html = '';
        $roles = $instance->get_trainer_roles();
        if (!empty($roles)) {
            foreach ($roles as $id => $role) {
                if (isset($session->trainers[$id]) && !empty($session->trainers[$id])) {
                    $trainers = array();
                    foreach ($session->trainers[$id] as $trainer) {
                        $trainerurl = new moodle_url('/user/view.php', array('id' => $trainer->id));
                        $trainers[] = html_writer::link($trainerurl, fullname($trainer, $viewfullnames));
                    }
                    if (!$table || !isset($table->data)) {
                        $html .= format_string($role->name) . ': ';
                        $html .= implode(', ', $trainers);
                        $html .= html_writer::empty_tag('br');
                    } else {
                        $table->data[] = array(format_string($role->name), implode(', ', $trainers));
                    }
                }
            }
        }

        if (!$table || !isset($table->data)) {
            return $html;
        }
    }
}

