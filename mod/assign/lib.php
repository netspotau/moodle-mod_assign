<?PHP

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

define('ASSIGN_SUBMISSION_STATUS_DRAFT', 'draft'); // student thinks it is a draft
define('ASSIGN_SUBMISSION_STATUS_SUBMITTED', 'submitted'); // student thinks it is finished

define('ASSIGN_FILTER_SUBMITTED', 'submitted');
define('ASSIGN_FILTER_REQUIRE_GRADING', 'require_grading');

require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir . '/plagiarismlib.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once('mod_form.php');

class assign_base {
   
    // list of configuration options for the assignment base type

    // list of all settings
    protected $data;

    // context cache
    protected $context;
    // cached current course and module
    protected $course;
    protected $coursemodule;
    var $filearea = 'submission';
    var $editoroptions = array();
    
    
    
    function hide_config_setting_hook($name) {
        return false;
    }

    /**
     * Constructor for the base assign class
     *
     */
    function assign_base(& $context, & $data = null, & $coursemodule = null, & $course = null) {
        if (!$context) {
            print_error('invalidcontext');
            die();
        }

        $this->context = & $context;
        $this->data = & $data;
        $this->coursemodule = & $coursemodule; 
        $this->course = & $course; 
    }

    private function get_course_context() {
        if ($this->context->contextlevel == CONTEXT_COURSE) {
            return $this->context;
        } else if ($this->context->contextlevel == CONTEXT_MODULE) {
            return $this->context->get_parent_context();
        } 
    }

    private function get_course_module() {
        if ($this->coursemodule) {
            return $this->coursemodule;
        }

        if ($this->context->contextlevel == CONTEXT_MODULE) {
            $this->coursemodule = get_coursemodule_from_id('assign', $this->context->instanceid);
            return $this->coursemodule;
        }
        return null;
    }

    private function get_course() {
        global $DB;
        if ($this->course) {
            return $this->course;
        }

        $this->course = $DB->get_record('course', array('id' => get_courseid_from_context($this->context)));
        return $this->course;
    }
    
    function view_header($subpage='') {
        global $CFG, $PAGE, $OUTPUT, $COURSE;

        if ($subpage) {
            $PAGE->navbar->add($subpage);
        }

        $PAGE->set_title(get_string('pluginname', 'assign'));
        $PAGE->set_heading($this->data->name);

        echo $OUTPUT->header();
        echo $OUTPUT->heading($this->data->name);

        groups_print_activity_menu($this->get_course_module(), $CFG->wwwroot . '/mod/assign/view.php?id=' . $this->get_course_module()->id);
        
    }

    /**
     * Display the assignment intro
     *
     * This will most likely be extended by assignment type plug-ins
     * The default implementation prints the assignment description in a box
     */
    function view_intro() {
        global $OUTPUT;
        if ($this->data->alwaysshowdescription || time() > $this->data->allowsubmissionsfromdate) {
            echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
            echo format_module_intro('assign', $this->data, $this->get_course_module()->id);
            echo $OUTPUT->box_end();
        }
        plagiarism_print_disclosure($this->get_course_module()->id);
    }
    
