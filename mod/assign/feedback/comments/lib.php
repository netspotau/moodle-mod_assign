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
 * @subpackage assignfeedback_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 defined('MOODLE_INTERNAL') || die();
 
 /*
 * library class for comment feedback plugin extending feedback plugin
 * base class
 * 
 * @package   mod_assign
 * @subpackage assignfeedback_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_feedback_comments extends assignment_feedback_plugin {

    /** @var object the assignment record that contains the global settings for this assign instance */
    private $instance;

   /**
    * get the name of the online comment feedback plugin
    * @return string 
    */  
    public function get_name() {
        return get_string('pluginname', 'assignfeedback_comments');
    }
    
    /**
     * get the feedback comment from the database
     *  
     * @global moodle_database $DB
     * @param int $gradeid
     * @return stdClass or false 
     */
    private function get_feedback_comments($gradeid) {
        global $DB;
        return $DB->get_record('assign_feedback_comments', array('grade'=>$gradeid));
    }
    
    /**
     * get form elements for the grading page
     * 
     * @param stdClass $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool (true if elements were added to the form) 
     */
    public function get_form_elements(stdClass $grade, MoodleQuickForm $mform, stdClass $data) {
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

        $mform->addElement('editor', 'assignfeedbackcomments_editor', '', null, null);
        return true;
    }

    /**
     * saving the comment content into dtabase 
     * 
     * @global moodle_database $DB
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $grade, stdClass $data) {
        global $DB;


        $feedback_comment = $this->get_feedback_comments($grade->id);
        if ($feedback_comment) {
            $feedback_comment->commenttext = $data->assignfeedbackcomments_editor['text'];
            $feedback_comment->commentformat = $data->assignfeedbackcomments_editor['format'];
            return $DB->update_record('assign_feedback_comments', $feedback_comment);
        } else {
            $feedback_comment = new stdClass();
            $feedback_comment->commenttext = $data->assignfeedbackcomments_editor['text'];
            $feedback_comment->commentformat = $data->assignfeedbackcomments_editor['format'];
            $feedback_comment->grade = $grade->id;
            $feedback_comment->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assign_feedback_comments', $feedback_comment) > 0;
        }
    }

    /**
     * display the comment in the feedback table
     *  
     * @param stdClass $grade
     * @return string 
     */
    public function view_summary(stdClass $grade) {
        $feedback_comments = $this->get_feedback_comments($grade->id);
        if ($feedback_comments) {
            $text = format_text($feedback_comments->commenttext, $feedback_comments->commentformat);
            return shorten_text($text, 140);
        }
        return '';
    }
    
    /**
     * Should the assignment module show a link to view the full submission or feedback for this plugin?
     *
     * @param stdClass $grade
     * @return bool
     */
    public function show_view_link(stdClass $grade) {
        $feedback_comments = $this->get_feedback_comments($grade->id);
        if ($feedback_comments) {
            $text = format_text($feedback_comments->commenttext, $feedback_comments->commentformat);
            return shorten_text($text, 140) != $text;
        }
        return false;
    }
    
    /**
     * display the comment in the feedback table
     * 
     * @param stdClass $grade
     * @return string
     */
    public function view(stdClass $grade) {
        $feedback_comments = $this->get_feedback_comments($grade->id);
        if ($feedback_comments) {
            return format_text($feedback_comments->commenttext, $feedback_comments->commentformat);
        } 
        return '';
    }

    /**
     * If this plugin adds to the gradebook comments field, it must specify the format of the text
     * of the comment
     *
     * Only one feedback plugin can push comments to the gradebook and that is chosen by the assignment
     * settings page.
     *
     * @param stdClass $grade The grade
     * @return int
     */
    public function format_for_gradebook(stdClass $grade) {
        $feedback_comments = $this->get_feedback_comments($grade->id);
        if ($feedback_comments) {
            return $feedback_comments->commentformat;
        }
        return FORMAT_MOODLE;
    }
    
    /**
     * If this plugin adds to the gradebook comments field, it must format the text
     * of the comment
     *
     * Only one feedback plugin can push comments to the gradebook and that is chosen by the assignment
     * settings page.
     *
     * @param stdClass $grade The grade
     * @return string
     */
    public function text_for_gradebook($grade) {
        $feedback_comments = $this->get_feedback_comments($grade->id);
        if ($feedback_comments) {
            return $feedback_comments->commenttext;
        }
        return '';
    }
}
