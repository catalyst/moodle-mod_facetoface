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
 * Face-to-Face class definition
 *
 * Copyright (C) 2015 onwards Catalyst IT (http://www.catalyst-eu.net)
 *
 * @package    mod
 * @subpackage facetoface
 * @copyright  2014 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/facetoface/lib.php');

define('FACETOFACE_NO_DATES',    0);
define('FACETOFACE_FINISHED',    1);
define('FACETOFACE_NOT_STARTED', 2);
define('FACETOFACE_IN_PROGRESS', 3);

/**
 * Class to store, cache and manage Face-to-Face
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read int $course
 * @property-read int $display
 * @property-read string $shortname
 * @property-read int $showoncalendar
 * @property-read int $approvalreqd
 * @property-read int $usercalentry
 * @property-read string $intro
 * @property-read int $introformat
 * @property-read string $thirdparty
 * @property-read int $thirdpartywaitlist
 * @property-read string $confirmationsubject
 * @property-read string $confirmationinstrmngr
 * @property-read string $confirmationmessage
 * @property-read string $waitlistedsubject
 * @property-read string $waitlistedmessage
 * @property-read string $cancellationsubject
 * @property-read string $cancellationinstrmngr
 * @property-read string $cancellationmessage
 * @property-read string $remindersubject
 * @property-read string $reminderinstrmngr
 * @property-read string $remindermessage
 * @property-read int $reminderperiod
 * @property-read string $requestsubject
 * @property-read string $requestinstrmngr
 * @property-read string $requestmessage
 * @property-read int $timecreated
 * @property-read int $timemodified
 *
 * @package    mod
 * @subpackage facetoface
 * @copyright  2014 onwards Catalyst IT <http://www.catalyst-eu.net>
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class facetoface implements cacheable_object, IteratorAggregate  {

    /** @var array list of all fields and their short name and default value for caching */
    protected static $fields = array(
        'id'                    => array('id', 0),
        'name'                  => array('na', ''),
        'course'                => array('co', 0),
        'display'               => array('ds', 0),
        'shortname'             => array('sn', ''),
        'showoncalendar'        => array('soc', 0),
        'approvalreqd'          => array('ar', 0),
        'usercalentry'          => array('uc', 0),
        'intro'                 => array('in', ''),
        'introformat'           => array('if', 1),
        'thirdparty'            => array('tp', ''),
        'thirdpartywaitlist'    => array('tw', 0),
        'confirmationsubject'   => array('cs', ''),
        'confirmationinstrmngr' => array('cim', ''),
        'confirmationmessage'   => array('cm', ''),
        'waitlistedsubject'     => array('ws', ''),
        'waitlistedmessage'     => array('wm', ''),
        'cancellationsubject'   => array('cs', ''),
        'cancellationinstrmngr' => array('cim', ''),
        'cancellationmessage'   => array('cm', ''),
        'remindersubject'       => array('rs', ''),
        'reminderinstrmngr'     => array('rim', ''),
        'remindermessage'       => array('rm', ''),
        'reminderperiod'        => array('rp', 0),
        'requestsubject'        => array('rqs', ''),
        'requestinstrmngr'      => array('rqim', ''),
        'requestmessage'        => array('rqm', ''),
        'timecreated'           => null, // Not cached.
        'timemodified'          => null, // Not cached.
    );

    /** @var int */
    protected $id;

    /** @var string */
    protected $name;

    /** @var int */
    protected $course;

    /** @var int */
    protected $display;

    /** @var string */
    protected $shortname;

    /** @var int */
    protected $showoncalendar;

    /** @var int */
    protected $approvalreqd;

    /** @var int */
    protected $usercalentry;

    /** @var string */
    protected $intro;

    /** @var string */
    protected $introformat;

    /** @var string */
    protected $thirdparty;

    /** @var int */
    protected $thirdpartywaitlist;

    /** @var string */
    protected $confirmationsubject;

    /** @var string */
    protected $confirmationinstrmngr;

    /** @var string */
    protected $confirmationmessage;

    /** @var string */
    protected $waitlistedsubject;

    /** @var string */
    protected $waitlistedmessage;

    /** @var string */
    protected $cancellationsubject;

    /** @var string */
    protected $cancellationinstrmngr;

    /** @var string */
    protected $cancellationmessage;

    /** @var string */
    protected $remindersubject;

    /** @var string */
    protected $reminderinstrmngr;

    /** @var string */
    protected $remindermessage;

    /** @var int */
    protected $reminderperiod;

    /** @var string */
    protected $requestsubject;

    /** @var string */
    protected $requestinstrmngr;

    /** @var string */
    protected $requestmessage;

    /** @var int */
    protected $timecreated;

    /** @var int */
    protected $timemodified;

    /** @var bool */
    protected $fromcache;

    /**
     * Magic setter method, we do not want anybody to modify properties from the outside.
     *
     * @param string $name property name
     * @param mixed $value property value
     */
    public function __set($name, $value) {
        debugging('Can not change Face-to-Face instance properties!', DEBUG_DEVELOPER);
    }

    /**
     * Magic method getter, redirects to read only values.
     *
     * Queries from DB the fields that were not cached.
     *
     * @param string $name property name
     * @return mixed
     */
    public function __get($name) {
        global $DB;
        if (array_key_exists($name, self::$fields)) {

            // Property was not retrieved from DB, retrieve all not retrieved fields.
            if ($this->$name === false) {
                $dbfields = array_diff_key(self::$fields, array_filter(self::$fields));
                $record = $DB->get_record('facetoface', array('id' => $this->id),
                        join(',', array_keys($dbfields)), MUST_EXIST);
                foreach ($record as $key => $value) {
                    $this->$key = $value;
                }
            }

            return $this->$name;
        }

        debugging('Invalid Face-to-Face property accessed! ' . $name, DEBUG_DEVELOPER);
        return null;
    }

    /**
     * Full support for isset on our magic read only properties.
     *
     * @param string $name property name
     * @return bool
     */
    public function __isset($name) {
        if (array_key_exists($name, self::$fields)) {
            return isset($this->$name);
        }

        return false;
    }

    /**
     * All properties are read only, sorry.
     *
     * @param string $name property name
     */
    public function __unset($name) {
        debugging('Can not unset Face-to-Face instance properties!', DEBUG_DEVELOPER);
    }

    /**
     * Create an iterator because magic vars can't be seen by 'foreach'.
     *
     * Implementing method from interface IteratorAggregate
     *
     * @return ArrayIterator
     */
    public function getIterator() {
        $ret = array();
        foreach (self::$fields as $property => $unused) {
            if ($this->$property !== false) {
                $ret[$property] = $this->$property;
            }
        }

        return new ArrayIterator($ret);
    }

    /**
     * Constructor
     *
     * Constructor is protected, use facetoface::get($id) to retrieve instance
     *
     * @param stdClass $record record from DB (may not contain all fields)
     * @param bool $fromcache whether it is being restored from cache
     */
    protected function __construct(stdClass $record, $fromcache=false) {
        context_helper::preload_from_record($record);
        foreach ($record as $key => $val) {
            if (array_key_exists($key, self::$fields)) {
                $this->$key = $val;
            }
        }
        $this->fromcache = $fromcache;
    }

    /**
     * Prepares the object for caching. Works like the __sleep method.
     *
     * Implementing method from interface cacheable_object
     *
     * @return array ready to be cached
     */
    public function prepare_to_cache() {
        $a = array();
        foreach (self::$fields as $property => $cachedirectives) {
            if ($cachedirectives !== null) {
                list($shortname, $defaultvalue) = $cachedirectives;
                if ($this->$property !== $defaultvalue) {
                    $a[$shortname] = $this->$property;
                }
            }
        }
        $context = $this->get_context();
        $a['xi'] = $context->id;
        $a['xp'] = $context->path;

        return $a;
    }

    /**
     * Takes the data provided by prepare_to_cache and reinitialises an instance of the associated from it.
     *
     * Implementing method from interface cacheable_object
     *
     * @param array $a
     * @return object Face-to-Face
     */
    public static function wake_from_cache($a) {
        $record = new stdClass;
        foreach (self::$fields as $property => $cachedirectives) {
            if ($cachedirectives !== null) {
                list($shortname, $defaultvalue) = $cachedirectives;
                if (array_key_exists($shortname, $a)) {
                    $record->$property = $a[$shortname];
                } else {
                    $record->$property = $defaultvalue;
                }
            }
        }
        $record->ctxid = $a['xi'];
        $record->ctxpath = $a['xp'];
        $record->ctxlevel = CONTEXT_MODULE;
        $record->ctxinstance = $record->id;

        return new facetoface($record, true);
    }

    /**
     * Returns the Face-to-Face context.
     *
     * @return object CONTEXT_MODULE
     */
    public function get_context() {
        if ($this->id === 0) {
            return null;
        } else {
            $cm = get_coursemodule_from_instance('facetoface', $this->id);
            return context_module::instance($cm->id);
        }
    }

    /**
     * Returns Face-to-Face object
     *
     * @param int $id Face-to-Face id
     * @param int $strictness whether to throw an exception (MUST_EXIST) or
     *     return null (IGNORE_MISSING) in case the Face-to-Face is not found or
     *     not visible to current user
     * @return null|Face-to-Face
     * @throws moodle_exception
     */
    public static function get($id, $strictness=MUST_EXIST) {
        if (!$id) {
            return null;
        }

        $facetofacecache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_facetoface', 'instances');
        $facetoface = $facetofacecache->get($id);
        if ($facetoface === false) {
            if ($records = self::get_records('f.id = :id', array('id' => $id))) {
                $record = reset($records);
                $facetoface = new facetoface($record);

                // Store in cache.
                $facetofacecache->set($id, $facetoface);
            }
        }
        if ($facetoface) {
            return $facetoface;
        } else {
            if ($strictness == MUST_EXIST) {
                throw new moodle_exception('unknowninstance');
            }
        }

        return null;
    }

    /**
     * Retrieves number of records from the facetoface table
     *
     * Only cached fields are retrieved. Records are ready for preloading context
     *
     * @param string $whereclause SQL query string
     * @param array $params SQL query params
     * @return array array of stdClass objects
     */
    protected static function get_records($whereclause, $params) {
        global $DB;

        // Retrieve from DB only the fields that need to be stored in cache.
        $fields = array_keys(array_filter(self::$fields));
        $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
        $sql = "SELECT f.". join(',f.', $fields) . ", $ctxselect
                FROM {facetoface} f
                JOIN {course_modules} cm ON cm.instance = f.id
                JOIN {modules} m ON m.id = cm.module AND m.name = 'facetoface'
                JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :context
                WHERE " . $whereclause . " ORDER BY f.id";

        return $DB->get_records_sql($sql,
                array('context' => CONTEXT_MODULE) + $params);
    }

    /**
     * Return Face-to-Face locations
     *
     * @return array $locations
     */
    public function get_locations() {
        global $CFG, $DB;

        // Check if there is a location field set.
        $params = array('shortname' => 'location');
        $fieldid = $DB->get_field_select('facetoface_session_field', 'id', 'LOWER(shortname) ILIKE :shortname', $params);
        if (!$fieldid) {
            return array();
        }

        $sql = "SELECT DISTINCT d.data AS location
                  FROM {facetoface} f
                  JOIN {facetoface_sessions} s ON s.facetoface = f.id
                  JOIN {facetoface_session_data} d ON d.sessionid = s.id
                 WHERE f.id = ? AND d.fieldid = ?";
        if ($records = $DB->get_records_sql($sql, array($this->id, $fieldid))) {
            $menu[0] = get_string('alllocations', 'facetoface');
            foreach ($records as $record) {
                $menu[$record->location] = $record->location;
            }

            return $menu;
        }

        return array();
    }

    /**
     * Return the Face-to-Face instance sessions list
     *
     * @param string $location a specified location filter
     * @return array $locations
     */
    public function get_sessions_list($location=null) {
        global $DB;
        $params = array('facetoface' => $this->id);

        // Build location SQL if filter is used.
        $locationsql = '';
        if (!empty($location)) {
            $locationsql = ' AND s.id IN (
                SELECT sessionid
                FROM {facetoface_session_data}
                WHERE ' . $DB->sql_compare_text('data') . ' = :location
            )';
            $params['location'] = $location;
        }

        // Fetch sessions.
        $sessions = $DB->get_records_sql('SELECT s.*
            FROM {facetoface_sessions} s
            LEFT OUTER JOIN (
                SELECT sessionid, MIN(timestart) AS mintimestart
                FROM {facetoface_sessions_dates}
                GROUP BY sessionid
            ) m ON m.sessionid = s.id
            WHERE s.facetoface = :facetoface'
            . $locationsql
            . 'ORDER BY s.datetimeknown, m.mintimestart', $params);

        // Add duration and dates.
        if ($sessions) {
            foreach ($sessions as $key => $value) {
                $sessions[$key]->duration   = $this->session_minutes_to_hours($value->duration);
                $sessions[$key]->dates      = $this->session_get_dates($value->id);
                $sessions[$key]->customdata = $this->session_get_customdata($value->id);
                $sessions[$key]->attendees  = $this->get_attendees_count($value->id, MDL_F2F_STATUS_APPROVED);
                $sessions[$key]->status     = $this->session_status($value);
            }
        }

        return $sessions;
    }

    /**
     * Sort the Face-to-Face instance sessions into relative groups:
     *  - in-progress
     *  - upcoming or dateless
     *  - finished
     *
     * @param array $sessions the current list of sessions to sort
     * @return array the sorted sessions keyed by type
     */
    public function sort_sessions_list($sessions) {
        $sorted = array(
            'inprogress' => array(),
            'upcoming'   => array(),
            'previous'   => array(),
        );

        if (!empty($sessions)) {
            foreach ($sessions as $session) {
                switch ($session->status) {
                    case FACETOFACE_FINISHED:
                        $sorted['previous'][$session->id] = $session;
                        break;
                    case FACETOFACE_NOT_STARTED:
                        $sorted['upcoming'][$session->id] = $session;
                        break;
                    case FACETOFACE_IN_PROGRESS:
                        $sorted['current'][$session->id] = $session;
                        break;
                    default:
                        $sorted['upcoming'][$session->id] = $session;
                        break;
                }
            }
        }

        return $sorted;
    }

    /**
     * Converts minutes to hours
     * @param int $minutes the session duration to format
     * @return int
     */
    protected function session_minutes_to_hours($minutes) {
        if (!intval($minutes)) {
            return 0;
        }
        if ($minutes > 0) {
            $hours = floor($minutes / 60.0);
            $mins = $minutes - ($hours * 60.0);
            return "{$hours}:{$mins}";
        }

        return $minutes;
    }

    /**
     * Get all of the dates for a given session
     *
     * @param int $session the current session record ID
     * @return array
     */
    protected function session_get_dates($session) {
        global $DB;

        $dates = array();
        if ($records = $DB->get_records('facetoface_sessions_dates', array('sessionid' => $session), 'timestart')) {
            $i = 0;
            foreach ($records as $record) {
                $dates[$i++] = $record;
            }
        }

        return $records;
    }

    /**
     * Get all trainers associated with a session, optionally
     * restricted to a certain roleid
     *
     * If a roleid is not specified, will return a multi-dimensional
     * array keyed by roleids, with an array of the chosen roles
     * for each role
     *
     * @param object $session the session to fetch trainers for
     * @param integer $roleid (optional for checking specific role types)
     * @return array
     */
    public function get_trainers($session, $roleid=null) {
        global $CFG, $DB;

        $params  = array();
        $rolesql = '';
        if ($roleid) {
            $rolesql = ' AND r.roleid = :role';
            $params['role'] = $roleid;
        }

        $params['session'] = $session->id;
        $usernamefields = get_all_user_name_fields(true, 'u');
        $rs = $DB->get_recordset_sql("SELECT u.id, r.roleid, {$usernamefields}
            FROM {facetoface_session_roles} r
            LEFT JOIN {user} u ON u.id = r.userid
            WHERE r.sessionid = ? {$rolesql}", $params);

        $trainers = array();
        foreach ($rs as $record) {

            // Create new array for this role.
            if (!isset($trainers[$record->roleid])) {
                $trainers[$record->roleid] = array();
            }
            $trainers[$record->roleid][$record->id] = $record;
        }
        $rs->close();

        // If we are only after one roleid.
        if ($roleid) {
            if (!empty($trainers[$roleid])) {
                return $trainers[$roleid];
            }
            return array();
        }

        return $trainers;
    }

    /**
     * Get all of the customdata for a given session
     *
     * @param int $session the current session record ID
     * @return array
     */
    protected function session_get_customdata($session) {
        global $DB;

        $customdata = $DB->get_records_sql('SELECT f.shortname, d.data
            FROM {facetoface_session_data} d
            JOIN {facetoface_session_field} f ON f.id = d.fieldid
            WHERE d.sessionid = :session
            GROUP BY f.shortname, d.data', array('session' => $session));

        return $customdata;
    }

    /**
     * Returns the status of the Face-to-Face session based
     * on the session dates, such as:
     * - No dates given
     * - Finished
     * - Not yet started
     * - In progress
     *
     * @param object $session record from the facetoface_sessions table
     * @return bool
     */
    public function session_status($session) {
        $timenow = time();
        if (!$session->datetimeknown) {
            return FACETOFACE_NO_DATES;
        }

        // Check each session date.
        $status = FACETOFACE_NO_DATES;
        foreach ($session->dates as $date) {
            if ($date->timefinish <= $timenow) {
                $status = FACETOFACE_FINISHED;
            } else if ($date->timestart > $timenow) {
                $status = FACETOFACE_NOT_STARTED;
            } else if ($date->timestart <= $timenow && $date->timefinish > $timenow) {
                $status = FACETOFACE_IN_PROGRESS;
            }
        }

        return $status;
    }


    /**
     * Return a list of all customfields configured as part
     * of the Face-to-Face global settings
     *
     * @return array|null
     */
    public function get_customfields() {
        global $DB;

        static $fields = null;
        if (null == $fields) {
            if (!$fields = $DB->get_records('facetoface_session_field')) {
                $fields = array();
            }
        }

        return $fields;
    }

    /**
     * Return number of attendees signed up to a the Face-to-Face session
     *
     * @param int $session the session record ID
     * @param int $status MDL_F2F_STATUS_* constant (optional)
     * @return int
     */
    public function get_attendees_count($session, $status=MDL_F2F_STATUS_BOOKED) {
        global $CFG, $DB;

        $sql = 'SELECT COUNT(s.id)
            FROM {facetoface_signups} u
            JOIN {facetoface_signups_status} s ON s.signupid = u.id
                WHERE u.sessionid = :session
                AND s.superceded = 0
                AND s.statuscode >= :status';

        return $DB->count_records_sql($sql, array('session' => $session, 'status' => $status));
    }

    public function get_current_booking_submission($user=0, $includecancellations=false) {
        $submission = null;
        if ($submissions = $this->user_booking_submissions($user, $includecancellations)) {
            $submission = array_shift($submissions);
        }

        return $submission;
    }

    /**
     * Return a users is current bookings on a session within
     * the Face-to-Face module
     *
     * @param int $user the current user ID to fetch submissions for
     * @param bool $includecancellations if true also return cancelled bookings
     * @return array
     */
    public function user_booking_submissions($user=0, $includecancellations=false) {
        global $DB, $USER;

        if (!$user) {
            $user = $USER->id;
        }
        $params = array('facetoface' => $this->id, 'user' => $user);

        // Don't include cancellations.
        $where = '';
        if (!$includecancellations) {
            $where = ' AND ss.statuscode >= :statuscode1 AND ss.statuscode < :statuscode2';
            $params['statuscode1'] = MDL_F2F_STATUS_REQUESTED;
            $params['statuscode2'] = MDL_F2F_STATUS_NO_SHOW;
        }

        return $DB->get_records_sql("SELECT
            su.id, s.facetoface, s.id as sessionid,
            su.userid, ss.statuscode
                FROM {facetoface_sessions} s
                JOIN {facetoface_signups} su ON su.sessionid = s.id
                JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                    WHERE s.facetoface = :facetoface AND su.userid = :user
                    AND ss.superceded != 1
                    {$where}
                    ORDER BY s.timecreated DESC", $params);
    }

    /**
     * Returns the human readable code for a Face-to-Face booking status
     *
     * @param int $code one of the MDL_F2F_STATUS* constants codes
     * @return string
     */
    public function format_booking_status($code) {
        global $MDL_F2F_STATUS;

        $string = null;
        if (isset($MDL_F2F_STATUS[$code])) {
            $status = $MDL_F2F_STATUS[$code];
            $string = get_string("status_{$status}", 'facetoface');
        }

        return $string;
    }

    /**
     * Return array of trainer roles configured for the Face-to-Face
     * instance
     *
     * @return array
     */
    public function get_trainer_roles() {
        global $CFG, $DB;

        // Check that roles have been selected.
        if (empty($CFG->facetoface_session_roles)) {
            return false;
        }
        $cleanroles = clean_param($CFG->facetoface_session_roles, PARAM_SEQUENCE);
        $roles = explode(',', $cleanroles);
        list($rolesql, $params) = $DB->get_in_or_equal($roles);
        if ($roles = $DB->get_records_select('role', "id {$rolesql} AND id <> 0", $params, '', 'id,name')) {
            return $roles;
        }

        return array();
    }

    /**
     * Return all user fields to include in export lists
     *
     * @return array
     */
    private function get_userfields() {
        global $CFG;

        static $fields = null;
        if ($fields == null) {
            $fields = array();
            $names = array(
                'firstname', 'lastname', 'email', 'city',
                'idnumber', 'institution', 'department', 'address'
            );
            foreach ($names as $shortname) {
                $fields[$shortname] = get_string($shortname);
            }
            $fields['managersemail'] = get_string('manageremail', 'facetoface');
        }

        return $fields;
    }

    /* Export attendance functionality */

    /**
     * Export the attendance for a set of Face-to-Face sessions
     *
     * @param string $format the format ot export (ODS, XLS)
     * @param string $location the location filter
     */
    public function export_attendance($format, $location) {
        global $CFG;

        $timeformat = str_replace(' ', '_', get_string('strftimedatetime'));
        $downloadfilename = clean_filename($this->name . '_' . userdate(time(), $timeformat));
        if ('ods' === $format) {

            // OpenDocument format (ISO/IEC 26300).
            require_once($CFG->dirroot . '/lib/odslib.class.php');
            $downloadfilename .= '.ods';
            $workbook = new MoodleODSWorkbook('-');
        } else {

            // Excel format.
            require_once($CFG->dirroot . '/lib/excellib.class.php');
            $downloadfilename .= '.xls';
            $workbook = new MoodleExcelWorkbook('-');
        }

        $workbook->send($downloadfilename);
        $worksheet = $workbook->add_worksheet('attendance');
        $this->export_write_header($worksheet);
        $this->export_write_attendance($worksheet, 1, $location);
        $workbook->close();
    }

    /**
     * Add the appropriate column headers to the given worksheet export
     *
     * @param object $worksheet the worksheet to modify (passed by reference)
     * @return integer the index of the next column
     */
    private function export_write_header(&$worksheet) {
        $pos = 0;
        $customfields = $this->get_customfields();
        foreach ($customfields as $field) {
            if (!empty($field->showinsummary)) {
                $worksheet->write_string(0, $pos++, $field->name);
            }
        }
        $worksheet->write_string(0, $pos++, get_string('date', 'facetoface'));
        $worksheet->write_string(0, $pos++, get_string('timestart', 'facetoface'));
        $worksheet->write_string(0, $pos++, get_string('timefinish', 'facetoface'));
        $worksheet->write_string(0, $pos++, get_string('duration', 'facetoface'));
        $worksheet->write_string(0, $pos++, get_string('status', 'facetoface'));

        if ($trainerroles = $this->get_trainer_roles()) {
            foreach ($trainerroles as $role) {
                $worksheet->write_string(0, $pos++, get_string('role') . ': ' . $role->name);
            }
        }

        $userfields = $this->get_userfields();
        foreach ($userfields as $shortname => $fullname) {
            $worksheet->write_string(0, $pos++, $fullname);
        }
        $worksheet->write_string(0, $pos++, get_string('attendance', 'facetoface'));
        $worksheet->write_string(0, $pos++, get_string('datesignedup', 'facetoface'));

        return $pos;
    }

    /**
     * Write in the worksheet the given Face-to-Face attendance information
     * filtered by location
     *
     * This function includes lots of custom SQL because it's otherwise
     * way too slow.
     *
     * @param object $worksheet currently open worksheet
     * @param integer $start index of the starting row
     * @param string $location session location filter
     * @returns integer index of the last row written
     */
    private function export_write_attendance(&$worksheet, $start=1, $location=null) {
        global $CFG, $DB;
        $params  = array();
        $customfields = $this->get_customfields();
        $trainerroles = $this->get_trainer_roles();
        $userfields   = $this->get_userfields();

        // Build optional location query.
        $locationsql = '';
        if ($location && !empty($location)) {
            $locationsql = ' AND s.id IN (
                SELECT sessionid
                FROM {facetoface_session_data}
                WHERE ' . $DB->sql_compare_text('data') . ' = :location
            )';
            $params['location'] = $location;
        }

        // Fetch attendee signups.
        $params['facetoface1'] = $this->id;
        $params['facetoface2'] = $this->id;
        $params['status1'] = MDL_F2F_STATUS_BOOKED;
        $params['status2'] = MDL_F2F_STATUS_WAITLISTED;
        $params['status3'] = MDL_F2F_STATUS_APPROVED;
        $signups = $DB->get_records_sql("SELECT su.id AS submissionid, s.id AS sessionid, u.*,
            f.course AS courseid, ss.grade, sign.timecreated
            FROM {facetoface} f
                JOIN {facetoface_sessions} s ON s.facetoface = f.id
                JOIN {facetoface_signups} su ON su.sessionid = s.id
                JOIN {facetoface_signups_status} ss ON ss.signupid = su.id
                LEFT JOIN (
                    SELECT ss.signupid, MAX(ss.timecreated) AS timecreated
                        FROM {facetoface_signups_status} ss
                        INNER JOIN {facetoface_signups} su ON su.id = ss.signupid
                        INNER JOIN {facetoface_sessions} s ON s.id = su.sessionid AND s.facetoface = :facetoface1
                            WHERE ss.statuscode IN (:status1, :status2)
                            GROUP BY ss.signupid
                ) sign ON sign.signupid = su.id
                JOIN {user} u ON u.id = su.userid
                    WHERE f.id = :facetoface2 AND ss.superceded != 1 AND ss.statuscode >= :status3
                    {$locationsql}
                    ORDER BY s.id, u.firstname, u.lastname", $params);

        // Organise attendees by adding grading and custom user profile fields.
        $sessionsignups = array();
        if ($signups) {
            $userids = array();
            foreach ($signups as $signup) {
                if ($signup->id > 0) {
                    $userids[$signup->id] = array();
                }
            }
            if (!empty($userids)) {
                $gradinginfo = grade_get_grades(reset($signups)->courseid, 'mod', 'facetoface', $this->id, array_keys($userids));
            }

            // Build signups list for worksheet.
            foreach ($signups as $signup) {
                $userid = $signup->id;
                if ($customuserfields = facetoface_get_user_customfields($userid, $userfields)) {
                    foreach ($customuserfields as $fieldname => $value) {
                        if (!isset($signup->$fieldname)) {
                            $signup->$fieldname = $value;
                        }
                    }
                }

                // Set grade.
                $signup->grade = null;
                if (isset($gradinginfo)) {
                    if (!empty($gradinginfo->items) and !empty($gradinginfo->items[0]->grades[$userid])) {
                        $signup->grade = $gradinginfo->items[0]->grades[$userid]->str_grade;
                    }
                }

                $sessionsignups[$signup->sessionid][$signup->id] = $signup;
            }
        }

        // Fetch sessions based on dates.
        $sessions = $DB->get_records_sql("SELECT d.id as dateid, s.id, s.datetimeknown,
            s.capacity, s.duration, d.timestart, d.timefinish
            FROM {facetoface_sessions} s
            JOIN {facetoface_sessions_dates} d ON d.sessionid = s.id
                WHERE s.facetoface = :facetoface1 AND d.sessionid = s.id
                $locationsql
                ORDER BY s.datetimeknown, d.timestart", $params);

        $timenow = time();
        $row = $start - 1;
        foreach ($sessions as $session) {
            $dates = array(
                'sessiondate' => '',
                'duration'    => (int) $session->duration,
                'starttime'   => get_string('wait-listed', 'facetoface'),
                'finishtime'  => get_string('wait-listed', 'facetoface'),
            );
            $status = get_string('wait-listed', 'facetoface');
            if ($session->datetimeknown) {

                // Session dates.
                if (method_exists($worksheet, 'write_date')) {
                    $dates['sessiondate'] = (int)$session->timestart;
                } else {
                    $dates['sessiondate'] = userdate($session->timestart, get_string('strftimedate'));
                }
                $dates['starttime']  = userdate($session->timestart, get_string('strftimetime'));
                $dates['finishtime'] = userdate($session->timefinish, get_string('strftimetime'));

                // Session status.
                if ($session->timestart < $timenow) {
                    $status = get_string('sessionover', 'facetoface');
                } else {
                    $signupcount = 0;
                    if (!empty($sessionsignups[$session->id])) {
                        $signupcount = count($sessionsignups[$session->id]);
                    }

                    if ($signupcount >= $session->capacity) {
                        $status = get_string('bookingfull', 'facetoface');
                    } else {
                        $status = get_string('bookingopen', 'facetoface');
                    }
                }
            }
            $sessiontrainers = $this->get_trainers($session);
            $customdata = $DB->get_records('facetoface_session_data', array('sessionid' => $session->id), '', 'fieldid, data');

            // Session has attendees.
            if (!empty($sessionsignups[$session->id])) {
                foreach ($sessionsignups[$session->id] as $attendee) {
                    $row++; $column = 0;
                    $column = $this->export_write_customfields($worksheet, $customfields, $customdata, $row, $column);
                    $column = $this->export_write_dates($worksheet, $dates, $status, $row, $column);
                    $column = $this->export_write_trainers($worksheet, $trainerroles, $sessiontrainers, $row, $column);
                    $column = $this->export_write_userdata($worksheet, $userfields, $attendee, $row, $column);
                }
            } else {

                // No one is sign-up, so let's just print the basic session info.
                $row++; $column = 0;
                $column = $this->export_write_customfields($worksheet, $customfields, $customdata, $row, $column);
                $column = $this->export_write_dates($worksheet, $dates, $status, $row, $column);

                // Dummy userfields as we have no attendees.
                foreach ($userfields as $unused) {
                    $worksheet->write_string($row, $column++, '-');
                }
                $worksheet->write_string($row, $column++, '-');
            }
        }

        return $row;
    }

    /**
     * Write the custom session fields to the worksheet
     *
     * @param object $worksheet currently open worksheet
     * @param array $customfields the customfields to write out in columns
     * @param array $customdata the sessions customdata matching the fields
     * @param integer $row the current row being written
     * @param string $column the starting column to write out
     * @returns integer index of the last column written
     */
    private function export_write_customfields(&$worksheet, $customfields, $customdata, $row, $column) {
        foreach ($customfields as $field) {
            if (empty($field->showinsummary)) {
                continue; // Skip.
            }

            $data = '-';
            if (!empty($customdata[$field->id])) {
                if (CUSTOMFIELD_TYPE_MULTISELECT == $field->type) {
                    $data = str_replace(CUSTOMFIELD_DELIMITER, "\n", $customdata[$field->id]->data);
                } else {
                    $data = $customdata[$field->id]->data;
                }
            }
            $worksheet->write_string($row, $column++, $data);
        }

        // Return final column count.
        return $column;
    }

    /**
     * Write the session dates to the worksheet
     *
     * @param object $worksheet currently open worksheet
     * @param array $dates the session dates to write out in columns
     * @param string $status the current status of the session
     * @param integer $row the current row being written
     * @param string $column the starting column to write out
     * @returns integer index of the last column written
     */
    private function export_write_dates(&$worksheet, $dates, $status, $row, $column) {
        if (empty($dates['sessiondate'])) {
            $worksheet->write_string($row, $column++, $status); // Session date replaced by status.
        } else {
            if (method_exists($worksheet, 'write_date')) {
                $worksheet->write_date($row, $column++, $dates['sessiondate'], 0);
            } else {
                $worksheet->write_string($row, $column++, $dates['sessiondate']);
            }
        }
        $worksheet->write_string($row, $column++, $dates['starttime']);
        $worksheet->write_string($row, $column++, $dates['finishtime']);
        $worksheet->write_number($row, $column++, $dates['duration']);
        $worksheet->write_string($row, $column++, $status);

        // Return final column count.
        return $column;
    }

    /**
     * Write the session trainers fields to the worksheet
     *
     * @param object $worksheet currently open worksheet
     * @param array $roles the allowed roles for trainers
     * @param array $sessiontrainers the sessions trainer list
     * @param integer $row the current row being written
     * @param string $column the starting column to write out
     * @returns integer index of the last column written
     */
    private function export_write_trainers(&$worksheet, $roles, $sessiontrainers, $row, $column) {
        if ($roles) {
            foreach (array_keys($roles) as $roleid) {
                if (!empty($sessiontrainers[$roleid])) {

                    $trainers = array();
                    foreach ($sessiontrainers[$roleid] as $trainer) {
                        $trainers[] = fullname($trainer);
                    }
                    $trainers = implode(', ', $trainers);
                } else {
                    $trainers = '-';
                }
                $worksheet->write_string($row, $column++, $trainers);
            }
        }

        // Return final column count.
        return $column;
    }

    /**
     * Write the attendees userdata to the worksheet
     *
     * @param object $worksheet currently open worksheet
     * @param array $userfields the user fields to write out
     * @param object $attendee the current attendee to write out
     * @param integer $row the current row being written
     * @param string $column the starting column to write out
     * @returns integer index of the last column written
     */
    private function export_write_userdata(&$worksheet, $userfields, $attendee, $row, $column) {

        // General user fields.
        $datefields = array('firstaccess', 'lastaccess', 'lastlogin', 'currentlogin');
        foreach ($userfields as $shortname => $fullname) {
            $value = '-';
            if (!empty($attendee->$shortname)) {
                $value = $attendee->$shortname;
            }
            if (in_array($shortname, $datefields)) {
                if (method_exists($worksheet, 'write_date')) {
                    $worksheet->write_date($row, $column++, (int) $value, 0);
                } else {
                    $worksheet->write_string($row, $column++, userdate($value, get_string('strftimedate')));
                }
            } else {
                $worksheet->write_string($row, $column++, $value);
            }
        }

        // Attendee grade.
        $worksheet->write_string($row, $column++, $attendee->grade);

        // Attendee signup date.
        if (method_exists($worksheet, 'write_date')) {
            $worksheet->write_date($row, $column++, (int)$attendee->timecreated, 0);
        } else {
            $signupdate = userdate($attendee->timecreated, get_string('strftimedatetime'));
            if (empty($signupdate)) {
                $signupdate = '-';
            }
            $worksheet->write_string($row, $column++, $signupdate);
        }

        // Return final column count.
        return $column;
    }
}
