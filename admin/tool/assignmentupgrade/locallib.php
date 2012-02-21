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
 * Question engine upgrade helper library code.
 *
 * @package    tool
 * @subpackage assignmentupgrade
 * @copyright  2012 NetSpot
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Get the URL of a script within this plugin.
 * @param string $script the script name, without .php. E.g. 'index'.
 * @param array $params URL parameters (optional).
 */
function tool_assignmentupgrade_url($script, $params = array()) {
    return new moodle_url('/admin/tool/assignmentupgrade/' . $script . '.php', $params);
}


/**
 * Class to encapsulate one of the functionalities that this plugin offers.
 *
 * @copyright  2012 NetSpot
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_assignmentupgrade_action {
    /** @var string the name of this action. */
    public $name;
    /** @var moodle_url the URL to launch this action. */
    public $url;
    /** @var string a description of this aciton. */
    public $description;

    /**
     * Constructor to set the fields.
     */
    protected function __construct($name, moodle_url $url, $description) {
        $this->name = $name;
        $this->url = $url;
        $this->description = $description;
    }

    /**
     * Make an action with standard values.
     * @param string $shortname internal name of the action. Used to get strings
     * and build a URL.
     * @param array $params any URL params required.
     */
    public static function make($shortname, $params = array()) {
        return new self(
                get_string($shortname, 'tool_assignmentupgrade'),
                tool_assignmentupgrade_url($shortname, $params),
                get_string($shortname . '_desc', 'tool_assignmentupgrade'));
    }
}


/**
 * A class to represent a list of assignments with various information about
 * plugins that can be displayed as a table.
 *
 * @copyright  2012 NetSpot
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_assignmentupgrade_assignment_list {
    public $title;
    public $intro;
    public $sql;
    public $assignmentlist = null;
    public $totalassignments = 0;
    public $totalupgradable = 0;
    public $totalsubmissions = 0;

    function __construct() {
        global $DB;
        $this->title = get_string('notupgradedtitle', 'tool_assignmentupgrade');
        $this->intro = get_string('notupgradedintro', 'tool_assignmentupgrade');
        $this->build_sql();
        $this->assignmentlist = $DB->get_records_sql($this->sql);

    }

    /**
     * Check against the list of supported types for upgrade
     * to see if there is any assign plugin that can upgrade this 
     * assignment type
     */
    protected function is_upgradable($type) {
        $version = get_config('assignment_' . $type, 'version');
            
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        $assignment = new assignment();
        return $assignment->can_upgrade($type, $version);
    }

    protected function build_sql() {
        $this->sql = '
            SELECT                                                                                                                        assignment.id,                                                                                                      assignment.name,                                                                                                    assignment.assignmenttype,                                                                                          c.shortname,                                                                                                        c.id AS courseid,COUNT(submission.id) as submissioncount                                                        FROM {assignment} assignment                                                                                      JOIN {course} c ON c.id = assignment.course                                                                       LEFT JOIN {assignment_submissions} submission ON assignment.id = submission.assignment 
              GROUP BY assignment.id, assignment.name, assignment.assignmenttype, c.shortname, c.id 
              ORDER BY c.shortname, assignment.name, assignment.id';
    }

    public function get_col_headings() {
        return array(
            get_string('assignmentid', 'tool_assignmentupgrade'),
            get_string('course'),
            get_string('name'),
            get_string('assignmenttype', 'tool_assignmentupgrade'),
            get_string('submissions', 'tool_assignmentupgrade'),
            get_string('upgradable', 'tool_assignmentupgrade'),
        );
    }

    public function get_row($assignmentinfo) {
        $this->totalassignments += 1;
        $upgradable = $this->is_upgradable($assignmentinfo->assignmenttype);
        if ($upgradable) {
            $this->totalupgradable += 1;
        }
        $this->totalsubmissions += $assignmentinfo->submissioncount;
        return array(
            $assignmentinfo->id,
            html_writer::link(new moodle_url('/course/view.php',
                    array('id' => $assignmentinfo->courseid)), format_string($assignmentinfo->shortname)),
            html_writer::link(new moodle_url('/mod/assignment/view.php',
                    array('a' => $assignmentinfo->id)), format_string($assignmentinfo->name)),
            $assignmentinfo->assignmenttype,
            $assignmentinfo->submissioncount,
            $upgradable ? 
            html_writer::link(new moodle_url('/admin/tool/assignmentupgrade/upgradesingleconfirm.php',
                    array('id' => $assignmentinfo->id)), get_string('supported', 'tool_assignmentupgrade'))
            : get_string('notsupported', 'tool_assignmentupgrade'));
    }

    public function get_row_class($assignmentinfo) {
        return null;
    }

    public function get_total_row() {
        return array(
            '',
            html_writer::tag('b', get_string('total')),
            '',
            html_writer::tag('b', $this->totalassignments),
            html_writer::tag('b', $this->totalsubmissions),
            html_writer::tag('b', $this->totalupgradable),
        );
    }

    public function is_empty() {
        return empty($this->assignmentlist);
    }
}

/**
 * Convert a single assignment from the old format to the new one.
 * @param integer $assignmentid the assignment id.
 * @param string log This gets appended to with the details of the conversion process
 * @return boolean This is the overall result (true/false)
 */
function tool_assignmentupgrade_upgrade_assignment($assignmentinfo, & $log) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    require_once($CFG->dirroot . '/mod/assign/upgradelib.php');
    $assignment_upgrader = new assignment_upgrade_manager();
    return $assignment_upgrader->upgrade_assignment($assignmentinfo->id, $log);
}

/**
 * Get the information about a assignment to be upgraded.
 * @param integer $assignmentid the assignment id.
 * @return object the information about that assignment, as for
 *      {@link tool_assignmentupgrade_get_upgradable_assignments()}.
 */
function tool_assignmentupgrade_get_assignment($assignmentid) {
    global $DB;
    return $DB->get_record_sql("
            SELECT
                assignment.id,
                assignment.name,
                c.shortname,
                c.id AS courseid

            FROM {assignment} assignment
            JOIN {course} c ON c.id = assignment.course

            WHERE assignment.id = ?
            ", array($assignmentid));
}

