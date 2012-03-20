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
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Abstract class for assignment_plugin (submission/feedback).
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class assignment_plugin {

    /** @var assignment the assignment record that contains the global settings for this assign instance */
    protected $assignment;
    /** @var string assignment plugin type */
    private $type = '';
    /** @var string error message */
    private $error = '';

        
    /**
     * Constructor for the abstract plugin type class
     * 
     * @param assignment $assignment
     * @param string $type 
     */
    public final function __construct(assignment $assignment, $type) {
        $this->assignment = $assignment;
        $this->type = $type;
    }
    
    /**
     * Is this the first plugin in the list?
     *
     * @return bool
     */
    public final function is_first() {
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
    public final function is_last() {
        if ((count(get_plugin_list($this->get_subtype()))-1) == get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder')) {
            return true;
        }

        return false;
    }

    /**
     * This function should be overridden to provide an array of elements that can be added to a moodle
     * form for display in the settings page for the assignment.
     * @param MoodleQuickForm $mform The form to add the elements to 
     * @return $array 
     */
    public function get_settings(MoodleQuickForm $mform) {
        return;
    }

    /**
     * Allows the plugin to update the default_values passed in to
     * the settings form (needed to set up draft areas for editor
     * and filemanager elements)
     * @param array $default_values
     */
    public function data_preprocessing(&$default_values) {
        return;
    }

    /**
     * The assignment subtype is responsible for saving it's own settings as the database table for the 
     * standard type cannot be modified. 
     * 
     * @param stdClass $formdata - the data submitted from the form
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save_settings(stdClass $formdata) {
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
     * @return string - either 'assignsubmission' or 'feedback'
     */
    public abstract function get_subtype();
    
    /**
     * Should return the type of this plugin. 
     * 
     * @return string - the type
     */
    public final function get_type() {
        return $this->type;
    }
    
    /**
     * Get the installed version of this plugin
     *
     * @return string
     */
    public final function get_version() {
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
    public final function get_requires() {
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
     * @param mixed assignment_submission|assignment_grade - For submission plugins this is the submission data, for feedback plugins it is the grade data
     * @param stdClass $data - the data submitted from the form
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save($submissionorgrade, stdClass $data) {
        return true;   
    }
    
    /**
     * Set this plugin to enabled
     *
     * @return bool
     */
    public final function enable() {
        return $this->set_config('enabled', 1);
    }

    /**
     * Set this plugin to disabled
     *
     * @return bool
     */
    public final function disable() {
        return $this->set_config('enabled', 0);
    }
    
    /**
     * Allows hiding this plugin from the submission/feedback screen if it is not enabled.
     * 
     * @return bool - if false - this plugin will not accept submissions / feedback
     */
    public final function is_enabled() {
        return $this->get_config('enabled');
    }

    /**
     * Get any additional fields for the submission/grading form for this assignment.
     * 
     * @param mixed submission|grade - For submission plugins this is the submission data, for feedback plugins it is the grade data
     * @param MoodleQuickForm $mform - This is the form
     * @param stdClass $data - This is the form data that can be modified for example by a filemanager element
     * @return boolean - true if we added anything to the form
     */
    public function get_form_elements($submissionorgrade, MoodleQuickForm $mform, stdClass $data) {
        return false;
    }

    /**
     * Should not output anything - return the result as a string so it can be consumed by webservices.
     * 
     * @param mixed assignment_submission|assignment_grade - For submission plugins this is the submission data, for feedback plugins it is the grade data
     * @return string - return a string representation of the submission in full
     */
    public function view($submissionorgrade) {
        return '';
    }

    /**
     * Should the assignment module show a link to view the full submission or feedback for this plugin?
     *
     * @param mixed assignment_submission|assignment_grade - For submission plugins this is the submission data, for feedback plugins it is the grade data
     * @return bool
     */
    public function show_view_link($submissionorgrade) {
        return true;
    }

    

    
    /**
     * Get the numerical sort order for this plugin
     *
     * @return int
     */
    public final function get_sort_order() {
        $order = get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder');
        return $order?$order:0;
    }

    /**
     * Is this plugin enaled?
     *
     * @return bool
     */
    public final function is_visible() {
        $disabled = get_config($this->get_subtype() . '_' . $this->get_type(), 'disabled');
        return !$disabled;
    }
    

    /**
     * Has this plugin got a custom settings.php file?
     *
     * @return bool
     */
    public final function has_admin_settings() {
        global $CFG;
        
        return file_exists($CFG->dirroot . '/mod/assign/' . substr($this->get_subtype(), strlen('assign')) . '/' . $this->get_type() . '/settings.php');        
    }
    
    /**
     * Set a configuration value for this plugin
     *
     * @global moodle_database $DB
     * @param string $name The config key
     * @param string $value The config value
     * @return bool
     */
    public final function set_config($name, $value) {
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
     * @global moodle_database $DB
     * @param string $name The config key
     * @return mixed string | false
     */
    public final function get_config($setting = null) {
        global $DB;

        if ($setting) {
            if (!$this->assignment->has_instance()) {
                return false;
            }
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
     * @param mixed assignment_submission| assignment_grade - For submission plugins this is the submission data, for feedback plugins it is the grade data
     * @return string - return a string representation of the submission in full
     */
    public function view_summary($submissionorgrade) {
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
     * Produce a list of files suitable for export that represent this feedback or submission
     * 
     * @param mixed assignment_submission| assignment_grade - For submission plugins this is the submission data, for feedback plugins it is the grade data
     * @return array - return an array of files indexed by filename
     */
    public function get_files($submissionorgrade) {
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
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, $log) {
        $log = $log . ' ' . get_string('upgradenotimplemented', 'mod_assign', array('type'=>$this->type, 'subtype'=>$this->get_subtype()));
        return false;
    }

    /**
     * Upgrade the submission from the old assignment to the new one
     * 
     * @param context $oldcontext The data record for the old context
     * @param stdClass $oldassignment The data record for the old assignment
     * @param stdClass $oldsubmissionorgrade The data record for the old submission
     * @param mixed assignment_submission| assignment_grade The new submission or grade
     * @param string $log Record upgrade messages in the log
     * @return boolean true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmissionorgrade, $submissionorgrade, $log) {
        $log = $log . ' ' . get_string('upgradenotimplemented', 'mod_assign', array('type'=>$this->type, 'subtype'=>$this->get_subtype()));
        return false;
    }
    
    /**
     * formatting for log info    
     * @param assignment_submission|assignment_grade The new submission or grade
     * 
     * @return string
     */
    public function format_for_log($submissionorgrade) {
        // format the info for each submission plugin add_to_log
        return '';
    }

    /**
     * The assignment has been deleted - remove the plugin specific data
     * 
     * @return bool
     */
    public function delete_instance() {
        return true;
    }

    /**
     * Run cron for this plugin
     */
    public static function cron() {
    }
}
