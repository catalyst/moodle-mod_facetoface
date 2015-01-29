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

/*
* Unit tests for mod/facetoface/lib.php
*
* @author Chris Wharton <chrisw@catalyst.net.nz>
* @author Aaron Barnes <aaronb@catalyst.net.nz>
*/
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->libdir . '/simpletestlib.php');

class facetofacelib_test extends prefix_changing_test_case {

    // Test database data.
    public $configdata = array(
        array('id', 'name', 'value'),
        array(0, '', ''),
    );

    public $facetofacedata = array(
        array('id',                     'course',              'name',                     'thirdparty',
            'thirdpartywaitlist',       'display',             'confirmationsubject',      'confirmationinstrmngr',
            'confirmationmessage',      'waitlistedsubject',   'waitlistedmessage',        'cancellationsubject',
            'cancellationinstrmngr',    'cancellationmessage', 'remindersubject',          'reminderinstrmngr',
            'remindermessage',          'reminderperiod',      'requestsubject',           'requestinstrmngr',
            'requestmessage',           'timecreated',         'timemodified',             'shortname',
            'description',              'showoncalendar',      'approvalreqd'
        ),
        array(1,                        1,                  'name1',                    'thirdparty1',
            0,                          0,                  'consub1',                  'coninst1',
            'conmsg1',                  'waitsub1',         'waitmsg1',                 'cansub1',
            'caninst1',                 'canmsg1',          'remsub1',                  'reminst1',
            'remmsg1',                  0,                  'reqsub1',                  'reqinst1',
            'reqmsg1',                  0,                  0,                          'short1',
            'desc1',                    1,                  0
        ),
        array(2,                        2,                  'name2',                    'thirdparty2',
            0,                          0,                  'consub2',                  'coninst2',
            'conmsg2',                  'waitsub2',         'waitmsg2',                 'cansub2',
            'caninst2',                 'canmsg2',          'remsub2',                  'reminst2',
            'remmsg2',                 0,                  'reqsub2',                  'reqinst2',
            'reqmsg2',                  0,                  0,                          'short2',
            'desc2',                    1,                  0
        ),
        array(3,                        3,                  'name3',                    'thirdparty3',
            0,                          0,                  'consub3',                  'coninst3',
            'conmsg3',                  'waitsub3',         'waitmsg3',                 'cansub3',
            'caninst3',                 'canmsg3',          'remsub3',                  'reminst3',
            'remmsg3',                  0,                  'reqsub3',                  'reqinst3',
            'reqmsg3',                  0,                  0,                          'short3',
            'desc3',                    1,                  0
        ),
        array(4,                        4,                  'name4',                    'thirdparty4',
            0,                          0,                  'consub4',                  'coninst4',
            'conmsg4',                  'waitsub4',         'waitmsg4',                 'cansub4',
            'caninst4',                 'canmsg4',          'remsub4',                  'reminst4',
            'remmsg4',                  0,                  'reqsub4',                  'reqinst4',
            'reqmsg4',                  0,                  0,                          'short4',
            'desc4',                    1,                  0
        ),
    );

    public $facetofacesessionsdata = array(
        array('id', 'facetoface', 'capacity', 'allowoverbook', 'details', 'datetimeknown',
              'duration', 'normalcost', 'discountcost', 'timecreated', 'timemodified'),
        array(1,    1,   100,    1,  'dtl1',     1,     4,     75,     60,     1500,   1600),
        array(2,    2,    50,    0,  'dtl2',     0,     1,     90,   null,     1400,   1500),
        array(3,    3,    10,    1,  'dtl3',     1,     7,    100,     80,     1500,   1500),
        array(4,    4,    1,     0,  'dtl4',     0,     7,     10,      8,     0500,   1900),
        );

    public $facetofacesessionsdatesdata = array(
        array('id',     'sessionid',    'timestart',    'timefinish'),
        array(1,        1,              1100,           1300),
        array(2,        2,              1900,           2100),
        array(3,        3,              0900,           1100),
        array(3,        3,              1200,           1400),
    );

    public $facetofacesignupsdata = array(
        array('id', 'sessionid', 'userid', 'mailedreminder', 'discountcode', 'notificationtype'),
        array(1,    1,  1,  1,  'disc1',    7),
        array(2,    2,  2,  0,  null,       6),
        array(3,    2,  3,  0,  null,       5),
        array(4,    2,  4,  0,  'disc4',   11),
    );

    public $facetofacesignupsstatusdata = array(
        array('id',     'signupid',     'statuscode',   'superceded',   'grade',
            'note',     'advice',       'createdby',    'timecreated'),
        array(1,        1,              70,             0,              99.12345,
            'note1',    'advice1',      'create1',      1600),
        array(2,        2,              70,             0,              32.5,
            'note2',    'advice2',      'create2',      1700),
        array(3,        3,              70,             0,              88,
            'note3',    'advice3',      'create3',      0700),
        array(4,        4,              70,             0,              12.5,
            'note4',    'advice4',      'create4',      1100),
    );

    public $facetofacesessionfielddata = array(
        array('id',     'name',     'shortname',    'type',     'possiblevalues',
            'required',     'defaultvalue',   'isfilter',     'showinsummary'),
        array(1,    'name1',    'shortname1',   0,  'possible1',    0,  'defaultvalue1',    1,  1),
        array(2,    'name2',    'shortname2',   2,  'possible2',    0,  'defaultvalue2',    1,  1),
        array(3,    'name3',    'shortname3',   3,  'possible3',    1,  'defaultvalue3',    1,  1),
        array(4,    'name4',    'shortname4',   4,  'possible4',    1,  'defaultvalue4',    1,  1),
    );

    public $facetofacesessiondatadata = array(
        array('id', 'fieldid', 'sessionid', 'data'),
        array(1,    0,  0,  'test data1'),
        array(2,    1,  1,  'test data2'),
        array(3,    2,  2,  'test data3'),
        array(4,    3,  3,  'test data4'),
    );

    public $coursedata = array(
        array('id',            'category',         'sortorder',             'password',
            'fullname',        'shortname',        'idnumber',              'summary',
            'format',          'showgrades',       'modinfo',               'newsitems',
            'teacher',         'teachers',         'student',               'students',
            'guest',           'startdate',        'enrolperiod',           'numsections',
            'marker',          'maxbytes',         'showreports',           'visible',
            'hiddensections',  'groupmode',        'groupmodeforce',        'defaultgroupid',
            'lang',            'theme',            'cost',                  'currency',
            'timecreated',     'timemodified',     'metacourse',            'requested',
            'restrictmodules', 'expirynotify',     'expirythreshold',       'notifystudents',
            'enrollable',      'enrolstartdate',   'enrolenddate',          'enrol',
            'defaultrole',     'enablecompletion', 'completionstartenrol',  'icon'
        ),
        array(1,            0,              0,              'pw1',
            'name1',        'sn1',          '101',          'summary1',
            'format1',      1,              'mod1',         1,
            'teacher1',     'teachers1',    'student1',     'students1',
            0,              0,              0,              1,
            0,              0,              0,              1,
            0,              0,              0,              0,
            'lang1',        'theme1',       'cost1',        'cu1',
            0,              0,              0,              0,
            0,              0,              0,              0,
            1,              0,              0,              'enrol1',
            0,              0,              0,              'icon1'
        ),
        array(2,            0,              0,              'pw2',
            'name2',        'sn2',          '102',          'summary2',
            'format2',      1,              'mod2',         1,
            'teacher2',     'teachers2',    'student2',     'students2',
            0,              0,              0,              1,
            0,              0,              0,              1,
            0,              0,              0,              0,
            'lang2',        'theme2',       'cost2',        'cu2',
            0,              0,              0,              0,
            0,              0,              0,              0,
            1,              0,              0,              'enrol2',
            0,              0,              0,              'icon2'
        ),
        array(3,            0,              0,              'pw3',
            'name3',        'sn3',          '103',          'summary3',
            'format3',      1,              'mod3',         1,
            'teacher3',     'teachers3',    'student3',     'students3',
            0,              0,              0,              1,
            0,              0,              0,              1,
            0,              0,              0,              0,
            'lang3',        'theme3',       'cost3',        'cu3',
            0,              0,              0,              0,
            0,              0,              0,              0,
            1,              0,              0,              'enrol3',
            0,              0,              0,              'icon3'
        ),
        array(4,            0,              0,              'pw4',
            'name4',        'sn4',          '104',          'summary4',
            'format4',      1,              'mod4',         1,
            'teacher4',     'teachers4',    'student4',     'students4',
            0,              0,              0,              1,
            0,              0,              0,              1,
            0,              0,              0,              0,
            'lang4',        'theme4',       'cost4',        'cu4',
            0,              0,              0,              0,
            0,              0,              0,              0,
            1,              0,              0,              'enrol4',
            0,              0,              0,              'icon4'
        ),
    );

