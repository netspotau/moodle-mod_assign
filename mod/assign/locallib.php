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
 * This file contains the definition for the class assignment
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Assignment submission statuses
 */
define('ASSIGN_SUBMISSION_STATUS_DRAFT', 'draft'); // student thinks it is a draft
define('ASSIGN_SUBMISSION_STATUS_SUBMITTED', 'submitted'); // student thinks it is finished

/**
 * Search filters for grading page 
 */
define('ASSIGN_FILTER_SUBMITTED', 'submitted');
define('ASSIGN_FILTER_REQUIRE_GRADING', 'require_grading');

/**
 * File areas for the assignment
 */
define('ASSIGN_PLUGIN_CLASS_FILE', 'lib.php');


/**
 * File areas for assignment portfolio if enabled
 */
define('ASSIGN_FILEAREA_PORTFOLIO_FILES', 'portfolio_files');


/** Include accesslib.php */
require_once($CFG->libdir.'/accesslib.php');
/** Include formslib.php */
require_once($CFG->libdir.'/formslib.php');
require_once('HTML/QuickForm/input.php');
/** Include plagiarismlib.php */
require_once($CFG->libdir . '/plagiarismlib.php');
/** Include repository/lib.php */
require_once($CFG->dirroot . '/repository/lib.php');
/** Include local mod_form.php */
require_once('mod_form.php');
/** Include portfoliolib.php */
require_once($CFG->libdir . '/portfoliolib.php');
/** Include submission_plugin.php */
require_once('feedback_plugin.php');
require_once('submission_plugin.php');

