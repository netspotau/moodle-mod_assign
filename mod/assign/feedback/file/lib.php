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
 * This file contains the definition for the library class for file
 *  feedback plugin 
 * 
 * This class provides all the functionality for the new assign module.
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
/**
 * File areas for file feedback assignment
 */
define('ASSIGN_MAX_FEEDBACK_FILES', 20);
define('ASSIGN_FILEAREA_FEEDBACK_FILES', 'feedback_files');

/*
 * library class for file feedback plugin extending feedback plugin
 * base class
 * 
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feedback_file extends feedback_plugin {
    
    /** @var object the assignment record that contains the global settings for this assign instance */
    private $instance;

    
    /**
     * get the name of the file feedback plugin
     * @return string 
     */
    public function get_name() {
        return get_string('file', 'feedback_file');
    }
    
    /**
     * get file feedback information from the database  
     *  
     * @global object $DB
     * @param int $gradeid
     * @return mixed 
     */
    private function get_file_feedback($gradeid) {
        global $DB;
        return $DB->get_record('assign_feedback_file', array('grade'=>$gradeid));
    }
    
    /**
     * get the default setting for file feedback plugin
     * @global object $CFG
     * @global object $COURSE
     * @global object $DB
     * @return mixed
     */
    public function get_settings() {
        global $CFG, $COURSE, $DB;

        $default_maxfiles = $this->get_config('maxfilesubmissions');
        $default_maxsizebytes = $this->get_config('maxsubmissionsizebytes');

        $settings = array();
        $options = array();
        for($i = 1; $i <= ASSIGN_MAX_FEEDBACK_FILES; $i++) {
            $options[$i] = $i;
        }
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        
        $settings[] = array('type' => 'select', 
                            'name' => 'maxfiles', 
                            'description' => get_string('maxfiles', 'feedback_file'), 
                            'options'=>$options, 'default'=>$default_maxfiles);

        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $settings[] = array('type' => 'select', 
                            'name' => 'maxsizebytes', 
                            'description' => get_string('maximumsize', 'feedback_file'), 
                            'options'=>$choices,
                            'default'=>$default_maxsizebytes);

        return $settings;

    }
    
    /**
     * save the settings for file submission plugin 
     * @param object $mform
     * @return bool 
     */
    public function save_settings($mform) {
        $this->set_config('maxfiles', $mform->maxfiles);
        $this->set_config('maxsizebytes', $mform->maxsizebytes);
        return true;
    }

    /**
     * file format options 
     * @return mixed
     */
    private function get_file_options() {
        $fileoptions = array('subdirs'=>1,
                                'maxbytes'=>$this->get_config('maxsizebytes'),
                                'maxfiles'=>$this->get_config('maxfiles'),
                                'accepted_types'=>'*',
                                'return_types'=>FILE_INTERNAL);
        return $fileoptions;
    }
   
    /**
     * get form elements for settings
     * 
     * @param object $submission
     * @param object $data
     * @return mixed 
     */
    public function get_form_elements($grade, & $data) {

        $elements = array();

        if ($this->get_config('maxfiles') <= 0) {
            return $elements;
        }

        $fileoptions = $this->get_file_options();
        $gradeid = $grade ? $grade->id : 0;


        $data = file_prepare_standard_filemanager($data, 'files', $fileoptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_FEEDBACK_FILES, $gradeid);
        
        $elements[] = array('type'=>'filemanager', 'name'=>'files_filemanager', 'description'=>'', 'options'=>$fileoptions);

        return $elements;
    }

    /**
     * count the number of files
     * 
     * @global object $USER
     * @param int $gradeid
     * @param string $area
     * @return int 
     */
    private function count_files($gradeid = 0, $area = ASSIGN_FILEAREA_FEEDBACK_FILES) {
        global $USER;

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id, 'mod_assign', $area, $gradeid, "id", false);

        return count($files);
    }

    /**
     * save the files
     * @global object $USER
     * @global object $DB
     * @param object $grade
     * @param object $data
     * @return mixed 
     */
    public function save($grade, $data) {

        global $USER, $DB;

        $fileoptions = $this->get_file_options();
        

        $data = file_postupdate_standard_filemanager($data, 'files', $fileoptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_FEEDBACK_FILES, $grade->id);

        
        $file_feedback = $this->get_file_feedback($grade->id);
        if ($file_feedback) {
            $file_feedback->numfiles = $this->count_files($grade->id);
            return $DB->update_record('assign_feedback_file', $file_feedback);
        } else {
            $file_feedback = new stdClass();
            $file_feedback->numfiles = $this->count_files($grade->id);
            $file_feedback->grade = $grade->id;
            $file_feedback->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assign_feedback_file', $file_feedback) > 0;
        }
    }
    
    /**
     * display the list of files  in the feedback status table 
     * @param object $feedback
     * @return string
     */
    public function view_summary($grade) {
        return $this->assignment->render_area_files(ASSIGN_FILEAREA_FEEDBACK_FILES, $grade->id);
    }
    
    /**
     * display the list of files  in the feedback status table 
     * @param object $grade
     * @return string 
     */
    public function view($grade) {
        return $this->view_summary($grade);
    }
    
}
