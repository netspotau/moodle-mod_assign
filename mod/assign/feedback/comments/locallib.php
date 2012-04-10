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
 * This file contains the definition for the library class for comment feedback plugin 
 * 
 *
 * @package   assignfeedback_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 defined('MOODLE_INTERNAL') || die();
 
/**
 * library class for comment feedback plugin extending feedback plugin base class
 * 
 * @package   assignfeedback_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_feedback_comments extends assignment_feedback_plugin {

   /**
    * Get the name of the online comment feedback plugin
    * @return string 
    */  
    public function get_name() {
        return get_string('pluginname', 'assignfeedback_comments');
    }
    
    /**
     * Get the feedback comment from the database
     *  
     * @global moodle_database $DB
     * @param int $gradeid
     * @return stdClass|false The feedback comments for the given grade if it exists. False if it doesn't.
     */
    public function get_feedback_comments($gradeid) {
        global $DB;
        return $DB->get_record('assign_feedback_comments', array('grade'=>$gradeid));
    }
    
    /**
     * Get form elements for the grading page
     * 
     * @param stdClass|null $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool true if elements were added to the form
     */
    public function get_form_elements($grade, MoodleQuickForm $mform, stdClass $data) {
        if ($grade) {
            $feedbackcomments = $this->get_feedback_comments($grade->id);
            if ($feedbackcomments) {
                $data->feedbackcomments_editor['text'] = $feedbackcomments->commenttext;
                $data->feedbackcomments_editor['format'] = $feedbackcomments->commentformat;
            }
        }

        $mform->addElement('editor', 'assignfeedbackcomments_editor', '', null, null);
        return true;
    }

    /**
     * Saving the comment content into dtabase
     * 
     * @global moodle_database $DB
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $grade, stdClass $data) {
        global $DB;
        $feedbackcomment = $this->get_feedback_comments($grade->id);
        if ($feedbackcomment) {
            $feedbackcomment->commenttext = $data->assignfeedbackcomments_editor['text'];
            $feedbackcomment->commentformat = $data->assignfeedbackcomments_editor['format'];
            return $DB->update_record('assign_feedback_comments', $feedbackcomment);
        } else {
            $feedbackcomment = new stdClass();
            $feedbackcomment->commenttext = $data->assignfeedbackcomments_editor['text'];
            $feedbackcomment->commentformat = $data->assignfeedbackcomments_editor['format'];
            $feedbackcomment->grade = $grade->id;
            $feedbackcomment->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assign_feedback_comments', $feedbackcomment) > 0;
        }
    }

    /**
     * display the comment in the feedback table
     *  
     * @param stdClass $grade
     * @return string 
     */
    public function view_summary(stdClass $grade) {
        $feedbackcomments = $this->get_feedback_comments($grade->id);
        if ($feedbackcomments) {
            $text = format_text($feedbackcomments->commenttext, $feedbackcomments->commentformat, array('context' => $this->assignment->get_context()));
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
        $feedbackcomments = $this->get_feedback_comments($grade->id);
        if ($feedbackcomments) {
            $text = format_text($feedbackcomments->commenttext, $feedbackcomments->commentformat, array('context' => $this->assignment->get_context()));
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
        $feedbackcomments = $this->get_feedback_comments($grade->id);
        if ($feedbackcomments) {
            return format_text($feedbackcomments->commenttext, $feedbackcomments->commentformat, array('context' => $this->assignment->get_context()));
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
        $feedbackcomments = $this->get_feedback_comments($grade->id);
        if ($feedbackcomments) {
            return $feedbackcomments->commentformat;
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
    public function text_for_gradebook(stdClass $grade) {
        $feedbackcomments = $this->get_feedback_comments($grade->id);
        if ($feedbackcomments) {
            return $feedbackcomments->commenttext;
        }
        return '';
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
        $DB->delete_records('assign_feedback_comments', array('assignment'=>$this->assignment->get_instance()->id));
        return true;
    }
    
    /**
     * Returns true if there are no feedback comments for the given grade
     *
     * @param stdClass $grade
     * @return bool
     */
    public function is_empty(stdClass $grade) {
        return $this->view($grade) == '';
    }
}
