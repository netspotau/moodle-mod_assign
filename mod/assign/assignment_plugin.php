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
 * This file contains the functions for assignment_plugin abstract class 
 *
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Abstract class for assignment_plugin (submission/feedback).
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class assignment_plugin {

    /** @var object the assignment record that contains the global settings for this assign instance */
    protected $assignment;
    /** @var string assignment plugin type */
    private $type = '';
    /** @var string error message */
    private $error = '';

        
    /**
     * Constructor for the abstract plugin type class
     * 
     * @param object $assignment
     * @param string $type 
     */
    public function __construct($assignment = null, $type = null) {
        $this->assignment = $assignment;
        $this->type = $type;
    }
    
    /**
     * Is this the first plugin in the list?
     *
     * @return bool
     */
    public function is_first() {
        global $DB;

        $order = get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder');

        if ($order == 0) {
            return true;
        }
        return false;
    }

    /**
     * Is this the last plugin in the list?
     *
     * @return bool
     */
    public function is_last() {
        global $DB;

        if ((count(get_plugin_list($this->get_subtype()))-1) == get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder')) {
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
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save_settings($mform) {
        return true;
    }

    /**
     * Save the error message from the last error
     * 
     * @access protected
     * @param string $msg - the error description
     */
    protected final function set_error($msg) {
        $this->error = $msg;
    }

    /**
     * What was the last error?
     *
     * 
     * @return string
     */
    public final function get_error() {
        return $this->error;
    }

    /**
     * Should return the name of this plugin type. 
     * 
     * @return string - the name
     */
    public abstract function get_name();
    
    /**
     * Should return the subtype of this plugin. 
     * 
     * @return string - either 'submission' or 'feedback'
     */
    public abstract function get_subtype();
    
    /**
     * Should return the type of this plugin. 
     * 
     * @return string - the type
     */
    public function get_type() {
        return $this->type;
    }
    
    /**
     * Get the installed version of this plugin
     *
     * @return string
     */
    public function get_version() {
        $version = get_config($this->get_subtype() . '_' . $this->get_type(), 'version');
        if ($version) {
            return $version;
        } else {
            return '';
        }
    }
    
    /**
     * Get the required moodle version for this plugin
     *
     * @return string
     */
    public function get_requires() {
        $requires = get_config($this->get_subtype() . '_' . $this->get_type(), 'requires');
        if ($requires) {
            return $requires;
        } else {
            return '';
        }
    }

    /**
     * Save any custom data for this form submission
     * 
     * @param object $submission_grade - For submission plugins this is the submission data, for feedback plugins it is the grade data
     * @param object $data - the data submitted from the form
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save($submission_grade, $data) {
        return true;   
    }
    
    /**
     * Set this plugin to enabled
     *
     * @return string
     */
    public function enable() {
        return $this->set_config('enabled', 1);
    }

    /**
     * Set this plugin to disabled
     *
     * @return string
     */
    public function disable() {
        return $this->set_config('enabled', 0);
    }
    
    /**
     * Allows hiding this plugin from the submission/feedback screen if it is not enabled.
     * 
     * @return bool - if false - this plugin will not accept submissions / feedback
     */
    public function is_enabled() {
        return $this->get_config('enabled');
    }

    /**
     * Get any additional fields for the submission/grading form for this assignment.
     * 
     * @param object $submission_grade - For submission plugins this is the submission data, for feedback plugins it is the grade data
     * @param object $data - This is the form data that can be modified for example by a filemanager element
     * @return array - a list of form elements to include in the submission/grading form
     */
    public function get_form_elements($submission, & $data) {
        return array();
    }

    /**
     * Should not output anything - return the result as a string so it can be consumed by webservices.
     * 
     * @param object $submission_grade - For submission plugins this is the submission data, for feedback plugins it is the grade data
     * @return string - return a string representation of the submission in full
     */
    public function view($submission_grade) {
        return '';
    }

    

    /**
     * Change the display order for the plugins. Will renormalize the list.
     *
     * @param string $dir up or down
     * @return None
     */
    public function move($dir='down') {
        // get a list of the current plugins
        $plugins = array();

        $names = get_plugin_list($this->get_subtype());
        $current_index = 0;

        // get a sorted list of plugins
        foreach ($names as $name) {
            if (file_exists($name . '/' . ASSIGN_PLUGIN_CLASS_FILE)) {
                require_once($name . '/' . ASSIGN_PLUGIN_CLASS_FILE);

                $name = basename($name);

                $plugin_class = $this->get_subtype() . "_$name";
                $plugin = new $plugin_class($this, $name);

                if ($plugin instanceof assignment_plugin) {
                    $idx = $plugin->get_sort_order();
                    while (array_key_exists($idx, $plugins)) $idx +=1;
                 
                    $plugins[$idx] = $plugin;
                }
            }
        }
        ksort($plugins);
        // throw away the keys

        $plugins = array_values($plugins);

        // find this plugin in the list
        foreach ($plugins as $key => $plugin) {
            if ($plugin->get_type() == $this->get_type()) {
                $current_index = $key;
                break;
            }
        }

        // make the switch
        if ($dir == 'up') {
            if ($current_index > 0) {
                $a = $plugins[$current_index - 1];
                $plugins[$current_index - 1] = $plugins[$current_index];
                $plugins[$current_index] = $a;
            }
        } else if ($dir == 'down') {
            if ($current_index < (count($plugins) - 1)) {
                $a = $plugins[$current_index + 1];
                $plugins[$current_index + 1] = $plugins[$current_index];
                $plugins[$current_index] = $a;
            }
        }

        // save the new normal order 
        foreach ($plugins as $key => $plugin) {
            set_config('sortorder', $key, $this->get_subtype() . '_' . $plugin->get_type());
        }
    }
    
    /**
     * Get the numerical sort order for this plugin
     *
     * @return int
     */
    public function get_sort_order() {
        $order = get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder');
        return $order?$order:0;
    }

    /**
     * Is this plugin enaled?
     *
     * @return bool
     */
    public function is_visible() {
        $disabled = get_config($this->get_subtype() . '_' . $this->get_type(), 'disabled');
        return !$disabled;
    }
    
    /**
     * Set this plugin to visible
     *
     * @return None
     */
    public function show() {
        set_config('disabled', 0, $this->get_subtype() . '_' . $this->get_type());
    }
    
    /**
     * Set this plugin to hidden
     *
     * @return None
     */
    public function hide() {
        set_config('disabled', 1, $this->get_subtype() . '_' . $this->get_type());
    }

    /**
     * Has this plugin got a custom settings.php file?
     *
     * @return bool
     */
    public function has_admin_settings() {
        global $CFG;
        
        return file_exists($CFG->dirroot . '/mod/assign/' . $this->get_subtype() . '/' . $this->get_type() . '/settings.php');        
    }
    
    /**
     * Set a configuration value for this plugin
     *
     * @param string $name The config key
     * @param string $value The config value
     * @return bool
     */
    public function set_config($name, $value) {
        global $DB;
        
        $current = $DB->get_record('assign_plugin_config', array('assignment'=>$this->assignment->get_instance()->id, 'subtype'=>$this->get_subtype(), 'plugin'=>$this->get_type(), 'name'=>$name));

        if ($current) {
            $current->value = $value;
            return $DB->update_record('assign_plugin_config', $current, array('id'=>$current->id));
        } else {
            $setting = new stdClass();
            $setting->assignment = $this->assignment->get_instance()->id;
            $setting->subtype = $this->get_subtype();
            $setting->plugin = $this->get_type();
            $setting->name = $name;
            $setting->value = $value;
             
            return $DB->insert_record('assign_plugin_config', $setting) > 0;
        }
    }

    /**
     * Get a configuration value for this plugin
     *
     * @param string $name The config key
     * @return string | false
     */
    public function get_config($setting = null) {
        global $DB;

        if ($setting) {
            $assignment = $this->assignment->get_instance();
            if ($assignment) {
                $result = $DB->get_record('assign_plugin_config', array('assignment'=>$assignment->id, 'subtype'=>$this->get_subtype(), 'plugin'=>$this->get_type(), 'name'=>$setting));
                if ($result) {
                    return $result->value;
                }
            }
            return false;
        }
        $results = $DB->get_records('assign_plugin_config', array('assignment'=>$this->assignment->get_instance()->id, 'subtype'=>$this->get_subtype(), 'plugin'=>$this->get_type()));

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
     * @param object $submission_grade - For submission plugins this is the submission data, for feedback plugins it is the grade data
     * @return string - return a string representation of the submission in full
     */
    public function view_summary($submission_grade) {
        return '';
    }
    
    /**
     * Given a field name, should return the text of an editor field that is part of
     * this plugin. This is used when exporting to portfolio.
     *
     * @return string - The text for the editor field
     */
    public function get_editor_text($name, $submissionid) {
        return '';
    }

    /**
     * Produce a list of files suitable for export that represent this feedback or submission
     * 
     * @param object $submission_grade - For submission plugins this is the submission data, for feedback plugins it is the grade data
     * @return array - return an array of files indexed by filename
     */
    public function get_files($submission_grade) {
        return array();
    }
    
     /**
     * Given a field name, should return the format of an editor field that is part of
     * this plugin. This is used when exporting to portfolio.
     * 
     * @return int - The format for the editor field
     */
    public function get_editor_format($name, $submissionid) {
        return 0;
    }

}
