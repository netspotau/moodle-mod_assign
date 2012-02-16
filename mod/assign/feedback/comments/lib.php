<?php

class feedback_comments extends feedback_plugin {

    private $instance;

    public function get_name() {
        return get_string('pluginname', 'feedback_comments');
    }
    
    private function get_feedback_comments($gradeid) {
        global $DB;
        return $DB->get_record('assign_feedback_comments', array('grade'=>$gradeid));
    }

    public function get_form_elements($grade, & $data) {
        $elements = array();

       
        $gradeid = $grade ? $grade->id : 0;
        $default_comment = '';
        if ($grade) {
            $feedback_comments = $this->get_feedback_comments($grade->id);
            if ($feedback_comments) {
                $data->feedbackcomments_editor['text'] = $feedback_comments->commenttext;
                $data->feedbackcomments_editor['format'] = $feedback_comments->commentformat;
            }
        }


        $elements[] = array('type'=>'editor', 'name'=>'feedbackcomments_editor', 'description'=>'', 'paramtype'=>PARAM_RAW, 'options'=>null);

        return $elements;
    }

    public function save($grade, $data) {

        global $USER, $DB;


        $feedback_comment = $this->get_feedback_comments($grade->id);
        if ($feedback_comment) {
            $feedback_comment->commenttext = $data->feedbackcomments_editor['text'];
            $feedback_comment->commentformat = $data->feedbackcomments_editor['format'];
            return $DB->update_record('assign_feedback_comments', $feedback_comment);
        } else {
            $feedback_comment = new stdClass();
            $feedback_comment->commenttext = $data->feedbackcomments_editor['text'];
            $feedback_comment->commentformat = $data->feedbackcomments_editor['format'];
            $feedback_comment->grade = $grade->id;
            $feedback_comment->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assign_feedback_comments', $feedback_comment) > 0;
        }
    }

    public function view_summary($grade) {
        $feedback_comments = $this->get_feedback_comments($grade->id);
        if ($feedback_comments) {
            return shorten_text(format_text($feedback_comments->commenttext, $feedback_comments->commentformat));
        }
        return '';
    }
    
    public function view($grade) {
        $feedback_comments = $this->get_feedback_comments($grade->id);
        if ($feedback_comments) {
            return format_text($feedback_comments->commenttext, $feedback_comments->commentformat);
        } 
        return '';
    }

}