    public $eventdata = array(
        array(
            'id',           'name',     'description',      'format',
            'courseid',     'groupid',  'userid',           'repeatid',
            'modulename',   'instance', 'eventtype',        'timestart',
            'timeduration', 'visible',  'uuid',             'sequence',
            'timemodified'
        ),
        array(
            1,              'name1',    'desc1',             0,
            1,              1,          1,                   0,
            'facetoface',   1,          'facetofacesession', 1300,
            3,              1,          'uuid1',             1,
            0
        ),
        array(
            2,             'name2',    'desc2',              0,
            2,              2,          2,                   0,
            'facetoface',   2,          'facetofacesession', 2300,
            3,              2,          'uuid2',             2,
            0
        ),
        array(
            3,             'name3',    'desc3',              0,
            3,              3,          3,                   0,
            'facetoface',   3,          'facetofacesession', 3300,
            3,              3,          'uuid3',             3,
            0
        ),
        array(
            4,             'name4',    'desc4',              0,
            4,              4,          4,                   0,
            'facetoface',   4,          'facetofacesession', 4300,
            3,              4,          'uuid4',             4,
            0
        ),
    );

    public $roledata = array(
        array('id', 'name',     'shortname'),
        array(1,    'Manager',    'manager'),
        array(2,    'Trainer',    'trainer'),
    );

    public $roleassignmentsdata = array(
        array(
            'id', 'roleid', 'contextid', 'userid', 'hidden',
            'timestart', 'timeend'
        ),
        array(1,  1,  1,  2,  0,  0,  0),
        array(2,  2,  2,  2,  1,  0,  0),
        array(3,  3,  3,  3,  0,  0,  0),
        array(4,  2,  3,  2,  0,  0,  0),
    );

    public $posassignmentdata = array(
        array(
            'id', 'fullname', 'shortname', 'idnumber', 'description',
            'timevalidfrom', 'timevalidto', 'timecreated', 'timemodified',
            'usermodified', 'organisationid', 'userid', 'positionid',
            'reportstoid', 'type'
        ),
        array(
            1, 'fullname1', 'shortname1', 'idnumber1', 'desc1',
            0900, 1000, 0800, 1300,
            1, 1122, 1, 2,
            1, 1
        ),
        array(
            2, 'fullname2', 'shortname2', 'idnumber2', 'desc2',
            0900, 2000, 0800, 2300,
            2, 2222, 2, 2,
            2, 2
        ),
        array(
            3, 'fullname3', 'shortname3', 'idnumber3', 'desc3',
            0900, 3000, 0800, 3300,
            3, 3322, 3, 2,
            3, 3
        ),
        array(
            4, 'fullname4', 'shortname4', 'idnumber4', 'desc4',
            0900, 4000, 0800, 4300,
            4, 4422, 4, 2,
            4, 4
        ),
    );

    public $coursemodulesdata = array(
        array(
            'id', 'course', 'module', 'instance', 'section', 'idnumber',
            'added', 'score', 'indent', 'visible', 'visibleold', 'groupmode',
            'groupingid', 'groupmembersonly', 'completion', 'completiongradeitemnumber',
            'completionview', 'completionview', 'completionexpected', 'availablefrom',
            'availableuntil', 'showavailability'
        ),
        array(
            1, 2, 3, 4, 5, '1001',
            6, 1, 7, 1, 1, 0,
            8, 0, 0, 10,
            0, 11, 12, 13,
            14, 1
        ),
        array(
            2, 2, 3, 4, 5, '1002',
            6, 1, 7, 1, 1, 0,
            8, 0, 0, 10,
            0, 11, 12, 13,
            14, 1
        ),
        array(
            3, 2, 3, 4, 5, '1003',
            6, 1, 7, 1, 1, 0,
            8, 0, 0, 10,
            0, 11, 12, 13,
            14, 1
        ),
        array(
            4, 2, 3, 4, 5, '1004',
            6, 1, 7, 1, 1, 0,
            8, 0, 0, 10,
            0, 11, 12, 13,
            14, 1
        ),
    );

    public $gradeitemsdata = array(
        array(
            'id', 'courseid', 'categoryid', 'itemname', 'itemtype',
            'itemmodule', 'iteminstance', 'itemnumber', 'iteminfo', 'idnumber',
            'calculation', 'gradetype', 'grademax', 'grademin', 'scaleid',
            'outcomeid', 'gradepass', 'multfactor', 'plusfactor', 'aggregationcoef',
            'sortorder', 'display', 'decimals', 'hidden', 'locked',
            'locktime', 'needsupdate', 'timecreated', 'timemodified'
        ),
        array(
            1, 1, 1, 'itemname1', 'type1',
            'module1', 1, 100, 'info1', '10012',
            'calc1', 1, 100, 0, 70,
            80, 0, 1.0, 0, 0,
            0, 0, 1, 0, 0,
            0, 0, 0, 0
        ),
        array(
            2, 1, 1, 'itemname1', 'type1',
            'module1', 1, 100, 'info1', '10012',
            'calc1', 1, 100, 0, 70,
            80, 0, 1.0, 0, 0,
            0, 0, 1, 0, 0,
            0, 0, 0, 0
        ),
        array(
            3, 1, 1, 'itemname1', 'type1',
            'module1', 1, 100, 'info1', '10012',
            'calc1', 1, 100, 0, 70,
            80, 0, 1.0, 0, 0,
            0, 0, 1, 0, 0,
            0, 0, 0, 0
        ),
        array(
            4, 1, 1, 'itemname1', 'type1',
            'module1', 1, 100, 'info1', '10012',
            'calc1', 1, 100, 0, 70,
            80, 0, 1.0, 0, 0,
            0, 0, 1, 0, 0,
            0, 0, 0, 0
        ),
    );

    public $modulesdata = array(
        array(
            'id', 'name', 'version', 'cron',
            'lastcron', 'search', 'visible'
        ),
        array(
            1, 'name1', 0, 0,
            0, 'search1', 1
        ),
        array(
            2, 'name1', 0, 0,
            0, 'search1', 1
        ),
        array(
            3, 'name1', 0, 1,
            0, 'search1', 1
        ),
        array(
            4, 'name1', 0, 1,
            0, 'search1', 1
        ),
    );

    public $gradecategoriesdata = array(
        array(
            'id', 'courseid', 'parent', 'depth', 'path',
            'fullname', 'aggregation', 'keephigh', 'droplow',
            'aggregateonlygraded', 'aggregateoutcomes', 'aggregatesubcats',
            'timecreated', 'timemodified'
        ),
        array(
            1, 1, 1, 1, 'path1',
            'fullname1', 0, 0, 0,
            0, 0, 0,
            1300, 1400
        ),
        array(
            2, 1, 1, 1, 'path1',
            'fullname1', 0, 0, 0,
            0, 0, 0,
            1300, 1400
        ),
        array(
            3, 1, 1, 1, 'path1',
            'fullname1', 0, 0, 0,
            0, 0, 0,
            1300, 1400
        ),
        array(
            4, 1, 1, 1, 'path1',
            'fullname1', 0, 0, 0,
            0, 0, 0,
            1300, 1400
        ),
    );

