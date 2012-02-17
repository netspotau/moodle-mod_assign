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
 * This file contains the classes for the admin settings of the assign 
 * module.
 *
 * This class provides an interface for enabling and configuring
 * submission plugins.
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Include config.php */
require_once(dirname(__FILE__) . '/../../config.php');
/** Include adminlib.php */
require_once($CFG->libdir . '/adminlib.php');
/** Include tablelib.php */
require_once($CFG->libdir . '/tablelib.php');
/** Include locallib.php */
require_once('locallib.php');

defined('MOODLE_INTERNAL') || die();

/*
 * Admin external page that displays a list of the installed submission
 * plugins.
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_page_manage_assignment_plugins extends admin_externalpage {

    private $subtype = '';

    /**
     *
     * @global object $CFG
     * @param string $subtype 
     */
    public function __construct($subtype) {
        global $CFG;
        $this->subtype = $subtype;
        parent::__construct('manage' . $subtype . 'plugins', get_string('manage' . $subtype . 'plugins', 'assign'),
                new moodle_url('/mod/assign/admin_manage_plugins.php', array('subtype'=>$subtype)));
    }

    /**
     * Search plugins for the specified string
     *
     * @param string $query The string to search for
     * @return array
     */
    public function search($query) {
        global $CFG;
        if ($result = parent::search($query)) {
            return $result;
        }

        $found = false;
        $textlib = textlib_get_instance();
        foreach (get_plugin_list($this->subtype) as $name => $notused) {
            if (strpos($textlib->strtolower(get_string('pluginname', $this->subtype . '_' . $name)),
                    $query) !== false) {
                $found = true;
                break;
            }
        }
        if ($found) {
            $result = new stdClass();
            $result->page     = $this;
            $result->settings = array();
            return array($this->name => $result);
        } else {
            return array();
        }
    }
}


