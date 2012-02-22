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
 * This file contains the definition for the library class for onlinetext
 *  submission plugin 
 * 
 * This class provides all the functionality for the new assign module.
 *
 * @package   mod_assign
 * @subpackage submission_onlinetext
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
/**
 * File area for online text submission assignment
 */
define('ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT', 'submissions_onlinetext');

/*
 * library class for onlinetext submission plugin extending submission plugin
 * base class
 * 
 * @package   mod_assign
 * @subpackage submission_onlinetext
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_onlinetext extends submission_plugin {
    
    /** @var object the assignment record that contains the global settings for this assign instance */
    private $instance;
       
    /**
     * get the name of the online text submission plugin
     * @return string 
     */
    public function get_name() {
        return get_string('onlinetext', 'submission_onlinetext');
    }


   /**
    * get onlinetext submission information from the database   
    * 
    * @global object $DB
    * @param  int $submissionid
    * @return mixed 
    */
    private function get_onlinetext_submission($submissionid) {
        global $DB;
        
        return $DB->get_record('assign_submission_onlinetext', array('submission'=>$submissionid));
    }
    
    /**
     * get submission form elements for settings
     * @global object $USER
     * @param object $submission
     * @param object $data
     * @return string 
     */
    public function get_form_elements($submission, & $data) {
        global $USER;
        
        
        $elements = array();

        $editoroptions = $this->get_edit_options();
        $submissionid = $submission ? $submission->id : 0;
        
        if (!isset($data->onlinetext)) {
            $data->onlinetext = '';
        } 
        if (!isset($data->onlinetextformat)) {
            $data->onlinetextformat = editors_get_preferred_format();
        } 
        
        if ($submission) {
            $onlinetext_submission = $this->get_onlinetext_submission($submission->id);
            if ($onlinetext_submission) {
                $data->onlinetext = $onlinetext_submission->onlinetext;
                $data->onlinetextformat = $onlinetext_submission->onlineformat;
            }
            
        }
        
        
        $data = file_prepare_standard_editor($data, 'onlinetext', $editoroptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $submissionid);      
        
        $elements[] = array('type'=>'editor', 'name'=>'onlinetext_editor', 'description'=>'', 'options'=>$editoroptions);
  
        return $elements;
    }
    
    /**
     * editor format options
     * 
     * @return mixed
     */
    private function get_edit_options() {
         $editoroptions = array(
           'noclean' => false,
           'maxfiles' => EDITOR_UNLIMITED_FILES,
           'maxbytes' => $this->assignment->get_course()->maxbytes,
           'context' => $this->assignment->get_context()
        );
        return $editoroptions;
    }

     /**
      * save data to the database
      * @global object $USER
      * @global object $DB
      * @param object $submission
      * @param object $data
      * @return mixed 
      */
     public function save($submission, $data) {     
       
        global $USER, $DB;

        $editoroptions = $this->get_edit_options();
        
        $data = file_postupdate_standard_editor($data, 'onlinetext', $editoroptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $submission->id);

        $onlinetext_submission = $this->get_onlinetext_submission($submission->id);
        if ($onlinetext_submission) {
            
            $onlinetext_submission->onlinetext = $data->onlinetext;
            $onlinetext_submission->onlineformat = $data->onlinetext_editor['format'];
            
          
            return $DB->update_record('assign_submission_onlinetext', $onlinetext_submission);
        } else {
           
            $onlinetext_submission = new stdClass();
            $onlinetext_submission->onlinetext = $data->onlinetext;
            $onlinetext_submission->onlineformat = $data->onlinetext_editor['format'];
               
            $onlinetext_submission->submission = $submission->id;
            $onlinetext_submission->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assign_submission_onlinetext', $onlinetext_submission) > 0;
        }
        
     
    }
    
    /**
     * get the saved text content from the editor
     * @param string $name
     * @param int $submissionid
     * @return string 
     */
    public function get_editor_text($name, $submissionid) {
        if ($name == 'onlinetext') {
            $onlinetext_submission = $this->get_onlinetext_submission($submissionid);
            if ($onlinetext_submission) {
                return $onlinetext_submission->onlinetext;
            }
        }

        return '';
    }
    
    /**
     * get the content format for the editor 
     * @param string $name
     * @param int $submissionid
     * @return bool
     */
    public function get_editor_format($name, $submissionid) {
        if ($name == 'onlinetext') {
            $onlinetext_submission = $this->get_onlinetext_submission($submissionid);
            if ($onlinetext_submission) {
                return $onlinetext_submission->onlineformat;
            }
        }
     
         
         return 0;
    }
    
    
     /**
      * display onlinetext word count in the submission status table 
      * @global object $OUTPUT
      * @global object $USER
      * @param object $submission
      * @return string 
      */
    public function view_summary($submission) {
        global $OUTPUT,$USER;
         
        $onlinetext_submission = $this->get_onlinetext_submission($submission->id);

        if ($onlinetext_submission) {
            $text = format_text($onlinetext_submission->onlinetext);
            $shorttext = shorten_text($text, 140);
            if ($text != $shorttext) {  
                return get_string('numwords', '', count_words(format_text($onlinetext_submission->onlinetext)));                    
            } else {
                return $shorttext;
            }
        }    
        return '';
    }

    /**
     * Produce a list of files suitable for export that represent this submission
     * 
     * @param object $submission - For this is the submission data
     * @return array - return an array of files indexed by filename
     */
    public function get_files($submission) {
        $onlinetext_submission = $this->get_onlinetext_submission($submission->id);
        if ($onlinetext_submission) {
            $submissioncontent = "<html><body>". format_text($onlinetext_submission->onlinetext, $onlinetext_submission->onlineformat). "</body></html>";      //fetched from database

            return array(get_string('onlinetextfilename', 'submission_onlinetext') => array($submissioncontent));
        }

        return array();
    }

    /**
     * Should the assignment module show a link to view the full submission or feedback for this plugin?
     *
     * @return bool
     */
    public function show_view_link($submission) {
        $onlinetext_submission = $this->get_onlinetext_submission($submission->id);
        if ($onlinetext_submission) {
            $text = format_text($onlinetext_submission->onlinetext, $onlinetext_submission->onlineformat);
            return shorten_text($text, 140) != $text;
        }
        return false;
    }
    
    /**
     * display the saved text content from the editor in the view table 
     * @param object $submission
     * @return string  
     */
    public function view($submission) {
        $result = '';
        
        $onlinetext_submission = $this->get_onlinetext_submission($submission->id);
        
        
        if ($onlinetext_submission) {
            
            // render for portfolio API
            $result .= $this->assignment->render_editor_content(ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $onlinetext_submission->submission, $this->get_type(), 'onlinetext');
                       
        } 
        
        return $result;
    }
    
     /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     * 
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        if ($type == 'online' && $version >= 2011112900) {
            return true;
        }
        return false;
    }
  
    
     /**
     * Upgrade the settings from the old assignment 
     * to the new plugin based one
     * 
     * @param object $oldcontext - the database for the old assignment context
     * @param object $oldassignment - the database for the old assignment instance
     * @param string log record log events here
     * @return bool Was it a success?
     */
    public function upgrade_settings($oldcontext, $oldassignment, & $log) {
        // first upgrade settings (nothing to do)
        return true;
    }
     
    /**
     * Upgrade the submission from the old assignment to the new one
     * 
     * @param object $oldcontext - the database for the old assignment context
     * @param object $oldassignment The data record for the old assignment
     * @param object $oldsubmission The data record for the old submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade($oldcontext, $oldassignment, $oldsubmission, $submission, & $log) {
        global $DB;
        
       $comments_submission = new stdClass();
        $onlinetext_submission->onlinetext = $oldsubmission->data1;
        $onlinetext_submission->onlineformat = $oldsubmission->data2;
               
        $onlinetext_submission->submission = $submission->id;
        $onlinetext_submission->assignment = $this->assignment->get_instance()->id;
        if (!$DB->insert_record('assign_submission_onlinetext', $onlinetext_submission) > 0) {
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
                                                        ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, 
                                                        $submission->id);
        return true;
    }
}


