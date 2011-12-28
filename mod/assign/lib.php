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

    /** @var object */
    var $context;

 
    // list of configuration options for the assignment base type
    /** @var date */
    var $config_due_date
    /** @var date */
    var $config_final_submissions_date
    /** @var boolean */
    var $config_allow_late_submissions
    /** @var boolean */
    var $config_allow_online_text_submission
    /** @var boolean */
    var $config_require_online_text_submission
    /** @var int */
    var $config_max_upload_file_submissions
    /** @var int */
    var $config_min_required_upload_file_submissions
    /** @var boolean */
    var $config_require_online_text_submission
    /** @var int */
    var $config_max_submission_file_size
    /** @var int */
    var $config_max_feedback_file_size
    /** @var boolean */
    var $config_allow_feedback_files
    /** @var boolean */
    var $config_allow_feedback_text

    /**
     * Configure all this assignment instance settings from
     * the submitted form data
     */
    function configure_from_form($form_data) {
    }

    /**
     * Constructor for the base assign class
     *
     */
    function assign_base($context, $form_data = null) {
        if (!$context) {
            print_error('invalidcontext');
            die();
        }

        if ($form_data) {
            $this->configure_from_form($form_data);
        
            // get the course id from the course context
        } else {
            // get the course module id from the course module context
        }

    }
    
    /**
     * Display the assignment, used by view.php
     *
     * The assignment is displayed differently depending on your role, 
     * the settings for the assignment and the status of the assignment.
     */
    function view_main() {
        // check view permissions
            // show no permission error 
            // return
        // check is hidden
            // show hidden assignment page
            // return
        // check can grade
            // display link to grading interface
        // check can submit
            // display current submission status
            // check submissions open
            // display submit interface
    }
    
    /**
     * Show the screen for creating an assignment submission
     *
     */
    function view_submit() {
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
    

    /**
     * Add this instance to the database
     */
    function add_instance() {
        // call pre_create hook (for subtypes)
        // validation check
        // add the database record
        // add event to the calendar
        // add the item in the gradebook
        // call post_create hook (for subtypes)
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
}