/*
 * Class that handles the display and configuration of the list of submission
 * plugins.
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_plugin_manager {

    /** @var object the url of the manage submission plugin page */
    private $pageurl; 
    /** @var string any error from the current action */
    private $error = ''; 
    /** @var string either submission or feedback */
    private $subtype = '';

    /**
     *
     * @param string $subtype 
     */
    public function __construct($subtype) {
        $this->pageurl = new moodle_url('/mod/assign/admin_manage_plugins.php', array('subtype'=>$subtype));
        $this->subtype = $subtype;
    }


    /** 
     * Return a list of plugins sorted by the order defined in the admin interface
     * @return array The list of plugins
     */
    private function get_sorted_plugins_list() {
        $assignment = new assignment();

        $functionname = 'get_' . $this->subtype . '_plugins';

        return $assignment->$functionname();
    }
    

    /** 
     * Util function for writing an action icon link
     * 
     * @global object $OUTPUT For writing to the page
     * @param string $action URL parameter to include in the link
     * @param string $plugintype URL parameter to include in the link
     * @param string $icon The key to the icon to use (e.g. 't/up')
     * @param string $alt The string description of the link used as the title and alt text
     * @return string The icon/link
     */
    private function format_icon_link($action, $plugintype, $icon, $alt) {
        global $OUTPUT;

        return $OUTPUT->action_icon(new moodle_url($this->pageurl,
                array('action' => $action, 'plugin'=> $plugintype, 'sesskey' => sesskey())),
                new pix_icon($icon, $alt, 'moodle', array('title' => $alt)),
                null, array('title' => $alt)) . ' ';
    }

    /** 
     * Write the HTML for the submission plugins table.
     * 
     * @global object $OUTPUT For writing to the page
     * @return None
     */
    private function view_plugins_table() {
        global $OUTPUT;
        // Set up the table.
        $this->view_header();
        $table = new flexible_table($this->subtype . 'pluginsadminttable');
        $table->define_baseurl($this->pageurl);
        $table->define_columns(array('pluginname', 'version', 'hideshow', 'order',
                'delete', 'settings'));
        $table->define_headers(array(get_string($this->subtype . 'pluginname', 'assign'), 
                get_string('version'), get_string('hideshow', 'assign'),
                get_string('order'), get_string('delete'), get_string('settings')));
        $table->set_attribute('id', $this->subtype . 'plugins');
        $table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthwide');
        $table->setup();


        $plugins = $this->get_sorted_plugins_list();

        foreach ($plugins as $plugin) {
            $row = array();
            $row[] = $plugin->get_name();
            $row[] = $plugin->get_version();

            if ($plugin->is_visible()) {
                $row[] = $this->format_icon_link('hide', $plugin->get_type(), 'i/hide', get_string('disable'));
            } else {
                $row[] = $this->format_icon_link('show', $plugin->get_type(), 'i/show', get_string('enable'));
            }

            $move_links = '';
            if (!$plugin->is_first()) {
                $move_links .= $this->format_icon_link('moveup', $plugin->get_type(), 't/up', get_string('up'));
            } else {
                $move_links .= $OUTPUT->spacer(array('width'=>15));
            }
            if (!$plugin->is_last()) {
                $move_links .= $this->format_icon_link('movedown', $plugin->get_type(), 't/down', get_string('down'));
            }
            $row[] = $move_links;

            if ($plugin->get_version() != '') {
                $row[] = $this->format_icon_link('delete', $plugin->get_type(), 't/delete', get_string('delete'));
            } else {
                $row[] = '&nbsp;';
            }   
            if ($plugin->get_version() != '' && $plugin->has_admin_settings()) {
                $row[] = html_writer::link(new moodle_url('/admin/settings.php',
                        array('section' => $this->subtype . '_' . $plugin->get_type())), get_string('settings'));
            } else {
                $row[] = '&nbsp;';
            }
            $table->add_data($row);
        }
    
        $table->finish_output();
        $this->view_footer();
    }

    /** 
     * Write the page header
     * 
     * @global object $OUTPUT For writing to the page
     * @return None
     */
    private function view_header() {
        global $OUTPUT;
        admin_externalpage_setup('manage' . $this->subtype . 'plugins');
        // Print the page heading.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('manage' . $this->subtype . 'plugins', 'assign'));
    }
    
    /** 
     * Write the page footer
     * 
     * @global object $OUTPUT For writing to the page
     * @return None
     */
    private function view_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }

    /** 
     * Check this user has permission to edit the list of installed plugins
     * 
     * @return None
     */
    private function check_permissions() {
        // Check permissions.
        require_login();
        $systemcontext = get_context_instance(CONTEXT_SYSTEM);
        require_capability('moodle/site:config', $systemcontext);
    }
    
    /** 
     * Delete the database and files associated with this plugin.
     * 
     * @global object $CFG global config
     * @global object $DB database connection
     * @param string $plugintype - The type of the plugin to delete
     * @return string the name of the next page to display
     */
    private function delete_plugin($plugin) {
        global $CFG, $DB;
        $confirm = optional_param('confirm', null, PARAM_BOOL);

        if ($confirm) {
            // Delete any configuration records.
            if (!unset_all_config_for_plugin('submission_' . $plugin->get_type())) {
                $this->error = $OUTPUT->notification(get_string('errordeletingconfig', 'admin', $this->subtype . '_' . $plugin->get_type()));
            }

    
            // Should be covered by the previous function - but just in case
            unset_config('disabled', $this->subtype . '_' . $plugin->get_type());
            unset_config('sortorder', $this->subtype . '_' . $plugin->get_type());

            // delete the plugin specific config settings
            $DB->delete_records('assign_plugin_config', array('plugin'=>$plugin->get_type(), 'subtype'=>$this->subtype));

            // Then the tables themselves
            drop_plugin_tables($this->subtype . '_' . $plugin->get_type(), $CFG->dirroot . '/mod/assign/' . $this->subtype . '/' .$plugin->get_type(). '/db/install.xml', false);

            // Remove event handlers and dequeue pending events
            events_uninstall($this->subtype . '_' . $plugin->get_type());

            // the page to display
            return 'plugindeleted';
        } else {
            // the page to display
            return 'confirmdelete';
        }
        
    }
    
    /** 
     * Show the page that gives the details of the plugin that was just deleted
     * 
     * @global object $OUTPUT For writing to the page
     * @param object $plugin - The plugin that was just deleted
     * @return None
     */
    private function view_plugin_deleted($plugin) {
        global $OUTPUT;
        $this->view_header();
        echo $OUTPUT->heading(get_string('deletingplugin', 'assign', $plugin->get_name()));
        echo $this->error;
        echo $OUTPUT->notification(get_string('plugindeletefiles', 'moodle', array('name'=>$plugin->get_name(), 'directory'=>('/mod/assign/' . $this->subtype . '/'.$plugin->get_type()))));
        echo $OUTPUT->continue_button($this->pageurl);
        $this->view_footer();
    }

    /** 
     * Show the page that asks the user to confirm they want to delete a plugin
     * 
     * @global object $OUTPUT For writing to the page
     * @param object $plugin - The plugin that will be deleted
     * @return None
     */
    private function view_confirm_delete($plugin) {
        global $OUTPUT;
        $this->view_header();
        echo $OUTPUT->heading(get_string('deletepluginareyousure', 'assign', $plugin->get_name()));
        echo $OUTPUT->confirm(get_string('deletepluginareyousuremessage', 'assign', $plugin->get_name()),
                new moodle_url($this->pageurl, array('action' => 'delete', 'plugin'=>$plugin->get_type(), 'confirm' => 1)),
                $this->pageurl);
        $this->view_footer();
    }

    /** 
     * Hide this plugin
     * 
     * @param string $plugin - The plugin to hide
     * @return string The next page to display
     */
    private function hide_plugin($plugin) {
        $plugin->hide();
        return 'view';
    }
    
    /** 
     * Show this plugin
     * 
     * @param string $plugin - The plugin to show
     * @return string The next page to display
     */
    private function show_plugin($plugin) {
        $plugin->show();
        return 'view';
    }
    
    /** 
     * Change the order of this plugin
     * 
     * @param string $plugin - The plugin to move
     * @param string $dir - up or down
     * @return string The next page to display
     */
    private function move($plugin, $dir) {
        $plugin->move($dir);
        return 'view';
    }

    /** 
     * This is the entry point for this controller class
     * 
     * @param string $action - The action to perform
     * @param string $plugintype - Optional name of a plugin type to perform the action on
     * @return None
     */
    public function execute($action = null, $plugintype = null) {
        if ($action == null) {
            $action = 'view';
        }

        $this->check_permissions();

        $plugin = null;
        if ($plugintype != null) {
            $assignment = new assignment();
            $functionname = 'get_' . $this->subtype . '_plugin_by_type';
            $plugin = $assignment->$functionname($plugintype);        
        }

        // process
        if ($action == 'delete' && $plugin != null) {
            $action = $this->delete_plugin($plugin);
        } else if ($action == 'hide' && $plugin != null) {
            $action = $this->hide_plugin($plugin);
        } else if ($action == 'show' && $plugin != null) {
            $action = $this->show_plugin($plugin);
        } else if ($action == 'moveup' && $plugin != null) {
            $action = $this->move($plugin, 'up');
        } else if ($action == 'movedown' && $plugin != null) {
            $action = $this->move($plugin, 'down');
        }


        // view
        if ($action == 'confirmdelete' && $plugin != null) {
            $this->view_confirm_delete($plugin);
        } else if ($action == 'plugindeleted' && $plugin != null) {
            $this->view_plugin_deleted($plugin);
        } else if ($action == 'view') {
            $this->view_plugins_table();
        }
    }

    /** 
     * This function adds plugin pages to the navigation menu
     * 
     * @param string $subtype - The type of plugin (submission or feedback)
     * @param object $admin - The handle to the admin menu
     * @param object $settings - The handle to current node in the navigation tree
     * @param object $module - The handle to the current module
     * @return None
     */
    static function add_admin_assignment_plugin_settings($subtype, & $admin, & $settings, $module) {
        global $CFG;

        $plugins = get_plugin_list_with_file($subtype, 'settings.php', false);
        $pluginsbyname = array();
        foreach ($plugins as $plugin => $plugindir) {
            $str_pluginname = get_string('pluginname', $subtype . '_'.$plugin);
            $pluginsbyname[$str_pluginname] = $plugin;
        }
        ksort($pluginsbyname);

        $tmp_settings = $settings;
        foreach ($pluginsbyname as $str_pluginname => $plugin) {
            $pluginname = $plugin;

            $settings = new admin_settingpage($subtype . '_'.$pluginname,
                    $str_pluginname, 'moodle/site:config', !$module->visible);
            if ($admin->fulltree) {
                include($CFG->dirroot . "/mod/assign/$subtype/$pluginname/settings.php");
            }
                
            $admin->add($subtype . 'plugins', $settings);
        }

        $settings = $tmp_settings;
    
    }
}