    public $userdata = array(
        array(
            'id',                   'auth',             'confirmed',
            'policyagreed',         'deleted',          'mnethostid',
            'username',             'password',         'idnumber',
            'firstname',            'lastname',         'email',
            'emailstop',            'icq',              'skype',
            'yahoo',                'aim',              'msn',
            'phone1',               'phone2',           'institution',
            'department',           'address',          'city',
            'country',              'lang',             'theme',
            'timezone',             'firstaccess',      'lastaccess',
            'lastlogin',            'currentlogin',     'lastip',
            'secret',               'picture',          'url',
            'description',          'mailformat',       'maildigest',
            'maildisplay',          'htmleditor',       'ajax',
            'autosubscribe',        'trackforums',      'timemodified',
            'trustbitmask',         'imagealt',         'screenreader',
        ),

        // 16 lines * 3 columns = 48 fields.
        array(
            1,                      'auth1',            0,
            0,                      0,                  10,
            'user1',                'test',             'idnumber',
            '10012',                'name1',            'user1@example.com',
            1,                      0,                  'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'test',                 'NZ',               'en_utf8',
            'test',                 '12',               0,
            0,                      'desc1',            1,
            0,                      2,                  1,
            1,                      1,                  0,
            0,                      0,                  'imgalt1',
            0,                      0,                  0,
            0,                      0,                  0
        ),

        array(
            2,                      'auth2',            0,
            0,                      0,                  20,
            'user2',                'test',             'idnumber',
            '20022',                'name2',            'user2@example.com',
            1,                      0,                  'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'test',                 'NZ',               'en_utf8',
            'test',                 '22',               0,
            0,                      'desc2',            2,
            0,                      2,                  2,
            2,                      2,                  0,
            0,                      0,                  'imgalt2',
            0,                      0,                  0,
            0,                      0,                  0
        ),

        array(
            3,                      'auth3',            0,
            0,                      0,                  30,
            'user3',                'test',             'idnumber',
            '30032',                'name3',            'user3@example.com',
            1,                      0,                  'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'test',                 'NZ',               'en_utf8',
            'test',                 '32',               0,
            0,                      'desc3',            3,
            0,                      2,                  3,
            3,                      3,                  0,
            0,                      0,                  'imgalt3',
            0,                      0,                  0,
            0,                      0,                  0
        ),

        array(
            4,                      'auth4',            0,
            0,                      0,                  40,
            'user4',                'test',             'idnumber',
            '40042',                'name4',            'user4@example.com',
            1,                      0,                  'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'test',                 'NZ',               'en_utf8',
            'test',                 '42',               0,
            0,                      'desc4',            4,
            0,                      2,                  4,
            4,                      4,                  0,
            0,                      0,                  'imgalt4',
            0,                      0,                  0,
            0,                      0,                  0
        ),
    );

    public $gradegradesdata = array(
        array(
            'id',                 'itemid',           'userid',
            'rawgrade',             'rawgrademax',      'rawgrademin',
            'rawscaleid',           'usermodified',     'finalgrade',
            'hidden',               'locked',           'locktime',
            'exported',             'overridden',       'excluded',
            'feedback',             'feedbackformat',   'information',
            'informationformat',    'timecreated',      'timemodified'
        ),
        array(
            1,                      2,                  3,
            50,                     100,                0,
            30,                     1 ,                 80.2,
            0,                      0,                  0,
            0,                      0,                  0,
            'feedback1',            0,                  'info1',
            0,                      1300,               1400
        ),
        array(
            2,                      2,                  3,
            50,                     200,                0,
            30,                     2 ,                 80.2,
            0,                      0,                  0,
            0,                      0,                  0,
            'feedback2',            0,                  'info2',
            0,                      2300,               2400
        ),
        array(
            3,                      2,                  3,
            50,                     300,                0,
            30,                     3 ,                 80.2,
            0,                      0,                  0,
            0,                      0,                  0,
            'feedback3',            0,                  'info3',
            0,                      3300,               3400
        ),
        array(
            4,                      2,                  3,
            50,                     400,                0,
            30,                     4 ,                 80.2,
            0,                      0,                  0,
            0,                      0,                  0,
            'feedback4',            0,                  'info4',
            0,                      4300,               4400
        ),
    );

    public $userinfofielddata = array(
        array(
            'id',                   'shortname',        'name',
            'datatype',             'description',      'categoryid',
            'sortorder',            'required',         'locked',
            'visible',              'forceunique',      'signup',
            'defaultdata',          'param1',           'param2',
            'param3',               'param4',           'param5'
        ),
        array(
            1,                      'shortname1',      'name1',
            'datatype1',            'desc1',            0,
            0,                      0,                  0,
            0,                      0,                  0,
            0,                      'param1',           'param2',
            'param3',               'param4',           'param5'
        ),
        array(
            2,                      'shortname2',      'name2',
            'datatype2',            'desc2',            0,
            0,                      0,                  0,
            0,                      0,                  0,
            0,                      'param1',           'param2',
            'param3',               'param4',           'param5'
        ),
        array(
            3,                     'shortname3',       'name3',
            'datatype3',            'desc3',            0,
            0,                      0,                  0,
            0,                      0,                  0,
            0,                      'param1',           'param2',
            'param3',               'param4',           'param5'
        ),
        array(
            4,                      'shortname4',       'name4',
            'datatype4',            'desc4',            0,
            0,                      0,                  0,
            0,                      0,                  0,
            0,                      'param1',           'param2',
            'param4',               'param4',           'param5'
        ),
    );

    public $userinfodatadata = array(
        array('id',    'userid',   'fieldid',  'data'),
        array(1,    1,  1,  'data1'),
        array(2,    2,  2,  'data2'),
        array(3,    3,  3,  'data3'),
        array(4,    4,  4,  'data4'),
    );

    public $blockinstancedata = array(
        array(
            'id',       'blockid',  'pageid',   'pagetype',
            'position', 'weight',   'visible',  'configdata'
        ),
        array(
            1,           0,          0,          'pagetype1',
            'position1', 0,          0,          'configdata1'
        ),
        array(
            2,           0,          0,          'pagetype2',
            'position2', 0,          0,          'configdata2'
        ),
        array(
            3,           0,          0,          'pagetype3',
            'position3', 0,          0,          'configdata3'
        ),
        array(
            4,           0,          0,          'pagetype4',
            'position4', 0,          0,          'configdata4'
        ),
    );

    public $userinfocategorydata = array(
        array('id', 'name', 'sortorder'),
        array(1,    'name1',          0),
        array(2,    'name2',          0),
        array(3,    'name3',          0),
        array(4,    'name4',          0),
    );

    public $contextdata = array(
        array('id', 'contextlevel', 'instanceid',   'path', 'depth'),
        array(1,    0,              0,              'path1',    0),
        array(2,    1,              1,              'path2',    1),
        array(3,    1,              1,              'path3',    1),
        array(4,    1,              1,              'path4',    1),
    );

    public $coursecategoriesdata = array(
        array(
            'id',            'name',     'description',  'parent',   'sortorder',
            'coursecount',   'visible',  'timemodified', 'depth',
            'path', 'theme', 'icon'
        ),
        array(
            1,          'name1',    'desc1',    0,  0,
            0,          1,          0,          0,
            'path1',    'theme1',   'icon1'
        ),
        array(
            2,          'name2',    'desc2',    0,  0,
            0,          2,          0,          0,
            'path2',    'theme2',   'icon2'
        ),
        array(
            3,          'name3',    'desc3',    0,  0,
            0,          3,          0,          0,
            'path3',    'theme3',   'icon3'
        ),
        array(
            4,          'name4',    'desc4',    0,  0,
            0,          4,          0,          0,
            'path4',    'theme4',   'icon4'
        ),
    );

    public $facetofacesessionrolesdata = array (
        array('id', 'sessionid', 'roleid', 'userid'),
        array(1,    1,  1,  1),
        array(2,    2,  2,  2),
        array(3,    3,  3,  3),
        array(4,    4,  4,  4),
    );

    public $facetofacenoticedata = array (
        array('id', 'name',     'text'),
        array(1,    'name1',    'text1'),
        array(2,    'name2',    'text2'),
        array(3,    'name3',    'text3'),
        array(4,    'name4',    'text4'),
    );

