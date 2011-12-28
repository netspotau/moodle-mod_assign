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

class assign_base {

    // list of configuration options for the assignment base type

    var $data;
    /** @var date */
    var $timeavailable;
    /** @var date */
    var $timedue;
    /** @var date */
    var $timefinal;
    /** @var boolean */
    var $preventlate;
    /** @var boolean */
    var $allowonlinetextsubmission;
    /** @var boolean */
    var $requireonlinetextsubmission;
    /** @var int */
    var $allowmaxfiles;
    /** @var int */
    var $allowminfiles;
    /** @var int */
    var $maxsubmissionsizebytes;
    /** @var int */
    var $maxfeedbacksizebytes;
    /** @var boolean */
    var $allowfeedbackfiles;
    /** @var boolean */
    var $allowfeedbacktext;

    // context cache
    protected $context;

    function hide_config_setting_hook($name) {
        return false;
    }

    /**
     * Constructor for the base assign class
     *
     */
    function assign_base(& $context, & $form_data = null) {
        if (!$context) {
            print_error('invalidcontext');
            die();
        }

        $this->context = & $context;

        if ($form_data) {
            $this->data = $form_data;
        }

    }
    
    
    
    /**
     * Display the assignment, used by view.php
     *
     * The assignment is displayed differently depending on your role, 
     * the settings for the assignment and the status of the assignment.
     */
    function view() {
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
            // check submissions open
            // display submit interface
            $this->view_submit();
        }
    }
    
    /**
     * Show the screen for creating an assignment submission
     *
     */
    function view_submit() {
        echo "View Submit";
        // check view permissions
        // check submissions open
        // check submit permissions
        // if online text allowed
            // show online text submission form
        // if upload files allowed
            // show upload files submission form
        // call view_submit_hook() for subtypes   

        // plagiarism?
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
        // call pre_update hook (for subtypes)
        // update the database record
        // update all the calendar events 
        // call post_update hook (for subtypes)
    }

    /**
     * Add settings to edit form (called statically)
     *
     * Add the list of assignment specific settings to the edit form
     * static
     */
    function add_settings(& $mform) {
        global $CFG, $COURSE;
        if (!assign_base::hide_config_setting_hook('timeavailable')) {
            $mform->addElement('date_time_selector', 'timeavailable', get_string('availabledate', 'assign'), array('optional'=>true));
            $mform->setDefault('timeavailable', time());
        }
        if (!assign_base::hide_config_setting_hook('timedue')) {
            $mform->addElement('date_time_selector', 'timedue', get_string('duedate', 'assign'), array('optional'=>true));
            $mform->setDefault('timedue', time()+7*24*3600);
        }
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        if (!assign_base::hide_config_setting_hook('preventlate')) {
            $mform->addElement('select', 'preventlate', get_string('preventlate', 'assign'), $ynoptions);
            $mform->setDefault('preventlate', 0);
        }
        if (!assign_base::hide_config_setting_hook('allowonlinetextsubmission')) {
            $mform->addElement('select', 'allowonlinetextsubmission', get_string('allowonlinetextsubmission', 'assign'), $ynoptions);
            $mform->setDefault('allowonlinetextsubmission', 0);
        }
        if (!assign_base::hide_config_setting_hook('requireonlinetextsubmission')) {
            $mform->addElement('select', 'requireonlinetextsubmission', get_string('requireonlinetextsubmission', 'assign'), $ynoptions);
            $mform->setDefault('requireonlinetextsubmission', 0);
        }
        if (!assign_base::hide_config_setting_hook('allowmaxfiles')) {
            $options = array();
            for($i = 1; $i <= 20; $i++) {
                $options[$i] = $i;
            }
            $mform->addElement('select', 'allowmaxfiles', get_string('allowmaxfiles', 'assign'), $options);
            $mform->setDefault('allowmaxfiles', 3);
        }
        if (!assign_base::hide_config_setting_hook('allowminfiles')) {
            $options = array();
            for($i = 1; $i <= 20; $i++) {
                $options[$i] = $i;
            }
            $mform->addElement('select', 'allowminfiles', get_string('allowminfiles', 'assign'), $options);
            $mform->setDefault('allowminfiles', 3);
        }
        if (!assign_base::hide_config_setting_hook('maxsubmissionsizebytes')) {
            $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
            $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
            $mform->addElement('select', 'maxsubmissionsizebytes', get_string('maximumsubmissionsize', 'assign'), $choices);
 //           $mform->setDefault('maxsubmissionsizebytes', $CFG->assign_maxsubmissionsizebytes);

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
