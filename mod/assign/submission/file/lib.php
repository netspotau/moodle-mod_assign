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
 * This file contains the definition for the library class for file
 *  submission plugin 
 * 
 * This class provides all the functionality for the new assign module.
 *
 * @package   mod_assign
 * @subpackage assignsubmission_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Include eventslib.php */
require_once($CFG->libdir.'/eventslib.php');

defined('MOODLE_INTERNAL') || die();
/**
 * File areas for file submission assignment
 */
define('ASSIGN_MAX_SUBMISSION_FILES', 20);
define('ASSIGN_SUBMISSION_FILE_MAX_SUMMARY_FILES', 5);
define('ASSIGN_FILEAREA_SUBMISSION_FILES', 'submission_files');

/*
 * library class for file submission plugin extending submission plugin
 * base class
 * 
 * @package   mod_assign
 * @subpackage submission_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_submission_file extends assignment_submission_plugin {
    
    /**
     * get the name of the file submission plugin
     * @return string 
     */
    public function get_name() {
        return get_string('file', 'assignsubmission_file');
    }
    
    /**
     * get file submission information from the database  
     * 
     * @global moodle_database $DB
     * @param int $submissionid
     * @return mixed 
     */
    private function get_file_submission($submissionid) {
        global $DB;
        return $DB->get_record('assign_submission_file', array('submission'=>$submissionid));
    }
    
    /**
     * get the default setting for file submission plugin
     * @global stdClass $CFG
     * @global stdClass $COURSE
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        $defaultmaxfilesubmissions = $this->get_config('maxfilesubmissions');
        $defaultmaxsubmissionsizebytes = $this->get_config('maxsubmissionsizebytes');

        $settings = array();
        $options = array();
        for($i = 1; $i <= ASSIGN_MAX_SUBMISSION_FILES; $i++) {
            $options[$i] = $i;
        }
        
        $mform->addElement('select', 'assignsubmission_file_maxfiles', get_string('maxfilessubmission', 'assignsubmission_file'), $options);
        $mform->setDefault('assignsubmission_file_maxfiles', $defaultmaxfilesubmissions);

        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $settings[] = array('type' => 'select', 
                            'name' => 'maxsubmissionsizebytes', 
                            'description' => get_string('maximumsubmissionsize', 'assignsubmission_file'), 
                            'options'=>$choices,
                            'default'=>$defaultmaxsubmissionsizebytes);
        
        $mform->addElement('select', 'assignsubmission_file_maxsizebytes', get_string('maximumsubmissionsize', 'assignsubmission_file'), $choices);
        $mform->setDefault('assignsubmission_file_maxsizebytes', $defaultmaxsubmissionsizebytes);
    }
    
    /**
     * save the settings for file submission plugin 
     * @param stdClass $data
     * @return bool 
     */
    public function save_settings(stdClass $data) {
        $this->set_config('maxfilesubmissions', $data->assignsubmission_file_maxfiles);
        $this->set_config('maxsubmissionsizebytes', $data->assignsubmission_file_maxsizebytes);
        return true;
    }

    /**
     * file format options 
     * 
     * @return array
     */
    private function get_file_options() {
        $fileoptions = array('subdirs'=>1,
                                'maxbytes'=>$this->get_config('maxsubmissionsizebytes'),
                                'maxfiles'=>$this->get_config('maxfilesubmissions'),
                                'accepted_types'=>'*',
                                'return_types'=>FILE_INTERNAL);
        return $fileoptions;
    }
   
    /**
     * add elements to submission form
     * 
     * @param mixed stdClass|null $submission
     * @param MoodleQuickForm $submission
     * @param stdClass $data
     * @return bool 
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {

        if ($this->get_config('maxfilesubmissions') <= 0) {
            return false;
        }

        $fileoptions = $this->get_file_options();
        $submissionid = $submission ? $submission->id : 0;


        $data = file_prepare_standard_filemanager($data, 'files', $fileoptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_FILES, $submissionid);
        $mform->addElement('filemanager', 'files_filemanager', '', null, $fileoptions); 
        return true;
    }

    /**
     * count the number of files
     * 
     * @param int $submissionid
     * @param string $area
     * @return int 
     */
    private function count_files($submissionid, $area) {

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id, 'mod_assign', $area, $submissionid, "id", false);

        return count($files);
    }

    /**
     * save the files and trigger plagiarism plugin, if enabled, to scan the uploaded files via events trigger
     *
     * @global stdClass $USER
     * @global moodle_database $DB
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool 
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        $fileoptions = $this->get_file_options();


        $data = file_postupdate_standard_filemanager($data, 'files', $fileoptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_FILES, $submission->id);


        $filesubmission = $this->get_file_submission($submission->id);

        //plagiarism code event trigger when files are uploaded

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id, 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_FILES, $submission->id, "id", false);
        $count = $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_FILES);
        // send files to event system
        // Let Moodle know that an assessable file was uploaded (eg for plagiarism detection)
        $eventdata = new stdClass();
        $eventdata->modulename = 'assign';
        $eventdata->cmid = $this->assignment->get_course_module()->id;
        $eventdata->itemid = $submission->id;
        $eventdata->courseid = $this->assignment->get_course()->id;
        $eventdata->userid = $USER->id;
        if ($count > 1) {
            $eventdata->files = $files;
        }
            $eventdata->file = $files;
        events_trigger('assessable_file_uploaded', $eventdata);


        if ($filesubmission) {
            $filesubmission->numfiles = $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_FILES);
            return $DB->update_record('assign_submission_file', $filesubmission);
        } else {
            $filesubmission = new stdClass();
            $filesubmission->numfiles = $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_FILES);
            $filesubmission->submission = $submission->id;
            $filesubmission->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assign_submission_file', $filesubmission) > 0;
        }
    }

    /**
     * Produce a list of files suitable for export that represent this feedback or submission
     * 
     * @param stdClass $submission The submission
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission) {
        $result = array();
        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id, 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_FILES, $submission->id, "timemodified", false);

        foreach ($files as $file) {
            $result[$file->get_filename()] = $file;
        }
        return $result;
    }
    
    /**
     * display the list of files  in the submission status table 
     * @param stdClass $submission
     * @return string
     */
    public function view_summary($submission) {
        $count = $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_FILES);
        if ($count <= ASSIGN_SUBMISSION_FILE_MAX_SUMMARY_FILES) {
            return $this->assignment->render_area_files(ASSIGN_FILEAREA_SUBMISSION_FILES, $submission->id);
        } else {
            return get_string('countfiles', 'assignsubmission_file', $count);
        }
    }

    /**
     * Should the assignment module show a link to view the full submission or feedback for this plugin?
     *
     * @param stdClass $submission
     * @return bool
     */
    public function show_view_link(stdClass $submission) {
        $count = $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_FILES);
        return $count > ASSIGN_SUBMISSION_FILE_MAX_SUMMARY_FILES;
    }
    
    /**
     * No full submission view - the summary contains the list of files and that is the whole submission
     * 
     * @param stdClass $submission
     * @return string 
     */
    public function view(stdClass $submission) {
        return $this->assignment->render_area_files(ASSIGN_FILEAREA_SUBMISSION_FILES, $submission->id);
    }
    


    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     * 
     * @param string $type
     * @param int $version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        
        $uploadsingletype ='uploadsingle';
        $uploadtype ='upload';
        
        if (($type == $uploadsingletype || $type == $uploadtype) && $version >= 2011112900) {
            return true;
        }
        return false;
    }
  
    
    /**
     * Upgrade the settings from the old assignment 
     * to the new plugin based one
     * 
     * @param context $oldcontext - the old assignment context
     * @param stdClass $oldassignment - the old assignment data record
     * @param string log record log events here
     * @return bool Was it a success? (false will trigger rollback)
     */
    public function upgrade_settings(context $oldcontext,stdClass $oldassignment, $log) {
        if ($oldassignment->assignmenttype == 'uploadsingle') {
            $this->set_config('maxfilesubmissions', 1);
            $this->set_config('maxsubmissionsizebytes', $oldassignment->maxbytes);
            return true;
        }else {

            $this->set_config('maxfilesubmissions', $oldassignment->var1);
            $this->set_config('maxsubmissionsizebytes', $oldassignment->maxbytes);
            return true;
        }
        
        
        
    }
     
    /**
     * Upgrade the submission from the old assignment to the new one
     * 
     * @global moodle_database $DB
     * @param context $oldcontext The context of the old assignment
     * @param stdClass $oldassignment The data record for the old oldassignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $submission The data record for the new submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmission, stdClass $submission, $log) {
        global $DB;

        $filesubmission = new stdClass();
        
        $filesubmission->numfiles = $oldsubmission->numfiles;
        $filesubmission->submission = $submission->id;
        $filesubmission->assignment = $this->assignment->get_instance()->id;
        
        if (!$DB->insert_record('assign_submission_file', $filesubmission) > 0) {
            $log .= get_string('couldnotconvertsubmission', 'mod_assign', $submission->userid);
            return false;
        }

        
        
        
        // now copy the area files
        $this->assignment->copy_area_files_for_upgrade($oldcontext->id, 
                                                        'mod_assignment', 
                                                        'submission', 
                                                        $oldsubmission->id,
                                                        // New file area
                                                        $this->assignment->get_context()->id, 
                                                        'mod_assign', 
                                                        ASSIGN_FILEAREA_SUBMISSION_FILES, 
                                                        $submission->id);
        
        
       
        
        
        return true;
    }
    
    /**
     * formatting for log info    
     * @param stdClass $submission The submission
     * 
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // format the info for each submission plugin add_to_log
        $filecount = $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_FILES);
        $fileloginfo = '';
        $fileloginfo .= ' the number of file(s) : ' . $filecount . " file(s).<br>";

        return $fileloginfo;
    }

}