    public $timezonedata = array (
        array(
            'id',       'name',         'year',             'tzrule',       'gmtoff',
            'dstoff',   'dst_month',    'dst_saturday',     'dst_weekday',
            'dst_skipweeks',            'dst_time',
            'std_month',                'std_saturday',     'std_weekday',
            'std_skipweeks',            'std_time'
        ),
        array(
            1,          'test',         2010,                'rule1',       0,
            0,          0,              0,                   0,
            0,          '00:00',
            0,          0,              0,
            0,          '00:00'
        ),
        array(
            2,          'test2',        2010,                'rule2',       0,
            0,          0,              0,                   0,
            0,          '00:00',
            0,          0,              0,
            0,          '00:00'
        ),
        array(
            3,         'test3',        2010,                'rule3',       0,
            0,          0,              0,                   0,
            0,          '00:00',
            0,          0,              0,
            0,          '00:00'
        ),
        array(
            4,         'test4',        2010,                'rule4',       0,
            0,          0,              0,                   0,
            0,          '00:00',
            0,          0,              0,
            0,          '00:00'
        ),
    );

    public $userpreferencesdata = array (
        array('id',     'userid',   'name',     'value'),
        array(1,        1,          'name1',    'val1'),
        array(2,        2,          'name2',    'val2'),
        array(3,        3,          'name3',    'val3'),
        array(4,        4,          'name4',    'val4'),
    );

    // Function to load test tables.
    public function setup() {
        global $db, $CFG;
        parent::setUp();

        // Try statement temporary - rebuilds error'ed tables without having to manually disable setup / teardown functions.
        try {
            load_test_table($CFG->prefix . 'config', $this->configdata, $db, 255, true);
            load_test_table($CFG->prefix . 'facetoface_signups', $this->facetofacesignupsdata, $db);
            load_test_table($CFG->prefix . 'facetoface_sessions', $this->facetofacesessionsdata, $db);
            load_test_table($CFG->prefix . 'facetoface_session_field', $this->facetofacesessionfielddata, $db);
            load_test_table($CFG->prefix . 'facetoface_session_data', $this->facetofacesessiondatadata, $db);
            load_test_table($CFG->prefix . 'course', $this->coursedata, $db);
            load_test_table($CFG->prefix . 'facetoface', $this->facetofacedata, $db);
            load_test_table($CFG->prefix . 'facetoface_sessions_dates', $this->facetofacesessionsdatesdata, $db);
            load_test_table($CFG->prefix . 'facetoface_signups_status', $this->facetofacesignupsstatusdata, $db);
            load_test_table($CFG->prefix . 'event', $this->eventdata, $db, 2000);
            load_test_table($CFG->prefix . 'role', $this->roledata, $db);
            load_test_table($CFG->prefix . 'role_assignments', $this->roleassignmentsdata, $db);
            load_test_table($CFG->prefix . 'pos_assignment', $this->posassignmentdata, $db);
            load_test_table($CFG->prefix . 'course_modules', $this->coursemodulesdata, $db);
            load_test_table($CFG->prefix . 'grade_items', $this->gradeitemsdata, $db);
            load_test_table($CFG->prefix . 'modules', $this->modulesdata, $db);
            load_test_table($CFG->prefix . 'grade_categories', $this->gradecategoriesdata, $db);
            load_test_table($CFG->prefix . 'user', $this->userdata, $db);
            load_test_table($CFG->prefix . 'grade_grades', $this->gradegradesdata, $db);
            load_test_table($CFG->prefix . 'user_info_field', $this->userinfofielddata, $db);
            load_test_table($CFG->prefix . 'user_info_data', $this->userinfodatadata, $db);
            load_test_table($CFG->prefix . 'block_instance', $this->blockinstancedata, $db);
            load_test_table($CFG->prefix . 'user_info_category', $this->userinfocategorydata, $db);
            load_test_table($CFG->prefix . 'context', $this->contextdata, $db);
            load_test_table($CFG->prefix . 'course_categories', $this->coursecategoriesdata, $db);
            load_test_table($CFG->prefix . 'facetoface_session_roles', $this->facetofacesessionrolesdata, $db);
            load_test_table($CFG->prefix . 'facetoface_notice', $this->facetofacenoticedata, $db);
            load_test_table($CFG->prefix . 'timezone', $this->timezonedata, $db);
            load_test_table($CFG->prefix . 'user_preferences', $this->userpreferencesdata, $db);
        } catch (Exception $e) {
            tearDown();
            setup();
        }

        // Create sample objects.
        // Facetoface object 1.
        $this->facetoface = array();
        $this->facetoface[0] = new stdClass();
        $this->facetoface[0]->id = 1;
        $this->facetoface[0]->instance = 1;
        $this->facetoface[0]->course = 10;
        $this->facetoface[0]->name = 'name1';
        $this->facetoface[0]->thirdparty = 'thirdparty1';
        $this->facetoface[0]->thirdpartywaitlist = 0;
        $this->facetoface[0]->display = 1;
        $this->facetoface[0]->confirmationsubject = 'consub1';
        $this->facetoface[0]->confirmationinstrmngr = '';
        $this->facetoface[0]->confirmationmessage = 'conmsg1';
        $this->facetoface[0]->reminderinstrmngr = '';
        $this->facetoface[0]->reminderperiod = 0;
        $this->facetoface[0]->waitlistedsubject = 'waitsub1';
        $this->facetoface[0]->cancellationinstrmngr = '';
        $this->facetoface[0]->showoncalendar = 1;
        $this->facetoface[0]->shortname = 'shortname1';
        $this->facetoface[0]->description = 'description1';
        $this->facetoface[0]->timestart = 1300;
        $this->facetoface[0]->timefinish = 1500;
        $this->facetoface[0]->emailmanagerconfirmation = 'test1';
        $this->facetoface[0]->emailmanagerreminder = 'test2';
        $this->facetoface[0]->emailmanagercancellation = 'test3';
        $this->facetoface[0]->showcalendar = 1;
        $this->facetoface[0]->approvalreqd = 0;
        $this->facetoface[0]->requestsubject = 'reqsub1';
        $this->facetoface[0]->requestmessage = 'reqmsg1';
        $this->facetoface[0]->requestinstrmngr = '';

        // Facetoface object 2.
        $this->facetoface[1] = new stdClass();
        $this->facetoface[1]->id = 2;
        $this->facetoface[1]->instance = 2;
        $this->facetoface[1]->course = 20;
        $this->facetoface[1]->name = 'name2';
        $this->facetoface[1]->thirdparty = 'thirdparty2';
        $this->facetoface[1]->thirdpartywaitlist = 0;
        $this->facetoface[1]->display = 0;
        $this->facetoface[1]->confirmationsubject = 'consub2';
        $this->facetoface[1]->confirmationinstrmngr = 'conins2';
        $this->facetoface[1]->confirmationmessage = 'conmsg2';
        $this->facetoface[1]->reminderinstrmngr = 'remmngr2';
        $this->facetoface[1]->reminderperiod = 1;
        $this->facetoface[1]->waitlistedsubject = 'waitsub2';
        $this->facetoface[1]->cancellationinstrmngr = 'canintmngr2';
        $this->facetoface[1]->showoncalendar = 1;
        $this->facetoface[1]->shortname = 'shortname2';
        $this->facetoface[1]->description = 'description2';
        $this->facetoface[1]->timestart = 2300;
        $this->facetoface[1]->timefinish = 2330;
        $this->facetoface[1]->emailmanagerconfirmation = 'test2';
        $this->facetoface[1]->emailmanagerreminder = 'test2';
        $this->facetoface[1]->emailmanagercancellation = 'test3';
        $this->facetoface[1]->showcalendar = 1;
        $this->facetoface[1]->approvalreqd = 1;
        $this->facetoface[1]->requestsubject = 'reqsub2';
        $this->facetoface[1]->requestmessage = 'reqmsg2';
        $this->facetoface[1]->requestinstrmngr = 'reqinstmngr2';

        // Session object 1.
        $this->session = array();
        $this->session[0] = new stdClass();
        $this->session[0]->id = 1;
        $this->session[0]->facetoface = 1;
        $this->session[0]->capacity = 0;
        $this->session[0]->allowoverbook = 1;
        $this->session[0]->details = 'details1';
        $this->session[0]->datetimeknown = 1;
        $this->session[0]->sessiondates = array();
        $this->session[0]->id = 20;
        $this->session[0]->sessiondates[0]->timestart = time() - 1000;
        $this->session[0]->sessiondates[0]->timefinish = time() + 1000;
        $this->session[0]->duration = 3;
        $this->session[0]->normalcost = 100;
        $this->session[0]->discountcost = 75;
        $this->session[0]->timecreated = 1300;
        $this->session[0]->timemodified = 1400;

        // Session object 2.
        $this->session[1] = new stdClass();
        $this->session[1]->id = 2;
        $this->session[1]->facetoface = 2;
        $this->session[1]->capacity = 3;
        $this->session[1]->allowoverbook = 0;
        $this->session[1]->details = 'details2';
        $this->session[1]->datetimeknown = 0;
        $this->session[1]->sessiondates = array();
        $this->session[1]->sessiondates[0]->id = 20;
        $this->session[1]->sessiondates[0]->timestart = time() + 10000;
        $this->session[1]->sessiondates[0]->timefinish = time() + 100000;
        $this->session[1]->duration = 6;
        $this->session[1]->normalcost = 100;
        $this->session[1]->discountcost = 75;
        $this->session[1]->timecreated = 1300;
        $this->session[1]->timemodified = 1400;

        // Sessiondata object 1.
        $this->sessiondata = array();
        $this->sessiondata[0] = new stdClass();
        $this->sessiondata[0]->id = 1;
        $this->sessiondata[0]->fieldid = 1;
        $this->sessiondata[0]->sessionid = 1;
        $this->sessiondata[0]->data = 'testdata1';
        $this->sessiondata[0]->discountcost = 60;
        $this->sessiondata[0]->normalcost = 75;

        // Sessiondata object 2.
        $this->sessiondata[1] = new stdClass();
        $this->sessiondata[1]->id = 2;
        $this->sessiondata[1]->fieldid = 2;
        $this->sessiondata[1]->sessionid = 2;
        $this->sessiondata[1]->data = 'testdata2';
        $this->sessiondata[1]->discountcost = null;
        $this->sessiondata[1]->normalcost = 90;

        // User object 1.
        $this->user = array();
        $this->user[0] = new stdClass();
        $this->user[0]->id = 1;
        $this->user[0]->firstname = 'firstname1';
        $this->user[0]->lastname = 'lastname1';

        // User object 2.
        $this->user[1] = new stdClass();
        $this->user[1]->id = 2;
        $this->user[1]->firstname = 'firstname2';
        $this->user[1]->lastname = 'lastname2';

        // Course object 1.
        $this->course = array();
        $this->course[0] = new stdClass();
        $this->course[0]->id = 1;
        $this->course[0]->enablecompletion = true;

        // Course object 2.
        $this->course[1] = new stdClass();
        $this->course[1]->id = 42;
        $this->course[1]->enablecompletion = false;

        // Message string 1.
        $this->msgtrue = 'should be true';

        // Message string 2.
        $this->msgfalse = 'should be false';
    }

