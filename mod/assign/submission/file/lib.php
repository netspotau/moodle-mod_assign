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

/** Include eventslib.php */
require_once($CFG->libdir.'/eventslib.php');
/**
 * This file contains the definition for the library class for file
 *  submission plugin 
 * 
 * This class provides all the functionality for the new assign module.
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
/**
 * File areas for file submission assignment
 */
define('ASSIGN_MAX_SUBMISSION_FILES', 20);
define('ASSIGN_FILEAREA_SUBMISSION_FILES', 'submission_files');

/*
 * library class for file submission plugin extending submission plugin
 * base class
 * 
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_file extends submission_plugin {
    
    /** @var object the assignment record that contains the global settings for this assign instance */
    private $instance;

    
    /**
     * get the name of the file submission plugin
     * @return string 
     */
    public function get_name() {
        return get_string('file', 'submission_file');
    }
    
    /**
     * get file submission information from the database  
     * 
     * @access private
     * @global object $DB
     * @param int $submissionid
     * @return mixed 
     */
    private function get_file_submission($submissionid) {
        global $DB;
        return $DB->get_record('assign_submission_file', array('submission'=>$submissionid));
    }
    
    /**
     * get the default setting for file submission plugin
     * @global object $CFG
     * @global object $COURSE
     * @global object $DB
     * @return mixed
     */
    public function get_settings() {
        global $CFG, $COURSE, $DB;

        $default_maxfilesubmissions = $this->get_config('maxfilesubmissions');
        $default_maxsubmissionsizebytes = $this->get_config('maxsubmissionsizebytes');

        $settings = array();
        $options = array();
        for($i = 1; $i <= ASSIGN_MAX_SUBMISSION_FILES; $i++) {
            $options[$i] = $i;
        }
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        
        $settings[] = array('type' => 'select', 
                            'name' => 'maxfilesubmissions', 
                            'description' => get_string('maxfilessubmission', 'submission_file'), 
                            'options'=>$options, 'default'=>$default_maxfilesubmissions);

        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $settings[] = array('type' => 'select', 
                            'name' => 'maxsubmissionsizebytes', 
                            'description' => get_string('maximumsubmissionsize', 'submission_file'), 
                            'options'=>$choices,
                            'default'=>$default_maxsubmissionsizebytes);

        return $settings;

    }
    
    /**
     * save the settings for file submission plugin 
     * @param object $mform
     * @return bool 
     */
    public function save_settings($mform) {
        $this->set_config('maxfilesubmissions', $mform->maxfilesubmissions);
        $this->set_config('maxsubmissionsizebytes', $mform->maxsubmissionsizebytes);
        return true;
    }

    /**
     * file format options 
     * 
     * @access private
     * @return mixed
     */
    private function get_file_options() {
        $fileoptions = array('subdirs'=>1,
                                'maxbytes'=>$this->get_config('maxsubmissionsizebytes'),
                                'maxfiles'=>$this->get_config('maxfilesubmissions'),
                                'accepted_types'=>'*',
                                'return_types'=>FILE_INTERNAL);
        return $fileoptions;
    }
   
    /**
     * get submission form elements for settings
     * 
     * @param object $submission
     * @param object $data
     * @return mixed 
     */
    public function get_form_elements($submission, & $data) {

        $elements = array();

        if ($this->get_config('maxfilesubmissions') <= 0) {
            return $elements;
        }

        $fileoptions = $this->get_file_options();
        $submissionid = $submission ? $submission->id : 0;


        $data = file_prepare_standard_filemanager($data, 'files', $fileoptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_FILES, $submissionid);
        
        $elements[] = array('type'=>'filemanager', 'name'=>'files_filemanager', 'description'=>'', 'options'=>$fileoptions);

        return $elements;
    }

    /**
     * count the number of files
     * 
     * @access private
     * @global object $USER
     * @param int $submissionid
     * @param string $area
     * @return int 
     */
    private function count_files($submissionid = 0, $area = ASSIGN_FILEAREA_SUBMISSION_FILES) {
        global $USER;

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id, 'mod_assign', $area, $submissionid, "id", false);

        return count($files);
    }

    /**
     * save the files and trigger plagiarism plugin, if enabled, to scan the uploaded files via events trigger
     * @global object $USER
     * @global object $DB
     * @param object $submission
     * @param object $data
     * @return mixed 
     */
    public function save($submission, $data) {

        global $USER, $DB;

        $fileoptions = $this->get_file_options();


        $data = file_postupdate_standard_filemanager($data, 'files', $fileoptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_FILES, $submission->id);


        $file_submission = $this->get_file_submission($submission->id);

        //plagiarism code event trigger when files are uploaded

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id, 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_FILES, $submission->id, "id", false);
           
            // send files to event system
            // Let Moodle know that an assessable file was uploaded (eg for plagiarism detection)
            $eventdata = new stdClass();
            $eventdata->modulename = 'assign';
            $eventdata->cmid = $this->assignment->get_course_module()->id;
            $eventdata->itemid = $submission->id;
            $eventdata->courseid = $this->assignment->get_course()->id;
            $eventdata->userid = $USER->id;
            if ($files) {
                $eventdata->files = $files;
            }
        events_trigger('assessable_file_uploaded', $eventdata);


        if ($file_submission) {
            $file_submission->numfiles = $this->count_files($submission->id);
            return $DB->update_record('assign_submission_file', $file_submission);
        } else {
            $file_submission = new stdClass();
            $file_submission->numfiles = $this->count_files($submission->id);
            $file_submission->submission = $submission->id;
            $file_submission->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assign_submission_file', $file_submission) > 0;
        }
    }

    /**
     * Produce a list of files suitable for export that represent this feedback or submission
     * 
     * @param object $submission_grade - For submission plugins this is the submission data, for feedback plugins it is the grade data
     * @return array - return an array of files indexed by filename
     */
    public function get_files($submission) {
        global $DB;
        $result = array();
        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id, 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_FILES, $submission->id, "timemodified", false);

        foreach ($files as $file) {
            $result[$file->get_filename()] = $file;
        }
        return $result;
    }
    
    /**
     * display the list of files  in the submission status table 
     * @param object $submission
     * @return string
     */
    public function view_summary($submission) {
        return $this->assignment->render_area_files(ASSIGN_FILEAREA_SUBMISSION_FILES, $submission->id);
    }
    
    /**
     * display the list of files  in the submission status table 
     * @param object $submission
     * @return string 
     */
    public function view($submission) {
        return $this->view_summary($submission);
    }
    


 /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     * 
     * @return boolean True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        
        $uploadsingle_type ='uploadsingle';
        $upload_type ='upload';
        
        if (($type == $uploadsingle_type || $type == $upload_type) && $version >= 2011112900) {
            return true;
        }
        return false;
    }
  
    
     /**
     * Upgrade the settings from the old assignment 
     * to the new plugin based one
     * 
     * @param data - the database for the old assignment instance
     * @param string log record log events here
     * @return boolean Was it a success?
     */
    public function upgrade_settings($oldassignment, & $log) {
        // first upgrade settings (nothing to do)
        return true;
    }
     
    /**
     * Upgrade the submission from the old assignment to the new one
     * 
     * @param object $oldassignment The data record for the old oldassignment
     * @param object $oldsubmission The data record for the old submission
     * @param string $log Record upgrade messages in the log
     * @return boolean true or false - false will trigger a rollback
     */
    public function upgrade_submission($oldcontext,$oldassignment, $oldsubmission, $submission, & $log) {
        global $DB;

        $file_submission = new stdClass();
        
           
        
        $file_submission->numfiles = $oldsubmission->numfiles;
        $file_submission->submission = $submission->id;
        $file_submission->assignment = $this->assignment->get_instance()->id;
        
        // if note in old advanced uploading type enabled then
        // the content of is goes to submission comment in the new one 
        
        
        
        if (!$DB->insert_record('assign_submission_file', $file_submission) > 0) {
            $log .= get_string('couldnotconvertsubmission', 'mod_assign', $submission->userid);
            return false;
        }

        
        
        
        // now copy the area files
        $this->assignment->copy_area_files_for_upgrade($oldcontext->id, 
                                                        'mod_assignment', 
                                                        'submission', 
                                                        $oldsubmission->id,
                                                        // New file area
                                                        $this->assignment->get_context()->id, 
                                                        'mod_assign', 
                                                        ASSIGN_FILEAREA_SUBMISSION_FILES, 
                                                        $submission->id);
        
        
       
        
        
        return true;
    }
}