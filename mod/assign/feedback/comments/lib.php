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
 *  feedback plugin 
 * 
 *
 * @package   mod_assign
 * @subpackage feedback_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 defined('MOODLE_INTERNAL') || die();
 
 /*
 * library class for comment feedback plugin extending feedback plugin
 * base class
 * 
 * @package   mod_assign
 * @subpackage feedback_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feedback_comments extends feedback_plugin {

    /** @var object the assignment record that contains the global settings for this assign instance */
    private $instance;

   /**
    * get the name of the online comment feedback plugin
    * @return string 
    */  
    public function get_name() {
        return get_string('pluginname', 'feedback_comments');
    }
    
    /**
     * get the feedback comment from the database
     *  
     * @global object $DB
     * @param int $gradeid
     * @return mixed 
     */
    private function get_feedback_comments($gradeid) {
        global $DB;
        return $DB->get_record('assign_feedback_comments', array('grade'=>$gradeid));
    }
    
    /**
     * get form elements
     * 
     * @param object $grade
     * @param object $data
     * @return string 
     */
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

    /**
     * saving the comment content into dtabase 
     * 
     * @global object $USER
     * @global object $DB
     * @param object $grade
     * @param object $data
     * @return mixed
     */
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

    /**
     * display the comment in the feedback table
     *  
     * @param object $grade
     * @return string 
     */
    public function view_summary($grade) {
        $feedback_comments = $this->get_feedback_comments($grade->id);
        if ($feedback_comments) {
            return shorten_text(format_text($feedback_comments->commenttext, $feedback_comments->commentformat));
        }
        return '';
    }
    
    /**
     * display the comment in the feedback table
     * 
     * @param object $grade
     * @return string
     */
    public function view($grade) {
        $feedback_comments = $this->get_feedback_comments($grade->id);
        if ($feedback_comments) {
            return format_text($feedback_comments->commenttext, $feedback_comments->commentformat);
        } 
        return '';
    }

}
