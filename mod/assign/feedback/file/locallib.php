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
 * This file contains the definition for the library class for file feedback plugin
 * 
 *
 * @package   assignfeedback_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
/**
 * File areas for file feedback assignment
 */
define('ASSIGN_MAX_FEEDBACK_FILES', 20);
define('ASSIGN_FILEAREA_FEEDBACK_FILES', 'feedback_files');
define('ASSIGN_FEEDBACK_FILE_MAX_SUMMARY_FILES', 5);

/**
 * library class for file feedback plugin extending feedback plugin base class
 * 
 * @package   asignfeedback_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_feedback_file extends assignment_feedback_plugin {
    
    /**
     * Get the name of the file feedback plugin
     * @return string 
     */
    public function get_name() {
        return get_string('file', 'assignfeedback_file');
    }
    
    /**
     * Get file feedback information from the database
     *  
     * @global moodle_database $DB
     * @param int $gradeid
     * @return mixed 
     */
    public function get_file_feedback($gradeid) {
        global $DB;
        return $DB->get_record('assign_feedback_file', array('grade'=>$gradeid));
    }
    
    /**
     * File format options
     * @global stdClass $COURSE
     * @return array
     */
    private function get_file_options() {
        global $COURSE;

        $fileoptions = array('subdirs'=>1,
                                'maxbytes'=>$COURSE->maxbytes,
                                'accepted_types'=>'*',
                                'return_types'=>FILE_INTERNAL);
        return $fileoptions;
    }
   
    /**
     * Get form elements for grading form
     * 
     * @param stdClass | null $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool true if elements were added to the form
     */
    public function get_form_elements($grade, MoodleQuickForm $mform, stdClass $data) {

        $fileoptions = $this->get_file_options();
        $gradeid = $grade ? $grade->id : 0;


        $data = file_prepare_standard_filemanager($data, 'files', $fileoptions, $this->assignment->get_context(), 'assignfeedback_file', ASSIGN_FILEAREA_FEEDBACK_FILES, $gradeid);

        $mform->addElement('filemanager', 'files_filemanager', '', null, $fileoptions);

        return true;
    }

    /**
     * Count the number of files
     * 
     * @global object $USER
     * @param int $gradeid
     * @param string $area
     * @return int 
     */
    private function count_files($gradeid, $area) {
        global $USER;

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id, 'assignfeedback_file', $area, $gradeid, "id", false);

        return count($files);
    }

    /**
     * Save the feedback files
     * 
     * @global moodle_database $DB
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool 
     */
    public function save(stdClass $grade, stdClass $data) {

        global $DB;

        $fileoptions = $this->get_file_options();
        

        $data = file_postupdate_standard_filemanager($data, 'files', $fileoptions, $this->assignment->get_context(), 'assignfeedback_file', ASSIGN_FILEAREA_FEEDBACK_FILES, $grade->id);

        
        $filefeedback = $this->get_file_feedback($grade->id);
        if ($filefeedback) {
            $filefeedback->numfiles = $this->count_files($grade->id, ASSIGN_FILEAREA_FEEDBACK_FILES);
            return $DB->update_record('assign_feedback_file', $filefeedback);
        } else {
            $filefeedback = new stdClass();
            $filefeedback->numfiles = $this->count_files($grade->id, ASSIGN_FILEAREA_FEEDBACK_FILES);
            $filefeedback->grade = $grade->id;
            $filefeedback->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assign_feedback_file', $filefeedback) > 0;
        }
    }
    
    /**
     * Display the list of files  in the feedback status table
     *
     * @param stdClass $grade
     * @return string
     */
    public function view_summary(stdClass $grade) {
        $count = $this->count_files($grade->id, ASSIGN_FILEAREA_FEEDBACK_FILES);
        if ($count <= ASSIGN_FEEDBACK_FILE_MAX_SUMMARY_FILES) {
            return $this->assignment->render_area_files('assignfeedback_file', ASSIGN_FILEAREA_FEEDBACK_FILES, $grade->id);
        } else {
            return get_string('countfiles', 'assignfeedback_file', $count);
        }
    }
    
    /**
     * Should the assignment module show a link to view the full submission or feedback for this plugin?
     *
     * @param stdClass $grade
     * @return bool
     */
    public function show_view_link(stdClass $grade) {
        $count = $this->count_files($grade->id, ASSIGN_FILEAREA_FEEDBACK_FILES);
        return $count > ASSIGN_FEEDBACK_FILE_MAX_SUMMARY_FILES;
    }
    
    /**
     * Display the list of files  in the feedback status table
     * @param stdClass $grade
     * @return string 
     */
    public function view(stdClass $grade) {
        return $this->assignment->render_area_files('assignfeedback_file', ASSIGN_FILEAREA_FEEDBACK_FILES, $grade->id);
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
        $DB->delete_records('assign_feedback_file', array('assignment'=>$this->assignment->get_instance()->id));
        
        return true;
    }
    
    /**
     * Return true if there are no feedback files
     */
    public function is_empty(stdClass $grade) {
        return $this->count_files($grade->id, ASSIGN_FILEAREA_FEEDBACK_FILES) == 0;
    }
}