/*
 * Standard base class for mod_assign (assignment types).
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment {
   
    
    /** @var object the assignment record that contains the global settings for this assign instance */
    private $instance;

    /** @var object the context of the course module for this assign instance (or just the course if we are 
        creating a new one) */
    private $context;

    /** @var object the course this assign instance belongs to */
    private $course;
    /** @var object the course module for this assign instance */
    private $coursemodule;
    /** @var array cache for things like the coursemodule name or the scale menu - only lives for a single 
        request */
    private $cache;

    /** @var array list of the installed submission plugins */
    private $submission_plugins;
    

    
    
    /**
     * Constructor for the base assign class
     *
     * @param object $context the course module context (or the course context if the coursemodule has not been created yet)
     * @param object $data the submitted form data
     * @param object $coursemodule the current course module if it was already loaded - otherwise this class will load one from the context as required
     * @param object $course the current course  if it was already loaded - otherwise this class will load one from the context as required
     */
    public function __construct(& $context = null, & $data = null, & $coursemodule = null, & $course = null) {
        $this->context = & $context;
        $this->instance = & $data;
        $this->coursemodule = & $coursemodule; 
        $this->course = & $course; 
        $this->cache = array(); // temporary cache only lives for a single request - used to reduce db lookups

        $this->submission_plugins = $this->load_plugins('submission');
        $this->feedback_plugins = $this->load_plugins('feedback');

    }

    /** 
     * get list of feedback plugins installed
     * @return array 
     */
    public function get_feedback_plugins() {
        return $this->feedback_plugins;
    }
    
    /** 
     * get list of submission plugins installed
     * @return array 
     */
    public function get_submission_plugins() {
        return $this->submission_plugins;
    }
    

    /**
     * get a specific submission plugin by its type
     * @param string $type
     * @return object $plugin /null
     */
    private function get_plugin_by_type($subtype, $type) {
        $name = $subtype . '_plugins';
        $p = $this->$name;
        foreach ($p as $plugin) {
            if ($plugin->get_type() == $type) {
                return $plugin;
            }
        }
        return null;
    }

    /**
     * Get a feedback plugin by type
     * @param string $type - The type of plugin e.g comments
     * @return $plugin
     */
    public function get_feedback_plugin_by_type($type) {
        return $this->get_plugin_by_type('feedback', $type);
    }

    /**
     * Get a submission plugin by type
     * @param string $type - The type of plugin e.g comments
     * @return $plugin
     */
    public function get_submission_plugin_by_type($type) {
        return $this->get_plugin_by_type('submission', $type);
    }

    /**
     * Load the plugins from the sub folders under subtype
     *
     * @param string subtype - either submission or feedback
     * @return array - The list of plugins
     */
    private function load_plugins($subtype) {
        global $CFG;
        $result = array();

        $names = get_plugin_list($subtype);

        foreach ($names as $name) {
            if (file_exists($name . '/' . ASSIGN_PLUGIN_CLASS_FILE)) {
                require_once($name . '/' . ASSIGN_PLUGIN_CLASS_FILE);

                $name = basename($name);

                $plugin_class = $subtype . '_' . $name;
                $plugin = new $plugin_class($this, $name);

                if ($plugin instanceof assignment_plugin) {
                    $idx = $plugin->get_sort_order();
                    while (array_key_exists($idx, $result)) $idx +=1;
                    $result[$idx] = $plugin;
                }
            }
        }
        ksort($result);
        return $result;
    }

    
    /**
     * Display the assignment, used by view.php
     *
     * The assignment is displayed differently depending on your role, 
     * the settings for the assignment and the status of the assignment.
     * @param string $action The current action if any.
     */
    public final function view($action='') {

        // handle form submissions first
        if ($action == 'savesubmission') {
            $this->process_save_submission();
         } else if ($action == 'lock') {
            $this->process_lock();
            $action = 'grading';
         } else if ($action == 'reverttodraft') {
            $this->process_revert_to_draft();
            $action = 'grading';
         } else if ($action == 'unlock') {
            $this->process_unlock();
            $action = 'grading';
         } else if ($action == 'submit') {
            $this->process_submit_assignment_for_grading();
            // save and show next button
        } else if ($action == 'submitgrade') {
            if (optional_param('saveandshownext', null, PARAM_ALPHA)) {
                //save and show next
                $this->process_save_grade();                
                $action = 'nextgrade';
            } else if (optional_param('nosaveandnext', null, PARAM_ALPHA)) { 
                //show next button
                $action = 'nextgrade';
            } else if (optional_param('savegrade', null, PARAM_ALPHA)) {
                //save changes button
                $this->process_save_grade();
                $action = 'grading';
            } else {
                //cancel button
                $action = 'grading';
            }
        }else if ($action == 'saveoptions') {
            $this->process_save_grading_options();
            $action = 'grading';
        }
        
        // now show the right view page
        if ($action == 'nextgrade') {
            $this->view_next_single_grade();                        
        } else if ($action == 'grade') {
            $this->view_single_grade_page();
        } else if ($action == 'editsubmission') {
            $this->view_edit_submission_page();
        } else if ($action == 'grading') {
            $this->view_grading_page();
        } else if ($action == 'downloadall') {
            $this->download_submissions();
        } else {
            $this->view_submission_page();
        }
       
    }

    /**
     * Handle a request to view a single file.
     *
     * @global object $USER
     * @param string $filearea The area to serve the file from
     * @param array $args An array of args that repesents the path to the file
     * @return None or false. On success this function does not return
     */
    public final function send_file($filearea, $args) {
        global $USER;
        $userid = (int)array_shift($args);


        // check is users submission or has grading permission
        if ($USER->id != $userid and !has_capability('mod/assignment:grade', $this->context)) {
            return false;
        }
        
        $relativepath = implode('/', $args);

        $fullpath = "/{$this->context->id}/mod_assign/$filearea/$userid/$relativepath";

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }
        send_stored_file($file, 0, 0, true); // download MUST be forced - security!
    }
    
    /**
     * Add this instance to the database
     * 
     * @global DB
     * @return mixed false if an error occurs or the integer id of the new instance
     */
    public function add_instance() {
        global $DB;

        $err = '';

        // add the database record
        $this->instance->timemodified = time();
        $this->instance->courseid = $this->instance->course;


        $returnid = $DB->insert_record('assign', $this->instance);
        $this->instance->id = $returnid;

        // call save_settings hook for submission plugins
        foreach ($this->submission_plugins as $plugin) {
            if (!$this->update_plugin_instance($plugin)) {
                print_error($plugin->get_error());
                return false;
            }
        }
        foreach ($this->feedback_plugins as $plugin) {
            if (!$this->update_plugin_instance($plugin)) {
                print_error($plugin->get_error());
                return false;
            }
        }
        // TODO: add event to the calendar
        
        // TODO: add the item in the gradebook
        
        return $this->instance->id;
    }
    
    /**
     * Delete this instance from the database
     * 
     * @global DB
     * @return bool false if an error occurs
     */
    public function delete_instance() {
        global $DB;
        $result = true;
        
        // delete files associated with this assignment
        $fs = get_file_storage();
        if (! $fs->delete_area_files($this->context->id) ) {
            $result = false;
        }
        
        if (! $DB->delete_records('assign_submission', array('assignment'=>$this->instance->id))) {
            $result = false;
        }
        
        if (! $DB->delete_records('assign_grades', array('assignment'=>$this->instance->id))) {
            $result = false;
        }
        
        if (! $DB->delete_records('assign_plugin_config', array('assignment'=>$this->instance->id))) {
            $result = false;
        }

        if (! $DB->delete_records('event', array('modulename'=>'assign', 'instance'=>$this->instance->id))) {
            $result = false;
        }

        if (! $DB->delete_records('assign', array('id'=>$this->instance->id))) {
            $result = false;
        }

        // assignment_grade_item_delete($assignment);
        // update all the calendar events 

        return $result;
    }

    /**
     * Update the settings for a single plugin
     * 
     * @param object $plugin The plugin to update
     * @global DB
     * @return bool false if an error occurs
     */
    public function update_plugin_instance($plugin) {
        if ($plugin->is_visible()) {
            $enabled_name = $plugin->get_subtype() . '_' . $plugin->get_type() . '_enabled';
            if ($this->instance->$enabled_name) {
                $plugin->enable();
            } else {
                $plugin->disable();
            }


            if (!$plugin->save_settings($this->instance)) {
                print_error($plugin->get_error());
                return false;
            }
        }
        return true;
    }


    /**
     * Update this instance in the database
     * 
     * @global DB
     * @return bool false if an error occurs
     */
    public function update_instance() {
        global $DB;
        
        $this->instance->id = $this->instance->instance;
        $this->instance->timemodified = time();
        
        // load the assignment so the plugins have access to it

        // call save_settings hook for submission plugins
        foreach ($this->submission_plugins as $plugin) {
            if (!$this->update_plugin_instance($plugin)) {
                print_error($plugin->get_error());
                return false;
            }
        }
        foreach ($this->feedback_plugins as $plugin) {
            if (!$this->update_plugin_instance($plugin)) {
                print_error($plugin->get_error());
                return false;
            }
        }

        
        // update the database record

        $result = $DB->update_record('assign', $this->instance);
        
        // update all the calendar events 
        // call post_update hook (for subtypes)
        return $result;
    }

    /**
     * add elements in grading plugin form 
     * @param object $grade
     * @param object $mform
     * @param object $data 
     */
    private function add_plugin_grade_elements($grade, & $mform, & $data) {
        foreach ($this->feedback_plugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                $elements = $plugin->get_form_elements($grade, $data);

                if ($elements && count($elements) > 0) {
                    // add a header for the plugin data
                    $mform->addElement('header', 'general', $plugin->get_name());
                    foreach ($elements as $setting) {
                        // the editor element accepts it's arguments in a non-standard order
                        if ($setting['type'] == 'editor') {
                            $this->set_default_data_for_editor($setting['name'], $data);
                        }
                        if ($setting['type'] == 'editor' || $setting['type'] == 'filemanager') {
                            $mform->addElement($setting['type'], $setting['name'], $setting['description'], null, $setting['options']);
                        } else {
                            $mform->addElement($setting['type'], $setting['name'], $setting['description'], $setting['options']);
                        }
                        if (isset($setting['default'])) {
                            $mform->setDefault($setting['name'], $setting['default']);
                        }
                    }

                }
            }
        }
    }

    /**
     * Add one plugins settings to edit plugin form 
     *
     * @param object $plugin The plugin to add the settings from
     * @param object $mform The form to add the configuration settings to. This form is modified directly (not returned)
     *  
     */
    private function add_plugin_settings($plugin, & $mform) {
        if ($plugin->is_visible()) {
            // section heading
            $mform->addElement('header', 'general', $plugin->get_name());

            // enabled
            $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

            $mform->addElement('select', $plugin->get_subtype() . '_' . $plugin->get_type() . '_enabled', get_string('enabled', 'assign'), $ynoptions);
            $mform->setDefault($plugin->get_subtype() . '_' . $plugin->get_type() . '_enabled', $plugin->is_enabled());

            $settings = $plugin->get_settings();

            // settings is an array and each element of the array is a map of 'type', 'name', 'description', 'options'
            if ($settings && count($settings) > 0) {
                foreach ($settings as $setting) {
                    if (isset($setting['options'])) {
                        // the editor element accepts it's arguments in a non-standard order
                        if ($setting['type'] == 'editor' || $setting['type'] == 'filemanager') {
                            $mform->addElement($setting['type'], $setting['name'], $setting['description'], null, $setting['options']);
                        } else {
                            $mform->addElement($setting['type'], $setting['name'], $setting['description'], $setting['options']);
                        }
                    } else {
                        $mform->addElement($setting['type'], $setting['name'], $setting['description']);
                    }
                    if (isset($setting['default'])) {
                        $mform->setDefault($setting['name'], $setting['default']);
                    }
                }
            }
        }

    }


    /**
     * Add settings to edit plugin form 
     *
     * @param object $mform The form to add the configuration settings to. This form is modified directly (not returned)
     *  
     */
    private function add_all_plugin_settings(& $mform) {
        foreach ($this->submission_plugins as $plugin) {
            $this->add_plugin_settings($plugin, $mform);
        }
        foreach ($this->feedback_plugins as $plugin) {
            $this->add_plugin_settings($plugin, $mform);
        }
    }


    
    /**
     * Add settings to edit form
     *
     * Add the list of assignment specific settings to the edit form. 
     *
     * @global object $CFG
     * @global object $COURSE
     * @param object $mform The form to add the configuration settings to. This form is modified directly (not returned)
     */
    public function add_settings(& $mform) {
        global $CFG, $COURSE;
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        
        
        $mform->addElement('header', 'general', get_string('availability', 'assign'));
        $mform->addElement('date_time_selector', 'allowsubmissionsfromdate', get_string('allowsubmissionsfromdate', 'assign'), array('optional'=>true));
        $mform->setDefault('allowsubmissionsfromdate', time());
        $mform->addElement('date_time_selector', 'duedate', get_string('duedate', 'assign'), array('optional'=>true));
        $mform->setDefault('duedate', time()+7*24*3600);
        $mform->addElement('select', 'alwaysshowdescription', get_string('alwaysshowdescription', 'assign'), $ynoptions);
        $mform->setDefault('alwaysshowdescription', 1);
        $mform->addElement('select', 'preventlatesubmissions', get_string('preventlatesubmissions', 'assign'), $ynoptions);
        $mform->setDefault('preventlatesubmissions', 0);
        $mform->addElement('header', 'general', get_string('submissions', 'assign'));
        $mform->addElement('select', 'submissiondrafts', get_string('submissiondrafts', 'assign'), $ynoptions);
        $mform->setDefault('submissiondrafts', 0);

        /*
        $mform->addElement('header', 'general', get_string('filesubmissions', 'assign'));
    
        $mform->addElement('select', 'maxfilessubmission', get_string('maxfilessubmission', 'assign'), $options);
        $mform->setDefault('maxfilessubmission', 3);
        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $mform->addElement('select', 'maxsubmissionsizebytes', get_string('maximumsubmissionsize', 'assign'), $choices);
        */

          // plagiarism enabling form
        
        $course_context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        plagiarism_get_form_elements_module($mform, $course_context);
        
        
        $mform->addElement('header', 'general', get_string('notifications', 'assign'));
        $mform->addElement('select', 'sendnotifications', get_string('sendnotifications', 'assign'), $ynoptions);
        $mform->setDefault('sendnotifications', 1);

        $this->add_all_plugin_settings($mform);
    }

    /**
     * Add menu entries to the admin menu.
     *
     * @param object $navref Node in the admin tree to add settings to
     */
    public final function extend_settings_navigation(navigation_node & $navref) {

        // Link to gradebook
        if (has_capability('gradereport/grader:view', $this->get_course_context()) && has_capability('moodle/grade:viewall', $this->get_course_context())) {
            $link = new moodle_url('/grade/report/grader/index.php', array('id' => $this->get_course()->id));
            $node = $navref->add(get_string('viewgradebook', 'assign'), $link, navigation_node::TYPE_SETTING);
        }

        // Link to download all submissions
        if (has_capability('mod/assign:grade', $this->get_course_context())) {
            $link = new moodle_url('/mod/assign/view.php', array('id' => $this->get_course_module()->id,'action'=>'downloadall'));
            $node = $navref->add(get_string('downloadall', 'assign'), $link, navigation_node::TYPE_SETTING);
        }
        
    }

    /**
     * Get the name of the current module. 
     *
     * @return string the module name (Assignment)
     */
    protected function get_module_name() {
        if (!isset($this->cache['modulename'])) {
            $this->cache['modulename'] = get_string('modulename', 'assign');
        }
        return $this->cache['modulename'];
    }
    
    /**
     * Get the plural name of the current module.
     *
     * @return string the module name plural (Assignments)
     */
    protected function get_module_name_plural() {
        if (!isset($this->cache['modulename'])) {
            $this->cache['modulenameplural'] = get_string('modulenameplural', 'assign');
        }
        return $this->cache['modulenameplural'];
    }

    /**
     * Get the settings for the current instance of this assignment
     *
     * @return object The settings
     */
    public function get_instance() {
        global $DB;
        if ($this->instance) {
            return $this->instance;
        }
        if ($this->get_course_module()) {
            $this->instance = $DB->get_record('assign', array('id' => $this->get_course_module()->instance));
        }
        return $this->instance;
    }
    
    /**
     * Get the context of the current course
     * @uses die
     * @return object The course context
     */
    public function get_course_context() {
        if (!$this->context) {
            print_error('badcontext');
            die();
        }
        if ($this->context->contextlevel == CONTEXT_COURSE) {
            return $this->context;
        } else if ($this->context->contextlevel == CONTEXT_MODULE) {
            return $this->context->get_parent_context();
        } 
    }

    /**
     * Get the current course module
     *
     * @return object The course module
     */
    public function get_course_module() {
        if ($this->coursemodule) {
            return $this->coursemodule;
        }
        if (!$this->context) {
            return null;
        }

        if ($this->context->contextlevel == CONTEXT_MODULE) {
            $this->coursemodule = get_coursemodule_from_id('assign', $this->context->instanceid);
            return $this->coursemodule;
        }
        return null;
    }
    
    /**
     * Get context module
     * 
     * @return object 
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get the current course
     * @uses die
     * @global object
     * @return object The course
     */
    public function get_course() {
        global $DB;
        if ($this->course) {
            return $this->course;
        }

        if (!$this->context) {
            print_error('badcontext');
            die();
        }
        $this->course = $DB->get_record('course', array('id' => get_courseid_from_context($this->context)));
        return $this->course;
    }
    
    /**
     *  Return a grade in user-friendly form, whether it's a scale or not
     *
     * @global object $DB
     * @param mixed $grade
     * @return string User-friendly representation of grade
     */
    public function display_grade($grade) {
        global $DB;

        static $scalegrades = array();

                                        

        if ($this->instance->grade >= 0) {    // Normal number
            if ($grade == -1 || $grade === null) {
                return '-';
            } else {
                return round($grade) .' / '.$this->instance->grade;
            }

        } else {                                // Scale
            if (empty($this->cache['scale'])) {
                if ($scale = $DB->get_record('scale', array('id'=>-($this->instance->grade)))) {
                    $this->cache['scale'] = make_menu_from_list($scale->scale);
                } else {
                    return '-';
                }
            }
            if (isset($this->cache['scale'][$grade])) {
                return $this->cache['scale'][$grade];
            }
            return '-';
        }
    }
    
    /**
     * Load a list of users enrolled in the current course with the specified permission and group (optional)
     *
     * @return array List of user records
     */
    final protected function & list_enrolled_users_with_capability($permission,$currentgroup) {
        $users = & get_enrolled_users($this->context, $permission, $currentgroup);
        return $users;
    }

    /**
     * Load a count of users enrolled in the current course with the specified permission and group (optional)
     *
     * @return int number of matching users
     */
    final protected function count_enrolled_users_with_capability($permission,$currentgroup=0) {
        $users = & get_enrolled_users($this->context, $permission, $currentgroup, 'u.id');
        return count($users);
    }

    /**
     * Load a count of users enrolled in the current course with the specified permission and group (optional)
     *
     * @global object $DB
     * @param string $status The submission status - should match one of the constants 
     * @return int number of matching submissions
     */
    final protected function count_submissions_with_status($status) {
        global $DB;
        return $DB->count_records_sql("SELECT COUNT('x')
                                     FROM {assign_submission}
                                    WHERE assignment = ? AND
                                          status = ?", array($this->get_course_module()->instance, $status));
    }

    /**
     * Utility function to add a row of data to a table with 2 columns. Modified
     * the table param and does not return a value
     * 
     * @param object $t The table to append the row of data to
     * @param string $first The first column text
     * @param string $second The second column text
     */
    private function add_table_row_tuple(& $t, $first, $second) {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell2 = new html_table_cell($second);
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;
    }
    
    /**
     * Utility function get the userid based on the row number of the grading table.
     * This takes into account any active filters on the table.
     * 
     * @param int $num The row number of the user
     * @return mixed The user id of the matching user or false if there was an error
     */
    private function get_userid_for_row($num){
        $filter = get_user_preferences('assign_filter', '');
     
        return $this->load_submissions_table(1, $filter, $num, true);
    }

    /**
     * Return all assignment submissions by ENROLLED students (even empty)
     *
     * @global object $CFG;
     * @global object $DB;
     * @param $sort string optional field names for the ORDER BY in the sql query
     * @param $dir string optional specifying the sort direction, defaults to DESC
     * @return array The submission objects indexed by id
     */
    private function get_all_submissions( $sort="", $dir="DESC") {
        global $CFG, $DB;

        if ($sort == "lastname" or $sort == "firstname") {
            $sort = "u.$sort $dir";
        } else if (empty($sort)) {
            $sort = "a.timemodified DESC";
        } else {
            $sort = "a.$sort $dir";
        }

        return $DB->get_records_sql("SELECT a.*
                                       FROM {assign_submission} a, {user} u
                                      WHERE u.id = a.userid
                                            AND a.assignment = ?
                                   ORDER BY $sort", array($this->instance->id));

    }
     
    /**
     * Generate zip file from array of given files
     * 
     * @global object $CFG
     * @param array $filesforzipping - array of files to pass into archive_to_pathname - this array is indexed by the final file name and each element in the array is an instance of a stored_file object
     * @return path of temp file - note this returned file does not have a .zip extension - it is a temp file.
     */
     private function pack_files($filesforzipping) {
         global $CFG;
         //create path for new zip file.
         $tempzip = tempnam($CFG->tempdir.'/', 'assignment_');
         //zip files
         $zipper = new zip_packer();
         if ($zipper->archive_to_pathname($filesforzipping, $tempzip)) {
             return $tempzip;
         }
         return false;
    }

    /**
     * Update a grade in the grade table for the assignment and in the gradebook
     *
     * @global object $DB
     * @param object $grade a grade record keyed on id
     * @return boolean true for success
     */
    private function update_grade($grade) {
        global $DB;

        $grade->timemodified = time();
        $result = $DB->update_record('assign_grades', $grade);
        if ($result) {
            $this->gradebook_item_update(null,$grade);
        }
        return $result;
    }

    /**
     * Load a table object with data ready to display the grading data for this assignment.
     * This takes into account any active filters on the table via user preferences.
     * 
     * @param int $perpage The maximum number of results to show on one page
     * @param string $filter Any current filter that is set
     * @param int $start_page An optional param that controls the page to start from
     * @param bool $onlyfirstuserid An optional param that if true will change the function to only return the userid of the first row of data. This is used by {@link get_userid_for_row} to efficiently find the userid for any given row. 
     * @return mixed Either the entire table of data or just the id for the first user if $onlyfirstuserid is set.
     */
    private function & load_submissions_table($perpage=10,$filter=null,$startpage=null,$onlyfirstuserid=false) {
        global $CFG, $DB, $OUTPUT,$PAGE;
                     
        $tablecolumns = array('picture', 'fullname', 'status', 'edit', 'grade', 'timemodified', 'timemarked', 'finalgrade');

        $tableheaders = array('',
                              get_string('fullnameuser'),
                              get_string('status'),
                              get_string('edit'),
                              get_string('grade'),
                              get_string('lastmodified').' ('.get_string('submission', 'assign').')',
                              get_string('lastmodified').' ('.get_string('grade').')',
                              get_string('finalgrade', 'grades'));

        // more efficient to load this here
        require_once($CFG->libdir.'/tablelib.php');
        require_once($CFG->libdir.'/gradelib.php');

        // this is the class for styling
        $table = new flexible_table('mod-assign-submissions');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot.'/mod/assign/view.php?id='.$this->get_course_module()->id. '&action=grading');

        $table->sortable(true, 'lastname');//sorted by lastname by default
        $table->collapsible(true);
        $table->initialbars(true);

        $table->column_suppress('picture');
        $table->column_suppress('fullname');

        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        $table->column_class('status', 'status');
        $table->column_class('edit', 'edit');
        $table->column_class('grade', 'grade');
        $table->column_class('timemodified', 'timemodified');
        $table->column_class('timemarked', 'timemarked');
        $table->column_class('finalgrade', 'finalgrade');

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'attempts');
        $table->set_attribute('class', 'submissions');
        $table->set_attribute('width', '100%');

        $table->no_sorting('edit');
        $table->no_sorting('finalgrade');
        $table->no_sorting('outcome');
        $table->no_sorting('status');

        $table->setup();
       // group setting
        $groupmode = groups_get_activity_groupmode($this->get_course_module());
        $currentgroup = groups_get_activity_group($this->get_course_module(), true);
        
              
        list($where, $params) = $table->get_sql_where();
        if ($where) {
            $where .= ' AND ';
        }
        
        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY '.$sort;
        }

        $users = array_keys( $this->list_enrolled_users_with_capability("mod/assign:submit",$currentgroup));
          
        $ufields = user_picture::fields('u');
        if (!empty($users)) {
            $select = "SELECT $ufields,
                              s.id AS submissionid, g.grade, s.status,
                              s.timemodified as timesubmitted, g.timemodified AS timemarked, g.locked ";
            $sql = 'FROM {user} u '.
                   'LEFT JOIN {assign_submission} s ON u.id = s.userid
                    AND s.assignment = '.$this->instance->id.' '.
                   'LEFT JOIN {assign_grades} g ON u.id = g.userid
                    AND g.assignment = '.$this->instance->id.' '.                   
                   'WHERE '.$where.'u.id IN ('.implode(',',$users).') ';

            if ($filter != null) {
                if ($filter == ASSIGN_FILTER_REQUIRE_GRADING) {
                    $sql .= ' AND g.timemodified < s.timemodified '; 
                    
                } else if ($filter == ASSIGN_FILTER_SUBMITTED) {
                    $sql .= ' AND s.timemodified > 0 '; 
                }
            }
                                 
            $count = $DB->count_records_sql("SELECT COUNT(*) AS X ".$sql, $params);

            $table->pagesize($perpage, $count);
            
            $ausers = $DB->get_records_sql($select.$sql.$sort, $params, $startpage?$startpage:$table->get_page_start(), $table->get_page_size());
                             
            if ($ausers !== false) {
                $grading_info = grade_get_grades($this->get_course()->id, 'mod', 'assign', $this->instance->id, array_keys($ausers));
                foreach ($ausers as $auser) {
                                       
                    if ($onlyfirstuserid) {
                        return $auser->id;
                    }
                    
                    $picture = $OUTPUT->user_picture($auser);

                    $userlink = $OUTPUT->action_link(new moodle_url('/user/view.php', array('id' => $auser->id, 'course'=>$this->get_course()->id)), fullname($auser, has_capability('moodle/site:viewfullnames', $this->context)));

                    $grade = $this->display_grade($auser->grade);
                    $studentmodified = '-';
                    if ($auser->timesubmitted) {
                        $studentmodified = userdate($auser->timesubmitted);
                    }
                    $teachermodified = '-';
                    if ($auser->timemarked) {
                        $teachermodified = userdate($auser->timemarked);
                    }
                    $status = get_string('submissionstatus_' . $auser->status, 'assign');
                    //  get row number !
                    $rownum = array_search($auser->id,array_keys($ausers)) + $table->get_page_start();
                                        
                    $status = $OUTPUT->action_link(new moodle_url('/mod/assign/view.php', array('id' => $this->get_course_module()->id, 'rownum'=>$rownum,'action'=>'grade')), $status);
                    
                    $finalgrade = '-';
                    if (isset($grading_info->items[0]) && $grading_info->items[0]->grades[$auser->id]) {
                        // debugging
                        $finalgrade = $this->display_grade($grading_info->items[0]->grades[$auser->id]->grade);
                    }
                    
                    $edit = $OUTPUT->action_link(new moodle_url('/mod/assign/view.php', array('id' => $this->get_course_module()->id, 'rownum'=>$rownum,'action'=>'grade')), $OUTPUT->pix_icon('t/grades', get_string('grade')));
                    if (!$auser->status || $auser->status == ASSIGN_SUBMISSION_STATUS_DRAFT || !$this->instance->submissiondrafts) {
                        if (!$auser->locked) {
                            $edit .= $OUTPUT->action_link(new moodle_url('/mod/assign/view.php', array('id' => $this->get_course_module()->id, 'userid'=>$auser->id, 'action'=>'lock')), $OUTPUT->pix_icon('t/lock', get_string('preventsubmissions', 'assign')));
                        } else {
                            $edit .= $OUTPUT->action_link(new moodle_url('/mod/assign/view.php', array('id' => $this->get_course_module()->id, 'userid'=>$auser->id, 'action'=>'unlock')), $OUTPUT->pix_icon('t/unlock', get_string('allowsubmissions', 'assign')));
                        }
                    }
                    if ($auser->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED && $this->instance->submissiondrafts) {
                            $edit .= $OUTPUT->action_link(new moodle_url('/mod/assign/view.php', array('id' => $this->get_course_module()->id, 'userid'=>$auser->id, 'action'=>'reverttodraft')), $OUTPUT->pix_icon('t/left', get_string('reverttodraft', 'assign')));
                    }

                    $renderer = $PAGE->get_renderer('mod_assign');

                    $row = array($picture, $userlink, $status, $edit, $grade, $studentmodified, $teachermodified, $finalgrade);
                    $table->add_data($row);
                }
            }
        }
        
        // important bit for hiding buttons for the last user in the grading section 
        if ($onlyfirstuserid && count($ausers) == 0) {
            $result = false;
            // this function returns a reference so we have to use a variable in the return
            return $result;
        }

        return $table;

    }
    
    /**
     * display the submission that is used by a plugin  
     * @global object $OUTPUT
     * @global object $CFG
     * @param int $submissionid
     * @param string $plugintype 
     */
    public function view_submission($submissionid=null, $plugintype=null) {
           global $OUTPUT, $CFG;
           $this->view_header();
            echo $OUTPUT->container_start('viewsubmission');
            echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
            
            $submission = $this->get_submission(null, $submissionid, false);
            
            foreach ($this->submission_plugins as $plugin) {
                if ($plugin->get_type() == $plugintype) {
                    echo $plugin->view($submission);
                }
            }
          
            echo $OUTPUT->box_end();
            echo $OUTPUT->container_end();
            echo $OUTPUT->spacer(array('height'=>30));
                 
            $this->view_return_links();
          
            $this->view_footer();     
          
    }
    
    /**
     * render the content in editor that is often used by plugin
     *  
     * @global object $CFG
     * @param string $filearea
     * @param int  $submissionid
     * @param string $plugintype
     * @param string $editor
     * @return string
     */
    public function render_editor_content($filearea, $submissionid, $plugintype, $editor) {
        global $CFG;
        
        $result = '';
        
        $plugin = $this->get_submission_plugin_by_type($plugintype);
        
        $text = $plugin->get_editor_text($editor, $submissionid);
        $format = $plugin->get_editor_format($editor, $submissionid);
        
        $text = file_rewrite_pluginfile_urls($text, 'pluginfile.php', $this->get_context()->id, 'mod_assign', $filearea, $submissionid);
        $result .= format_text($text, $format, array('overflowdiv' => true));

        

        if ($CFG->enableportfolios) {
            require_once($CFG->libdir . '/portfoliolib.php');
           
            $button = new portfolio_add_button();
            $button->set_callback_options('assign_portfolio_caller', array('cmid' => $this->get_course_module()->id, 'sid' => $submissionid, 'plugin' => $plugintype, 'editor' => $editor, 'area'=>$filearea), '/mod/assign/portfolio_callback.php');
            $fs = get_file_storage();

            if ($files = $fs->get_area_files($this->context->id, 'mod_assign',$filearea, $submissionid, "timemodified", false)) {
                $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
            } else {
                $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
            }
            $result .= $button->to_html();
        }
        return $result;
    }
            

    /**
     * Setup the PAGE variable and print the assignment name as a header
     *
     * @global object $PAGE
     * @global object $OUTPUT
     * @param string $subpage optional sub page for the navbar
     */
    private function view_header($subpage='') {
        global $PAGE, $OUTPUT;

        if ($subpage) {
            $PAGE->navbar->add($subpage);
        }

        $PAGE->set_title(get_string('pluginname', 'assign'));
        $PAGE->set_heading($this->instance->name);

        echo $OUTPUT->header();
        echo $OUTPUT->heading($this->instance->name);

    }


    /**
     * Display the assignment intro
     *
     * The prints the assignment description in a box
     * @global object $OUTPUT
     */
    private function view_intro() {
        global $OUTPUT;
        if ($this->instance->alwaysshowdescription || time() > $this->instance->allowsubmissionsfromdate) {
            echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
            echo format_module_intro('assign', $this->instance, $this->get_course_module()->id);
            echo $OUTPUT->box_end();
        }
        plagiarism_print_disclosure($this->get_course_module()->id);
    }
    
    /**
     * Display the page footer
     *
     * @global object $OUTPUT
     */
    private function view_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }

    /**
     * View the grading summary information and a link to the grading page
     *
     * @global object $OUTPUT
     */
    private function view_grading_summary() {
        global $OUTPUT;

        // Permissions check 
        if (!has_capability('mod/assign:view', $this->context) ||
            !has_capability('mod/assign:grade', $this->context)) {
            return;
        }
        
        // create a table for the data
        echo $OUTPUT->container_start('gradingsummary');
        echo $OUTPUT->heading(get_string('gradingsummary', 'assign'), 3);
        echo $OUTPUT->box_start('boxaligncenter', 'intro');
        $t = new html_table();

        // status
        $this->add_table_row_tuple($t, get_string('numberofparticipants', 'assign'), 
                                   $this->count_enrolled_users_with_capability('mod/assign:submit'));

        // drafts
        if ($this->instance->submissiondrafts) {
            $this->add_table_row_tuple($t, get_string('numberofdraftsubmissions', 'assign'), 
                                       $this->count_submissions_with_status(ASSIGN_SUBMISSION_STATUS_DRAFT));
        }

        // submitted for grading
        $this->add_table_row_tuple($t, get_string('numberofsubmittedassignments', 'assign'), 
                                   $this->count_submissions_with_status(ASSIGN_SUBMISSION_STATUS_SUBMITTED));

        $time = time();
        if ($this->instance->duedate) {
            // due date
            // submitted for grading
            $this->add_table_row_tuple($t, get_string('duedate', 'assign'), 
                                       userdate($this->instance->duedate));

            // time remaining
            $due = '';
            if ($this->instance->duedate - $time <= 0) {
                $due = get_string('assignmentisdue', 'assign');
            } else {
                $due = format_time($this->instance->duedate - $time);
            }
            $this->add_table_row_tuple($t, get_string('timeremaining', 'assign'), $due);
        }

        // all done - write the table
        echo html_writer::table($t);
        echo $OUTPUT->box_end();
        
        // link to the grading page
        echo $OUTPUT->single_button(new moodle_url('/mod/assign/view.php',
            array('id' => $this->get_course_module()->id ,'action'=>'grading')), get_string('viewgrading', 'assign'), 'get');

        // close the container and insert a spacer
        echo $OUTPUT->container_end();
        echo $OUTPUT->spacer(array('height'=>30));
    }
    
    
    /**
     * View a redirect to the next submission grading page
     * 
     * @uses die
     */
    private function view_next_single_grade() {
        $rnum = required_param('rownum', PARAM_INT);
        $rnum +=1;
        $userid = $this->get_userid_for_row($rnum);
        if (!$userid) {
             print_error('outofbound exception array:rownumber&userid');
             die();
        }
    
        redirect('view.php?id=' . $this->get_course_module()->id . '&rownum=' . $rnum . '&action=grade');
        die();
    }
    
    /**
     * Download a zip file of all assignment submissions
     *
     * @global object $CFG
     * @global object $DB
     */
    private function download_submissions() {
        global $CFG,$DB;
        
        // more efficient to load this here
        require_once($CFG->libdir.'/filelib.php');

        // load all submissions
        $submissions = $this->get_all_submissions('','');
        
        if (empty($submissions)) {
            print_error('errornosubmissions', 'assign');
        }

        // build a list of files to zip
        $filesforzipping = array();
        $fs = get_file_storage();
        
        $groupmode = groups_get_activity_groupmode($this->get_course_module());
        $groupid = 0;   // All users
        $groupname = '';
        if ($groupmode) {
            $groupid = groups_get_activity_group($this->get_course_module(), true);
            $groupname = groups_get_group_name($groupid).'-';
        }

        // construct the zip file name
        $filename = str_replace(' ', '_', clean_filename($this->get_course()->shortname.'-'.$this->instance->name.'-'.$groupname.$this->get_course_module()->id.".zip")); //name of new zip file.
    
        // get all the files for each submission
        foreach ($submissions as $submission) {
            $a_userid = $submission->userid; //get userid
            if ((groups_is_member($groupid,$a_userid) or !$groupmode or !$groupid)) {
                // get the plugins to add their own files to the zip

                $a_user = $DB->get_record("user", array("id"=>$a_userid),'id,username,firstname,lastname'); 

                $prefix = clean_filename(fullname($a_user) . "_" .$a_userid . "_");


                foreach ($this->submission_plugins as $plugin) {
                    if ($plugin->is_enabled() && $plugin->is_visible()) {
                        $plugin_files = $plugin->get_files($submission);

                    
                        foreach ($plugin_files as $filename => $file) {
                            $filesforzipping[$prefix . $filename] = $file;
                        } 
                    }
                }
          
            } 
        } // end of foreach loop
        if ($zipfile = $this->pack_files($filesforzipping)) {
            $this->add_to_log('download all submissions', get_string('downloadall', 'assign'));
            send_temp_file($zipfile, $filename); //send file and delete after sending.
        }
    }

    /**
     * Util function to add a message to the log
     *
     * @global object $USER
     * @param string $action The current action
     * @param string $info A detailed description of the change. But no more than 255 characters.
     * @param string $url The url to the assign module instance.
     */
    private function add_to_log($action = '', $info = '', $url='') {
        global $USER; 
    
        $fullurl = 'view.php?id=' . $this->get_course_module()->id;
        if ($url != '') {
            $fullurl .= '&' . $url;
        }

        add_to_log($this->get_course()->id, 'assign', $action, $fullurl, $info, $this->get_course_module()->id, $USER->id);
    }
    
    /**
     * Load the submission object for a particular user, optionally creating it if required
     *
     * @global object $DB
     * @global object $USER
     * @param int $userid The id of the user whose submission we want or 0 in which case USER->id is used
     * @param bool $createnew optional Defaults to false. If set to true a new submission object will be created in the database
     * @return object The submission
     */
    public function get_submission($userid = null,$submissionid =null, $create = false) {
        global $DB, $USER;

        if (!$userid && !$submissionid) {
            $userid = $USER->id;
        }
        if ($userid){
            
              // if the userid is not null then use userid
             $submission = $DB->get_record('assign_submission', array('assignment'=>$this->instance->id, 'userid'=>$userid));
         }else{
         
             $submission = $DB->get_record('assign_submission', array('assignment'=>$this->instance->id, 'id'=>$submissionid));
         }
        if ($submission) {
            return $submission;
        }
        if ($create) {
            $submission = new stdClass();
            $submission->assignment   = $this->instance->id;
            $submission->userid       = $userid;
            $submission->timecreated = time();
            $submission->timemodified = $submission->timecreated;
            
            if ($this->instance->submissiondrafts) {
                $submission->status = ASSIGN_SUBMISSION_STATUS_DRAFT;
            } else {
                $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
            }
            $sid = $DB->insert_record('assign_submission', $submission);
            $submission->id = $sid;
            return $submission;
        }
        return FALSE;
    }
    
    
    /**
     * This will retrieve a grade object from the db, optionally creating it if required
     *
     * @global object $DB
     * @param int $userid The user we are grading
     * @param bool $create If true the grade will be created if it does not exist
     * @return object The grade record
     */
    private function get_grade($userid, $create = false) {
        global $DB;

        $grade = $DB->get_record('assign_grades', array('assignment'=>$this->instance->id, 'userid'=>$userid));
         
        if ($grade) {
            return $grade;
        }
        if ($create) {
            $grade = new stdClass();
            $grade->assignment   = $this->instance->id;
            $grade->userid       = $userid;
            $grade->timecreated = time();
            $grade->timemodified = $grade->timecreated;
            $grade->locked = 0;
            $grade->grade = -1;
            $gid = $DB->insert_record('assign_grades', $grade);
            $grade->id = $gid;
            return $grade;
        }
        return FALSE;
    }
    
    /**
     * Print the details for a single user
     *
     * @global object $CFG
     * @param object $user A user record from the database
     */
    private function view_user($user=null) {
        global $OUTPUT;
        if (!$user) {
            return;
        }
        echo $OUTPUT->container_start('userinfo');
        echo $OUTPUT->user_picture($user);
        echo $OUTPUT->spacer(array('width'=>30));
        echo $OUTPUT->action_link(new moodle_url('/user/view.php', array('id' => $user->id, 'course'=>$this->get_course()->id)), fullname($user, has_capability('moodle/site:viewfullnames', $this->context)));
        echo $OUTPUT->container_end();
        
    }
   
    /**
     * Print the grading page for a single user submission
     *
     * @global object $OUTPUT
     * @global object $DB
     * @uses die
     */
    private function view_single_grade_page() {
        global $OUTPUT, $DB;
        
        // Always require view permission to do anything
        require_capability('mod/assign:view', $this->context);
        // Need submit permission to submit an assignment
        require_capability('mod/assign:grade', $this->context);

        $this->view_header(get_string('grading', 'assign'));
       
        $rownum = required_param('rownum', PARAM_INT);  
        $userid = $this->get_userid_for_row($rownum);
        if(!$userid){
             print_error('outofbound exception array:rownumber&userid');
             die();
        }
        $user = $DB->get_record('user', array('id' => $userid));
        $this->view_user($user);
        $this->view_submission_status($userid);

        // now show the grading form
        $this->view_grade_form();

        $this->add_to_log('view grading form', get_string('viewgradingformforstudent', 'assign', array('id'=>$user->id, 'fullname'=>fullname($user))));
        
        $this->view_footer();
    }

   
     
    /**
     * View a link to go back to the previous page. Uses url parameters returnaction and returnparams.
     *
     * @global object $OUTPUT
     */
    private function view_return_links() {
        global $OUTPUT;
        
        $returnaction = optional_param('returnaction','', PARAM_ALPHA);
        $returnparams = optional_param('returnparams','', PARAM_TEXT);
        
        if ($returnaction) {
            $params = array();
            parse_str($returnparams, $params);
            $params = array_merge( array('id' => $this->get_course_module()->id, 'action' => $returnaction), $params);
           
            echo $OUTPUT->single_button(new moodle_url('/mod/assign/view.php', $params), get_string('back', 'assign'), 'get');
        }
        
    }
   
    /**
     * View the grading table of all submissions for this assignment
     *
     * @global object $USER
     */
    private function view_grading_table() {
        global $USER;

        $perpage = get_user_preferences('assign_perpage', 10);
        $filter = get_user_preferences('assign_filter', '');
        // print options for for changing the filter and changing the number of results per page
        $mform = new mod_assign_grading_options_form(null, array('cm'=>$this->get_course_module()->id, 'contextid'=>$this->context->id, 'userid'=>$USER->id), 'post', '', array('id'=>'gradingoptionsform'));


        $data = new stdClass();
        $data->perpage = $perpage;
        $data->filter = $filter;
        $mform->set_data($data);
        
        $mform->display();
        
        // load and print the table of submissions
        $table = & $this->load_submissions_table($perpage, $filter);
        $table->print_html();
        
    }

    /**
     * View the links beneath the grading table.
     *
     * @global object $OUTPUT
     */
    private function view_grading_links() {
        global $OUTPUT;
        // print navigation buttons

        echo $OUTPUT->spacer(array('height'=>30));
        $contextname = print_context_name($this->context);

        echo $OUTPUT->container_start('gradingnavigation');
        echo $OUTPUT->container_start('backlink');
        echo $OUTPUT->action_link(new moodle_url('/mod/assign/view.php', array('id' => $this->get_course_module()->id)), get_string('backto', '', $contextname));
        echo $OUTPUT->container_end();
        if (has_capability('gradereport/grader:view', $this->get_course_context()) && has_capability('moodle/grade:viewall', $this->get_course_context())) {
            echo $OUTPUT->container_start('gradebooklink');
            echo $OUTPUT->action_link(new moodle_url('/grade/report/grader/index.php', array('id' => $this->get_course()->id)), get_string('viewgradebook', 'assign'));
            echo $OUTPUT->container_end();
        }
        echo $OUTPUT->container_start('downloadalllink');
        echo $OUTPUT->action_link(new moodle_url('/mod/assign/view.php', array('id' => $this->get_course_module()->id, 'action' => 'downloadall')), get_string('downloadall', 'assign'));
        echo $OUTPUT->container_end();

        echo $OUTPUT->container_end();
        
    }
       
    /**
     * View entire grading page.
     *
     * @global object $OUTPUT
     * @global object $CFG
     * @global object $USER
     */
    private function view_grading_page() {
        global $OUTPUT, $CFG, $USER;

        // Always require view permission to do anything
        require_capability('mod/assign:view', $this->context);
        // Need submit permission to submit an assignment
        require_capability('mod/assign:grade', $this->context);

        // only load this if it is 
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->dirroot.'/grade/grading/lib.php');

        $this->view_header(get_string('grading', 'assign'));
        groups_print_activity_menu($this->get_course_module(), $CFG->wwwroot . '/mod/assign/view.php?id=' . $this->get_course_module()->id.'&action=grading');
        

        $this->view_grading_table();
        // add a link to the grade book if this user has permission
        
        $this->view_grading_links();



        $this->view_footer();
        $this->add_to_log('view submission grading table', get_string('viewsubmissiongradingtable', 'assign'));
    }
    
    /**
     * View edit submissions page.
     */
    private function view_edit_submission_page() {
        // Always require view permission to do anything
        require_capability('mod/assign:view', $this->context);
        // Need submit permission to submit an assignment
        require_capability('mod/assign:submit', $this->context);


        $this->view_header(get_string('editsubmission', 'assign'));
        $this->view_intro();
        $this->view_edit_submission_form();
        
        $this->view_footer();
        $this->add_to_log('view submit assignment form', get_string('viewownsubmissionform', 'assign'));
    }
    
    /**
     * View submissions page (contains details of current submission).
     *
     * @global object $CFG
     */
    private function view_submission_page() {
        global $CFG;
        
         // Always require view permission to do anything
        require_capability('mod/assign:view', $this->context);
       
        
        $this->view_header(get_string('pluginname', 'assign'));
        groups_print_activity_menu($this->get_course_module(), $CFG->wwwroot . '/mod/assign/view.php?id=' . $this->get_course_module()->id);

        $this->view_intro();
        $this->view_grading_summary();
        $this->view_submission_status();
        $this->view_submission_links();
        $this->view_feedback();
            
        $this->view_footer();
        $this->add_to_log('view', get_string('viewownsubmissionstatus', 'assign'));
    } 
    
    /**
     * convert the final raw grade(s) in the  grading table for the gradebook  
     * @param object $grade
     * @return object $gradebook_grade 
     */
    private function convert_grade_for_gradebook($grade) {
        $gradebook_grade = array();
        
        // trying to match those array keys in grade update function in gradelib.php
        // with keys in th database table assign_grades
        // starting around line 262
        $gradebook_grade['rawgrade'] = $grade->grade;
        $gradebook_grade['userid'] = $grade->userid;
        $gradebook_grade['usermodified'] = $grade->grader;
        $gradebook_grade['datesubmitted'] = NULL;
        $gradebook_grade['dategraded'] = $grade->timemodified;
       
        // more TODO ?
        return $gradebook_grade;
    }
    
    /**
     * convert submission details for the gradebook  
     * @param object $submission
     * @return object $gradebook_grade
     */
    private function convert_submission_for_gradebook($submission) {
        $gradebook_grade = array();
        
        
        $gradebook_grade['userid'] = $submission->userid;
        $gradebook_grade['usermodified'] = $submission->userid;
        $gradebook_grade['datesubmitted'] = $submission->timemodified;
        
       
        // more TODO ?
        return $gradebook_grade;
    }

    /**
     * update grades in the gradebook
     * @global object $CFG
     * @param object $submission
     * @param object $grade
     * @return mixed 
     */
    private function gradebook_item_update($submission=NULL, $grade=NULL) {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $params = array('itemname' => $this->instance->name, 'idnumber' => $this->get_course_module()->id);

        if ($this->instance->grade > 0) {
            $params['gradetype'] = GRADE_TYPE_VALUE;
            $params['grademax'] = $this->instance->grade;
            $params['grademin'] = 0;
        } else if ($this->instance->grade < 0) {
            $params['gradetype'] = GRADE_TYPE_SCALE;
            $params['scaleid'] = -$this->instance->grade;
        } else {
            $params['gradetype'] = GRADE_TYPE_TEXT; // allow text comments only
        }
        
        if($submission != NULL){
            
            $gradebook_grade = $this->convert_submission_for_gradebook($submission);
            
            
        }else{
            
        
            $gradebook_grade = $this->convert_grade_for_gradebook($grade);
        }
        return grade_update('mod/assign', $this->get_course()->id, 'mod', 'assign', $this->instance->id, 0, $gradebook_grade, $params);
    }

    /**
     * update grades in the gradebook based on submission time 
     * @global object $DB
     * @param object $submission
     * @param bool $updatetime
     * @return mixed 
     */
    private function update_submission($submission, $updatetime=true) {
        global $DB;

        if ($updatetime) {
            $submission->timemodified = time();
        }
        $result= $DB->update_record('assign_submission', $submission);
        if ($result) {
            $this->gradebook_item_update($submission);
        }
        return $result;
    }

    /**
     * Is this assignment open for submissions?
     *
     * Check the due date, 
     * prevent late submissions, 
     * has this person already submitted, 
     * is the assignment locked?
     * @global object $USER
     * @return bool 
     */
    protected final function submissions_open() {
        global $USER;

        $time = time();
        $date_open = TRUE;
        if ($this->instance->preventlatesubmissions && $this->instance->duedate) {
            $date_open = ($this->instance->allowsubmissionsfromdate <= $time && $time <= $this->instance->duedate);
        } else {
            $date_open = ($this->instance->allowsubmissionsfromdate <= $time);
        }

        if (!$date_open) {
            return FALSE;
        }

        // now check if this user has already submitted etc.
        if (!is_enrolled($this->get_course_context(), $USER)) {
            return FALSE;
        }
        if ($submission = $this->get_submission($USER->id)) {
            if ($this->instance->submissiondrafts && $submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                // drafts are tracked and the student has submitted the assignment
                return FALSE;
            }
        }
        if ($grade = $this->get_grade($USER->id)) {
            if ($grade->locked) {
                return FALSE;
            }
        }

        return TRUE;
    }
    /**
     * render the files in file area  
     * @global object $CFG
     * @global object $USER
     * @global object $OUTPUT
     * @global object $PAGE
     * @param string $area
     * @param int $submissionid
     * @return object 
     */
    public function render_area_files($area, $submissionid = null) {
        global $CFG, $USER, $OUTPUT, $PAGE;

        if (!$submissionid) {
            $submission = $this->get_submission($USER->id,null, false);
            $submissionid = $submission->id;
        }
    
        $fs = get_file_storage();
        $browser = get_file_browser();

        $renderer = $PAGE->get_renderer('mod_assign');
        return $renderer->assign_files($this->context, $submissionid, $area);
        
    }

    /**
     * Returns a list of teachers that should be grading given submission
     *
     * @param object $user
     * @return array
     */
    private function get_graders($user) {
        //potential graders
        $potgraders = get_users_by_capability($this->context, 'mod/assign:grade', '', '', '', '', '', '', false, false);

        $graders = array();
        if (groups_get_activity_groupmode($this->get_course_module()) == SEPARATEGROUPS) {   // Separate groups are being used
            if ($groups = groups_get_all_groups($this->get_course()->id, $user->id)) {  // Try to find all groups
                foreach ($groups as $group) {
                    foreach ($potgraders as $t) {
                        if ($t->id == $user->id) {
                            continue; // do not send self
                        }
                        if (groups_is_member($group->id, $t->id)) {
                            $graders[$t->id] = $t;
                        }
                    }
                }
            } else {
                // user not in group, try to find graders without group
                foreach ($potgraders as $t) {
                    if ($t->id == $user->id) {
                        continue; // do not send self
                    }
                    if (!groups_has_membership($this->get_course_module(), $t->id)) {
                        $graders[$t->id] = $t;
                    }
                }
            }
        } else {
            foreach ($potgraders as $t) {
                if ($t->id == $user->id) {
                    continue; // do not send self
                }
                // must be enrolled
                if (is_enrolled($this->get_course_context(), $t->id)) {
                    $graders[$t->id] = $t;
                }
            }
        }
        return $graders;
    }

    /**
     * Creates the text content for emails to grader
     *
     * @param $info object The info used by the 'emailgradermail' language string
     * @return string
     */
    private function format_email_grader_text($info) {
        $posttext  = format_string($this->get_course()->shortname, true, array('context' => $this->get_course_context())).' -> '.
                     $this->get_module_name().' -> '.
                     format_string($this->instance->name, true, array('context' => $this->context))."\n";
        $posttext .= '---------------------------------------------------------------------'."\n";
        $posttext .= get_string("emailgradermail", "assign", $info)."\n";
        $posttext .= "\n---------------------------------------------------------------------\n";
        return $posttext;
    }

     /**
     * Creates the html content for emails to graders
     *
     * @param $info object The info used by the 'emailgradermailhtml' language string
     * @return string
     */
    private function format_email_grader_html($info) {
        global $CFG;
        $posthtml  = '<p><font face="sans-serif">'.
                     '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$this->get_course()->id.'">'.format_string($this->get_course()->shortname, true, array('context' => $this->get_course_context())).'</a> ->'.
                     '<a href="'.$CFG->wwwroot.'/mod/assignment/index.php?id='.$this->get_course()->id.'">'.$this->get_module_name().'</a> ->'.
                     '<a href="'.$CFG->wwwroot.'/mod/assignment/view.php?id='.$this->get_course_module()->id.'">'.format_string($this->instance->name, true, array('context' => $this->context)).'</a></font></p>';
        $posthtml .= '<hr /><font face="sans-serif">';
        $posthtml .= '<p>'.get_string('emailgradermailhtml', 'assign', $info).'</p>';
        $posthtml .= '</font><hr />';
        return $posthtml;
    }
    
    /**
     * email graders upon student submissions 
     * @global object $CFG
     * @global object $DB
     * @param object $submission
     * @return mixed 
     */
    private function email_graders($submission) {
        global $CFG, $DB;

        if (empty($this->instance->sendnotifications)) {          // No need to do anything
            return;
        }

        $user = $DB->get_record('user', array('id'=>$submission->userid));

        if ($teachers = $this->get_graders($user)) {

            $strassignments = $this->get_module_name_plural();
            $strassignment  = $this->get_module_name();
            $strsubmitted  = get_string('submitted', 'assign');

            foreach ($teachers as $teacher) {
                $info = new stdClass();
                $info->username = fullname($user, true);
                $info->assignment = format_string($this->instance->name,true);
                $info->url = $CFG->wwwroot.'/mod/assign/view.php?id='.$this->get_course_module()->id;
                $info->timeupdated = strftime('%c',$submission->timemodified);

                $postsubject = $strsubmitted.': '.$info->username.' -> '.$this->instance->name;
                $posttext = $this->format_email_grader_text($info);
                $posthtml = ($teacher->mailformat == 1) ? $this->format_email_grader_html($info) : '';

                $eventdata = new stdClass();
                $eventdata->modulename       = 'assign';
                $eventdata->userfrom         = $user;
                $eventdata->userto           = $teacher;
                $eventdata->subject          = $postsubject;
                $eventdata->fullmessage      = $posttext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml  = $posthtml;
                $eventdata->smallmessage     = $postsubject;

                $eventdata->name            = 'assign_updates';
                $eventdata->component       = 'mod_assign';
                $eventdata->notification    = 1;
                $eventdata->contexturl      = $info->url;
                $eventdata->contexturlname  = $info->assignment;

                message_send($eventdata);
            }
        }
    }
    /**
     *  assignment submission is processed before grading 
     * @global object $USER 
     */
    private function process_submit_assignment_for_grading() {
        
         // Always require view permission to do anything
        require_capability('mod/assign:view', $this->context);
        // Need submit permission to submit an assignment
        require_capability('mod/assign:submit', $this->context);
        
        global $USER;
        $submission = $this->get_submission($USER->id,null, true);
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;

        $this->update_submission($submission);
        $this->add_to_log('submit for grading', $this->format_submission_for_log($submission));
        $this->email_graders($submission);
    }
    /**
     * save grading options 
     * @global object $USER 
     */
    private function process_save_grading_options() {
        global $USER;

        
         // Always require view permission to do anything
        require_capability('mod/assign:view', $this->context);
        // Need submit permission to submit an assignment
        require_capability('mod/assign:grade', $this->context);
        
        
        
        $mform = new mod_assign_grading_options_form(null, array('cm'=>$this->get_course_module()->id, 'contextid'=>$this->context->id, 'userid'=>$USER->id));
        
        if ($formdata = $mform->get_data()) {
            set_user_preference('assign_perpage', $formdata->perpage);
            set_user_preference('assign_filter', $formdata->filter);
        }
    }
    
   /**
    * Take a grade object and print a short summary for the log file. 
    * The size limit for the log file is 255 characters, so be careful not
    * to include too much information.
    * @global object $DB
    * @param object $grade
    * @return string 
    */
    private function format_grade_for_log($grade) {
        global $DB;

        $user = $DB->get_record('user', array('id' => $grade->userid));
        
        $info = get_string('gradestudent', 'assign', array('id'=>$user->id, 'fullname'=>fullname($user)));
        if ($grade->grade != '') {
            $info .= get_string('grade', 'assign') . ': ' . $this->display_grade($grade->grade) . '. ';
        } else {
            $info .= get_string('nograde', 'assign');
        }
        if ($grade->locked) {
            $info .= get_string('submissionslocked', 'assign') . '. ';
        }
        return $info;
    }
    
    /**
     * Take a submission object and print a short summary for the log file. 
     * The size limit for the log file is 255 characters, so be careful not
     * to include too much information.
     * @param object $submission
     * @return string 
     */
    private function format_submission_for_log($submission) {
        $info = '';
        $info .= get_string('submissionstatus', 'assign') . ': ' . get_string('submissionstatus_' . $submission->status, 'assign') . '.';
        /*
        if ($submission->numfiles > 0) {
            $info .= get_string('numfiles', 'assign', $submission->numfiles);
        } else {
            $info .= get_string('nofiles', 'assign');
        }
        if ($submission->onlinetext != '') {
            $info .= get_string('onlinetextnumwords', 'assign', count_words(format_text($submission->onlinetext)));
        } else {
            $info .= get_string('noonlinetext', 'assign');
        }
        if ($submission->submissioncommenttext != '') {
            $info .= get_string('submissioncommentnumwords', 'assign', count_words(format_text($submission->submissioncommenttext)));
        } else {
            $info .= get_string('nosubmissioncomment', 'assign');
        }
        */
        return $info;
    }
    
    /**
     * save assignment submission
     * @global object $USER
     * @return mixed 
     */
    private function process_save_submission() {       
        global $USER;
        
        // Always require view permission to do anything
        require_capability('mod/assign:view', $this->context);
        // Need submit permission to submit an assignment
        require_capability('mod/assign:submit', $this->context);
      
        $data = $this->get_default_submission_data();
        $mform = new mod_assign_submission_form(null, array($this, $data));
        if ($data = $mform->get_data()) {               
            $submission = $this->get_submission($USER->id, null, true); //create the submission if needed & its id              
            $grade = $this->get_grade($USER->id); // get the grade to check if it is locked
            if ($grade && $grade->locked) {
                print_error('submissionslocked', 'assign');
                return;
            }
          
        
            foreach ($this->submission_plugins as $plugin) {
                if ($plugin->is_enabled()) {
                    if (!$plugin->save($submission, $data)) {
                        print_error($plugin->get_error());
                    }
                }
            }
           
            $this->update_submission($submission);

            // Logging
            $this->add_to_log('submit', $this->format_submission_for_log($submission));

            if (!$this->instance->submissiondrafts) {
                $this->email_graders($submission);
            }
        }
         
    }
    
    /**
     * count the number of files in the file area
     * @global object $USER
     * @param int $userid
     * @param string $area
     * @return int  
     */
    private function count_files($userid = 0, $area = ASSIGN_FILEAREA_SUBMISSION_FILES) {
        global $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'mod_assign', $area, $userid, "id", false);

        return count($files);
    }

    /**
     * Determine if this users grade is locked or overridden
     * 
     * @param int $userid - The student userid
     * @return boolean $grading_disabled
     */
    private function grading_disabled($userid) {
        $grading_info = grade_get_grades($this->get_course()->id, 'mod', 'assign', $this->instance->id, array($userid));
        $gradingdisabled = $grading_info->items[0]->grades[$userid]->locked || $grading_info->items[0]->grades[$userid]->overridden;
        return $gradingdisabled;
    }


    /**
     * Get an instance of a grading form if advanced grading is enabled
     * This is specific to the assignment, marker and student
     * @param int $userid - The student userid
     * @param object $gradingdisabled - For speed this can be passed - otherwise this is looked up
     * @return object $gradinginstance
     */
    private function get_grading_instance($userid, $gradingdisabled = null) {
        global $CFG, $USER;
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->dirroot.'/grade/grading/lib.php');

        $grade = $this->get_grade($userid, false);
        $grademenu = make_grades_menu($this->instance->grade);

        if ($gradingdisabled === null) {
            $gradingdisabled = $this->grading_disabled($userid);
        }
        
        $advancedgradingwarning = false;
        $gradingmanager = get_grading_manager($this->context, 'mod_assign', 'submissions');
        $gradinginstance = null;
        if ($gradingmethod = $gradingmanager->get_active_method()) {
            $controller = $gradingmanager->get_controller($gradingmethod);
            if ($controller->is_form_available()) {
                $itemid = null;
                if ($grade) {
                    $itemid = $grade->id;
                }
                if ($gradingdisabled && $itemid) {
                    $gradinginstance = ($controller->get_current_instance($USER->id, $itemid));
                } else if (!$gradingdisabled) {
                    $instanceid = optional_param('advancedgradinginstanceid', 0, PARAM_INT);
                    $gradinginstance = ($controller->get_or_create_instance($instanceid, $USER->id, $itemid));
                }
            } else {
                $advancedgradingwarning = $controller->form_unavailable_notification();
            }
        }
        if ($gradinginstance) {
            $gradinginstance->get_controller()->set_grade_range($grademenu);
        }
        return $gradinginstance;
    }

    /**
     *  add elements to grade form 
     * @global object $USER
     * @param object $mform
     * @param object $data 
     */
    public function add_grade_form_elements(& $mform, & $data, $params) {
        global $USER, $CFG;
        $settings = $this->get_instance();

        $rownum = $params['rownum'];
        $userid = $this->get_userid_for_row($rownum);
        $grade = $this->get_grade($userid, false);
        
        // add advanced grading
        $gradingdisabled = $this->grading_disabled($userid);
        $gradinginstance = $this->get_grading_instance($userid, $gradingdisabled);

        if ($gradinginstance) {
            $gradingelement = $mform->addElement('grading', 'advancedgrading', get_string('grade').':', array('gradinginstance' => $gradinginstance));
            if ($gradingdisabled) {
                $gradingelement->freeze();
            } else {
                $mform->addElement('hidden', 'advancedgradinginstanceid', $gradinginstance->get_id());
            }
        } else {
            // use simple direct grading
            $grademenu = make_grades_menu($this->instance->grade);
            $grademenu['-1'] = get_string('nograde');

            $mform->addElement('select', 'grade', get_string('grade').':', $grademenu);
            $mform->setType('grade', PARAM_INT);
        }


        // plugins
        $this->add_plugin_grade_elements($grade, $mform, $data);

        
        // hidden params
        $mform->addElement('hidden', 'id', $this->get_course_module()->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'rownum', $params['rownum']);
        $mform->setType('rownum', PARAM_INT);
        
        $mform->addElement('hidden', 'action', 'submitgrade');
        $mform->setType('action', PARAM_ALPHA);
          
        $buttonarray=array();
             
        if (!$params['last']){
            $buttonarray[] = &$mform->createElement('submit', 'saveandshownext', get_string('savenext','assign')); 
            $buttonarray[] = &$mform->createElement('submit', 'nosaveandnext', get_string('nosavebutnext', 'assign'));
        }
        $buttonarray[] = &$mform->createElement('submit', 'savegrade', get_string('savechanges', 'assign'));        
        $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton', get_string('cancel','assign'));     
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');            
    }

    
    /**
     * display the grade form
     * 
     * @uses die
     * @global object $OUTPUT
     * @global object $USER 
     */
    private function view_grade_form() {
        global $OUTPUT, $USER;

         // Always require view permission to do anything
        require_capability('mod/assign:view', $this->context);
        // Need submit permission to submit an assignment
        require_capability('mod/assign:grade', $this->context);


        echo $OUTPUT->heading(get_string('grade'), 3);
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');

        $rownum = required_param('rownum', PARAM_INT);
        $userid = $this->get_userid_for_row($rownum);
        if(!$userid){
             print_error('outofbound exception array:rownumber&userid');
             die();
        }

        $grade = $this->get_grade($userid);
        if ($grade) {
            $data = new stdClass();
            $data->grade = $grade->grade;
            // set the grade 
        } else {
            $data = new stdClass();
            $data->grade = -1;
        }

        $options = array('subdirs'=>1,
                                        'maxbytes'=>$this->course->maxbytes,
                                        'maxfiles'=>EDITOR_UNLIMITED_FILES,
                                        'accepted_types'=>'*',
                                        'return_types'=>FILE_INTERNAL);

        $last = !$this->get_userid_for_row($rownum+1);
        $mform = new mod_assign_grade_form(null, array($this, $data, array('rownum'=>$rownum, 'last'=>$last)));

        // show upload form
        $mform->display();

        echo $OUTPUT->box_end();
        echo $OUTPUT->spacer(array('height'=>30));
    }

    /**
     *  get the default submission data 
     * @return stdClass 
     */
    private function get_default_submission_data() {
        $data = new stdClass();

        return $data;
    }
    
    /**
     * Used to set the default data for an editor element.
     * Prevents warnings being written to the page.
     *
     * @param string $name - The name of the element
     * @param object $data - The default data class to modify
     * @return None
     */
    private function set_default_data_for_editor($name, & $data) {
        $textname =$name . 'editor';
        $formatname =$name . 'format';
        if (!isset($data->$textname)) {
            $data->$textname = '';
        }
        if (!isset($data->$formatname)) {
            $data->$formatname = editors_get_preferred_format();
        }
    }
    
    /**
     * add elements in submission plugin form 
     * @param object $submission
     * @param object $mform
     * @param object $data 
     */
    private function add_plugin_submission_elements($submission, & $mform, & $data) {
        foreach ($this->submission_plugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                $submission_elements = $plugin->get_form_elements($submission, $data);

                if ($submission_elements && count($submission_elements) > 0) {
                    // add a header for the plugin data
                    $mform->addElement('header', 'general', $plugin->get_name());
                    foreach ($submission_elements as $setting) {
                        // the editor element accepts it's arguments in a non-standard order
                        if ($setting['type'] == 'editor') {
                            $this->set_default_data_for_editor($setting['name'], $data);
                        }
                        if ($setting['type'] == 'editor' || $setting['type'] == 'filemanager') {
                            $mform->addElement($setting['type'], $setting['name'], $setting['description'], null, $setting['options']);
                        } else {
                            $mform->addElement($setting['type'], $setting['name'], $setting['description'], $setting['options']);
                        }
                        if (isset($setting['default'])) {
                            $mform->setDefault($setting['name'], $setting['default']);
                        }
                    }
                    
                }
            }
        }
    }
    
    /**
     * display submission form 
     * @global object $OUTPUT
     * @global object $USER 
     */
    private function view_submission_form() {
        global $OUTPUT, $USER;
        
         // Always require view permission to do anything
        require_capability('mod/assign:view', $this->context);
        // Need submit permission to submit an assignment
        require_capability('mod/assign:submit', $this->context);

       
        echo $OUTPUT->heading(get_string('submission', 'assign'), 3);
        echo $OUTPUT->container_start('submission');

        $data = $this->get_default_submission_data();
        $submission = $this->get_submission($USER->id);

        $mform = new mod_assign_submission_form(null, array($this, $data));

        $mform->display();
        
        echo $OUTPUT->container_end();
        echo $OUTPUT->spacer(array('height'=>30));
    }

   /**
    *  Show the screen for creating an assignment submission
    * @global object $OUTPUT 
    */
    private function view_edit_submission_form() {
        global $OUTPUT;
         // Always require view permission to do anything
        require_capability('mod/assign:view', $this->context);
        // Need submit permission to submit an assignment
        require_capability('mod/assign:submit', $this->context);

       
        // check submissions open

        if ($this->submissions_open()) {
            $this->view_submission_form();
        }
        echo $OUTPUT->single_button(new moodle_url('/mod/assign/view.php',
            array('id' => $this->get_course_module()->id)), get_string('backtoassignment', 'assign'), 'get');

        // plagiarism?
    }
    
    /**
     * check if submission plugins installed are enabled 
     * @return bool
     */
    private function is_any_submission_plugin_enabled() {
        if (!isset($this->cache['any_submission_plugin_enabled'])) {
            $this->cache['any_submission_plugin_enabled'] = false;
            foreach ($this->submission_plugins as $plugin) {
                if ($plugin->is_enabled() && $plugin->is_visible()) {
                    $this->cache['any_submission_plugin_enabled'] = true;
                    break;
                }
            }
        }

        return $this->cache['any_submission_plugin_enabled'];
        
    }
        
    /**
     * display submission status page 
     * @global object $OUTPUT
     * @global object $USER
     * @param int $userid
     * @return mixed
     */
    private function view_submission_status($userid=null) {
        global $OUTPUT, $USER;


         // Always require view permission to do anything
        if (!has_capability('mod/assign:view', $this->context)) {
            return;
        }
       
       
        if (!$userid) {
            $userid = $USER->id;
        }
        
        if (!is_enrolled($this->get_course_context(), $userid)) {
            return;
        }
        if ($userid == $USER->id && !has_capability('mod/assign:submit', $this->context)) {
            return;
        }
        if ($userid != $USER->id && !has_capability('mod/assign:grade', $this->context)) {
            return;
        }
        $submission = null;

        echo $OUTPUT->container_start('submissionstatus');
        echo $OUTPUT->heading(get_string('submissionstatusheading', 'assign'), 3);
        $time = time();
        if (!$this->is_any_submission_plugin_enabled()) {
            echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
            echo get_string('noonlinesubmissions', 'assign');
            echo $OUTPUT->box_end();
        }

        if ($this->instance->allowsubmissionsfromdate &&
                $time <= $this->instance->allowsubmissionsfromdate) {
            echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
            echo get_string('allowsubmissionsfromdatesummary', 'assign', userdate($this->instance->allowsubmissionsfromdate));
            echo $OUTPUT->box_end();
        } 
        $submission = $this->get_submission($userid);
        $grade = $this->get_grade($userid);
        echo $OUTPUT->box_start('boxaligncenter', 'intro');
        $t = new html_table();

        // status
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('submissionstatus', 'assign'));
        $locked = '';
        if ($grade && $grade->locked) {
            $locked = '<br/><br/>' . get_string('submissionslocked', 'assign');
        }
        if ($submission) {
            $cell2 = new html_table_cell(get_string('submissionstatus_' . $submission->status, 'assign') . $locked);
        } else {
            $cell2 = new html_table_cell(get_string('nosubmission', 'assign') . $locked);
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        // grading status
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('gradingstatus', 'assign'));

        if ($grade && $grade->grade > 0) {
            $cell2 = new html_table_cell(get_string('graded', 'assign'));
        } else {
            $cell2 = new html_table_cell(get_string('notgraded', 'assign'));
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        
        
        if ($this->instance->duedate >= 1) {
            // due date
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('duedate', 'assign'));
            $cell2 = new html_table_cell(userdate($this->instance->duedate));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
            
            // time remaining
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timeremaining', 'assign'));
            if ($this->instance->duedate - $time <= 0) {
                if (!$submission || $submission->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED &&
                    $submission->status != ASSIGN_SUBMISSION_STATUS_LOCKED) {
                    $cell2 = new html_table_cell(get_string('overdue', 'assign', format_time($time - $this->instance->duedate)));
                } else {
                    if ($submission->timemodified > $this->instance->duedate) {
                        $cell2 = new html_table_cell(get_string('submittedlate', 'assign', format_time($submission->timemodified - $this->instance->duedate)));
                    } else {
                        $cell2 = new html_table_cell(get_string('submittedearly', 'assign', format_time($submission->timemodified - $this->instance->duedate)));
                    }
                }
            } else {
                $cell2 = new html_table_cell(format_time($this->instance->duedate - $time));
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        } 

        // last modified 
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('timemodified', 'assign'));
        if ($submission) {
            $cell2 = new html_table_cell(userdate($submission->timemodified));
        } else {
            $cell2 = new html_table_cell();
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        if ($submission) {
            foreach ($this->submission_plugins as $plugin) {
                if ($plugin->is_enabled() && $plugin->is_visible()) {
                    $row = new html_table_row();
                    $cell1 = new html_table_cell($plugin->get_name());
                    $cell2 = new html_table_cell($plugin->view_summary($submission));
                    $row->cells = array($cell1, $cell2);
                    $t->data[] = $row;
                }
            }
        }
        
       
       
                      
        echo html_writer::table($t);
        echo $OUTPUT->box_end();
        
        echo $OUTPUT->container_end();
    }

    /**
     * display feedback
     * display submission status page 
     * @global object $OUTPUT
     * @global object $USER
     * @global object $PAGE
     * @global object $DB
     * @global object $CFG
     * @param int $userid
     * @return mixed
     */
    private function view_feedback($userid=null) {
        global $OUTPUT, $USER, $PAGE, $DB, $CFG;
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->dirroot.'/grade/grading/lib.php');

        if (!$userid) {
            $userid = $USER->id;
        }
        
        if ($userid == $USER->id) {
            // Always require view permission to do anything
            if (!has_capability('mod/assign:view', $this->context)) {
                return;
            }
        } else {
            if (!has_capability('mod/assign:view', $this->context) ||
                !has_capability('mod/assign:grade', $this->context)) {
                return;
            }
        }
        
        if (!is_enrolled($this->get_course_context(), $userid)) {
            return;
        }

        $submission = null;

        $assignment_grade = $this->get_grade($userid);
        if (!$assignment_grade) {
            return;
        }
        echo $OUTPUT->container_start('feedback');
        echo $OUTPUT->heading(get_string('feedback', 'assign'), 3);
        echo $OUTPUT->box_start('boxaligncenter', 'intro');
        $t = new html_table();
        
        $grading_info = grade_get_grades($this->get_course()->id, 'mod', 'assign', $this->instance->id, $userid);
        $item = $grading_info->items[0];
        $grade = $item->grades[$userid];

        if ($grade->hidden or $grade->grade === false) { // hidden or error
            return;
        }

        if ($grade->grade === null and empty($grade->str_feedback)) {   /// Nothing to show yet
            return;
        }

        $graded_date = $grade->dategraded;

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('grade', 'assign'));

        $grading_manager = get_grading_manager($this->context, 'mod_assign', 'submissions');
    
        if ($controller = $grading_manager->get_active_controller()) {
            $controller->set_grade_range(make_grades_menu($this->instance->grade));
            $cell2 = new html_table_cell($controller->render_grade($PAGE, $assignment_grade->id, $item, $grade->str_long_grade, has_capability('mod/assignment:grade', $this->context)));
        } else {

            $cell2 = new html_table_cell($this->display_grade($grade->str_long_grade));
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;
        
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('gradedon', 'assign'));
        $cell2 = new html_table_cell(userdate($graded_date));
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;
        
        if ($grader = $DB->get_record('user', array('id'=>$grade->usermodified))) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('gradedby', 'assign'));
            $cell2 = new html_table_cell($OUTPUT->user_picture($grader) . $OUTPUT->spacer(array('width'=>30)) . fullname($grader));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }
    
        foreach ($this->feedback_plugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                $feedback = $plugin->view_summary($assignment_grade);
                if ($feedback != '') {
                    $row = new html_table_row();
                    $cell1 = new html_table_cell($plugin->get_name());
                    $cell2 = new html_table_cell($feedback);
                    $row->cells = array($cell1, $cell2);
                    $t->data[] = $row;
                }
            }
        }
 

        echo html_writer::table($t);
        echo $OUTPUT->box_end();
        
        echo $OUTPUT->container_end();
    }
    
    /**
     * display submission links
     * @global object $OUTPUT
     * @global object $USER
     * @param int $userid 
     */
    private function view_submission_links($userid = null) {
        global $OUTPUT, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }
        
        if (has_capability('mod/assign:submit', $this->context) &&
            $this->submissions_open() && ($this->is_any_submission_plugin_enabled())) {
            // submission.php test
            echo $OUTPUT->single_button(new moodle_url('/mod/assign/view.php',
                array('id' => $this->get_course_module()->id, 'action' => 'editsubmission')), get_string('editsubmission', 'assign'), 'get');

            $submission = $this->get_submission($userid);

            if ($submission) {
                if ($submission->status == ASSIGN_SUBMISSION_STATUS_DRAFT) {
                    // submission.php test
                    echo $OUTPUT->single_button(new moodle_url('/mod/assign/view.php',
                        array('id' => $this->get_course_module()->id, 'action'=>'submit')), get_string('submitassignment', 'assign'), 'get');
                    echo $OUTPUT->box_start('boxaligncenter', 'intro');
                    echo get_string('submitassignment_help', 'assign');
                    echo $OUTPUT->box_end();
                }
            }
        }
    }
    
    /**
     *  add elements to submission form 
     * @global object $USER
     * @param object $mform
     * @param object $data 
     */
    public function add_submission_form_elements(& $mform, & $data) {
        global $USER;
        
        // online text submissions

        $submission = $this->get_submission($USER->id, null, false);
        
        $this->add_plugin_submission_elements($submission, $mform, $data);

        // hidden params
        $mform->addElement('hidden', 'id', $this->get_course_module()->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'savesubmission');
        $mform->setType('action', PARAM_TEXT);
        // buttons
        
    }

    /**
     * revert to draft
     * @global object $USER
     * @global object $DB
     * @return mixed
     */
    private function process_revert_to_draft() {
        global $USER, $DB;
        
        // Need view permission
        require_capability('mod/assign:view', $this->context);
        // Need grade permission
        require_capability('mod/assign:grade', $this->context);

        $userid = required_param('userid', PARAM_INT);

        $submission = $this->get_submission($userid, null, false);
        if (!$submission) {
            return;
        }
        $submission->status = ASSIGN_SUBMISSION_STATUS_DRAFT;
        $this->update_submission($submission, false);

        // update the modified time on the grade (grader modified)
        $grade = $this->get_grade($userid, true);
        $this->update_grade($grade);

        $user = $DB->get_record('user', array('id' => $userid));

        $this->add_to_log('revert submission to draft', get_string('reverttodraftforstudent', 'assign', array('id'=>$user->id, 'fullname'=>fullname($user))));
        
    }
    
    /**
     * lock  the process
     * @global object $USER
     * @global object $DB 
     */
    private function process_lock() {
        global $USER, $DB;
        
        // Need view permission
        require_capability('mod/assign:view', $this->context);
        // Need grade permission
        require_capability('mod/assign:grade', $this->context);

        $userid = required_param('userid', PARAM_INT);

        $grade = $this->get_grade($userid, true);
        $grade->locked = 1;
        $this->update_grade($grade);

        $user = $DB->get_record('user', array('id' => $userid));

        $this->add_to_log('lock submission', get_string('locksubmissionforstudent', 'assign', array('id'=>$user->id, 'fullname'=>fullname($user))));
    }
    
    /**
     * unlock the process
     * @global object $USER
     * @global object $DB 
     */
    private function process_unlock() {
        global $USER, $DB;

        // Need view permission
        require_capability('mod/assign:view', $this->context);
        // Need grade permission
        require_capability('mod/assign:grade', $this->context);

        $userid = required_param('userid', PARAM_INT);

        $grade = $this->get_grade($userid, true);
        $grade->locked = 0;
        $this->update_grade($grade);
        
        $user = $DB->get_record('user', array('id' => $userid));

        $this->add_to_log('unlock submission', get_string('unlocksubmissionforstudent', 'assign', array('id'=>$user->id, 'fullname'=>fullname($user))));
    }
  
    /**
     * save grade
     * @global object $USER
     * @global object $DB 
     */
    private function process_save_grade() {
        global $USER, $DB;
        
        require_capability('mod/assign:view', $this->context);
        // Need submit permission to submit an assignment
        require_capability('mod/assign:grade', $this->context);

        $rownum = required_param('rownum', PARAM_INT);
        $userid = $this->get_userid_for_row($rownum);

        $mform = new mod_assign_grade_form(null, array($this, null, array('rownum'=>$rownum, 'last'=>false)));

        
        if ($formdata = $mform->get_data()) {
            $grade = $this->get_grade($userid, true);
            $gradinginstance = $this->get_grading_instance($userid);
            if ($gradinginstance) {
                $grade->grade = $gradinginstance->submit_and_get_grade($formdata->advancedgrading, $grade->id);
            } else {
                $grade->grade= $formdata->grade;
            }
            $grade->grader= $USER->id;

            $this->update_grade($grade);

            // call save in plugins
            foreach ($this->feedback_plugins as $plugin) {
                if ($plugin->is_enabled() && $plugin->is_visible()) {
                    if (!$plugin->save($grade, $formdata)) {
                        $result = false;
                        print_error($plugin->get_error());
                    }
                }
            }


            $user = $DB->get_record('user', array('id' => $userid));

            $this->add_to_log('grade submission', $this->format_grade_for_log($grade));
             
       
        }
        
    }

}
