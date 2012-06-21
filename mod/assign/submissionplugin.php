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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the definition for the abstract class for submission_plugin
 *
 * This class provides all the functionality for submission plugins.
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/assignmentplugin.php');

/**
 * Abstract base class for submission plugin types.
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class assign_submission_plugin extends assign_plugin {

    /**
     * return subtype name of the plugin
     *
     * @return string
     */
    public final function get_subtype() {
        return 'assignsubmission';
    }

    /**
     * This plugin accepts submissions from a student
     * The comments plugin has no submission component so should not be counted
     * when determining whether to show the edit submission link.
     * @return boolean
     */
    public function allow_submissions() {
        return true;
    }


    /**
     * Check if the submission plugin has all the required data to allow the work
     * to be submitted for grading
     * @return bool|string 'true' if OK to proceed with submission, otherwise a
     *                        a message to display to the user
     */
    public function precheck_submission() {
        return true;
    }

    /**
     * Carry out any extra processing required when the work is submitted for grading
     * @return void
     */
    public function submit_for_grading() {
    }

    /**
     * Save any custom data for this form submission
     *
     * @param stdClass $submission - assign_submission
     *              For submission plugins this is the submission data
     * @param stdClass $data - the data submitted from the form
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save(stdClass $submission, stdClass $data) {
        return true;
    }

    /**
     * Get any additional fields for the submission/grading form for this assignment.
     *
     * @param stdClass $submission This is the submission data
     * @param MoodleQuickForm $mform - This is the form
     * @param stdClass $data - This is the form data that can be modified for example by a filemanager element
     * @return boolean - true if we added anything to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        return false;
    }

    /**
     * Should not output anything - return the result as a string so it can be consumed by webservices.
     *
     * @param stdClass $submission This is the submission record
     * @return string - return a string representation of the submission in full
     */
    public function view(stdClass $submission) {
        return '';
    }

    /**
     * Given a field name, should return the text of an editor field that is part of
     * this plugin. This is used when exporting to portfolio.
     *
     * @param string $name Name of the field.
     * @param int $submissionid The id of the submission
     * @return string - The text for the editor field
     */
    public function get_editor_text($name, $submissionid) {
        return '';
    }

    /**
     * Produce a list of files suitable for export that represent this submission
     *
     * @param stdClass $submission This is the submission record
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission) {
        return array();
    }

     /**
     * Given a field name, should return the format of an editor field that is part of
     * this plugin. This is used when exporting to portfolio.
     *
     * @param string $name Name of the field.
     * @param int $submissionid The id of the submission
     * @return int - The format for the editor field
     */
    public function get_editor_format($name, $submissionid) {
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
     * @param stdClass $submission assign_submission or assign_grade The new submission
     * @param string $log Record upgrade messages in the log
     * @return boolean true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmissionorgrade, stdClass $submissionorgrade, & $log) {
        $log = $log . ' ' . get_string('upgradenotimplemented', 'mod_assign', array('type'=>$this->type, 'subtype'=>$this->get_subtype()));
        return false;
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $submission The submission record
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // format the info for each submission plugin add_to_log
        return '';
    }

    /**
     * Is this assignment plugin empty? (ie no submission or feedback)
     * @param stdClass $submission assign_grade
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        return true;
    }


}
