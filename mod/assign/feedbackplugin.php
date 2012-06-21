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
 * This file contains the function for feedback_plugin abstract class
 *
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Include assignmentplugin.php */
require_once($CFG->dirroot.'/mod/assign/assignmentplugin.php');

/**
 * Abstract class for feedback_plugin inherited from assign_plugin abstract class.
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class assign_feedback_plugin extends assign_plugin {

    /**
     * return subtype name of the plugin
     *
     * @return string
     */
    public function get_subtype() {
        return 'assignfeedback';
    }

    /**
     * If this plugin adds to the gradebook comments field, it must specify the format
     * of the comment
     *
     * (From weblib.php)
     * define('FORMAT_MOODLE',   '0');   // Does all sorts of transformations and filtering
     * define('FORMAT_HTML',     '1');   // Plain HTML (with some tags stripped)
     * define('FORMAT_PLAIN',    '2');   // Plain text (even tags are printed in full)
     * define('FORMAT_WIKI',     '3');   // Wiki-formatted text
     * define('FORMAT_MARKDOWN', '4');   // Markdown-formatted
     *
     * Only one feedback plugin can push comments to the gradebook and that is chosen by the assignment
     * settings page.
     *
     * @param stdClass $grade The grade
     * @return int
     */
    public function format_for_gradebook(stdClass $grade) {
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
        return '';
    }

    /**
     * Override to indicate a plugin supports quickgrading
     *
     * @return boolean - True if the plugin supports quickgrading
     */
    public function supports_quickgrading() {
        return false;
    }

    /**
     * Get quickgrading form elements as html
     *
     * @param int $userid The user id in the table this quickgrading element relates to
     * @param mixed $grade grade or null - The grade data. May be null if there are no grades for this user (yet)
     * @return mixed - A html string containing the html form elements required for quickgrading or false to indicate this plugin does not support quickgrading
     */
    public function get_quickgrading_html($userid, $grade) {
        return false;
    }

    /**
     * Has the plugin quickgrading form element been modified in the current form submission?
     *
     * @param int $userid The user id in the table this quickgrading element relates to
     * @param stdClass $grade The grade
     * @return boolean - true if the quickgrading form element has been modified
     */
    public function is_quickgrading_modified($userid, $grade) {
        return false;
    }

    /**
     * Save quickgrading changes
     *
     * @param int $userid The user id in the table this quickgrading element relates to
     * @param stdClass $grade The grade
     * @return boolean - true if the grade changes were saved correctly
     */
    public function save_quickgrading_changes($userid, $grade) {
        return false;
    }

    /**
     * Save any custom data for this form submission
     *
     * @param stdClass $grade - assign_grade
     *              This is the grade record
     * @param stdClass $data - the data submitted from the form
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save(stdClass $grade, stdClass $data) {
        return true;
    }

    /**
     * Get any additional fields for the submission/grading form for this assignment.
     *
     * @param mixed $grade This is th grade record
     * @param MoodleQuickForm $mform - This is the form
     * @param stdClass $data - This is the form data that can be modified for example by a filemanager element
     * @return boolean - true if we added anything to the form
     */
    public function get_form_elements($grade, MoodleQuickForm $mform, stdClass $data) {
        return false;
    }

    /**
     * Should not output anything - return the result as a string so it can be consumed by webservices.
     *
     * @param stdClass $grade This is the grade record
     * @return string - return a string representation of the feedback in full
     */
    public function view(stdClass $grade) {
        return '';
    }

    /**
     * Given a field name, should return the text of an editor field that is part of
     * this plugin. This is used when exporting to portfolio.
     *
     * @param string $name Name of the field.
     * @param int $gradeid The id of the grade
     * @return string - The text for the editor field
     */
    public function get_editor_text($name, $gradeid) {
        return '';
    }

    /**
     * Produce a list of files suitable for export that represent this feedback
     *
     * @param stdClass $grade This is the grade record
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $grade) {
        return array();
    }

     /**
     * Given a field name, should return the format of an editor field that is part of
     * this plugin. This is used when exporting to portfolio.
     *
     * @param string $name Name of the field.
     * @param int $gradeid The id of the gradeid
     * @return int - The format for the editor field
     */
    public function get_editor_format($name, $gradeid) {
        return 0;
    }

     /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     *
     * @param string $type The old assignment subtype
     * @param int $version The old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        return false;
    }

     /**
     * Upgrade the settings from the old assignment to the new one
     *
     * @param context $oldcontext The context for the old assignment module
     * @param stdClass $oldassignment The data record for the old assignment
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
        $log = $log . ' ' . get_string('upgradenotimplemented', 'mod_assign', array('type'=>$this->type, 'subtype'=>$this->get_subtype()));
        return false;
    }

    /**
     * Upgrade the submission from the old assignment to the new one
     *
     * @param context $oldcontext The data record for the old context
     * @param stdClass $oldassignment The data record for the old assignment
     * @param stdClass $oldsubmissionorgrade The data record for the old submission
     * @param stdClass $grade The new grade
     * @param string $log Record upgrade messages in the log
     * @return boolean true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmissionorgrade, stdClass $grade, & $log) {
        $log = $log . ' ' . get_string('upgradenotimplemented', 'mod_assign', array('type'=>$this->type, 'subtype'=>$this->get_subtype()));
        return false;
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $grade The grade record
     * @return string
     */
    public function format_for_log(stdClass $grade) {
        // format the info for each plugin add_to_log
        return '';
    }

    /**
     * Is this assignment plugin empty? (ie no submission or feedback)
     * @param stdClass $grade The grade record
     * @return bool
     */
    public function is_empty(stdClass $grade) {
        return true;
    }


}
