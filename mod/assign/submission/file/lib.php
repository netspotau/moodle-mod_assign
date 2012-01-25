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
        if ($this->assignment->get_instance()) {
            $this->instance = $DB->get_record('assign_submission_file_settings', array('assignment'=>$this->assignment->get_instance()->id));
        }
    
        return $this->instance;
    }

    public function get_settings() {
        global $CFG, $COURSE, $DB;

        $current_settings = $this->get_instance();

        $default_maxfilesubmissions = $current_settings?$current_settings->maxfilesubmissions:3;
        $default_maxsubmissionsizebytes = $current_settings?$current_settings->maxsubmissionsizebytes:0;

        $settings = array();
        $options = array();
        for($i = 0; $i <= ASSIGN_MAX_SUBMISSION_FILES; $i++) {
            $options[$i] = $i;
        }

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

            return $DB->update_record('assign_submission_file_settings', $file_settings);
        } else {
            $file_settings = new stdClass();
            $file_settings->assignment = $this->assignment->get_instance()->id;
            $file_settings->maxfilesubmissions = $mform->maxfilesubmissions;
            $file_settings->maxsubmissionsizebytes = $mform->maxsubmissionsizebytes;
            return $DB->insert_record('assign_submission_file_settings', $file_settings) > 0;
        }
    }

    public function get_submission_form_elements() {
        global $USER;
        $file_settings = $this->get_instance();
        if ($file_settings->maxfilesubmissions <= 0) {
            return;
        }
        $elements = array();
        

        $fileoptions = array('subdirs'=>1,
                                'maxbytes'=>$this->instance->maxsubmissionsizebytes,
                                'maxfiles'=>$this->instance->maxfilesubmissions,
                                'accepted_types'=>'*',
                                'return_types'=>FILE_INTERNAL);


        $default_data = new stdClass();
        $default_data = file_prepare_standard_filemanager($default_data, 'files', $fileoptions, $this->assignment->get_context(), 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_FILES, $USER->id);
        
        $elements[] = array('type'=>'filemanager', 'name'=>'files_filemanager', 'description'=>'', 'options'=>$fileoptions, 'default'=>$default_data);

        return $elements;
    }
}
