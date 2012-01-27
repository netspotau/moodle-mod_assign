<?php

class submission_comments extends submission_plugin {

    private $instance;

    public function get_name() {
        return get_string('pluginname', 'submission_comments');
    }

    private function get_instance() {
        global $DB;
        if ($this->instance) {
            return $this->instance;
        }
        $assignment = $this->assignment->get_instance();
        if ($assignment) {
            $this->instance = $DB->get_record('assign_submission_comments_settings', array('assignment'=>$assignment->id));
        }
    
        return $this->instance;
    }
    
    private function get_submission($submissionid) {
        global $DB;
        return $DB->get_record('assign_submission_comments', array('submission'=>$submissionid));
    }

    public function get_settings() {
        global $CFG, $COURSE, $DB;

        $current_settings = $this->get_instance();

        $default_allowcomments = $current_settings?$current_settings->enabled:0;

        $settings = array();
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        
        $settings[] = array('type' => 'select', 
                            'name' => 'allowcomments', 
                            'description' => get_string('allowsubmissioncomments', 'submission_comments'), 
                            'options'=>$ynoptions,
                            'default'=>$default_allowcomments);

        return $settings;

    }

    public function save_settings($mform) {
        global $DB;

        $settings = $this->get_instance();

        if ($settings) {
            $settings->enabled = $mform->allowcomments;

            return $DB->update_record('assign_submission_comments_settings', $settings);
        } else {
            $settings = new stdClass();
            $settings->assignment = $this->assignment->get_instance()->id;
            $settings->enabled = $mform->allowfilesubmissions;
            return $DB->insert_record('assign_submission_comments_settings', $settings) > 0;
        }
    }

    public function is_enabled() {
        $settings = $this->get_instance();
        if (!$settings) {
            return false;
        }
        return $settings->enabled;
    }

    public function get_submission_form_elements($submission, & $data) {
        $settings = $this->get_instance();
        $elements = array();

        if (!$settings->enabled) {
            return $elements;
        }

        $submissionid = $submission ? $submission->id : 0;
        $default_comment = '';
        if ($submission) {
            $submission_comment = $this->get_submission($submission->id);
            $data->submissioncomments_editor['text'] = $submission_comment->commenttext;
            $data->submissioncomments_editor['format'] = $submission_comment->commentformat;
        }


        $elements[] = array('type'=>'editor', 'name'=>'submissioncomments_editor', 'description'=>'', 'paramtype'=>PARAM_RAW, 'options'=>null);

        return $elements;
    }

    public function save($submission, $data) {

        global $USER, $DB;

        $settings = $this->get_instance();

        if (!$settings->enabled) {
            return;
        }
        
        $comment_submission = $this->get_submission($submission->id);
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
        $submission_comments = $this->get_submission($submission->id);
        if ($submission_comments) {
            return shorten_text(format_text($submission_comments->commenttext));
        }
        return '';
    }
    
    public function view($submission) {
        $submission_comments = $this->get_submission($submission->id);
        if ($submission_comments) {
            return format_text($submission_comments->commenttext);
        } 
        return '';
    }
}
