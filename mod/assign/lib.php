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

define('ASSIGN_SUBMISSION_STATUS_SUBMITTED', 'submitted'); // student thinks it is finished
define('ASSIGN_SUBMISSION_STATUS_GRADER_CLOSED', 'closed'); // teacher prevents more submissions

require_once($CFG->libdir.'/accesslib.php');

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

        $PAGE->set_title($this->pagetitle);
        $PAGE->set_heading($COURSE->fullname);

        echo $OUTPUT->header();

        groups_print_activity_menu($this->get_course_module(), $CFG->wwwroot . '/mod/assign/view.php?id=' . $this->get_course_module()->id);
        
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
    function view() {
        $this->view_header();
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
            $this->view_submission_status();
            // check submissions open
            // display submit interface
            $this->view_submit();
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
    function get_submission($userid) {
        global $DB;

        $submission = $DB->get_record('assign_submissions', array('assignment'=>$this->data->id, 'userid'=>$userid));

        if ($submission) {
            return $submission;
        }
        return FALSE;
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
            if ($submission->status == ASSIGN_SUBMISSION_STATUS_GRADER_CLOSED) {
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
    
    /**
     * Show the screen for creating an assignment submission
     *
     */
    function view_submit() {
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
                // show upload files submission form
            // call view_submit_hook() for subtypes   
        }

        // plagiarism?
    }

    function view_submission_status() {
        $time = time();
        if ($this->data->allowsubmissionsfromdate) {
            if ($time <= $this->data->allowsubmissionsfromdate) {
                echo "You are not allowed to submit to this assignment before " . $this->data->allowsubmissionsfromdate;
            }    
        }
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
            !assign_base::hide_config_setting_hook('minfilessubmission') ||
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
        if (!assign_base::hide_config_setting_hook('minfilessubmission')) {
            $options = array();
            for($i = 0; $i <= 20; $i++) {
                $options[$i] = $i;
            }
            $mform->addElement('select', 'minfilessubmission', get_string('minfilessubmission', 'assign'), $options);
            $mform->setDefault('minfilessubmission', 3);
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
