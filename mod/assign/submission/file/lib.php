<?php

define('ASSIGN_MAX_SUBMISSION_FILES', 20);
define('ASSIGN_FILEAREA_SUBMISSION_FILES', 'submissions_files');

class submission_file extends submission_plugin {
    private $instance;

    public function get_name() {
        return get_string('file', 'submission_file');
    }

    private function get_instance() {
        global $DB;
        if ($this->instance) {
            return $this->instance;
        }
        $assignment = $this->assignment->get_instance();
        if ($assignment) {
            $this->instance = $DB->get_record('assign_submission_file_settings', array('assignment'=>$assignment->id));
        }
    
        return $this->instance;
    }
    
    private function get_submission($submissionid) {
        global $DB;
        return $DB->get_record('assign_submission_file', array('submission'=>$submissionid));
    }

    public function get_settings() {
        global $CFG, $COURSE, $DB;

        $current_settings = $this->get_instance();

        $default_maxfilesubmissions = $current_settings?$current_settings->maxfilesubmissions:3;
        $default_maxsubmissionsizebytes = $current_settings?$current_settings->maxsubmissionsizebytes:0;
        $default_allowfilesubmissions = $current_settings?$current_settings->enabled:0;

        $settings = array();
        $options = array();
        for($i = 1; $i <= ASSIGN_MAX_SUBMISSION_FILES; $i++) {
            $options[$i] = $i;
        }
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        
        $settings[] = array('type' => 'select', 
                            'name' => 'allowfilesubmissions', 
                            'description' => get_string('allowfilesubmissions', 'submission_file'), 
                            'options'=>$ynoptions,
                            'default'=>$default_allowfilesubmissions);

        $settings[] = array('type' => 'select', 
                            'name' => 'maxfilesubmissions', 
                            'description' => get_string('maxfilessubmission', 'submission_file'), 
                            'options'=>$options, 'default'=>$default_maxfilesubmissions);

        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $settings[] = array('type' => 'select', 
                            'name' => 'maxsubmissionsizebytes', 
                            'description' => get_string('maximumsubmissionsize', 'submission_file'), 
                            'options'=>$choices,
                            'default'=>$default_maxsubmissionsizebytes);

        return $settings;

    }

    public function save_settings($mform) {
        global $DB;

        $file_settings = $this->get_instance();

        if ($file_settings) {
            $file_settings->maxfilesubmissions = $mform->maxfilesubmissions;
            $file_settings->maxsubmissionsizebytes = $mform->maxsubmissionsizebytes;
            $file_settings->enabled = $mform->allowfilesubmissions;

            return $DB->update_record('assign_submission_file_settings', $file_settings);
        } else {
            $file_settings = new stdClass();
            $file_settings->assignment = $this->assignment->get_instance()->id;
            $file_settings->maxfilesubmissions = $mform->maxfilesubmissions;
            $file_settings->maxsubmissionsizebytes = $mform->maxsubmissionsizebytes;
            $file_settings->enabled = $mform->allowfilesubmissions;
            return $DB->insert_record('assign_submission_file_settings', $file_settings) > 0;
        }
    }

    public function is_enabled() {
        $file_settings = $this->get_instance();
        if (!$file_settings) {
            return false;
        }
        return $file_settings->enabled;
    }

    private function get_file_options() {
        $file_settings = $this->get_instance();
        $fileoptions = array('subdirs'=>1,
                                'maxbytes'=>$file_settings->maxsubmissionsizebytes,
                                'maxfiles'=>$file_settings->maxfilesubmissions,
                                'accepted_types'=>'*',
                                'return_types'=>FILE_INTERNAL);
        return $fileoptions;
    }

    public function get_submission_form_elements($submission, & $data) {

        $file_settings = $this->get_instance();
        $elements = array();

        if (!$file_settings->enabled || $file_settings->maxfilesubmissions <= 0) {
            return $elements;
        }

        $fileoptions = $this->get_file_options();
        $submissionid = $submission ? $submission->id : 0;


        $data = file_prepare_standard_filemanager($data, 'files', $fileoptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_FILES, $submissionid);
        
        $elements[] = array('type'=>'filemanager', 'name'=>'files_filemanager', 'description'=>'', 'options'=>$fileoptions);

        return $elements;
    }

    private function count_files($submissionid = 0, $area = ASSIGN_FILEAREA_SUBMISSION_FILES) {
        global $USER;

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id, 'mod_assign', $area, $submissionid, "id", false);

        return count($files);
    }


    public function save($submission, $data) {

        global $USER, $DB;

        $settings = $this->get_instance();

        if (!$settings->enabled) {
            return;
        }
        $fileoptions = $this->get_file_options();
        

        $data = file_postupdate_standard_filemanager($data, 'files', $fileoptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_FILES, $submission->id);

        
        $file_submission = $this->get_submission($submission->id);
        if ($file_submission) {
            $file_submission->numfiles = $this->count_files($submission->id);
            return $DB->update_record('assign_submission_file', $file_submission);
        } else {
            $file_submission = new stdClass();
            $file_submission->numfiles = $this->count_files($submission->id);
            $file_submission->submission = $submission->id;
            $file_submission->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assign_submission_file', $file_submission) > 0;
        }
    }

    public function view_summary($submission) {
        return $this->assignment->render_area_files(ASSIGN_FILEAREA_SUBMISSION_FILES, $submission->id);
    }
    
    public function view($submission) {
        return $this->view_summary($submission);
    }
}