    public function tearDown() {
        global $db, $CFG;

        remove_test_table($CFG->prefix . 'config', $db);
        remove_test_table($CFG->prefix . 'facetoface_signups', $db);
        remove_test_table($CFG->prefix . 'facetoface_sessions', $db);
        remove_test_table($CFG->prefix . 'facetoface_session_field', $db);
        remove_test_table($CFG->prefix . 'facetoface_session_data', $db);
        remove_test_table($CFG->prefix . 'course', $db);
        remove_test_table($CFG->prefix . 'facetoface', $db);
        remove_test_table($CFG->prefix . 'facetoface_sessions_dates', $db);
        remove_test_table($CFG->prefix . 'facetoface_signups_status', $db);
        remove_test_table($CFG->prefix . 'event', $db);
        remove_test_table($CFG->prefix . 'role', $db);
        remove_test_table($CFG->prefix . 'role_assignments', $db);
        remove_test_table($CFG->prefix . 'pos_assignment', $db);
        remove_test_table($CFG->prefix . 'course_modules', $db);
        remove_test_table($CFG->prefix . 'grade_items', $db);
        remove_test_table($CFG->prefix . 'modules', $db);
        remove_test_table($CFG->prefix . 'grade_categories', $db);
        remove_test_table($CFG->prefix . 'user', $db);
        remove_test_table($CFG->prefix . 'grade_grades', $db);
        remove_test_table($CFG->prefix . 'user_info_field', $db);
        remove_test_table($CFG->prefix . 'user_info_data', $db);
        remove_test_table($CFG->prefix . 'block_instance', $db);
        remove_test_table($CFG->prefix . 'user_info_category', $db);
        remove_test_table($CFG->prefix . 'context', $db);
        remove_test_table($CFG->prefix . 'course_categories', $db);
        remove_test_table($CFG->prefix . 'facetoface_session_roles', $db);
        remove_test_table($CFG->prefix . 'facetoface_notice', $db);
        remove_test_table($CFG->prefix . 'timezone', $db);
        remove_test_table($CFG->prefix . 'user_preferences', $db);

        parent::tearDown();
    }

    // Test method - returns string.
    public function test_facetoface_get_status() {

        // Check for valid status codes.
        $this->assertEqual(facetoface_get_status(10), 'user_cancelled');

        // SESSION_CANCELLED is not yet implemented.
        // $this->assertEqual(facetoface_get_status(20), 'session_cancelled');
        $this->assertEqual(facetoface_get_status(30), 'declined');
        $this->assertEqual(facetoface_get_status(40), 'requested');
        $this->assertEqual(facetoface_get_status(50), 'approved');
        $this->assertEqual(facetoface_get_status(60), 'waitlisted');
        $this->assertEqual(facetoface_get_status(70), 'booked');
        $this->assertEqual(facetoface_get_status(80), 'no_show');
        $this->assertEqual(facetoface_get_status(90), 'partially_attended');
        $this->assertEqual(facetoface_get_status(100), 'fully_attended');

        // TODO error capture.
        // Check for invalid status code.
        // $this->expectError(facetoface_get_status(17));
        // $this->expectError(facetoface_get_status('b'));
        // $this->expectError(facetoface_get_status('%'));
    }

    // Test method - returns a string.
    // Test each method with the html parameter as true/false/null.
    public function test_format_cost() {

        // Test for a valid value.
        $this->assertEqual(format_cost(1000, true), '$1000');
        $this->assertEqual(format_cost(1000, false), '$1000');
        $this->assertEqual(format_cost(1000), '$1000');

        // Test for a large negative value, html true/false/null.
        $this->assertEqual(format_cost(-34000, true), '$-34000');
        $this->assertEqual(format_cost(-34000, false), '$-34000');
        $this->assertEqual(format_cost(-34000), '$-34000');

        // Test for a large positive value.
        $this->assertEqual(format_cost(100000000000, true), '$100000000000');
        $this->assertEqual(format_cost(100000000000, false), '$100000000000');
        $this->assertEqual(format_cost(100000000000), '$100000000000');

        // Test for a decimal value.
        $this->assertEqual(format_cost(32768.9045, true), '$32768.9045');
        $this->assertEqual(format_cost(32768.9045, false), '$32768.9045');
        $this->assertEqual(format_cost(32768.9045), '$32768.9045');

        // Test for a null value.
        $this->assertEqual(format_cost(null, true), '$');
        $this->assertEqual(format_cost(null, false), '$');
        $this->assertEqual(format_cost(null), '$');

        // Test for a text string value.
        $this->assertEqual(format_cost('string', true), '$string');
        $this->assertEqual(format_cost('string', false), '$string');
        $this->assertEqual(format_cost('string'), '$string');
    }

    // Test method - returns format_cost object.
    public function test_facetoface_cost() {

        // Test variables case WITH discount.
        $sessiondata1 = $this->sessiondata[0];

        $userid1 = 1;
        $sessionid1 = 1;

        $htmloutput1 = false; // Forced to true in the function.

        // Variable for test case NO discount.
        $sessiondata2 = $this->sessiondata[1];

        $userid2 = 2;
        $sessionid2 = 2;

        $htmloutput2 = false;

        // Test WITH discount.
        $this->assertEqual(facetoface_cost($userid1, $sessionid1, $sessiondata1, $htmloutput1), '$60');

        // Test NO discount case.
        $this->assertEqual(facetoface_cost($userid2, $sessionid2, $sessiondata2, $htmloutput2), '$90');
    }

