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
}
