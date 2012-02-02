<?php

define('ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT', 'submissions_onlinetext');


class submission_onlinetext extends submission_plugin {
    private $instance;

    public function get_name() {
        return get_string('onlinetext', 'submission_onlinetext');
    }



    private function get_onlinetext_submission($submissionid) {
        global $DB;
        
        return $DB->get_record('assign_submission_onlinetext', array('submission'=>$submissionid));
    }
    
    public function get_submission_form_elements($submission, & $data) {
        global $USER;
        
        $elements = array();

        $editoroptions = $this->get_edit_options();
        $submissionid = $submission ? $submission->id : 0;
        
      
        
        $data = file_prepare_standard_editor($data, 'onlinetext', $editoroptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $submissionid);      
        
        $elements[] = array('type'=>'editor', 'name'=>'onlinetext_editor', 'description'=>'', 'options'=>$editoroptions);
  
        if ($submission) {
            $onlinetext_submission = $this->get_onlinetext_submission($submission->id);
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

        $editoroptions = $this->get_edit_options();
        

        $data = file_postupdate_standard_editor($data, 'onlinetext', $editoroptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $submission->id);

        
        $onlinetext_submission = $this->get_onlinetext_submission($submission->id);
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
    
    
    public function get_editor_text($name, $submissionid) {
        if ($name == 'onlinetext') {
            $onlinetext_submission = $this->get_onlinetext_submission($submissionid);
            if ($onlinetext_submission) {
                return $onlinetext_submission->onlinetext;
            }
        }

        return '';
    }

    public function get_editor_format($name, $submissionid) {
        if ($name == 'onlinetext') {
            $onlinetext_submission = $this->get_onlinetext_submission($submissionid);
            if ($onlinetext_submission) {
                return $onlinetext_submission->onlineformat;
            }
        }
     
         
         return 0;
    }
    
    
    
    
     public function view_summary($submission) {
         global $OUTPUT,$USER;
         
         
         
           $link = new moodle_url ('/mod/assign/submission/onlinetext/onlinetext_view.php?id='.$this->assignment->get_course_module()->id.'&sid='.$submission->id.'&plugintype=onlinetext&returnaction='.  optional_param('action','view',PARAM_ALPHA).'&returnparams=rownum%3D'.  optional_param('rownum','', PARAM_INT));
         $onlinetext_submission = $this->get_onlinetext_submission($submission->id);
        if (!$onlinetext_submission) {
                return get_string('numwords', '', 0);                                                     
            } else if(count_words(format_text($onlinetext_submission->onlinetext)) < 1){                           
                return get_string('numwords', '', count_words(format_text($onlinetext_submission->onlinetext)));                                                     
            } else{    
                             
                return $OUTPUT->action_link($link,get_string('numwords', '', count_words(format_text($onlinetext_submission->onlinetext))));
            }    
        return '';
        
       
    }
    
  
    public function view($submission) {
        $result = '';
        
        $onlinetext_submission = $this->get_onlinetext_submission($submission->id);
        
        
        if ($onlinetext_submission) {
            
            // render for portfolio API
            $result .= $this->assignment->render_editor_content(ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $onlinetext_submission->submission, $this->get_type(), 'onlinetext');
            
            //$text = file_rewrite_pluginfile_urls($onlinetext_submission->onlinetext, 'pluginfile.php', $this->assignment->get_context()->id, 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_ONLINETEXT, $onlinetext_submission->submission);
            //$result .= format_text($text, $onlinetext_submission->onlineformat, array('overflowdiv' => true));
        } 
        
        
       
        
        
        return $result;
    }
    
  
    
}