    function view_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }

    function & list_enrolled_users_with_capability($permission) {
        $users = & get_enrolled_users($this->context, $permission, 0, 'u.id');
        return $users;
    }

    function count_enrolled_users_with_capability($permission) {
        // the 0 is for groupid - which we will have to support
        $users = & get_enrolled_users($this->context, $permission, 0, 'u.id');
        return count($users);
    }

    function count_submissions_with_status($status) {
        global $DB;
        return $DB->count_records_sql("SELECT COUNT('x')
                                     FROM {assign_submissions}
                                    WHERE assignment = ? AND
                                          status = ?", array($this->get_course_module()->instance, $status));
    }
    
    function view_grading_summary() {
        global $OUTPUT;
        echo $OUTPUT->container_start('gradingsummary');
        echo $OUTPUT->heading(get_string('gradingsummary', 'assign'), 3);
        echo $OUTPUT->box_start('boxaligncenter', 'intro');
        $t = new html_table();

        // status
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('numberofparticipants', 'assign'));
        $cell2 = new html_table_cell($this->count_enrolled_users_with_capability('mod/assign:submit'));
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        // drafts
        if ($this->data->submissiondrafts) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('numberofdraftsubmissions', 'assign'));
            $cell2 = new html_table_cell($this->count_submissions_with_status(ASSIGN_SUBMISSION_STATUS_DRAFT));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('numberofsubmittedassignments', 'assign'));
        $cell2 = new html_table_cell($this->count_submissions_with_status(ASSIGN_SUBMISSION_STATUS_SUBMITTED));
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        $time = time();
        if ($this->data->duedate) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('duedate', 'assign'));
            $cell2 = new html_table_cell(userdate($this->data->duedate));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timeremaining', 'assign'));
            if ($this->data->duedate - $time <= 0) {
                $cell2 = new html_table_cell(get_string('assignmentisdue', 'assign'));
            } else {
                $cell2 = new html_table_cell(format_time($this->data->duedate - $time));
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        
        }

        echo html_writer::table($t);
        echo $OUTPUT->box_end();
        
        echo $OUTPUT->single_button(new moodle_url('/mod/assign/grading.php',
            array('id' => $this->get_course_module()->id)), get_string('viewgrading', 'assign'), 'get');


        echo $OUTPUT->container_end();
        echo $OUTPUT->spacer(array('height'=>30));
    }

    function & load_submissions_table($perpage=10,$filter=null) {
        global $CFG, $DB, $OUTPUT, $PAGE;

        $tablecolumns = array('picture', 'fullname', 'status', 'edit', 'submissioncomment', 'feedback', 'grade', 'timemodified', 'timemarked', 'finalgrade');

        $tableheaders = array('',
                              get_string('fullnameuser'),
                              get_string('status'),
                              get_string('edit'),
                              get_string('submissioncomment', 'assign'),
                              get_string('feedback', 'assign'),
                              get_string('grade'),
                              get_string('lastmodified').' ('.get_string('submission', 'assign').')',
                              get_string('lastmodified').' ('.get_string('grade').')',
                              get_string('finalgrade', 'grades'));

        // more efficient to load this here
        require_once($CFG->libdir.'/tablelib.php');
        $table = new flexible_table('mod-assign-submissions');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot.'/mod/assign/grading.php?id='.$this->get_course_module()->id);

        $table->sortable(true, 'lastname');//sorted by lastname by default
        $table->collapsible(true);
        $table->initialbars(true);

        $table->column_suppress('picture');
        $table->column_suppress('fullname');

        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        $table->column_class('status', 'status');
        $table->column_class('edit', 'edit');
        $table->column_class('grade', 'grade');
        $table->column_class('feedback', 'feedback');
        $table->column_class('submissioncomment', 'comment');
        $table->column_class('timemodified', 'timemodified');
        $table->column_class('timemarked', 'timemarked');
        $table->column_class('finalgrade', 'finalgrade');

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'attempts');
        $table->set_attribute('class', 'submissions');
        $table->set_attribute('width', '100%');

        $table->no_sorting('edit');
        $table->no_sorting('finalgrade');
        $table->no_sorting('outcome');
        $table->no_sorting('feedback');
        $table->no_sorting('status');
        $table->no_sorting('submissioncomment');

        $table->setup();

        list($where, $params) = $table->get_sql_where();
        if ($where) {
            $where .= ' AND ';
        }

        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY '.$sort;
        }

        $users = array_keys( $this->list_enrolled_users_with_capability("mod/assign:submit"));

        $ufields = user_picture::fields('u');
        if (!empty($users)) {
            $select = "SELECT $ufields,
                              s.id AS submissionid, g.grade, s.submissioncommenttext, s.status,
                              s.timemodified as timesubmitted, g.timemodified AS timemarked, g.feedbacktext, g.locked ";
            $sql = 'FROM {user} u '.
                   'LEFT JOIN {assign_submissions} s ON u.id = s.userid
                    AND s.assignment = '.$this->data->id.' '.
                   'LEFT JOIN {assign_grades} g ON u.id = g.userid
                    AND g.assignment = '.$this->data->id.' '.
                   'WHERE '.$where.'u.id IN ('.implode(',',$users).') ';

            if ($filter != null) {
                if ($filter == ASSIGN_FILTER_REQUIRE_GRADING) {
                    $sql .= ' AND g.timemarked < g.timemodified '; 
                } else if ($filter == ASSIGN_FILTER_SUBMITTED) {
                    $sql .= ' AND s.timemodified > 0 '; 
                }
            }
            
            $count = $DB->count_records_sql("SELECT COUNT(*) AS X ".$sql, $params);

            $table->pagesize($perpage, $count);
            
            $ausers = $DB->get_records_sql($select.$sql.$sort, $params, $table->get_page_start(), $table->get_page_size());

            //$table->pagesize($perpage, count($ausers));
            if ($ausers !== false) {
                $grading_info = grade_get_grades($this->get_course()->id, 'mod', 'assign', $this->data->id, array_keys($ausers));
                foreach ($ausers as $auser) {

                    $picture = $OUTPUT->user_picture($auser);

                    $userlink = $OUTPUT->action_link(new moodle_url('/user/view.php', array('id' => $auser->id, 'course'=>$this->get_course()->id)), fullname($auser, has_capability('moodle/site:viewfullnames', $this->context)));

                    $grade = $auser->grade;
                    if ($grade < 0) {
                        $grade = '';
                    }
                    $comment = shorten_text(format_text($auser->submissioncommenttext));
                    $studentmodified = '-';
                    if ($auser->timesubmitted) {
                        $studentmodified = userdate($auser->timesubmitted);
                    }
                    $teachermodified = '-';
                    if ($auser->timemarked) {
                        $teachermodified = userdate($auser->timemarked);
                    }
                    $status = get_string('submissionstatus_' . $auser->status, 'assign');
                    $status = $OUTPUT->action_link(new moodle_url('/mod/assign/grade.php', array('id' => $this->get_course_module()->id, 'userid'=>$auser->id)), $status);
                    $finalgrade = '-';
                    if (isset($grading_info->items[0]) && $grading_info->items[0]->grades[$auser->id]) {
                        // debugging
                        $finalgrade = print_r($grading_info->items[0]->grades[$auser->id], true);
                    }
                    
                    $edit = $OUTPUT->action_link(new moodle_url('/mod/assign/grade.php', array('id' => $this->get_course_module()->id, 'userid'=>$auser->id)), $OUTPUT->pix_icon('t/grades', get_string('grade')));
                    if (!$auser->status || $auser->status == ASSIGN_SUBMISSION_STATUS_DRAFT || !$this->data->submissiondrafts) {
                        if (!$auser->locked) {
                            $edit .= $OUTPUT->action_link(new moodle_url('/mod/assign/grading.php', array('id' => $this->get_course_module()->id, 'userid'=>$auser->id, 'action'=>'lock')), $OUTPUT->pix_icon('t/lock', get_string('preventsubmissions', 'assign')));
                        } else {
                            $edit .= $OUTPUT->action_link(new moodle_url('/mod/assign/grading.php', array('id' => $this->get_course_module()->id, 'userid'=>$auser->id, 'action'=>'unlock')), $OUTPUT->pix_icon('t/unlock', get_string('allowsubmissions', 'assign')));
                        }
                    }

                    $feedback = shorten_text(format_text($auser->feedbacktext));
                    $renderer = $PAGE->get_renderer('mod_assign');
                    $feedback .= '<br/>' . $renderer->assign_files($this->context, $auser->id, 'feedback');

                    $row = array($picture, $userlink, $status, $edit, $comment, $feedback, $grade, $studentmodified, $teachermodified, $finalgrade);
                    $table->add_data($row);
                }
            }
        }

        return $table;

    }
    
    function process_lock() {
        global $USER;
        $userid = required_param('userid', PARAM_INT);

        $grade = $this->get_grade($userid, true);
        $grade->locked = 1;
        $this->update_grade($grade);
        
    }
    
    function process_unlock() {
        global $USER;
        $userid = required_param('userid', PARAM_INT);

        $grade = $this->get_grade($userid, true);
        $grade->locked = 0;
        $this->update_grade($grade);
        
    }

    function process_save_grade() {
        global $USER;
        $userid = required_param('userid', PARAM_INT);

        $options = array('subdirs'=>1, 
                                        'maxbytes'=>$this->course->maxbytes, 
                                        'maxfiles'=>EDITOR_UNLIMITED_FILES,
                                        'accepted_types'=>'*', 
                                        'return_types'=>FILE_INTERNAL);
        $mform = new mod_assign_grade_form(null, array('cm'=>$this->get_course_module()->id, 'options'=>$options, 'contextid'=>$this->context->id, 'userid'=>$userid, 'course'=>$this->get_course(), 'context'=>$this->context));
        
        if ($formdata = $mform->get_data()) {
            $fs = get_file_storage();
            $fs->delete_area_files($this->context->id, 'mod_assign', 'feedback', $userid);
            $formdata = file_postupdate_standard_filemanager($formdata, 'feedbackfiles', $options, $this->context, 'mod_assign', 'feedback', $userid);

            $grade = $this->get_grade($userid, true);
            $grade->grade= $formdata->grade;
            $grade->grader= $USER->id;
            $grade->feedbacktext= $formdata->feedback_editor['text'];
            $grade->feedbackformat= $formdata->feedback_editor['format'];

            $this->update_grade($grade);
        }
        
    }

    function view_grade($action='') {
        global $OUTPUT, $DB;

        if ($action == 'savegrade') {
            $this->process_save_grade();
            redirect('grading.php?id='.$this->get_course_module()->id);
            die();
        }


        $this->view_header(get_string('grading', 'assign'));

        $userid = required_param('userid', PARAM_INT);
        $user = $DB->get_record('user', array('id' => $userid));

        
        echo $OUTPUT->container_start('userinfo');
        echo $OUTPUT->user_picture($user);
        echo $OUTPUT->spacer(array('width'=>30));
        echo $OUTPUT->action_link(new moodle_url('/user/view.php', array('id' => $user->id, 'course'=>$this->get_course()->id)), fullname($user, has_capability('moodle/site:viewfullnames', $this->context)));
        echo $OUTPUT->container_end();

        $this->view_submission_status($userid);

        // now show the grading form
        $this->view_grade_form();
        
        $this->view_footer();
    }
    
    function view_online_text($action='') {
        global $OUTPUT, $CFG, $USER;
        
        $submission = $this->get_submission($USER->id);       
        $this->view_header(get_string('onlinetext', 'assign'));       
        echo $OUTPUT->container_start('viewonlinetext');
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
        $text = file_rewrite_pluginfile_urls($submission->onlinetext, 'pluginfile.php', $this->context->id, 'mod_assign', 'submission', $USER->id);
        echo format_text($text, $submission->onlineformat, array('overflowdiv'=>true));
        //echo format_text($submission->onlinetext);
        echo $OUTPUT->box_end();
        echo $OUTPUT->container_end();
        echo $OUTPUT->spacer(array('height'=>30));
        echo $OUTPUT->single_button(new moodle_url('/mod/assign/view.php',
            array('id' => $this->get_course_module()->id)), get_string('backtoassignment', 'assign'), 'get');

        $this->view_footer();       
    }
       
    function view_grading($action='') {
        global $OUTPUT, $CFG, $USER;

        if ($action == 'saveoptions') {
            $this->process_save_grading_options();
        } else if ($action == 'lock') {
            $this->process_lock();
        } else if ($action == 'unlock') {
            $this->process_unlock();
        }
        
        // only load this if it is 
        require_once($CFG->libdir.'/gradelib.php');

        $this->view_header(get_string('grading', 'assign'));
        
        // check view permissions
        // check grading permissions
        // show a paginated table with all student who can participate in this assignment (honour group mode)
        // for each row show:
        //      student identifier (may be anonymised), submission status, links to the submission, feedback comments, feedback files, grade, outcomes, rubrics, optional column for subtypes
        // need to explore options for online grading interface - currently quickgrade or popup. 
        // show offline marking interface (download all assignments for offline marking - upload marked assignments)
        // plagiarism links
        
        
    
        $perpage = get_user_preferences('assign_perpage', 10);
        $filter = get_user_preferences('assign_filter', '');
        // print options for for changing the filter and changing the number of results per page
        $mform = new mod_assign_grading_options_form(null, array('cm'=>$this->get_course_module()->id, 'contextid'=>$this->context->id, 'userid'=>$USER->id), 'post', '', array('id'=>'gradingoptionsform'));


        $data = new stdClass();
        $data->perpage = $perpage;
        $data->filter = $filter;
        $mform->set_data($data);
        
        $mform->display();
        
        // load and print the table of submissions
        $table = & $this->load_submissions_table($perpage, $filter);
        $table->print_html();

        // add a link to the grade book if this user has permission

        // print navigation buttons

        echo $OUTPUT->spacer(array('height'=>30));
        $contextname = print_context_name($this->context);

        echo $OUTPUT->container_start('gradingnavigation');
        echo $OUTPUT->container_start('backlink');
        echo $OUTPUT->action_link(new moodle_url('/mod/assign/view.php', array('id' => $this->get_course_module()->id)), get_string('backto', '', $contextname));
        echo $OUTPUT->container_end();
        if (has_capability('gradereport/grader:view', $this->get_course_context()) && has_capability('moodle/grade:viewall', $this->get_course_context())) {
            echo $OUTPUT->container_start('gradebooklink');
            echo $OUTPUT->action_link(new moodle_url('/grade/report/grader/index.php', array('id' => $this->get_course()->id)), get_string('viewgradebook', 'assign'));
            echo $OUTPUT->container_end();
        }
        if (has_capability('mod/assign:grade', $this->get_course_context())) {
            echo $OUTPUT->container_start('downloadalllink');
            echo $OUTPUT->action_link(new moodle_url('/mod/assign/download-all.php', array('id' => $this->get_course_module()->id)), get_string('downloadall', 'assign'));
            echo $OUTPUT->container_end();
        }
        echo $OUTPUT->container_end();


        $this->view_footer();
    }

    /**
     * Display the assignment, used by view.php
     *
     * The assignment is displayed differently depending on your role, 
     * the settings for the assignment and the status of the assignment.
     */
    function view($subpage='', $action='') {
        // handle custom actions first
        if ($action == "savesubmission") {
            $this->process_save_submission();
        } else if ($action == "submit") {
            $this->process_submit_assignment();
        }
        $this->view_header($subpage);
        $this->view_intro();
        // check view permissions
            // show no permission error 
            // return
        // check is hidden
            // show hidden assignment page
            // return
        // check can grade
        if (has_capability('mod/assign:grade', $this->context)) {
            if ($action != 'editsubmission') {
                $this->view_grading_summary();
            }
        }
            // display link to grading interface
        // check can submit
        if (has_capability('mod/assign:submit', $this->context)) {
            // display current submission status
            if ($action != 'editsubmission') {
                $this->view_submission_status();
                $this->view_submission_links();
            }
            // check submissions open
            // display submit interface
            if ($action == 'editsubmission') {
                $this->view_submit();
            }
    
            if ($action != 'editsubmission') {
                $this->view_feedback();
            }
        }
        
        $this->view_footer();
    }
    
    function get_grade($userid, $create = false) {
        global $DB;

        $grade = $DB->get_record('assign_grades', array('assignment'=>$this->data->id, 'userid'=>$userid));

        if ($grade) {
            return $grade;
        }
        if ($create) {
            $grade = new stdClass();
            $grade->assignment   = $this->data->id;
            $grade->userid       = $userid;
            $grade->timecreated = time();
            $grade->timemodified = $grade->timecreated;
            $grade->grade = -1;
            $grade->feedbacktext = '';
            $grade->feedbackformat = editors_get_preferred_format();
            $gid = $DB->insert_record('assign_grades', $grade);
            $grade->id = $gid;
            return $grade;
        }
        return FALSE;
    }

    /**
     * Load the submission object for a particular user
     *
     * @global object
     * @global object
     * @param $userid int The id of the user whose submission we want or 0 in which case USER->id is used
     * @param $createnew boolean optional Defaults to false. If set to true a new submission object will be created in the database
     * @param bool $teachermodified student submission set if false
     * @return object The submission
     */
    function get_submission($userid = null, $create = false) {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $submission = $DB->get_record('assign_submissions', array('assignment'=>$this->data->id, 'userid'=>$userid));

        if ($submission) {
            return $submission;
        }
        if ($create) {
            $submission = new stdClass();
            $submission->assignment   = $this->data->id;
            $submission->userid       = $userid;
            $submission->timecreated = time();
            $submission->timemodified = $submission->timecreated;
            $submission->submissioncommenttext = '';
            $submission->submissioncommentformat = editors_get_preferred_format();
            $submission->onlinetext = '';
            $submission->onlineformat = editors_get_preferred_format();
            
            if ($this->data->submissiondrafts) {
                $submission->status = ASSIGN_SUBMISSION_STATUS_DRAFT;
            } else {
                $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
            }
            $sid = $DB->insert_record('assign_submissions', $submission);
            $submission->id = $sid;
            return $submission;
        }
        return FALSE;
    }
    
    function update_grade($grade) {
        global $DB;

        $grade->timemodified = time();
        return $DB->update_record('assign_grades', $grade);
    }
    
    function update_submission($submission) {
        global $DB;

        $submission->timemodified = time();
        return $DB->update_record('assign_submissions', $submission);
    }

    /**
     * Is this assignment open for submissions?
     *
     * Check the due date, 
     * prevent late submissions, 
     * has this person already submitted, 
     * is the assignment locked?
     */
    function submissions_open() {
        global $USER;

        $time = time();
        $date_open = TRUE;
        if ($this->data->preventlatesubmissions && $this->data->duedate) {
            $date_open = ($this->data->allowsubmissionsfromdate <= $time && $time <= $this->data->duedate);
        } else {
            $date_open = ($this->data->allowsubmissionsfromdate <= $time);
        }

        if (!$date_open) {
            return FALSE;
        }

        // now check if this user has already submitted etc.
        if (!is_enrolled($this->get_course_context(), $USER)) {
            return FALSE;
        }
        if ($submission = $this->get_submission($USER->id)) {
            if ($this->data->submissiondrafts && $submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                // drafts are tracked and the student has submitted the assignment
                return FALSE;
            }
        }
        if ($grade = $this->get_grade($USER->id)) {
            if ($grade->locked) {
                return FALSE;
            }
        }

        return TRUE;
    }
   
    function list_response_files($userid = null) {
        global $CFG, $USER, $OUTPUT, $PAGE;

        if (!$userid) {
            $userid = $USER->id;
        }
    
        //$candelete = $this->can_manage_responsefiles();
        $strdelete   = get_string('delete');

        $fs = get_file_storage();
        $browser = get_file_browser();

        $renderer = $PAGE->get_renderer('mod_assign');
        return $renderer->assign_files($this->context, $userid, 'submission');
        
    }
    
    function process_submit_assignment() {
        global $USER;
        $submission = $this->get_submission($USER->id, true);
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;

        $this->update_submission($submission);
    }
    
    function process_save_grading_options() {
        global $USER;

        $mform = new mod_assign_grading_options_form(null, array('cm'=>$this->get_course_module()->id, 'contextid'=>$this->context->id, 'userid'=>$USER->id));
        
        if ($formdata = $mform->get_data()) {
            set_user_preference('assign_perpage', $formdata->perpage);
            set_user_preference('assign_filter', $formdata->filter);
        }
    }
    
    function process_save_submission() {       
        global $USER;
        $data = $this->get_default_submission_data();
        $mform = new mod_assign_submission_form(null, array($this, $data));
        if ($data = $mform->get_data()) {               
            $submission = $this->get_submission($USER->id, true); //create the submission if needed & its id              
            $grade = $this->get_grade($USER->id); // get the grade to check if it is locked
            if ($grade->locked) {
                print_error('submissionslocked', 'assign');
                return;
            }
            $this->process_online_text_submission($submission, $data);
            $this->process_file_upload_submission($submission, $data);
            $this->process_submission_comment_submission($submission, $data);
            $this->update_submission($submission);
        }
         
    }
    
    function process_online_text_submission(& $submission, & $data) {
        global $USER;
        if (!$this->data->onlinetextsubmission) {
            return;
        }
    
        $editoroptions = array(
           'noclean' => false,
           'maxfiles' => EDITOR_UNLIMITED_FILES,
           'maxbytes' => $this->get_course()->maxbytes,
           'context' => $this->context
        );

        file_postupdate_standard_editor($data, 'onlinetext', $editoroptions, $this->context, 'assign', 'submissions', $USER->id);

        $submission->onlinetext = $data->onlinetext_editor['text'];
        $submission->onlineformat = $data->onlinetext_editor['format'];
        
    }
    
    // process function for submission comment
    function process_submission_comment_submission(& $submission, & $data) {
        if (!$this->data->submissioncomments) {
            return;
        }

        $submission->submissioncommenttext = $data->submissioncomment_editor['text'];
        $submission->submissioncommentformat = $data->submissioncomment_editor['format'];
    }

    // process function for saved file upload submit form
    function process_file_upload_submission(& $submission, & $data) {
        global $USER;
        if ($this->data->maxfilessubmission <= 0) {
            return;
        }
        $fileoptions = array('subdirs'=>1, 
                                'maxbytes'=>$this->data->maxsubmissionsizebytes, 
                                'maxfiles'=>$this->data->maxfilessubmission, 
                                'accepted_types'=>'*', 
                                'return_types'=>FILE_INTERNAL);

            
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'mod_assign', 'submission', $USER->id);
        $data = file_postupdate_standard_filemanager($data, 'files', $fileoptions, $this->context, 'mod_assign', 'submission', $USER->id);

        file_save_draft_area_files($USER->id, $this->context->id, 'mod_assign', 'submission', 0, array('subdirs'=>true));
    }
   

    function view_grade_form() {
        global $OUTPUT, $USER;
        echo $OUTPUT->heading(get_string('grade'), 3);
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
        $userid = required_param('userid', PARAM_INT);

        $grade = $this->get_grade($userid);
        if ($grade) {
            $data = new stdClass();
            $data->grade = $grade->grade;
            $data->feedback = $grade->feedbacktext;
            $data->feedbackformat = $grade->feedbackformat;
            // set the grade 
        } else {
            $data = new stdClass();
            $data->feedback = '';
            $data->feedbackformat = editors_get_preferred_format();
            $data->grade = -1;
        }

        $options = array('subdirs'=>1, 
                                        'maxbytes'=>$this->course->maxbytes, 
                                        'maxfiles'=>EDITOR_UNLIMITED_FILES,
                                        'accepted_types'=>'*', 
                                        'return_types'=>FILE_INTERNAL);
        $mform = new mod_assign_grade_form(null, array('cm'=>$this->get_course_module()->id, 'contextid'=>$this->context->id, 'userid'=>$userid, 'options'=>$options, 'course'=>$this->get_course(), 'context'=>$this->context, 'data'=>$data));

        // show upload form
        $mform->display();
        
        echo $OUTPUT->box_end();
        echo $OUTPUT->spacer(array('height'=>30));
    }

    function get_default_submission_data() {
        $data = new stdClass();
        $data->onlinetext = '';
        $data->onlinetextformat = editors_get_preferred_format();        
        $data->submissioncomment_editor['format'] = editors_get_preferred_format();

        return $data;
    }
    
    function view_submission_form() {
        global $OUTPUT, $USER;
        echo $OUTPUT->heading(get_string('submission', 'assign'), 3);
        echo $OUTPUT->container_start('submission');

        $data = $this->get_default_submission_data();
        $submission = $this->get_submission($USER->id);
        if ($submission) {          
            $data->onlinetext = $submission->onlinetext;
            $data->onlinetextformat = $submission->onlineformat;          
            $data->submissioncomment_editor['text'] = $submission->submissioncommenttext;
            $data->submissioncomment_editor['format'] = $submission->submissioncommentformat;
        }

        $mform = new mod_assign_submission_form(null, array($this, $data));

        // show upload form
        $mform->display();
        
        echo $OUTPUT->container_end();
        echo $OUTPUT->spacer(array('height'=>30));
    }

    /**
     * Show the screen for creating an assignment submission
     *
     */
    function view_submit() {
        global $OUTPUT;
        // check view permissions
        // check submit permissions
        // check submissions open

        if ($this->submissions_open()) {
            $this->view_submission_form();
            // call view_submit_hook() for subtypes   
        }
        echo $OUTPUT->single_button(new moodle_url('/mod/assign/view.php',
            array('id' => $this->get_course_module()->id)), get_string('backtoassignment', 'assign'), 'get');

        // plagiarism?
    }

    function view_submission_status($userid=null) {
        global $OUTPUT, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }
        
        if (!is_enrolled($this->get_course_context(), $userid)) {
            return;
        }
        $submission = null;

        echo $OUTPUT->container_start('submissionstatus');
        echo $OUTPUT->heading(get_string('submissionstatusheading', 'assign'), 3);
        $time = time();
        if ($this->data->maxfilessubmission < 1 && !$this->data->onlinetextsubmission) {
            echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
            echo get_string('noonlinesubmissions', 'assign');
            echo $OUTPUT->box_end();
        }

        if ($this->data->allowsubmissionsfromdate &&
                $time <= $this->data->allowsubmissionsfromdate) {
            echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
            echo get_string('allowsubmissionsfromdatesummary', 'assign', userdate($this->data->allowsubmissionsfromdate));
            echo $OUTPUT->box_end();
        } 
        $submission = $this->get_submission($userid);
        $grade = $this->get_grade($userid);
        echo $OUTPUT->box_start('boxaligncenter', 'intro');
        $t = new html_table();

        // status
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('submissionstatus', 'assign'));
        $locked = '';
        if ($grade && $grade->locked) {
            $locked = '<br/><br/>' . get_string('submissionslocked', 'assign');
        }
        if ($submission) {
            $cell2 = new html_table_cell(get_string('submissionstatus_' . $submission->status, 'assign') . $locked);
        } else {
            $cell2 = new html_table_cell(get_string('nosubmission', 'assign') . $locked);
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        // grading status
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('gradingstatus', 'assign'));

        if ($grade && $grade->grade > 0) {
            $cell2 = new html_table_cell(get_string('graded', 'assign'));
        } else {
            $cell2 = new html_table_cell(get_string('notgraded', 'assign'));
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        
        
        if ($this->data->duedate >= 1) {
            // due date
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('duedate', 'assign'));
            $cell2 = new html_table_cell(userdate($this->data->duedate));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
            
            // time remaining
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timeremaining', 'assign'));
            if ($this->data->duedate - $time <= 0) {
                if (!$submission || $submission->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED &&
                    $submission->status != ASSIGN_SUBMISSION_STATUS_LOCKED) {
                    $cell2 = new html_table_cell(get_string('overdue', 'assign', format_time($time - $this->data->duedate)));
                } else {
                    if ($submission->timemodified > $this->data->duedate) {
                        $cell2 = new html_table_cell(get_string('submittedlate', 'assign', format_time($submission->timemodified - $this->data->duedate)));
                    } else {
                        $cell2 = new html_table_cell(get_string('submittedearly', 'assign', format_time($submission->timemodified - $this->data->duedate)));
                    }
                }
            } else {
                $cell2 = new html_table_cell(format_time($this->data->duedate - $time));
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        } 

        // last modified 
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('timemodified', 'assign'));
        if ($submission) {
            $cell2 = new html_table_cell(userdate($submission->timemodified));
        } else {
            $cell2 = new html_table_cell();
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        // if online text assignment submission is set to yes
        //onlinetextsubmission                      
        if ($this->data->onlinetextsubmission) {
            $link = new moodle_url ('/mod/assign/online_text.php?id='.$this->get_course_module()->id);
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('onlinetextwordcount', 'assign')); 
            if (!$submission) {
                $cell2 = new html_table_cell(get_string('numwords', '', 0));                                                     
            } else if(count_words(format_text($submission->onlinetext)) < 1){                           
                $cell2 = new html_table_cell(get_string('numwords', '', count_words(format_text($submission->onlinetext))));                                                     
            } else{                               
                $cell2 = new html_table_cell($OUTPUT->action_link($link,get_string('numwords', '', count_words(format_text($submission->onlinetext)))));
            }                      
            
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }             

        // files 
        if ($this->data->maxfilessubmission >= 1) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('submissionfiles', 'assign'));
            $cell2 = new html_table_cell($this->list_response_files($userid));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        } 

        // comments 
        if ($this->data->submissioncomments) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('submissioncomment', 'assign'));
            if ($submission) {
                $cell2 = new html_table_cell(format_text($submission->submissioncommenttext));
            } else {
                $cell2 = new html_table_cell();
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        } 
                      
        echo html_writer::table($t);
        echo $OUTPUT->box_end();
        
        echo $OUTPUT->container_end();
    }

    function view_feedback($userid=null) {
        global $OUTPUT, $USER, $PAGE, $DB;

        if (!$userid) {
            $userid = $USER->id;
        }
        
        if (!is_enrolled($this->get_course_context(), $userid)) {
            return;
        }
        $submission = null;

        $grade = $this->get_grade($userid);
        if (!$grade) {
            return;
        }
        echo $OUTPUT->container_start('feedback');
        echo $OUTPUT->heading(get_string('feedback', 'assign'), 3);
        echo $OUTPUT->box_start('boxaligncenter', 'intro');
        $t = new html_table();

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('feedbackcomments', 'assign'));
        $cell2 = new html_table_cell(format_text($grade->feedbacktext));
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;
        // feedback files
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('feedbackfiles', 'assign'));
        $fs = get_file_storage();
        $browser = get_file_browser();

        $renderer = $PAGE->get_renderer('mod_assign');
        $cell2 = new html_table_cell($renderer->assign_files($this->context, $userid, 'feedback'));
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;
        
        if ($grade->grade >= 0) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('grade', 'assign'));
            $cell2 = new html_table_cell(format_text($grade->grade));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }
        
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('gradedon', 'assign'));
        $cell2 = new html_table_cell(userdate($grade->timemodified));
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;
        
        if ($grader = $DB->get_record('user', array('id'=>$grade->grader))) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('gradedby', 'assign'));
            $cell2 = new html_table_cell($OUTPUT->user_picture($grader) . $OUTPUT->spacer(array('width'=>30)) . fullname($grader));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        echo html_writer::table($t);
        echo $OUTPUT->box_end();
        
        echo $OUTPUT->container_end();

    }

    function view_submission_links($userid = null) {
        global $OUTPUT, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }
        
        if (has_capability('mod/assign:submit', $this->context) &&
            $this->submissions_open() && ($this->data->maxfilessubmission >= 1 || $this->data->onlinetextsubmission)) {
            echo $OUTPUT->single_button(new moodle_url('/mod/assign/submission.php',
                array('id' => $this->get_course_module()->id)), get_string('editsubmission', 'assign'), 'get');

            $submission = $this->get_submission($userid);

            if ($submission) {
                if ($submission->status == ASSIGN_SUBMISSION_STATUS_DRAFT) {
                    echo $OUTPUT->single_button(new moodle_url('/mod/assign/submission.php',
                        array('id' => $this->get_course_module()->id, 'action'=>'submit')), get_string('submitassignment', 'assign'), 'get');
                    echo $OUTPUT->box_start('boxaligncenter', 'intro');
                    echo get_string('submitassignment_help', 'assign');
                    echo $OUTPUT->box_end();
                }
            }
        }
    }
    

    function pre_add_instance_hook() {
    }
    
    function post_add_instance_hook() {
    }

    function pre_update_instance_hook() {
    }
    
    function post_update_instance_hook() {
    }

    function validate(& $err) {
        // check all the settings 
        if (false) {
            $err = get_string('notvalidblah', 'assign');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Add this instance to the database
     */
    function add_instance() {
        global $DB;

        // call pre_create hook (for subtypes)
        $this->pre_add_instance_hook();

        // validation check
        $err = '';
        if (!$this->validate($err)) {
            print_error($err);
            // show add instance page?
            die();
        } 

        // add the database record
        $this->data->timemodified = time();
        $this->data->courseid = $this->data->course;

        $returnid = $DB->insert_record("assign", $this->data);
        $this->data->id = $returnid;

        // add event to the calendar
        // add the item in the gradebook
        // call post_create hook (for subtypes)
        $this->post_add_instance_hook();

        return $this->data->id;
    }
    
    /**
     * Deletes an assignment activity
     *
     * Deletes all database records, files and calendar events for this assignment.
     */
    function delete_instance() {
        // call pre_delete hook (for subtypes)
        // delete the database record
        // delete all the files
        // delete all the calendar events
        // delete entries from gradebook
        // call post_delete hook (for subtypes)
    }

    /**
     * Update instance
     *
     */
    function update_instance() {
        global $DB;
        
        $this->data->id = $this->data->instance;
        $this->data->timemodified = time();

        // call pre_update hook (for subtypes)
        $this->pre_update_instance_hook();
        // update the database record

        $result = $DB->update_record('assign', $this->data);
        
        // update all the calendar events 
        // call post_update hook (for subtypes)
        $this->post_update_instance_hook();
        return $result;
    }

    /**
     * Add settings to edit form (called statically)
     *
     * Add the list of assignment specific settings to the edit form
     * static
     */
    function add_settings(& $mform) {
        global $CFG, $COURSE;
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        if (!assign_base::hide_config_setting_hook('allowsubmissionsfromdate') ||
            !assign_base::hide_config_setting_hook('alwaysshowdescription') ||
            !assign_base::hide_config_setting_hook('duedate')) {
            $mform->addElement('header', 'general', get_string('availability', 'assign'));
        }
        if (!assign_base::hide_config_setting_hook('allowsubmissionsfromdate')) {
            $mform->addElement('date_time_selector', 'allowsubmissionsfromdate', get_string('allowsubmissionsfromdate', 'assign'), array('optional'=>true));
            $mform->setDefault('allowsubmissionsfromdate', time());
        }
        if (!assign_base::hide_config_setting_hook('duedate')) {
            $mform->addElement('date_time_selector', 'duedate', get_string('duedate', 'assign'), array('optional'=>true));
            $mform->setDefault('duedate', time()+7*24*3600);
        }
        if (!assign_base::hide_config_setting_hook('alwaysshowdescription')) {
            $mform->addElement('select', 'alwaysshowdescription', get_string('alwaysshowdescription', 'assign'), $ynoptions);
            $mform->setDefault('alwaysshowdescription', 1);
        }
        if (!assign_base::hide_config_setting_hook('preventlatesubmissions')) {
            $mform->addElement('select', 'preventlatesubmissions', get_string('preventlatesubmissions', 'assign'), $ynoptions);
            $mform->setDefault('preventlatesubmissions', 0);
        }
        if (!assign_base::hide_config_setting_hook('submissiondrafts') ||
            !assign_base::hide_config_setting_hook('submissioncomments')) {
            $mform->addElement('header', 'general', get_string('submissions', 'assign'));
        }
        if (!assign_base::hide_config_setting_hook('submissiondrafts')) {
            $mform->addElement('select', 'submissiondrafts', get_string('submissiondrafts', 'assign'), $ynoptions);
            $mform->setDefault('submissiondrafts', 0);
        }
        if (!assign_base::hide_config_setting_hook('submissioncomments')) {
            $mform->addElement('select', 'submissioncomments', get_string('submissioncomments', 'assign'), $ynoptions);
            $mform->setDefault('submissioncomments', 0);
        }
        if (!assign_base::hide_config_setting_hook('onlinetextsubmission')) {
            $mform->addElement('header', 'general', get_string('onlinesubmissions', 'assign'));
        }
        if (!assign_base::hide_config_setting_hook('onlinetextsubmission')) {
            $mform->addElement('select', 'onlinetextsubmission', get_string('onlinetextsubmission', 'assign'), $ynoptions);
            $mform->setDefault('onlinetextsubmission', 0);
        }
        if (!assign_base::hide_config_setting_hook('maxfilessubmission') ||
            !assign_base::hide_config_setting_hook('maxsubmissionsizebytes')) {
            $mform->addElement('header', 'general', get_string('filesubmissions', 'assign'));
        }
        if (!assign_base::hide_config_setting_hook('maxfilessubmission')) {
            $options = array();
            for($i = 0; $i <= 20; $i++) {
                $options[$i] = $i;
            }
            $mform->addElement('select', 'maxfilessubmission', get_string('maxfilessubmission', 'assign'), $options);
            $mform->setDefault('maxfilessubmission', 3);
        }
        if (!assign_base::hide_config_setting_hook('maxsubmissionsizebytes')) {
            $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
            $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
            $mform->addElement('select', 'maxsubmissionsizebytes', get_string('maximumsubmissionsize', 'assign'), $choices);
 //           $mform->setDefault('maxsubmissionsizebytes', $CFG->assign_maxsubmissionsizebytes);

        }
        if (!assign_base::hide_config_setting_hook('sendnotifications')) {
            $mform->addElement('header', 'general', get_string('notifications', 'assign'));
        }
        if (!assign_base::hide_config_setting_hook('sendnotifications')) {
            $mform->addElement('select', 'sendnotifications', get_string('sendnotifications', 'assign'), $ynoptions);
            $mform->setDefault('sendnotifications', 1);
        }
    }

    function extend_settings_navigation(navigation_node & $navref) {
        if (has_capability('gradereport/grader:view', $this->get_course_context()) && has_capability('moodle/grade:viewall', $this->get_course_context())) {
            $link = new moodle_url('/grade/report/grader/index.php', array('id' => $this->get_course()->id));
            $node = $navref->add(get_string('viewgradebook', 'assign'), $link, navigation_node::TYPE_SETTING);
        }
        if (has_capability('mod/assign:grade', $this->get_course_context())) {
            $link = new moodle_url('/mod/assign/downloadall.php', array('id' => $this->get_course_module()->id));
            $node = $navref->add(get_string('downloadall', 'assign'), $link, navigation_node::TYPE_SETTING);
        }
    }
    
    function add_file_upload_form_elements(& $mform, & $data) {
        global $USER;
        if ($this->data->maxfilessubmission <= 0) {
            return;
        }
        $mform->addElement('header', 'general', get_string('uploadfiles', 'assign'));

        $fileoptions = array('subdirs'=>1, 
                                'maxbytes'=>$this->data->maxsubmissionsizebytes, 
                                'maxfiles'=>$this->data->maxfilessubmission, 
                                'accepted_types'=>'*', 
                                'return_types'=>FILE_INTERNAL);


        $mform->addElement('filemanager', 'files_filemanager', '', null, $fileoptions);

        $mform->addElement('static', '', '', get_string('descriptionmaxfiles', 'assign', $this->data->maxfilessubmission));
    
        $data = file_prepare_standard_filemanager($data, 'files', $fileoptions, $this->context, 'mod_assign', 'submission', $USER->id);
    }
    
    function add_submission_comment_form_elements(& $mform, & $data) {
        if (!$this->data->submissioncomments) {
            return;
        }

        $mform->addElement('header', 'general', get_string('submissioncomment', 'assign'));
        $mform->addElement('editor', 'submissioncomment_editor', '', null, null);
        $mform->setType('submissioncomment_editor', PARAM_RAW); // to be cleaned before display
    }

    function add_online_text_form_elements(& $mform, & $data) {
        global $USER;
        if (!$this->data->onlinetextsubmission) {
            return;
        }


        $mform->addElement('header', 'general', get_string('onlinetextcomment', 'assign'));
        $editoroptions = array(
           'noclean' => false,
           'maxfiles' => EDITOR_UNLIMITED_FILES,
           'maxbytes' => $this->get_course()->maxbytes,
           'context' => $this->context
        );           

        $data = file_prepare_standard_editor($data, 'onlinetext', $editoroptions, $this->context, 'mod_assign', 'submission', $USER->id);      
        $mform->addElement('editor', 'onlinetext_editor', '', null, $editoroptions);
  
        $mform->setType('onlinetext_editor', PARAM_RAW); // to be cleaned before display
    }

    function add_submission_form_elements(& $mform, & $data) {
        // online text submissions
        $this->add_online_text_form_elements($mform, $data);
        // file uploads
        $this->add_file_upload_form_elements($mform, $data);
        // submission comment
        $this->add_submission_comment_form_elements($mform, $data);

        // hidden params
        $mform->addElement('hidden', 'id', $this->get_course_module()->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'savesubmission');
        $mform->setType('action', PARAM_TEXT);
        // buttons
        
    }
}

/**
 * Adds an assignment instance
 *
 * This is done by calling the add_instance() method of the assignment type class
 */
function assign_add_instance($form_data) {
    $context = get_context_instance(CONTEXT_COURSE,$form_data->course);
    $ass = new assign_base($context, $form_data);
    return $ass->add_instance();
}

/**
 * Update an assignment instance
 *
 * This is done by calling the update_instance() method of the assignment type class
 */
function assign_update_instance($form_data) {
    $context = get_context_instance(CONTEXT_MODULE,$form_data->coursemodule);
    $ass = new assign_base($context, $form_data);
    return $ass->update_instance();
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function assign_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

function assign_extend_settings_navigation($settings, navigation_node $navref) {
    global $PAGE;     

    $cm = $PAGE->cm;
    $context = $cm->context;

    $ass = new assign_base($context);
    return $ass->extend_settings_navigation($navref);
}

