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
 * @package    mod_facetoface
 * @copyright  2014 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @author     Alastair Munro <alastair.munro@totaralms.com>
 * @author     Aaron Barnes <aaron.barnes@totaralms.com>
 * @author     Francois Marier <francois@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
        $table->attributes['class'] = 'generaltable f2fsessionlist';
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
                    $sessionrow[] = new html_table_cell('&nbsp;');
                } else {
                    if (CUSTOMFIELD_TYPE_MULTISELECT == $field->type) {
                        $data = str_replace(
                            CUSTOMFIELD_DELIMITER,
                            html_writer::empty_tag('br'),
                            format_string($customdata[$field->id]->data)
                        );
                    } else {
                        $data = format_string($customdata[$field->id]->data);
                    }
                    $cell = new html_table_cell($data);
                    $cell->attributes['class'] = 'customfield';
                    $sessionrow[] = $cell;
                }
            }

            if ($uploadbookings) {
                $cell = new html_table_cell(
                    html_writer::tag('span', $session->id, ['class' => 'mr-3'])
                );
                $cell->attributes['class'] = 'sessionid';
                $sessionrow[] = $cell;
            }

            // Times.
            $allsessiontimes = '';
            if ($session->datetimeknown) {
                foreach ($session->sessiondates as $sessiondate) {
                    if (!empty($allsessiontimes)) {
                        $allsessiontimes .= html_writer::empty_tag('hr');
                    }
                    $date = \mod_facetoface\session::get_readable_session_date($sessiondate);
                    $time = \mod_facetoface\session::get_readable_session_time($sessiondate);
                    $allsessiontimes .= $date . ', ' . $time;
                }
            } else {
                $allsessiontimes = get_string('wait-listed', 'facetoface');
            }
            $cell = new html_table_cell($allsessiontimes);
            $cell->attributes['class'] = 'sessiontimes';
            $sessionrow[] = $cell;

            // Capacity.
            $signupcount = facetoface_get_num_attendees($session->id, MDL_F2F_STATUS_APPROVED);
            $stats = $session->capacity - $signupcount;
            if ($viewattendees) {
                $stats = $signupcount . ' / ' . $session->capacity;
            } else {
                $stats = max(0, $stats);
            }
            $cell = new html_table_cell($stats);
            $cell->attributes['class'] = 'capacity';
            $sessionrow[] = $cell;

            // Status.
            $status  = get_string('bookingopen', 'facetoface');
            if (!$session->visible) {
                $status = get_string('hidden', 'facetoface');
            } else if (
                $session->datetimeknown
                && facetoface_has_session_started($session, $timenow)
                && facetoface_is_session_in_progress($session, $timenow)
            ) {
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

            $cell = new html_table_cell($status);
            $cell->attributes['class'] = 'status';
            $sessionrow[] = $cell;

            // Options.
            $options = '';
            $actionsmenu = new action_menu();
            $label = get_string('actions');
            $actionsmenu->set_menu_trigger(
                $this->output->pix_icon('i/menu', '') . html_writer::span($label, 'sr-only'),
                action_menu::DEFAULT_KEBAB_TRIGGER_CLASSES
            );

            if ($isbookedsession) {
                $options .= html_writer::link(
                    'signup.php?s=' . $session->id . '&backtoallsessions=' . $session->facetoface,
                    get_string('moreinfo', 'facetoface'),
                    [
                        'title' => get_string('moreinfo', 'facetoface'),
                        'class' => 'btn btn-primary',
                    ]
                );
                $options .= html_writer::empty_tag('br');
                if ($session->allowcancellations) {
                    if (facetoface_cancellation_allowed($session)) {
                        $options .= html_writer::link(
                            'cancelsignup.php?s=' . $session->id . '&backtoallsessions=' . $session->facetoface,
                            get_string('cancelbooking', 'facetoface'),
                            [
                                'title' => get_string('cancelbooking', 'facetoface'),
                                'class' => 'btn btn-primary',
                            ]
                        );
                    } else {
                        $cancelrestriction = get_config('facetoface', 'cancelrestriction');
                        $options .= html_writer::link(
                            '',
                            get_string('cancelbooking', 'facetoface'),
                            [
                                'title' => get_string('error:cancellationtooclose', 'facetoface', format_time($cancelrestriction)),
                                'class' => 'btn btn-primary disabled',
                            ]
                        );
                    }
                }
            } else if (!$sessionstarted && !$bookedsession && $signuplinks) {
                $options .= html_writer::link(
                    'signup.php?s=' . $session->id . '&backtoallsessions=' . $session->facetoface,
                    get_string('signup', 'facetoface'),
                    ['class' => 'btn btn-primary']
                );
            }

            if ($editsessions) {
                $actionsmenu->add(
                    new action_menu_link_secondary(
                        new moodle_url('sessions.php', ['s' => $session->id]),
                        new pix_icon('t/edit', get_string('edit', 'facetoface')),
                        get_string('editsession', 'facetoface')
                    )
                );
                $actionsmenu->add(
                    new action_menu_link_secondary(
                        new moodle_url('sessions.php', ['s' => $session->id, 'c' => 1]),
                        new pix_icon('t/copy', get_string('copy', 'facetoface')),
                        get_string('copysession', 'facetoface')
                    )
                );
                $actionsmenu->add(
                    new action_menu_link_secondary(
                        new moodle_url('sessions.php', ['s' => $session->id, 'd' => 1]),
                        new pix_icon('t/delete', get_string('delete', 'facetoface')),
                        get_string('deletesession', 'facetoface')
                    )
                );
            }

            if ($viewattendees) {
                $actionsmenu->add(
                    new action_menu_link_secondary(
                        new moodle_url('attendees.php', ['s' => $session->id, 'backtoallsessions' => $session->facetoface]),
                        new pix_icon('i/group', get_string('seeattendees', 'facetoface')),
                        get_string('seeattendees', 'facetoface'),
                    )
                );
                $divider = new action_menu_filler();
                $divider->primary = false;
                $actionsmenu->add($divider);
                $actionsmenu->add(
                    new action_menu_link_secondary(
                        new moodle_url('attendees.php', ['s' => $session->id, 'download' => 'xlsx']),
                        new pix_icon('f/spreadsheet', get_string('attendeesdownloadexcel', 'facetoface')),
                        get_string('attendeesdownloadexcel', 'facetoface')
                    )
                );
                $actionsmenu->add(
                    new action_menu_link_secondary(
                        new moodle_url('attendees.php', ['s' => $session->id, 'download' => 'ods']),
                        new pix_icon('f/calc', get_string('attendeesdownloadods', 'facetoface')),
                        get_string('attendeesdownloadods', 'facetoface')
                    )
                );
            }

            if ($editsessions || $viewattendees) {
                $options .= $this->render($actionsmenu);
            }

            // If the session is no longer available and is not booked dim the session.
            // We do this on the cell to avoid dimming the options menu.
            $dimmed = ($sessionstarted || !$session->visible) || ($sessionfull && !$isbookedsession);
            if ($dimmed) {
                foreach ($sessionrow as &$cell) {
                    if (!$cell instanceof html_table_cell) {
                        $cell = new html_table_cell($cell);
                    }
                    $cell->attributes['class'] .= ' dimmed_text';
                }
            }

            if (empty($options)) {
                $options = get_string('none', 'facetoface');
                if ($dimmed) {
                    $options = new html_table_cell($options);
                    $options->attributes['class'] = 'options dimmed_text';
                }
            } else {
                $options = new html_table_cell(
                    html_writer::div($options, 'd-flex sessionoptions')
                );
                $options->attributes['class'] = 'options';
            }
            $sessionrow[] = $options;

            $row = new html_table_row($sessionrow);

            // Set the CSS class for the row.
            if ($session->visible && !$sessionstarted && $isbookedsession) {
                $row->attributes = ['class' => 'highlight'];
            }

            // Add row to table.
            $table->data[] = $row;
        }

        // This table should not be responsive, as it cuts off the last dropdown menu.
        $table->responsive = false;
        $output .= html_writer::table($table);

        return $output;
    }
}