    // Test method - returns a string.
    public function test_format_duration() {
        // ISSUES:
        // Expects a space after hour/s but not minute/s.
        // Minutes > 59 are not being converted to hour values.
        // Negative values are not interpreted correctly.

        // Test for positive single hour value.
        $this->assertEqual(format_duration('1:00'), '1 hour ');
        $this->assertEqual(format_duration('1.00'), '1 hour ');

        // Test for positive multiple hours value.
        $this->assertEqual(format_duration('3:00'), '3 hours ');
        $this->assertEqual(format_duration('3.00'), '3 hours ');

        // Test for positive single minute value.
        $this->assertEqual(format_duration('0:01'), '1 minute');
        $this->assertEqual(format_duration('0.1'), '6 minutes');

        // Test for positive minutes value.
        $this->assertEqual(format_duration('0:30'), '30 minutes');
        $this->assertEqual(format_duration('0.50'), '30 minutes');

        // Test for out of range minutes value.
        $this->assertEqual(format_duration('9:70'), '');

        // Test for zero value.
        $this->assertEqual(format_duration('0:00'), '');
        $this->assertEqual(format_duration('0.00'), '');

        // Test for negative hour value.
        $this->assertEqual(format_duration('-1:00'), '');
        $this->assertEqual(format_duration('-1.00'), '');

        // Test for negative multiple hours value.
        $this->assertEqual(format_duration('-7:00'), '');
        $this->assertEqual(format_duration('-7.00'), '');

        // Test for negative single minute value.
        $this->assertEqual(format_duration('-0:01'), '');
        $this->assertEqual(format_duration('-0.01'), '');

        // Test for negative multiple minutes value.
        $this->assertEqual(format_duration('-0:33'), '');
        $this->assertEqual(format_duration('-0.33'), '');

        // Test for negative hours & minutes value.
        $this->assertEqual(format_duration('-5:42'), '');
        $this->assertEqual(format_duration('-5.42'), '');

        // Test for invalid characters value.
        $this->assertEqual(format_duration('invalid_string'), '');
    }

    // Test method - returns a string.
    public function test_facetoface_minutes_to_hours() {

        // Test for positive minutes value.
        $this->assertEqual(facetoface_minutes_to_hours('11'), '0:11');

        // Test for positive hours & minutes value.
        $this->assertEqual(facetoface_minutes_to_hours('67'), '1:7');

        // Test for negative minutes value.
        $this->assertEqual(facetoface_minutes_to_hours('-42'), '-42');

        // Test for negative hours and minutes value.
        $this->assertEqual(facetoface_minutes_to_hours('-7:19'), '-7:19');

        // Test for invalid characters value.
        $this->assertEqual(facetoface_minutes_to_hours('invalid_string'), '0');
    }

    // Rest method - returns a float.
    public function test_facetoface_hours_to_minutes() {
        // TODO: Should negative values return 0 or a negative value?

        // Test for positive hours value.
        $this->assertEqual(facetoface_hours_to_minutes('10'), '600');

        // Test for positive minutes and hours value.
        $this->assertEqual(facetoface_hours_to_minutes('11:17'), '677');

        // Test for negative hours value.
        $this->assertEqual(facetoface_hours_to_minutes('-3'), '-180');

        // Test for negative hours & minutes value.
        $this->assertEqual(facetoface_hours_to_minutes('-2:1'), '-119');

        // Test for invalid characters value.
        $this->assertEqual(facetoface_hours_to_minutes('invalid_string'), '');
    }

    public function test_facetoface_fix_settings() {
        // Test for facetoface object.
        $facetoface1 = $this->facetoface[0];

        // Test for empty values.
        $this->assertEqual(facetoface_fix_settings($facetoface1), null);
    }

    // Test method - returns integer, being the new id in the facetoface table.
    public function test_facetoface_add_instance() {

        // Define test variables.
        $facetoface1 = $this->facetoface[0];
        $this->assertEqual(facetoface_add_instance($facetoface1), 5);
    }

    // Test method - returns boolean.
    public function test_facetoface_update_instance() {

        // Test variables.
        // Copy object from add_instance function test.
        $facetoface1 = $this->facetoface[0];
        $this->assertTrue(facetoface_update_instance($facetoface1));
    }

    // Test method - returns boolean.
    public function test_facetoface_delete_instance() {
        $id = 1;
        $this->assertTrue(facetoface_delete_instance($id));
    }

    // Test method -returns session object.
    public function test_cleanup_session_data() {

        // Define session object for test.
        // Valid values.
        $sessionvalid = new stdClass();
        $sessionvalid->duration = '1.5';
        $sessionvalid->capacity = '250';
        $sessionvalid->normalcost = '70';
        $sessionvalid->discountcost = '50';

        // Invalid values.
        $sessioninvalid = new stdClass();
        $sessioninvalid->duration = '0';
        $sessioninvalid->capacity = '100999';
        $sessioninvalid->normalcost = '-7';
        $sessioninvalid->discountcost = 'b';

        // Test for valid values.
        $this->assertEqual(cleanup_session_data($sessionvalid), $sessionvalid);

        // Test for invalid values.
        $this->assertEqual(cleanup_session_data($sessioninvalid), $sessioninvalid);
    }

    // Test method - returns false or session id number.
    public function test_facetoface_add_session() {
        $session1 = $this->session[0];

        // Test TODO fix me.
        // $this->assertEqual(facetoface_add_session($session1, $sessiondates1), 4);
    }

    // Test method - returns boolean.
    public function test_facetoface_update_session() {
        $session1 = $this->session[0];
        $sessiondates = new stdClass();
        $sessiondates->sessionid = 1;
        $sessiondates->timestart = 1300;
        $sessiondates->timefinish = 1400;
        $sessiondates->sessionid = 1;

        // TODO fix this.
        // $this->assertTrue(facetoface_update_session($session, $sessiondates), $this->msgtrue);
    }

    // Test method - returns false or int?
    public function test_facetoface_update_attendees() {
        $session1 = $this->session[0];
        $this->assertTrue(facetoface_update_attendees($session1), $this->msgtrue);
    }

    // Test method- returns array or empty string if no match found in table.
    public function test_facetoface_get_facetoface_menu() {
        // TODO negative test.

        $this->assertIsA(facetoface_get_facetoface_menu(), 'array');
    }

    // Test method - returns boolean.
    public function test_facetoface_delete_session() {
        // TODO invalid test.

        $session1 = $this->session[0];
        $this->assertTrue(facetoface_delete_session($session1));
    }

    // Test method - returns string.
    public function test_facetoface_email_substitutions() {

        // Define test variables.
        $msg = 'test message';
        $facetofacename = 'test f2f name';
        $reminderperiod = 'test reminder period';
        $user1 = $this->user[0];
        $session1 = $this->session[0];
        $sessionid = 101;

        $this->assertEqual(
            facetoface_email_substitutions($msg, $facetofacename, $reminderperiod, $user1, $session1, $sessionid),
            $msg
        );
        $this->assertTrue(
            facetoface_email_substitutions($msg, $facetofacename, $reminderperiod, $user1, $session1, $sessionid),
            $this->msgtrue
        );
    }

    // Test method - returns boolean.
    public function test_facetoface_cron() {
        $this->assertTrue(facetoface_cron(), $this->msgtrue);
    }

