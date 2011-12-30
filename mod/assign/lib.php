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

define('ASSIGN_SUBMISSION_STATUS_DRAFT', 'draft'); // student thinks it is finished
define('ASSIGN_SUBMISSION_STATUS_SUBMITTED', 'submitted'); // student thinks it is finished
define('ASSIGN_SUBMISSION_STATUS_LOCKED', 'locked'); // teacher prevents more submissions

require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir . '/plagiarismlib.php');

class assign_base {

    // list of configuration options for the assignment base type

    // list of all settings
    protected $data;

    // context cache
    protected $context;
    // cached current course and module
    protected $course;
    protected $coursemodule;

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
    }

    private function get_course() {
        if ($this->course) {
            return $this->course;
        }
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
    
    /**
     * Display the assignment, used by view.php
     *
     * The assignment is displayed differently depending on your role, 
     * the settings for the assignment and the status of the assignment.
     */
    function view($subpage='', $action='') {
        // handle custom actions first
        if ($action == "uploadfile") {
            $this->process_file_upload();
        } else if ($action == "savecomments") {
            $this->process_submission_comments();
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
            // display link to grading interface
        // check can submit
        if (has_capability('mod/assign:submit', $this->context)) {
            // display current submission status
            if ($action != 'editsubmission') {
                $this->view_submission_status();
            }
            // check submissions open
            // display submit interface
            if ($action == 'editsubmission') {
                $this->view_submit();
            }
        }
        $this->view_footer();
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
    function get_submission($userid, $create = false) {
        global $DB;

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
            $submission->submissioncomment = '';
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
            if ($submission->status == ASSIGN_SUBMISSION_STATUS_LOCKED) {
                // the marker has prevented any more submissions
                return FALSE;
            }
        }

        return TRUE;
    }
    
    /**
     * Show the form for creating an online text submission
     *
     */
    function view_online_text_submit_form() {
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
    
    function process_submission_comments() {
        global $USER;

        $mform = new mod_assign_submission_comments_form(null, array('cm'=>$this->get_course_module()->id, 'contextid'=>$this->context->id, 'userid'=>$USER->id));
        
        if ($formdata = $mform->get_data()) {
            $submission = $this->get_submission($USER->id, true);
            $submission->submissioncomment = $formdata->submissioncomment;

            $this->update_submission($submission);
        
        }
    }

    function process_file_upload() {
        global $USER;
    
        $options = array('subdirs'=>1, 
                                        'maxbytes'=>$this->data->maxsubmissionsizebytes, 
                                        'maxfiles'=>$this->data->maxfilessubmission, 
                                        'accepted_types'=>'*', 
                                        'return_types'=>FILE_INTERNAL);

        $mform = new mod_assign_upload_form(null, array('cm'=>$this->get_course_module()->id, 'options'=>$options, 'contextid'=>$this->context->id, 'userid'=>$USER->id));
    
        if ($formdata = $mform->get_data()) {
            $fs = get_file_storage();
            $fs->delete_area_files($this->context->id, 'mod_assign', 'submission', $USER->id);
            $formdata = file_postupdate_standard_filemanager($formdata, 'files', $options, $this->context, 'mod_assign', 'submission', $USER->id);

            $submission = $this->get_submission($USER->id, true);

            $this->update_submission($submission);
            redirect('view.php?id='.$this->get_course_module()->id);
            die();
        }
        
    }
    
    function view_submission_notes_form() {
        global $OUTPUT, $USER;
        echo $OUTPUT->heading(get_string('submissioncomment', 'assign'), 3);
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
        
        $mform = new mod_assign_submission_comments_form(null, array('cm'=>$this->get_course_module()->id, 'contextid'=>$this->context->id, 'userid'=>$USER->id));
        $submission = $this->get_submission($USER->id);
        if ($submission) {
            $data = new stdClass();
            $data->submissioncomment = $submission->submissioncomment;
            $mform->set_data($data);
        }
        $mform->display();
        echo $OUTPUT->box_end();
        echo $OUTPUT->spacer(array('height'=>30));
    }

    function view_files_submit_form() {
        global $OUTPUT, $USER;
        echo $OUTPUT->heading(get_string('uploadfiles', 'assign'), 3);
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');

        
        // move submission files to user draft area
        $filemanager_options = array('subdirs'=>1, 
                                        'maxbytes'=>$this->data->maxsubmissionsizebytes, 
                                        'maxfiles'=>$this->data->maxfilessubmission, 
                                        'accepted_types'=>'*', 
                                        'return_types'=>FILE_INTERNAL);

        $mform = new mod_assign_upload_form(null, array('cm'=>$this->get_course_module()->id, 'options'=>$filemanager_options, 'contextid'=>$this->context->id, 'userid'=>$USER->id));
        $data = new stdClass();
        $data = file_prepare_standard_filemanager($data, 'files', $filemanager_options, $this->context, 'mod_assign', 'submission', $USER->id);
        // set file manager itemid, so it will find the files in draft area
        $mform->set_data($data);
        


        // show upload form
        $mform->display();
        
        echo $OUTPUT->box_end();
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
            // if online text allowed
            if ($this->data->onlinetextsubmission) {
                // show online text submission form
                $this->view_online_text_submit_form();
            }
            // if upload files allowed
            if ($this->data->maxfilessubmission >= 1) {
                // show upload files submission form
                $this->view_files_submit_form();
            }
            if ($this->data->submissionnotes) {
                $this->view_submission_notes_form();
            }
            // call view_submit_hook() for subtypes   
        }
        echo $OUTPUT->single_button(new moodle_url('/mod/assign/view.php',
            array('id' => $this->get_course_module()->id)), get_string('backtoassignment', 'assign'), 'get');

        // plagiarism?
    }

    function view_submission_status() {
        global $OUTPUT, $USER;
        
        if (!is_enrolled($this->get_course_context(), $USER)) {
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
        } else {
            if ($this->data->allowsubmissionsfromdate) {
                if ($time <= $this->data->allowsubmissionsfromdate) {
                    echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
                    echo get_string('allowsubmissionsfromdatesummary', 'assign', userdate($this->data->allowsubmissionsfromdate));
                    echo $OUTPUT->box_end();
                }    
            } else {
                $submission = $this->get_submission($USER->id);
                if ($submission) {
                    echo $OUTPUT->box_start('boxaligncenter', 'intro');
                    $t = new html_table();

                    // status
                    $row = new html_table_row();
                    $cell1 = new html_table_cell(get_string('submissionstatus', 'assign'));
                    $cell2 = new html_table_cell(get_string('submissionstatus_' . $submission->status, 'assign'));
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
                            if ($submission->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED &&
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
                    $cell2 = new html_table_cell(userdate($submission->timemodified));
                    $row->cells = array($cell1, $cell2);
                    $t->data[] = $row;

                    // notes 
                    if ($this->data->submissionnotes) {
                        $row = new html_table_row();
                        $cell1 = new html_table_cell(get_string('submissioncomment', 'assign'));
                        $cell2 = new html_table_cell(format_text($submission->submissioncomment));
                        $row->cells = array($cell1, $cell2);
                        $t->data[] = $row;
                    } 

                    // files 
                    if ($this->data->maxfilessubmission >= 1) {
                        $row = new html_table_row();
                        $cell1 = new html_table_cell(get_string('responsefiles', 'assign'));
                        $cell2 = new html_table_cell($this->list_response_files());
                        $row->cells = array($cell1, $cell2);
                        $t->data[] = $row;
                    } 

                    // online text assignment submission

                    echo html_writer::table($t);
                    echo $OUTPUT->box_end();
                } else {
                    // no submission
                    echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
                    echo get_string('nosubmission', 'assign');
                    echo $OUTPUT->box_end();
                }
            }
        }
        if (has_capability('mod/assign:submit', $this->context) &&
            $this->submissions_open() && ($this->data->maxfilessubmission >= 1 || $this->data->onlinetextsubmission)) {
            echo $OUTPUT->single_button(new moodle_url('/mod/assign/submission.php',
                array('id' => $this->get_course_module()->id)), get_string('editsubmission', 'assign'), 'get');
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
        echo $OUTPUT->container_end();
        echo $OUTPUT->spacer(array('height'=>30));
    }
    

    function view_grading() {
        // check view permissions
        // check grading permissions
        // show a paginated table with all student who can participate in this assignment (honour group mode)
        // for each row show:
        //      student identifier (may be anonymised), submission status, links to the submission, feedback comments, feedback files, grade, outcomes, rubrics, optional column for subtypes
        // need to explore options for online grading interface - currently quickgrade or popup. 
        // show offline marking interface (download all assignments for offline marking - upload marked assignments)
        // plagiarism links
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
            !assign_base::hide_config_setting_hook('submissionnotes')) {
            $mform->addElement('header', 'general', get_string('submissions', 'assign'));
        }
        if (!assign_base::hide_config_setting_hook('submissiondrafts')) {
            $mform->addElement('select', 'submissiondrafts', get_string('submissiondrafts', 'assign'), $ynoptions);
            $mform->setDefault('submissiondrafts', 0);
        }
        if (!assign_base::hide_config_setting_hook('submissionnotes')) {
            $mform->addElement('select', 'submissionnotes', get_string('submissionnotes', 'assign'), $ynoptions);
            $mform->setDefault('submissionnotes', 0);
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

/**
 * @package   mod-assign
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assign_upload_form extends moodleform {
    function definition() {
        $mform = $this->_form;
        $instance = $this->_customdata;

        // visible elements
        $mform->addElement('filemanager', 'files_filemanager', get_string('uploadafile'), null, $instance['options']);

        $mform->addElement('static', '', '', get_string('descriptionmaxfiles', 'assign', $instance['options']['maxfiles']));
        // hidden params
        $mform->addElement('hidden', 'contextid', $instance['contextid']);
        $mform->setType('contextid', PARAM_INT);
        $mform->addElement('hidden', 'id', $instance['cm']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $instance['userid']);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'action', 'uploadfile');
        $mform->setType('action', PARAM_ALPHA);

        // buttons
        $this->add_action_buttons(false, get_string('savefiles', 'assign'));
    }
}

/**
 * @package   mod-assign
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assign_submission_comments_form extends moodleform {
    function definition() {
        $mform = $this->_form;
        $instance = $this->_customdata;

        // visible elements
        $mform->addElement('textarea', 'submissioncomment', get_string('submissioncomment', 'assign'), array('cols' => 60, 'rows' => 5));

        // hidden params
        $mform->addElement('hidden', 'contextid', $instance['contextid']);
        $mform->setType('contextid', PARAM_INT);
        $mform->addElement('hidden', 'id', $instance['cm']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $instance['userid']);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'action', 'savecomments');
        $mform->setType('action', PARAM_ALPHA);

        // buttons
        $this->add_action_buttons(false, get_string('savecomments', 'assign'));
    }
}
