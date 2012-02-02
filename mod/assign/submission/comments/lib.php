<?php

class submission_comments extends submission_plugin {

    private $instance;

    public function get_name() {
        return get_string('pluginname', 'submission_comments');
    }
    
   
    
    private function get_submission_comment($submissionid) {
        global $DB;
        return $DB->get_record('assign_submission_comments', array('submission'=>$submissionid));
    }

    public function get_submission_form_elements($submission, & $data) {
        $elements = array();

        $submissionid = $submission ? $submission->id : 0;
        $default_comment = '';
        if ($submission) {
            $submission_comment = $this->get_submission_comment($submission->id);
            // This can null if the assignment settings are changed after an assignment is created
            if ($submission_comment) {
                $data->submissioncomments_editor['text'] = $submission_comment->commenttext;
                $data->submissioncomments_editor['format'] = $submission_comment->commentformat;
            }
        }


        $elements[] = array('type'=>'editor', 'name'=>'submissioncomments_editor', 'description'=>'', 'paramtype'=>PARAM_RAW, 'options'=>null);

        return $elements;
    }

    public function save($submission, $data) {

        global $USER, $DB;

    
        $comment_submission = $this->get_submission_comment($submission->id);
        if ($comment_submission) {
            $comment_submission->commenttext = $data->submissioncomments_editor['text'];
            $comment_submission->commentformat = $data->submissioncomments_editor['format'];
            return $DB->update_record('assign_submission_comments', $comment_submission);
        } else {
            $comment_submission = new stdClass();
            $comment_submission->commenttext = $data->submissioncomments_editor['text'];
            $comment_submission->commentformat = $data->submissioncomments_editor['format'];
            $comment_submission->submission = $submission->id;
            $comment_submission->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assign_submission_comments', $comment_submission) > 0;
        }
    }

    public function view_summary($submission) {
        $submission_comments = $this->get_submission_comment($submission->id);
        if ($submission_comments) {
            return shorten_text(format_text($submission_comments->commenttext));
        }
        return '';
    }
    
    public function view($submission) {
        $submission_comments = $this->get_submission_comment($submission->id);
        if ($submission_comments) {
            return format_text($submission_comments->commenttext);
        } 
        return '';
    }

}
