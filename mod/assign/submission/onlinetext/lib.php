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
 * @subpackage assignsubmission_onlinetext
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_submission_onlinetext extends assignment_submission_plugin {
    
    /**
     * get the name of the online text submission plugin
     * @return string 
     */
    public function get_name() {
        return get_string('onlinetext', 'assignsubmission_onlinetext');
    }


   /**
    * get onlinetext submission information from the database   
    * 
    * @global moodle_database $DB
    * @param  int $submissionid
    * @return mixed 
    */
    private function get_onlinetext_submission($submissionid) {
        global $DB;
        
        return $DB->get_record('assign_submission_onlinetext', array('submission'=>$submissionid));
    }
    
    /**
     * add form elements for settings
     * 
     * @param mixed $submission|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form 
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
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
            $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);
            if ($onlinetextsubmission) {
                $data->onlinetext = $onlinetextsubmission->onlinetext;
                $data->onlinetextformat = $onlinetextsubmission->onlineformat;
            }
            
        }
        
        
        $data = file_prepare_standard_editor($data, 'onlinetext', $editoroptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $submissionid);      
        $mform->addElement('editor', 'onlinetext_editor', '', null, $editoroptions);
        return true;
    }
    
    /**
     * editor format options
     * 
     * @return array
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
      * @global moodle_database $DB
      * @param object $submission
      * @param object $data
      * @return bool 
      */
     public function save(stdClass $submission, stdClass $data) {     
        global $DB;

        $editoroptions = $this->get_edit_options();
        
        $data = file_postupdate_standard_editor($data, 'onlinetext', $editoroptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $submission->id);

        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);
        if ($onlinetextsubmission) {
            
            $onlinetextsubmission->onlinetext = $data->onlinetext;
            $onlinetextsubmission->onlineformat = $data->onlinetext_editor['format'];
            
          
            return $DB->update_record('assign_submission_onlinetext', $onlinetextsubmission);
        } else {
           
            $onlinetextsubmission = new stdClass();
            $onlinetextsubmission->onlinetext = $data->onlinetext;
            $onlinetextsubmission->onlineformat = $data->onlinetext_editor['format'];
               
            $onlinetextsubmission->submission = $submission->id;
            $onlinetextsubmission->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assign_submission_onlinetext', $onlinetextsubmission) > 0;
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
            $onlinetextsubmission = $this->get_onlinetext_submission($submissionid);
            if ($onlinetextsubmission) {
                return $onlinetextsubmission->onlinetext;
            }
        }

        return '';
    }
    
    /**
     * get the content format for the editor 
     * @param string $name
     * @param int $submissionid
     * @return int
     */
    public function get_editor_format($name, $submissionid) {
        if ($name == 'onlinetext') {
            $onlinetextsubmission = $this->get_onlinetext_submission($submissionid);
            if ($onlinetextsubmission) {
                return $onlinetextsubmission->onlineformat;
            }
        }
     
         
         return 0;
    }
    
    
     /**
      * display onlinetext word count in the submission status table 
      * @param stdClass $submission
      * @return string 
      */
    public function view_summary(stdClass $submission) {
         
        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);

        if ($onlinetextsubmission) {
            $text = format_text($onlinetextsubmission->onlinetext, $onlinetextsubmission->onlineformat, array('context'=>$this->assignment->get_context()));
            $shorttext = shorten_text($text, 140);
            if ($text != $shorttext) {  
                return get_string('numwords', '', count_words($text));                    
            } else {
                return $shorttext;
            }
        }    
        return '';
    }

    /**
     * Produce a list of files suitable for export that represent this submission
     * 
     * @global moodle_database $DB
     * @param stdClass $submission - For this is the submission data
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission) {
        global $DB;
        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);
        $result = array();
        if ($onlinetextsubmission) {
            $fs = get_file_storage();

            $files = $fs->get_area_files($this->assignment->get_context()->id, 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $submission->id, "timemodified", false);

            foreach ($files as $file) {
                $result[$file->get_filename()] = $file;
            }
            
            $user = $DB->get_record("user", array("id"=>$submission->userid),'id,username,firstname,lastname', MUST_EXIST); 

            if (!$this->assignment->is_blind_marking()) {
                $prefix = clean_filename(str_replace('_', '', fullname($user)) . '_' . $this->assignment->get_uniqueid_for_user($userid) . '_' . $this->get_name() . '_');
            } else {
                $prefix = clean_filename(get_string('participant', 'assign') . '_' . $this->assignment->get_uniqueid_for_user($userid) . '_' . $this->get_name() . '_');
            }


            $text = format_text($onlinetextsubmission->onlinetext, $onlinetextsubmission->onlineformat, array('context'=>$this->assignment->get_context()));      //fetched from database
            $submissioncontent = '<html><body>'. str_replace('@@PLUGINFILE@@/', $prefix, $text). '</body></html>';

            $result[get_string('onlinetextfilename', 'assignsubmission_onlinetext')] = array($submissioncontent);
        }

        return $result;
    }

    /**
     * display the saved text content from the editor in the view table 
     * @param stdClass $submission
     * @return string  
     */
    public function view(stdClass $submission) {
        $result = '';
        
        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);
        
        
        if ($onlinetextsubmission) {
            
            // render for portfolio API
            $result .= $this->assignment->render_editor_content(ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $onlinetextsubmission->submission, $this->get_type(), 'onlinetext');
                       
        } 
        
        return $result;
    }
    
     /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     * 
     * @param string old assignment subtype
     * @param int old assignment version
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
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment - the database for the old assignment instance
     * @param string log record log events here
     * @return bool Was it a success?
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, $log) {
        // first upgrade settings (nothing to do)
        return true;
    }
     
    /**
     * Upgrade the submission from the old assignment to the new one
     * 
     * @global moodle_database $DB
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment The data record for the old assignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $submission The data record for the new submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmission, stdClass $submission, $log) {
        global $DB;
        
        $commentssubmission = new stdClass();
        $onlinetextsubmission->onlinetext = $oldsubmission->data1;
        $onlinetextsubmission->onlineformat = $oldsubmission->data2;
               
        $onlinetextsubmission->submission = $submission->id;
        $onlinetextsubmission->assignment = $this->assignment->get_instance()->id;
        if (!$DB->insert_record('assign_submission_onlinetext', $onlinetextsubmission) > 0) {
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
    
    /**
     * formatting for log info    
     *
     * @param stdClass $submission The new submission 
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // format the info for each submission plugin add_to_log
        $onlinetextsubmission = $this->get_onlinetext_submission($submission->id);
        $onlinetextloginfo = '';
        $text = format_text($onlinetextsubmission->onlinetext,
                            $onlinetextsubmission->onlineformat, 
                            array('context'=>$this->assignment->get_context()));
        $onlinetextloginfo .= 'Onlinetext word count : ' . get_string('numwords', '', count_words($text)) . " <br>";

        return $onlinetextloginfo;
    }

    /**
     * The assignment has been deleted - cleanup
     * 
     * @global moodle_database $DB
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // will throw exception on failure
        $DB->delete_records('assign_submission_onlinetext', array('assignment'=>$this->assignment->get_instance()->id));
        
        return true;
    }

    /**
     * No text is set for this plugin
     * 
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        return $this->view($submission) == '';
    }
}


