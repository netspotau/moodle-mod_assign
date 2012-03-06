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
 * This file contains the definition for the grading table which subclassses easy_table
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/mod/assign/locallib.php');

/*
 * Extends table_sql to provide a table of assignment submissions
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grading_table extends table_sql implements renderable {
    private $assignment = null;
    private $perpage = 10;
    private $rownum = -1;
    private $output = null;
    private $grading_info = null;

    /**
     * overridden constructor keeps a reference to the assignment class that is displaying this table
     * 
     * @param assignment $assignment The assignment class
     */
    function __construct($assignment, $perpage=10, $filter='') {
        global $CFG, $PAGE;
        parent::__construct('mod_assign_grading');
        $this->assignment = $assignment;
        $this->perpage = $perpage;
        $this->output = $PAGE->get_renderer('mod_assign');

        $this->define_baseurl(new moodle_url($CFG->wwwroot . '/mod/assign/view.php', array('action'=>'grading', 'id'=>$assignment->get_course_module()->id)));

        // do some business - then set the sql

        $currentgroup = groups_get_activity_group($assignment->get_course_module(), true);

        $users = array_keys( $assignment->list_enrolled_users_with_capability('mod/assign:submit',$currentgroup));
        
        $fields = user_picture::fields('u') . ', u.id as userid, u.firstname as firstname, u.lastname as lastname, ';
        $fields .= 's.status as status, s.id as submissionid, s.timecreated as firstsubmission, s.timemodified as timesubmitted, ';
        $fields .= 'g.id as gradeid, g.grade as grade, g.timemodified as timemarked, g.timecreated as firstmarked, g.mailed as mailed';
        $from = '{user} u LEFT JOIN {assign_submission} s ON u.id = s.userid AND s.assignment = ' . $this->assignment->get_instance()->id . 
                        ' LEFT JOIN {assign_grades} g ON u.id = g.userid AND g.assignment = ' . $this->assignment->get_instance()->id;
        $where = 'u.id IN (' . implode(',', $users) . ')';
        if ($filter == ASSIGN_FILTER_SUBMITTED) {
            $where .= ' AND s.timecreated > 0 ';
        }
        if ($filter == ASSIGN_FILTER_REQUIRE_GRADING) {
            $where .= ' AND s.timemodified > g.timemodified ';
        }
        $params = array($assignment->get_instance()->id, $assignment->get_instance()->id);
        $this->set_sql($fields, $from, $where, array());

        $columns = array();
        $headers = array();
    
        // User picture
        $columns[] = 'picture';
        $headers[] = '';
        
        // Fullname
        $columns[] = 'fullname';
        $headers[] = get_string('fullname');

        // Submission status
        $columns[] = 'status';
        $headers[] = get_string('status');

        // Edit links
        if (!$this->is_downloading()) {
            $columns[] = 'edit';
            $headers[] = get_string('edit');
        }

        // Grade 
        $columns[] = 'grade';
        $headers[] = get_string('grade');

        // Submission plugins
        if ($assignment->is_any_submission_plugin_enabled()) {
            $columns[] = 'timesubmitted';
            $headers[] = get_string('lastmodifiedsubmission', 'assign');

            foreach ($this->assignment->get_submission_plugins() as $plugin) {
                if ($plugin->is_visible() && $plugin->is_enabled()) {
                    $columns[] = 'submission_' . $plugin->get_type();
                    $headers[] = $plugin->get_name();
                }
            }
        }

        // time marked
        $columns[] = 'timemarked';
        $headers[] = get_string('lastmodifiedgrade', 'assign');

        // Feedback plugins
        foreach ($this->assignment->get_feedback_plugins() as $plugin) {
            if ($plugin->is_visible() && $plugin->is_enabled()) {
                $columns[] = 'feedback_' . $plugin->get_type();
                $headers[] = $plugin->get_name();
            }
        }

        // final grade
        $columns[] = 'finalgrade';
        $headers[] = get_string('finalgrade', 'grades');



        // set the columns
        $this->define_columns($columns);
        $this->define_headers($headers);

        // load the grading info for all users
        $this->grading_info = grade_get_grades($this->assignment->get_course()->id, 'mod', 'assign', $this->assignment->get_instance()->id, $users);
               
    }

    /**
     * Return the number of rows to display on a single page
     * 
     * @return int The number of rows per page
     */
    function get_rows_per_page() {
        return $this->perpage;
    }

    /**
     * Display a grade with scales etc.
     * 
     * @param string $grade
     * @return string The formatted grade
     */
    function display_grade($grade) {
        if ($this->is_downloading()) {
            return $grade;
        }
        return $this->assignment->display_grade($grade);
    }
    
    /**
     * Format a user picture for display (and update rownum as a sideeffect)
     * 
     * @param object $row
     */
    function col_picture($row) {
        global $PAGE;
        if ($this->rownum < 0) {
            $this->rownum = $this->currpage * $this->pagesize;
        } else {
            $this->rownum += 1;
        }
        if ($row->picture) {
            return $this->output->user_picture($row);
        }
        return '';
    }

    /**
     * Return a users grades from the listing of all grade data for this assignment
     * 
     * @param int $userid
     * @return mixed object or false
     */
    private function get_gradebook_data_for_user($userid) {
        if (isset($this->grading_info->items[0]) && $this->grading_info->items[0]->grades[$userid]) {
            return $this->grading_info->items[0]->grades[$userid];
        }
        return false;
    }
    
    /**
     * Format a column of data for display
     * 
     * @param object $row
     */
    function col_grade($row) {
        $o = '-';

        if ($row->grade) {
            $o = $this->display_grade($row->grade);
        }

        return $o;
    }
    
    /**
     * Format a column of data for display
     * 
     * @param object $row
     */
    function col_finalgrade($row) {
        $o = '';

        $grade = $this->get_gradebook_data_for_user($row->userid);
        if ($grade) {
            $o = $this->display_grade($grade->grade);
        }

        return $o;
    }
    
    /**
     * Format a column of data for display
     * 
     * @param object $row
     */
    function col_timemarked($row) {
        $o = '-';

        if ($row->timemarked) {
            $o = userdate($row->timemarked);
        }

        return $o;
    }
    
    /**
     * Format a column of data for display
     * 
     * @param object $row
     */
    function col_timesubmitted($row) {
        $o = '-';

        if ($row->timesubmitted) {
            $o = userdate($row->timesubmitted);
        }

        return $o;
    }

    /**
     * Format a column of data for display
     * 
     * @param object $row
     */
    function col_status($row) {
        $o = '';

        $o .= $this->output->action_link(new moodle_url('/mod/assign/view.php', 
                                                        array('id' => $this->assignment->get_course_module()->id, 
                                                              'rownum'=>$this->rownum,
                                                              'action'=>'grade')), 
                                         get_string('submissionstatus_' . $row->status, 'assign'));

        return $o;
    }

    /**
     * Format a column of data for display
     * 
     * @param object $row
     */
    function col_edit($row) {
        $edit = '';

        $edit .= $this->output->action_link(new moodle_url('/mod/assign/view.php', 
                                            array('id' => $this->assignment->get_course_module()->id, 
                                                  'rownum'=>$this->rownum,'action'=>'grade')),
                                            $this->output->pix_icon('t/grades', get_string('grade') ));

        $grade = $this->get_gradebook_data_for_user($row->id);
        if (!$row->status || $row->status == ASSIGN_SUBMISSION_STATUS_DRAFT || !$this->assignment->get_instance()->submissiondrafts) {
            if (!$grade || !$grade->locked) {
                $edit .= $this->output->action_link(new moodle_url('/mod/assign/view.php', 
                                                                   array('id' => $this->assignment->get_course_module()->id, 
                                                                         'userid'=>$row->id, 
                                                                         'action'=>'lock')), 
                                                                   $this->output->pix_icon('t/lock', get_string('preventsubmissions', 'assign')));

            } else {
                $edit .= $this->output->action_link(new moodle_url('/mod/assign/view.php', 
                                                                   array('id' => $this->assignment->get_course_module()->id, 
                                                                         'userid'=>$row->id, 
                                                                         'action'=>'unlock')), 
                                                                   $this->output->pix_icon('t/unlock', get_string('allowsubmissions', 'assign')));
            }
        }
        if ($row->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED && $this->assignment->get_instance()->submissiondrafts) {
            $edit .= $this->output->action_link(new moodle_url('/mod/assign/view.php', 
                                                               array('id' => $this->assignment->get_course_module()->id, 
                                                                     'userid'=>$row->id, 
                                                                     'action'=>'reverttodraft')), 
                                                               $this->output->pix_icon('t/left', get_string('reverttodraft', 'assign')));
        }

        return $edit;
    }

    /**
     * Write the plugin summary with an optional link to view the full feedback/submission.
     *
     * @param object $plugin Submission plugin or feedback plugin
     * @param object $item Submission or grade
     * @param string $returnaction The return action to pass to the view_submission page (the current page)
     * @param string $returnparams The return params to pass to the view_submission page (the current page)
     * @return string The summary with an optional link
     */
    private function format_plugin_summary_with_link($plugin, $item, $returnaction='view', $returnparams=array()) {
        $link = '';

        if ($plugin->show_view_link($item)) {
            $icon = $this->output->pix_icon('t/preview', get_string('view' . $plugin->get_subtype(), 'mod_assign'));
            $link = $this->output->action_link(
                                new moodle_url('/mod/assign/view.php',
                                               array('id' => $this->assignment->get_course_module()->id,
                                                     'sid'=>$item->id,
                                                     'gid'=>$item->id,
                                                     'plugin'=>$plugin->get_type(),
                                                     'action'=>'viewplugin' . $plugin->get_subtype(),
                                                     'returnaction'=>$returnaction,
                                                     'returnparams'=>http_build_query($returnparams))),
                                $icon);
            $link .= $this->output->spacer(array('width'=>15));
        }

        return $link . $plugin->view_summary($item);
    }


    /**
     * Format the submission and feedback columns
     *
     * @param string $colname The column name
     * @param object $row The submission row
     * @return mixed string or NULL
     */
    function other_cols($colname, $row){
        if (($pos = strpos($colname, 'submission_')) !== false) {
            $plugin = $this->assignment->get_submission_plugin_by_type(substr($colname, strlen('submission_')));
            if ($plugin->is_visible() && $plugin->is_enabled()) {
                if ($row->submissionid) {
                    $submission = new stdClass();
                    $submission->id = $row->submissionid;
                    $submission->timecreated = $row->firstsubmission;
                    $submission->timemodified = $row->timesubmitted;
                    $submission->assignment = $this->assignment->get_instance()->id;
                    $submission->userid = $row->userid;
                    
                    return $this->format_plugin_summary_with_link($plugin, $submission, 'grading');
                }
            }
            return '';            
        }
        if (($pos = strpos($colname, 'feedback_')) !== false) {
            $plugin = $this->assignment->get_feedback_plugin_by_type(substr($colname, strlen('feedback_')));
            if ($plugin->is_visible() && $plugin->is_enabled()) {
                if ($row->gradeid) {
                    $grade = new stdClass();
                    $grade->id = $row->gradeid;
                    $grade->timecreated = $row->firstmarked;
                    $grade->timemodified = $row->timemarked;
                    $grade->assignment = $this->assignment->get_instance()->id;
                    $grade->userid = $row->userid;
                    $grade->grade = $row->grade;
                    $grade->mailed = $row->mailed;
                    
                    return $this->format_plugin_summary_with_link($plugin, $grade, 'grading');
                }
            }
            return '';            
        }
        return NULL;
    }

    /**
     * Using the current filtering and sorting - load a single row and return a single column from it
     *
     * @param int $rownum The rownumber to load
     * @param string $colname The name of the raw column data
     * @return mixed column data or false
     */
    function get_cell_data($rownumber, $columnname) {
        $this->setup();
        $this->currpage = $rownumber;
        $this->query_db(1);
        foreach ($this->rawdata as $row) {
            return $row->$columnname;
        }
        return false;
    }
    
    /**
     * Return the current assignemnt to the renderer
     *
     * @return object assignment
     */
    function get_assignment() {
        return $this->assignment;
    }
    

}
