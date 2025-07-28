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

class mod_facetoface_renderer extends plugin_renderer_base {

    /**
     * Builds session list table given an array of sessions
     */
    public function print_session_list_table(
        $customfields,
        $sessions,
        $viewattendees,
        $editsessions,
        $signuplinks = true,
        $uploadbookings = false
    ) {
        $output = '';

        $tableheader = [];
        foreach ($customfields as $field) {
            if (facetoface_can_view_field($field->visibleto, $editsessions)) {
                $tableheader[] = format_string($field->name);
            }
        }

        if ($uploadbookings) {
            $tableheader[] = get_string('sessionnumber', 'facetoface');
        }

        $tableheader[] = get_string('date', 'facetoface');
        $tableheader[] = get_string('time', 'facetoface');
        if ($viewattendees) {
            $tableheader[] = get_string('capacity', 'facetoface');
        } else {
            $tableheader[] = get_string('seatsavailable', 'facetoface');
        }
        $tableheader[] = get_string('status', 'facetoface');
        $tableheader[] = get_string('options', 'facetoface');

        $timenow = time();

        $table = new html_table();
        $table->attributes['class'] = 'f2fsessionlist';
        $table->head = $tableheader;
        $table->data = [];

        foreach ($sessions as $session) {
            $isbookedsession = false;
            $bookedsession = $session->bookedsession;
            $sessionstarted = false;
            $sessionfull = false;

            $sessionrow = [];

            // Custom fields.
            $customdata = $session->customfielddata;
            foreach ($customfields as $field) {
                if (!facetoface_can_view_field($field->visibleto, $editsessions)) {
                    continue;
                }

                if (empty($customdata[$field->id])) {
                    $sessionrow[] = '&nbsp;';
                } else {
                    if (CUSTOMFIELD_TYPE_MULTISELECT == $field->type) {
                        $sessionrow[] = str_replace(
                            CUSTOMFIELD_DELIMITER,
                            html_writer::empty_tag('br'),
                            format_string($customdata[$field->id]->data)
                        );
                    } else {
                        $sessionrow[] = format_string($customdata[$field->id]->data);
                    }
                }
            }

            if ($uploadbookings) {
                $sessionrow[] = html_writer::tag('span', $session->id, ['class' => 'mr-3']);
            }

            // Dates/times.
            $allsessiondates = '';
            $allsessiontimes = '';
            if ($session->datetimeknown) {
                foreach ($session->sessiondates as $date) {
                    if (!empty($allsessiondates)) {
                        $allsessiondates .= html_writer::empty_tag('br');
                    }
                    $allsessiondates .= \mod_facetoface\session::get_readable_session_date($date);
                    if (!empty($allsessiontimes)) {
                        $allsessiontimes .= html_writer::empty_tag('br');
                    }
                    $allsessiontimes .= \mod_facetoface\session::get_readable_session_time($date);
                }
            } else {
                $allsessiondates = get_string('wait-listed', 'facetoface');
                $allsessiontimes = get_string('wait-listed', 'facetoface');
            }
            $sessionrow[] = $allsessiondates;
            $sessionrow[] = $allsessiontimes;

            // Capacity.
            $signupcount = facetoface_get_num_attendees($session->id, MDL_F2F_STATUS_APPROVED);
            $stats = $session->capacity - $signupcount;
            if ($viewattendees) {
                $stats = $signupcount . ' / ' . $session->capacity;
            } else {
                $stats = max(0, $stats);
            }
            $sessionrow[] = $stats;

            // Status.
            $status  = get_string('bookingopen', 'facetoface');
            if (!$session->visible) {
                $status = get_string('hidden', 'facetoface');
            } else if ($session->datetimeknown
                && facetoface_has_session_started($session, $timenow)
                && facetoface_is_session_in_progress($session, $timenow)) {
                $status = get_string('sessioninprogress', 'facetoface');
                $sessionstarted = true;
            } else if ($session->datetimeknown && facetoface_has_session_started($session, $timenow)) {
                $status = get_string('sessionover', 'facetoface');
                $sessionstarted = true;
            } else if ($bookedsession && $session->id == $bookedsession->sessionid) {
                $signupstatus = facetoface_get_status($bookedsession->statuscode);
                $status = get_string('status_' . $signupstatus, 'facetoface');
                $isbookedsession = true;
            } else if ($signupcount >= $session->capacity) {
                $status = get_string('bookingfull', 'facetoface');
                $sessionfull = true;
            }

            $sessionrow[] = $status;

            // Options.
            $options = '';
            if ($editsessions) {
                $options .= $this->output->action_icon(new moodle_url('sessions.php', ['s' => $session->id]),
                        new pix_icon('t/edit', get_string('edit', 'facetoface')), null,
                        ['title' => get_string('editsession', 'facetoface')]) . ' ';
                $options .= $this->output->action_icon(new moodle_url('sessions.php', ['s' => $session->id, 'c' => 1]),
                        new pix_icon('t/copy', get_string('copy', 'facetoface')), null,
                        ['title' => get_string('copysession', 'facetoface')]) . ' ';
                $options .= $this->output->action_icon(new moodle_url('sessions.php', ['s' => $session->id, 'd' => 1]),
                        new pix_icon('t/delete', get_string('delete', 'facetoface')), null,
                        ['title' => get_string('deletesession', 'facetoface')]) . ' ';
            }
            if ($viewattendees) {
                $options .= html_writer::link('attendees.php?s='.$session->id.'&backtoallsessions='.$session->facetoface,
                        get_string('attendees', 'facetoface'),
                        ['title' => get_string('seeattendees', 'facetoface')]) . ' &nbsp; ';
                $options .= $this->output->action_icon(new moodle_url('attendees.php', ['s' => $session->id, 'download' => 'xlsx']),
                        new pix_icon('f/spreadsheet', get_string('downloadexcel')), null,
                        ['title' => get_string('downloadexcel')]) . ' ';
                $options .= $this->output->action_icon(new moodle_url('attendees.php', ['s' => $session->id, 'download' => 'ods']),
                        new pix_icon('f/calc', get_string('downloadods')), null,
                        ['title' => get_string('downloadods')]) . ' ' . html_writer::empty_tag('br');
            }
            if ($isbookedsession) {
                $options .= html_writer::link('signup.php?s='.$session->id.'&backtoallsessions='.$session->facetoface,
                        get_string('moreinfo', 'facetoface'),
                        ['title' => get_string('moreinfo', 'facetoface')]) . html_writer::empty_tag('br');
                if ($session->allowcancellations) {
                    if (facetoface_cancellation_allowed($session)) {
                        $options .= html_writer::link(
                            'cancelsignup.php?s=' . $session->id . '&backtoallsessions=' . $session->facetoface,
                            get_string('cancelbooking', 'facetoface'),
                            ['title' => get_string('cancelbooking', 'facetoface')]
                        );
                    } else {
                        $cancelrestriction = get_config('facetoface', 'cancelrestriction');
                        $options .= html_writer::link(
                            '',
                            get_string('cancelbooking', 'facetoface'),
                            ['title' => get_string('error:cancellationtooclose', 'facetoface', format_time($cancelrestriction)), 'class' => 'disabled']
                        );
                    }
                }
            } else if (!$sessionstarted && !$bookedsession && $signuplinks) {
                $options .= html_writer::link('signup.php?s='.$session->id.'&backtoallsessions='.$session->facetoface,
                    get_string('signup', 'facetoface'));
            }
            if (empty($options)) {
                $options = get_string('none', 'facetoface');
            }
            $sessionrow[] = $options;

            $row = new html_table_row($sessionrow);

            // Set the CSS class for the row.
            if ($sessionstarted || !$session->visible) {
                $row->attributes = ['class' => 'dimmed_text'];
            } else if ($isbookedsession) {
                $row->attributes = ['class' => 'highlight'];
            } else if ($sessionfull) {
                $row->attributes = ['class' => 'dimmed_text'];
            }

            // Add row to table.
            $table->data[] = $row;
        }

        $output .= html_writer::table($table);

        return $output;
    }
}
