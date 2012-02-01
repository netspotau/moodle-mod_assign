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
 * This file contains the definition for the class assign_base
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Standard base class for mod_assign (assignment types).
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



abstract class submission_plugin {

    protected $assignment;
    private $type = '';
    private $error = '';
    
   
    /**
     * Constructor for the abstract submission type class
     *
     * @param object $assignment 
     */
    public function __construct($assignment = null, $type = null) {
        $this->assignment = $assignment;
        $this->type = $type;
    }
    
    public function is_first() {
        global $DB;

        $order = get_config('submission_' . $this->get_type(), 'sortorder');

        if ($order == 0) {
            return true;
        }
        return false;
    }

    public function is_last() {
        global $DB;

        if ((count(get_plugin_list('submission'))-1) == get_config('submission_' . $this->get_type(), 'sortorder')) {
            return true;
        }

        return false;
    }

    /**
     * This function should be overridden to provide an array of elements that can be added to a moodle
     * form for display in the settings page for the assignment.
     * @return $array 
     */
    public function get_settings() {
        return array();
    }

    /**
     * The assignment subtype is responsible for saving it's own settings as the database table for the 
     * standard type cannot be modified. 
     * 
     * @param object $mform - the data submitted from the form
     * @return boolean - on error the subtype should call set_error and return false.
     */
    public function save_settings($mform) {
        return true;
    }

    /**
     * Save the error message from the last error
     * 
     * @param string $msg - the error description
     */
    protected final function set_error($msg) {
        $this->error = $msg;
    }

    public final function get_error() {
        return $this->error;
    }

    /**
     * Should return the name of this submission type. 
     * 
     * @return string - the name
     */
    public abstract function get_name();
    
    /**
     * Should return the type of this submission plugin. 
     * 
     * @return string - the type
     */
    public function get_type() {
        return $this->type;
    }
    
    public function get_version() {
        $version = get_config('submission_' . $this->get_type(), 'version');
        if ($version) {
            return $version;
        } else {
            return '';
        }
    }
    
    public function get_requires() {
        $requires = get_config('submission_' . $this->get_type(), 'requires');
        if ($requires) {
            return $requires;
        } else {
            return '';
        }
    }

    /**
     * Save any custom data for this student submission
     * 
     * @param object $mform - the data submitted from the form
     * @return boolean - on error the subtype should call set_error and return false.
     */
    public function save($submission, $data) {
        return true;   
    }
    
    public function enable() {
        return $this->set_config('enabled', 1);
    }

    public function disable() {
        return $this->set_config('enabled', 0);
    }
    
    /**
     * Allows hiding this plugin from the submission screen if it is not enabled.
     * 
     * @return boolean - if false - this plugin will not accept submissions
     */
    public function is_enabled() {
        return $this->get_config('enabled');
    }

    /**
     * Get any additional fields for the submission form for this assignment.
     * 
     * @param object $defaults - The list of default values for the settings added by this plugin
     * @param object $data - This is the form data that can be modified for example by a filemanager element
     * @return array - a list of form elements to include in the submission form
     */
    public function get_submission_form_elements($submission, & $data) {
        return array();
    }

    /**
     * Should not output anything - return the result as a string so it can be consumed by webservices.
     * 
     * @return string - return a string representation of the submission in full
     */
    public function view($submission) {
        return '';
    }
    