    // Test method - returns boolean.
    public function test_facetoface_has_session_started() {
        $session1 = $this->session[0];
        $session1->sessiondates[0]->timestart = time() - 100;
        $session1->sessiondates[0]->timefinish = time() + 100;
        $session2 = $this->session[1];
        $timenow = time();

        // Test for Valid case.
        $this->assertTrue(facetoface_has_session_started($session1, $timenow), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse(facetoface_has_session_started($session2, $timenow), $this->msgfalse);
    }

    // Test method - returns boolean.
    public function test_facetoface_is_session_in_progress() {

        // Define test variables.
        $session1 = $this->session[0];
        $session1->sessiondates[0]->timestart = time() - 100;
        $session1->sessiondates[0]->timefinish = time() + 100;
        $session2 = $this->session[1];
        $timenow = time();

        // Test for valid case.
        $this->assertTrue(facetoface_is_session_in_progress($session1, $timenow), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse(facetoface_is_session_in_progress($session2, $timenow), $this->msgfalse);
    }

    // Test method - returns array.
    public function test_facetoface_get_session_dates() {
        $sessionid1 = 1;
        $sessionid2 = 10;

        // Test for valid case.
        $this->assertTrue(facetoface_get_session_dates($sessionid1), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse(facetoface_get_session_dates($sessionid2), $this->msgfalse);
    }

    // Rest method - returns a session object.
    public function test_facetoface_get_session() {
        $sessionid1 = 1;
        $sessionid2 = 10;

        // Test for valid case.
        $this->assertTrue(facetoface_get_session($sessionid1), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse(facetoface_get_session($sessionid2), $this->msgfalse);
    }

    // Test method - returns session object.
    public function test_facetoface_get_sessions() {
        $facetofaceid1 = 1;
        $facetofaceid2 = 42;

        // Test for valid case.
        $this->assertTrue(facetoface_get_sessions($facetofaceid1), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse(facetoface_get_sessions($facetofaceid2), $this->msgfalse);
    }

    // Test method - returns user list array or false.
    public function test_facetoface_get_attendees() {
        $sessionid1 = 1;
        $sessionid2 = 42;

        // Test for valid sessionid.
        $this->assertTrue(count(facetoface_get_attendees($sessionid1)));

        // Test for invalid sessionid.
        $this->assertEqual(facetoface_get_attendees($sessionid2), array());

    }

    // Test method - returns boolean or object.
    public function test_facetoface_get_attendee() {
        $sessionid1 = 1;
        $sessionid2 = 42;
        $userid1 = 1;
        $userid2 = 14;

        // Test for valid case.
        $this->assertTrue(is_object(facetoface_get_attendee($sessionid1, $userid1)), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse(facetoface_get_attendee($sessionid2, $userid2), $this->msgfalse);
    }

    // Test method - returns userfields.
    public function test_facetoface_get_userfields() {
        $this->assertTrue(facetoface_get_userfields(), $this->msgtrue);
    }

    // Test method - returns worksheet object.
    public function test_facetoface_download_attendance() {

        // ODS format.
        $facetofacename1 = 'testf2fname1';
        $facetofaceid1 = 1;
        $location1 = 'testlocation1';
        $format1 = 'ods';

        // Excel format.
        $facetofacename2 = 'testf2fname2';
        $facetofacename2 = 2;
        $location2 = 'testlocation2';
        $format2 = 'xls';

        // Test for ODS format.
        // $this->assertTrue(facetoface_download_attendance($facetofacename1, $facetofaceid1, $location1, $format1), $this->msgtrue);
        // TODO this returns JUNK.
        // Test for Excel format.
        // $this->assertTrue(facetoface_download_attendance($facetofacename2, $facetofaceid2, $location2, $format2), $this->msgtrue);
    }

    // Test method - returns integer.
    public function test_facetoface_write_worksheet_header() {
        // TODO check on how to define worksheet object.
    }

    public function test_facetoface_write_activity_attendance() {
        // TODO check on how to define worksheet object.
    }

    // Test method - returns object.
    public function test_facetoface_get_user_custom_fields() {
        $userid1 = 1;
        $userid2 = 42;
        $fieldstoinclude1 = true;

        // Test for valid case.
        $this->assertTrue(facetoface_get_user_customfields($userid1, $fieldstoinclude1), $this->msgtrue);
        $this->assertTrue(facetoface_get_user_customfields($userid1), $this->msgtrue);
        // TODO invalid case.
        // Test for invalid case.
    }

    // Test method - returns boolean.
    public function test_facetoface_user_signup() {
        $session1 = $this->session[0];
        $facetoface1 = $this->facetoface[0];
        $course1 = $this->course[0];

        $discountcode1 = 'disc1';
        $notificationtype1 = 1;
        $statuscode1 = 1;
        $userid1 = 100;

        $notifyuser1 = true;
        $displayerrors = true;

        // Test for valid case.
        $this->assertTrue(
            facetoface_user_signup($session1, $facetoface1, $course1, $discountcode1, $notificationtype1, $statuscode1),
            $this->msgtrue
        );

        // TODO invalid case.
    }

    // Test method - returns string.
    public function test_facetoface_send_request_notice() {
        $session1 = $this->session[0];
        $facetoface1 = $this->facetoface[0];
        $userid1 = 1;
        $userid2 = 25;

        // Test for valid case.
        $this->assertEqual(facetoface_send_request_notice($facetoface1, $session1, $userid1), '');

        // Test for invalid case.
        $this->assertEqual(facetoface_send_request_notice($facetoface1, $session1, $userid2), 'No manager email is set');
    }

    // Test method - returns int or false.
    public function test_facetoface_update_signup_status() {
        $signupid1 = 1;
        $statuscode1 = 1;
        $createdby1 = 1;
        $note1 = 'note1';
        $grade1 = 85;

        $signupid2 = 42;
        $statuscode2 = 7;
        $createdby2 = 40;
        $note2 = '';
        $grade1 = 0;

        // Test for valid case.
        $this->assertEqual(facetoface_update_signup_status($signupid1, $statuscode1, $createdby1, $note1), 5);

        // Test for invalid case.
        // TODO invlaid case - how to cause sql error from here?
        // $this->assertFalse(facetoface_update_signup_status($signupid2, $statuscode2, $createdby2, $note2), $this->msgfalse);
    }

    // Test method - returns boolean.
    public function test_facetoface_user_cancel() {
        $session1 = $this->session[0];
        $userid1 = 1;
        $forcecancel1 = true;
        $errorstr1 = 'error1';
        $cancelreason1 = 'cancelreason1';
        $session2 = $this->session[1];
        $userid2 = 42;

        // Test for valid case.
        // $this->assertTrue(facetoface_user_cancel($session1, $userid1, $forcecancel1, $errorstr1, $cancelreason1), $this->msgtrue);

        // Test for invalid case.
        // TODO invalid case?
        // $this->assertFalse(facetoface_user_cancel($session2, $userid2), $this->msgfalse);
    }

    // Test method - returns string.
    public function test_facetoface_send_notice() {
        $facetoface1 = $this->facetoface[0];
        $session1 = $this->session[0];
        // TODO where is sessiondata coming from in here? check table references.

        $postsubject1 = 'postsubject1';
        $posttext1 = 'posttext1';
        $posttextmgrheading1 = 'posttextmgrheading1';
        $notificationtype1 = 'notificationtype1';
        $userid1 = 1;

        // Test for valid case.
        // $this->assertEqual(facetoface_send_notice($postsubject1, $posttext1, $posttextmgrheading1, $notificationtype1, $facetoface1, $session1, $userid1), '');
    }

    public function test_facetoface_send_confirmation_notice() {
        $facetoface1 = $this->facetoface[0];
        $session1 = $this->session[0];
        // TODO where is sessiondata coming from in here? check table references.

        $postsubject1 = 'postsubject1';
        $posttext1 = 'posttext1';
        $posttextmgrheading1 = 'posttextmgrheading1';
        $notificationtype1 = 'notificationtype1';
        $userid1 = 1;

        // TODO test for valid case.
    }

    // Test method - returns string.
    public function test_facetoface_send_cancellation_notice() {
        $facetoface1 = $this->facetoface[0];
        $session1 = $this->session[0];
        $userid1 = 1;

        // Test for valid case.
        // $this->assertEqual(facetoface_send_cancellation_notice($facetoface1, $session1, $userid1), '');
    }

    // Test method - returns string.
    public function test_facetoface_get_manageremail() {

        // Find manager of user 1 (which is user2).
        $this->assertEqual(facetoface_get_manageremail(1), 'user2@example.com');

        // Find manager of non existant user.
        $this->assertEqual(facetoface_get_manageremail(25), '');
    }

    // Test method - returns string.
    public function test_facetoface_get_manageremailformat() {
        // TODO how to run negative test?

        // Test for no address format.
        $this->assertEqual(facetoface_get_manageremailformat(), '');
    }

    // Test method - returns boolean.
    public function test_facetoface_check_manageremail() {
        global $CFG;

        set_config('facetoface_manageraddressformat', 'example.com');

        // Define test variables.
        $validemail = 'user@example.com';
        $invalidemail = null;

        // Test for valid case.
        $this->assertTrue(facetoface_check_manageremail($validemail), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse(facetoface_check_manageremail($invalidemail), $this->msgfalse);
    }

    // Test method - returns boolean.
    public function test_facetoface_take_attendance() {
        $data1 = new stdClass();
        $data1->s = 1;
        $data1->submissionid = 1;

        // Test for valid case.
        $this->assertTrue(facetoface_take_attendance($data1), $this->msgtrue);

        // TODO test for invalid case.
    }

    // Test method - returns boolean.
    public function test_facetoface_approve_requests() {
        $data1 = new stdClass();
        $data1->s = 1;
        $data1->submissionid = 1;
        $data1->requests = array();
        $data1->requests[0]->request = 1;

        // Test for valid case.
        $this->assertTrue(facetoface_approve_requests($data1), $this->msgtrue);

        // TODO test for invalid case.
    }

    // Test method - returns object.
    public function test_facetoface_take_individual_attendance() {
        // Test variables.
        // $submissionid1 = 1;
        // $grading1 = 100;
        // TODO bug check function.
        // Test for valid case.
        // $this->assertTrue(facetoface_take_individual_attendance($submissionid1, $grading1), $this->msgtrue);
    }

    // Test method - returns html $table string.
    public function test_facetoface_print_coursemodule_info() {
        // TODO bug check the function.
        $coursemodule1 = new stdClass();
        $coursemodule1->id = 1;
        $coursemodule1->course = 1;
        $coursemodule1->instance = 1;

        // Test for valid case.
        // $this->assertTrue(facetoface_print_coursemodule_info($coursemodule1), $this->msgtrue);
    }

    public function test_facetoface_get_ical_attachment() {
        // TODO ical format definintion.
    }

    // Test method - returns datetimestamp.
    public function test_facetoface_ical_generate_timestamp() {
        $timenow = time();
        $return = gmdate('Ymd', $timenow) . 'T' . gmdate('His', $timenow) . 'Z';

        // TODO check if this is the correct return value to compare.
        // Test for valid case.
        $this->assertEqual(facetoface_ical_generate_timestamp($timenow), $return);
    }

    // Test method - returns string variable $text.
    public function test_facetoface_ical_escape() {
        // TODO correct this function for ICAL format.

        /*
         * NEEDS REVIEW: see T-7566
         *
         * // Define test variables.
         * $text1 = "this is a test!&nbsp";
         * $text2 = null;
         * $text3 = "more than 75 characters1 more than 75 characters2 more than 75 characters3 more than 75 characters4 more than 75 characters5";
         * $text4 = addslashes("/'s ; \" ' \n , . & &nbsp;");
         *
         * $converthtml1 = false;
         * $converthtml2 = true;
         *
         * // Tests.
         * $this->assertEqual(facetoface_ical_escape($text1, $converthtml1), $text1);
         * $this->assertEqual(facetoface_ical_escape($text1, $converthtml2), $text1);
         *
         * $this->assertEqual(facetoface_ical_escape($text2, $converthtml1), $text2);
         * $this->assertEqual(facetoface_ical_escape($text2, $converthtml2), $text2);
         *
         * $this->assertEqual(facetoface_ical_escape($text3, $converthtml1),
         *        'more than 75 characters more than 75 characters more than 75 characters more than 75 characters more than 75 characters');
         * $this->assertEqual(facetoface_ical_escape($text3, $converthtml2),
         *        'more than 75 characters more than 75 characters more than 75 characters\nmore than 75 characters more than 75 characters');
         *
         * $this->assertEqual(facetoface_ical_escape($text4, $converthtml1), "/\\\\'s \; \" \\\\' \\n \, . & &nbsp\;");
         * $this->assertEqual(facetoface_ical_escape($text4, $converthtml2), "/'s \; \" ' \, . & ");
         */
    }

    public function test_facetoface_update_grades() {
        $facetoface1 = $this->facetoface[0];
        $userid = 0;
        $this->assertTrue(facetoface_update_grades($facetoface1, $userid), $this->msgtrue);
    }

    // Test method - returns boolean.
    public function test_facetoface_grade_item_update() {
        $facetoface1 = $this->facetoface[0];
        $grades = null;
        $this->assertTrue(facetoface_grade_item_update($facetoface1), $this->msgtrue);
    }

    // Test method - returns int code boolean.
    public function test_facetoface_grade_item_delete() {
        $facetoface1 = $this->facetoface[0];
        $this->assertTrue(facetoface_grade_item_delete($facetoface1), $this->msgtrue);
    }

    // Test method - returns integer.
    public function test_facetoface_get_num_attendees() {
        $sessionid1 = 2;
        $sessionid2 = 42;

        // Test for valid case.
        $this->assertEqual(facetoface_get_num_attendees($sessionid1), 3);

        // Test for invalid case.
        $this->assertEqual(facetoface_get_num_attendees($sessionid2), 0);
    }

    // Test method - returns array or false.
    public function test_facetoface_get_user_submissions() {
        $facetofaceid1 = 1;
        $userid1 = 1;
        $includecancellations1 = true;

        $facetofaceid2 = 11;
        $userid2 = 11;
        $includecancellations2 = true;

        // Test for valid case.
        $this->assertTrue(facetoface_get_user_submissions($facetofaceid1, $userid1, $includecancellations1), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse(facetoface_get_user_submissions($facetofaceid2, $userid2, $includecancellations2), $this->msgfalse);
    }

    // Test method - returns boolean.
    public function test_facetoface_user_cancel_submission() {
        $sessionid1 = 1;
        $userid1 = 1;
        $cancelreason1 = 'cancel1';

        $sessionid2 = 2;
        $userid2 = 2;
        $cancelreason2 = 'cancel2';

        // TODO fix - table relation error.
        // Test for valid case.
        // $this->assertTrue(facetoface_user_cancel_submission($sessionid1, $userid1, $cancelreason1), $this->msgtrue);

        // Test for invalid case.
        // $this->assertFalse(facetoface_user_cancel_submission($sessionid2, $userid2, $cancelreason2), $this->msgfalse);
    }

    // Test method - returns an array.
    public function test_facetoface_get_view_actions() {
        $testarray = array('view', 'view all');
        $this->assertEqual(facetoface_get_view_actions(), $testarray);
    }

    // Test method - returns an array.
    public function test_facetoface_get_post_actions() {
        $testarray = array('cancel booking', 'signup');
        $this->assertEqual(facetoface_get_post_actions(), $testarray);
    }

    // Test method - returns boolean.
    public function test_facetoface_session_has_capacity() {
        $session1 = $this->session[0];
        $session2 = $this->session[1];

        // Test for valid case.
        $this->assertFalse(facetoface_session_has_capacity($session1), $this->msgfalse);

        // Test for invalid case.
        $this->assertFalse(facetoface_session_has_capacity($session2), $this->msgfalse);
    }

    // Test method - returns array.
    public function test_facetoface_get_trainer_roles() {

        // No session roles.
        $this->assertFalse(facetoface_get_trainer_roles(), $this->msgfalse);

        // Add some roles.
        set_config('facetoface_session_roles', '2');

        $result = facetoface_get_trainer_roles();
        $this->assertEqual($result[2]->name, 'Trainer');
    }

    // Test method - returns array.
    public function test_facetoface_get_trainers() {
        $sessionid1 = 1;
        $roleid1 = 1;

        // Test for valid case.
        $this->assertTrue(facetoface_get_trainers($sessionid1, $roleid1), $this->msgtrue);
        $this->assertTrue(facetoface_get_trainers($sessionid1), $this->msgtrue);
    }

    // Test method - returns boolean.
    public function test_facetoface_manager_needed() {
        $facetoface1 = $this->facetoface[1];
        $facetoface2 = $this->facetoface[0];

        // Test for valid case.
        $this->assertTrue(facetoface_manager_needed($facetoface1), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse(facetoface_manager_needed($facetoface2), $this->msgfalse);
    }

    // Test method - returns string.
    public function test_facetoface_list_of_sitenotices() {
        $this->assertTrue(facetoface_list_of_sitenotices(), $this->msgtrue);
    }
}
