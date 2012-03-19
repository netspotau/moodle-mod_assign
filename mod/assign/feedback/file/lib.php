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
 *  feedback plugin 
 * 
 * This class provides all the functionality for the new assign module.
 *
 * @package   mod_assign
 * @subpackage   assignfeedback_file
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

/*
 * library class for file feedback plugin extending feedback plugin
 * base class
 * 
 * @package   mod_assign
 * @subpackage   feedback_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_feedback_file extends assignment_feedback_plugin {
    
    /**
     * get the name of the file feedback plugin
     * @return string 
     */
    public function get_name() {
        return get_string('file', 'assignfeedback_file');
    }
    
    /**
     * get file feedback information from the database  
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
     * file format options 
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
     * get form elements for grading form
     * 
     * @global renderer_base $OUTPUT
     * @global stdClass $CFG
     * @param mixed stdClass | null $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return mixed 
     */
    public function get_form_elements($grade, MoodleQuickForm $mform, stdClass $data) {
        global $OUTPUT, $CFG;

        $fileoptions = $this->get_file_options();
        $gradeid = $grade ? $grade->id : 0;

        // Print a downloadable link to a template feedback file (if it has been set)
        $fs = get_file_storage();
        $systemcontext = context_system::instance();

        $files = $fs->get_area_files($systemcontext->id, 'mod_assign', 'template_feedback_file', 1);
        foreach ($files as $file) {
            if ($file->get_filename() != ".") {
                $icon = mimeinfo("icon", $file->get_filename());
                $image = $OUTPUT->pix_icon("f/$icon", $file->get_filename(), 'moodle', array('class'=>'icon'));
                $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/'.$systemcontext->id.'/mod_assign/template_feedback_file/' . $file->get_itemid() . '/'. $file->get_filepath().$file->get_filename(), true);

                $mform->addElement('static', 'templatefile', get_string('templatefeedbackfile', 'assignfeedback_file'), $OUTPUT->action_link($url, $image . $file->get_filename()));
            }
        }



        $data = file_prepare_standard_filemanager($data, 'files', $fileoptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_FEEDBACK_FILES, $gradeid);

        $mform->addElement('filemanager', 'files_filemanager', '', null, $fileoptions);

        return true;
    }

    /**
     * count the number of files
     * 
     * @global object $USER
     * @param int $gradeid
     * @param string $area
     * @return int 
     */
    private function count_files($gradeid, $area) {
        global $USER;

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id, 'mod_assign', $area, $gradeid, "id", false);

        return count($files);
    }

    /**
     * save the feedback files
     * 
     * @global moodle_database $DB
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool 
     */
    public function save(stdClass $grade, stdClass $data) {

        global $DB;

        $fileoptions = $this->get_file_options();
        

        $data = file_postupdate_standard_filemanager($data, 'files', $fileoptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_FEEDBACK_FILES, $grade->id);

        
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
     * display the list of files  in the feedback status table 
     *
     * @param stdClass $grade
     * @return string
     */
    public function view_summary(stdClass $grade) {
        $count = $this->count_files($grade->id, ASSIGN_FILEAREA_FEEDBACK_FILES);
        if ($count <= ASSIGN_FEEDBACK_FILE_MAX_SUMMARY_FILES) {
            return $this->assignment->render_area_files(ASSIGN_FILEAREA_FEEDBACK_FILES, $grade->id);
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
     * display the list of files  in the feedback status table 
     * @param stdClass $grade
     * @return string 
     */
    public function view(stdClass $grade) {
        return $this->assignment->render_area_files(ASSIGN_FILEAREA_FEEDBACK_FILES, $grade->id);
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
     * Produce a list of files suitable for export that represent this feedback 
     * 
     * @param mixed stdClass|null $grade The grade
     * @return array - return an array of files indexed by filename
     */
    public function get_files($grade) {
        $result = array();
        $fs = get_file_storage();
        // First add any template feedback files (if they exist)
        $systemcontext = context_system::instance();

        $files = $fs->get_area_files($systemcontext->id, 'mod_assign', 'template_feedback_file', 1);
        foreach ($files as $file) {
            if ($file->get_filename() != ".") {
                $result[get_string('templatefilenamepart', 'assignfeedback_file') . '_' . $file->get_filename()] = $file;
            }
        }

        if ($grade) {
            // Then add the existing feedback files.
            $files = $fs->get_area_files($this->assignment->get_context()->id, 'mod_assign', ASSIGN_FILEAREA_FEEDBACK_FILES, $grade->id, "timemodified", false);

            foreach ($files as $file) {
                $result[$file->get_filename()] = $file;
            }
        }
        return $result;
    }

    /**
     * If this plugin supports additional grading features, return a list of actions that will be passed to grading_page while clicked
     *
     * @param stdClass $grade The grade
     * @return array
     */
    public function additional_grading_pages() {
        return array('upload');
    }
    
    /**
     * Process the importer form
     *
     * @global moodle_page $PAGE
     * @global stdClass $CFG
     * @global stdClass $USER
     * @uses die
     * @return string
     */
    public function process_importer_form() {
        global $PAGE, $CFG, $USER;
        require_once($CFG->dirroot . '/mod/assign/feedback/file/importer_form.php');

        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        @set_time_limit(0);
        raise_memory_limit(MEMORY_EXTRA);

        $renderer = $PAGE->get_renderer('assignfeedback_file');

        // process the file
        $data = new stdClass();
        $data->importid = required_param('importid', PARAM_ALPHANUM);

        $importform = new assignfeedback_file_importer_form(null, array($this, $data));
        
        $filessent = $importform->process_files();

        if ($filessent > 0) {
            return $renderer->render(new importer_summary($filessent, $this->assignment));
        }

        redirect(new moodle_url('/mod/assign/view.php', array('id'=>$this->assignment->get_course_module()->id, 'action'=>'plugingradingpage', 'gradingaction'=>'upload', 'plugin'=>'file')));
        die();
    }
    
    /**
     * Process the uploaded zip
     *
     * @global moodle_page $PAGE
     * @global stdClass $CFG
     * @global stdClass $USER
     * @uses die
     * @return string
     */
    public function process_zip_upload() {
        global $PAGE, $CFG, $USER;
        require_once($CFG->dirroot . '/mod/assign/feedback/file/uploadzip_form.php');
        require_once($CFG->dirroot . '/mod/assign/feedback/file/importer_form.php');

        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        @set_time_limit(0);
        raise_memory_limit(MEMORY_EXTRA);

        $renderer = $PAGE->get_renderer('assignfeedback_file');
        $uploadform = new assignfeedback_file_uploadzip_form(null, array($this, null));

        if ($uploadform->is_cancelled()) {
            redirect(new moodle_url('/mod/assign/view.php', array('id' => $this->assignment->get_course_module()->id, 'action'=>'grading')));
            die();
        }
    
        // check for validation error
        $data = $uploadform->get_data();
        if (!$data) {
            return $renderer->render($uploadform);
        }

        // process the file
        $tmpdir = 'assignfeedback_file_import';
        $importid = $data->importid;
        $realfilename = 'feedback-import.zip';
        $importfile = "{$CFG->tempdir}/{$tmpdir}/{$importid}/{$realfilename}";
        make_temp_directory($tmpdir);
        make_temp_directory($tmpdir . '/' . $importid);
        if (!$result = $uploadform->save_file('uploadzip', $importfile, true)) {
            throw new moodle_exception('uploadproblem');
        }

        $packer = get_file_packer('application/zip');

        $tmpfilesdir = $tmpdir . '/' . $importid . '/files';
        $fulltmpfilesdir = $CFG->tempdir . '/' . $tmpdir . '/' . $importid . '/files';
        make_temp_directory($tmpfilesdir);
        if (!$result = $packer->extract_to_pathname($importfile, $fulltmpfilesdir)) {
            throw new moodle_exception('uploadproblem');
        }

        $data = new stdClass();
        $data->importid = $importid;
        $importform = new assignfeedback_file_importer_form(null, array($this, $data));
        return $renderer->render($importform);
    }

    /**
     * Display a custom grading page
     *
     * @global moodle_page $PAGE
     * @global stdClass $CFG
     * @return string
     */
    public function view_zip_upload_page() {
        global $PAGE, $CFG;
        require_once($CFG->dirroot . '/mod/assign/feedback/file/uploadzip_form.php');
        
        $data = new stdClass();
        $data->importid = uniqid();

        $mform = new assignfeedback_file_uploadzip_form(null, array($this, $data));
        $renderer = $PAGE->get_renderer('assignfeedback_file');
        
        return $renderer->render($mform);
    }

    /**
     * Display a custom grading page
     *
     * @param string $action The action that was chosen from additional_grading_pages
     * @return string
     */
    public function grading_page($action) {
        if ($action == 'upload') {
            return $this->view_zip_upload_page();
        }
        if ($action == 'submitupload') {
            return $this->process_zip_upload();
        }
        if ($action == 'import') {
            return $this->process_importer_form();
        }
        
        throw new coding_exception('Unknown grading page');
    }

    /**
     * Run cron for this plugin
     *
     * For this function this deletes all files from the temp dir older than 1 hour
     */
    public static function cron() {
        global $CFG;
        mtrace('Deleting temporary files from feedback import...');
        $now = time();
        foreach (scandir($CFG->tempdir . '/assignfeedback_file_import') as $file) {
            if (strpos($file, ".") == 0) {
                continue;
            }
            if (strpos($file, "~") == 0) {
                continue;
            }
            if ($now - filemtime($CFG->tempdir . '/assignfeedback_file_import/' . $file) > 3600) {
                @fulldelete($CFG->tempdir . '/assignfeedback_file_import/' . $file);
            }
        }
        mtrace('done.\n');
    }
}