    public function move($dir='down') {
        // get a list of the current plugins
        $submission_plugins = array();

        $names = get_plugin_list('submission');
        $current_index = 0;

        // get a sorted list of plugins
        foreach ($names as $name) {
            if (file_exists($name . '/' . ASSIGN_SUBMISSION_TYPES_FILE)) {
                require_once($name . '/' . ASSIGN_SUBMISSION_TYPES_FILE);

                $name = basename($name);

                $submission_plugin_class = "submission_$name";
                $submission_plugin = new $submission_plugin_class($this, $name);

                if ($submission_plugin instanceof submission_plugin) {
                    $idx = $submission_plugin->get_sort_order();
                    while (array_key_exists($idx, $submission_plugins)) $idx +=1;
                 
                    $submission_plugins[$idx] = $submission_plugin;
                }
            }
        }
        ksort($submission_plugins);
        // throw away the keys

        $submission_plugins = array_values($submission_plugins);

        // find this plugin in the list
        foreach ($submission_plugins as $key => $plugin) {
            if ($plugin->get_type() == $this->get_type()) {
                $current_index = $key;
                break;
            }
        }

        // make the switch
        if ($dir == 'up') {
            if ($current_index > 0) {
                $a = $submission_plugins[$current_index - 1];
                $submission_plugins[$current_index - 1] = $submission_plugins[$current_index];
                $submission_plugins[$current_index] = $a;
            }
        } else if ($dir == 'down') {
            if ($current_index < (count($submission_plugins) - 1)) {
                $a = $submission_plugins[$current_index + 1];
                $submission_plugins[$current_index + 1] = $submission_plugins[$current_index];
                $submission_plugins[$current_index] = $a;
            }
        }

        // save the new normal order 
        foreach ($submission_plugins as $key => $plugin) {
            set_config('sortorder', $key, 'submission_' . $plugin->get_type());
        }
    }
    
    public function get_sort_order() {
        $order = get_config('submission_' . $this->get_type(), 'sortorder');
        return $order?$order:0;
    }

    public function is_visible() {
        return !get_config('submission_' . $this->get_type(), 'disabled');
    }
    
    public function show() {
        set_config('disabled', 0, 'submission_' . $this->get_type());
    }
    
    public function hide() {
        set_config('disabled', 1, 'submission_' . $this->get_type());
    }

    public function has_admin_settings() {
        global $CFG;
        
        return file_exists($CFG->dirroot . '/mod/assign/submission/' . $this->get_type() . '/settings.php');        
    }
    
    public function set_config($name, $value) {
        global $DB;
        
        $current = $DB->get_record('assign_plugin_config', array('assignment'=>$this->assignment->get_instance()->id, 'plugin'=>$this->get_type(), 'name'=>$name));

        if ($current) {
            $current->value = $value;
            return $DB->update_record('assign_plugin_config', $current, array('id'=>$current->id));
        } else {
            $setting = new stdClass();
            $setting->assignment = $this->assignment->get_instance()->id;
            $setting->plugin = $this->get_type();
            $setting->name = $name;
            $setting->value = $value;
             
            return $DB->insert_record('assign_plugin_config', $setting) > 0;
        }
    }

    public function get_config($setting = null) {
        global $DB;

        if ($setting) {
            $assignment = $this->assignment->get_instance();
            if ($assignment) {
                $result = $DB->get_record('assign_plugin_config', array('assignment'=>$assignment->id, 'plugin'=>$this->get_type(), 'name'=>$setting));
                if ($result) {
                    return $result->value;
                }
            }
            return false;
        }
        $results = $DB->get_records('assign_plugin_config', array('assignment'=>$this->assignment->get_instance()->id, 'plugin'=>$this->get_type()));

        $config = new stdClass();
        if (is_array($results)) {
            foreach ($results as $setting) {
                $name = $setting->name;
                $config->$name = $setting->value;
            }
        }
        return $config;
    }
    
    /**
     * Should not output anything - return the result as a string so it can be consumed by webservices.
     * 
     * @return string - return a string representation of the submission in full
     */
    public function view_summary($submission) {
        return '';
    }
    
    
    /**
     * Should not output anything - return the result as a string so it can be consumed by webservices.
     * 
     * @return string - return a string representation of the submission in full
     */
    public function get_editor_text($name, $submissionid) {
        return '';
    }
    
     /**
     * Should not output anything - return the result as a string so it can be consumed by webservices.
     * 
     * @return string - return a string representation of the submission in full
     */
    public function get_editor_format($name, $submissionid) {
        return 0;
    }
    
    
    
    
    
}
