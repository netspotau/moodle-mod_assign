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
 * This file contains the definition for the library class for online comment
 *  submission plugin 
 * 
 * This class provides all the functionality for the new assign module.
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 * library class for comment submission plugin extending submission plugin
 * base class
 * 
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_comments extends submission_plugin {

    /** @var object the assignment record that contains the global settings for this assign instance */
    private $instance;

     /**
     * get the name of the online comment submission plugin
     * @return string 
     */   
    public function get_name() {
        return get_string('pluginname', 'submission_comments');
    }
    
   
    /**
    * get comment submission information from the database   
    * 
    * @global object $DB
    * @param  integer $submissionid
    * @return mixed 
    */
    private function get_comment_submission($submissionid) {
        global $DB;
        return $DB->get_record('assign_submission_comments', array('submission'=>$submissionid));
    }

    
    /**
     * get submission form elements for settings
     * 
     * @param object $submission
     * @param object $data
     * @return string 
     */   
    public function get_submission_form_elements($submission, & $data) {
        $elements = array();

        $submissionid = $submission ? $submission->id : 0;
        $default_comment = '';
        if ($submission) {
            $submission_comment = $this->get_comment_submission($submission->id);
            // This can null if the assignment settings are changed after an assignment is created
            if ($submission_comment) {
                $data->submissioncomments_editor['text'] = $submission_comment->commenttext;
                $data->submissioncomments_editor['format'] = $submission_comment->commentformat;
            }
        }


        $elements[] = array('type'=>'editor', 'name'=>'submissioncomments_editor', 'description'=>'', 'paramtype'=>PARAM_RAW, 'options'=>null);

        return $elements;
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

    
        $comment_submission = $this->get_comment_submission($submission->id);
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
    
     /**
      * display shortened text content/comment in the submission status table 
      * 
      * @param object $submission
      * @return string 
      */
    public function view_summary($submission) {
        $submission_comments = $this->get_comment_submission($submission->id);
        if ($submission_comments) {
            return shorten_text(format_text($submission_comments->commenttext));
        }
        return '';
    }
    
    /**
     * display the saved text content from the editor in the view table 
     * @param object $submission
     * @return string  
     */
    public function view($submission) {
        $submission_comments = $this->get_comment_submission($submission->id);
        if ($submission_comments) {
            return format_text($submission_comments->commenttext);
        } 
        return '';
    }

}
