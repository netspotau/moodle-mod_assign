<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_assign_mod_form extends moodleform_mod {
    protected $_assignmentinstance = null;

    function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('assignmentname', 'assignment'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(true, get_string('description', 'assignment'));

        assign_base::add_settings($mform);
        
        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    // Needed by plugin assignment types if they include a filemanager element in the settings form
    function has_instance() {
        return ($this->_instance != NULL);
    }

    /*
    // Needed by plugin assignment types if they include a filemanager element in the settings form
    function get_context() {
        return $this->context;
    }


    function data_preprocessing(&$default_values) {
        // Allow plugin assignment types to preprocess form data (needed if they include any filemanager elements)
        //$this->get_assignment_instance()->form_data_preprocessing($default_values, $this);
    }


    function validation($data, $files) {
        // Allow plugin assignment types to do any extra validation after the form has been submitted
        $errors = parent::validation($data, $files);
        //$errors = array_merge($errors, $this->get_assignment_instance()->form_validation($data, $files));
        return $errors;
    }
      */
}

