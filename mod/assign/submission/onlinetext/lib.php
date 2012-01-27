<?php

//define('ASSIGN_MAX_SUBMISSION_FILES', 20);
//define('ASSIGN_FILEAREA_SUBMISSION_FILES', 'submissions_files');

define('ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT', 'submissions_onlinetext');


class submission_onlinetext extends submission_plugin {
    private $instance;

    public function get_name() {
        return get_string('onlinetext', 'submission_onlinetext');
    }

    
    
    private function get_instance() {
        global $DB;
        if ($this->instance) {
            return $this->instance;
        }
        $assignment = $this->assignment->get_instance();
        if ($assignment) {
            $this->instance = $DB->get_record('assign_submission_onlinetext_settings', array('assignment'=>$assignment->id));
        }
    
        return $this->instance;
    }

    private function get_submission($submissionid) {
        global $DB;
        return $DB->get_record('assign_submission_onlinetext', array('submission'=>$submissionid));
    }
    
    public function get_settings() {
        // global $CFG, $COURSE;

        $current_settings = $this->get_instance();
        $settings = array();
        $options = array();
        $default_enabled_allowonlinetextsubmissions = $current_settings?$current_settings->enabled:0;
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
         
          
           
         
          $settings[] = array('type' => 'select', 
                            'name' => 'allowonlinetextsubmissions', 
                            'description' => get_string('allowonlinetextsubmissions', 'submission_onlinetext'), 
                            'options'=>$ynoptions,
                            'default'=>$default_enabled_allowonlinetextsubmissions);
        
    
          
          
      

      
        return $settings;

    }

    public function save_settings($mform) {
        global $DB;

        $onlinetext_settings = $this->get_instance();

        if ($onlinetext_settings) {
            $onlinetext_settings->enabled = $mform->allowonlinetextsubmissions;

            return $DB->update_record('assign_submission_onlinetext_settings', $onlinetext_settings);
        } else {
            $onlinetext_settings = new stdClass();
            $onlinetext_settings->assignment = $this->assignment->get_instance()->id;
           
            $onlinetext_settings->enabled = $mform->allowonlinetextsubmissions;
            return $DB->insert_record('assign_submission_onlinetext_settings', $onlinetext_settings) > 0;
        }
    }
  
    public function is_enabled() {
        $onlinetext_settings = $this->get_instance();
        if (!$onlinetext_settings) {
            return false;
        }
        return $onlinetext_settings->enabled;
    }
   
    public function get_submission_form_elements($submission, & $data) {
        global $USER;
        $onlinetext_settings = $this->get_instance();
        $elements = array();

        if (!$this->is_enabled()) {
            return $elements;
        }
        

        $editoroptions = $this->get_edit_options();
        $submissionid = $submission ? $submission->id : 0;
        
      
        // $fs = get_file_storage();
        $data = file_prepare_standard_editor($data, 'onlinetext', $editoroptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $submissionid);      
        
        $elements[] = array('type'=>'editor', 'name'=>'onlinetext_editor', 'description'=>'', 'options'=>$editoroptions);
  
        if ($submission) {
            $onlinetext_submission = $this->get_submission($submission->id);
            if ($onlinetext_submission) {
                $data->onlinetext_editor['text'] = $onlinetext_submission->onlinetext;
                $data->onlinetext_editor['format'] = $onlinetext_submission->onlineformat;
            }
            
        }
        return $elements;
    }
    
    
    private function get_edit_options() {
         $editoroptions = array(
           'noclean' => false,
           'maxfiles' => EDITOR_UNLIMITED_FILES,
           'maxbytes' => $this->assignment->get_course()->maxbytes,
           'context' => $this->assignment->get_context()
        );
        return $editoroptions;
    }

    
     public function save($submission, $data) {     
       
        global $USER, $DB;

        $settings = $this->get_instance();

        if (!$settings->enabled) {
            return true;
        }
        $editoroptions = $this->get_edit_options();
        

        $data = file_postupdate_standard_editor($data, 'onlinetext', $editoroptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $submission->id);

        
        $onlinetext_submission = $this->get_submission($submission->id);
        if ($onlinetext_submission) {
            
            $onlinetext_submission->onlinetext = $data->onlinetext_editor['text'];
            $onlinetext_submission->onlineformat = $data->onlinetext_editor['format'];
            
          
            return $DB->update_record('assign_submission_onlinetext', $onlinetext_submission);
        } else {
           
            $onlinetext_submission = new stdClass();
            $onlinetext_submission->onlinetext = $data->onlinetext_editor['text'];
            $onlinetext_submission->onlineformat = $data->onlinetext_editor['format'];
               
            $onlinetext_submission->submission = $submission->id;
            $onlinetext_submission->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assign_submission_onlinetext', $onlinetext_submission) > 0;
        }
        
     
    }
    
    
    
    
     public function view_summary($submission) {
        $online_submission = $this->get_submission($submission->id);
        if ($online_submission) {
            return shorten_text(format_text($online_submission->onlinetext));
        }
        return '';
    }
    
    public function view($submission) {
        $online_submission = $this->get_submission($submission->id);
        if ($online_submission) {
            $text = file_rewrite_pluginfile_urls($online_submission->onlinetext, 'pluginfile.php', $this->assignment->get_context()->id, 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $submission->id);
            return format_text($text, $online_submission->onlineformat, array('overflowdiv' => true));
        } 
        return '';
    }
    
    
    
    
    
    
    
}


