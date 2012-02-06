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

defined('MOODLE_INTERNAL') || die;

/**
 * Manage submission plugins page
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_page_managesubmissions extends admin_externalpage {
    /**
     * Constructor
     */
    public function __construct() {
        global $CFG;
        parent::__construct('managesubmissionplugins', get_string('managesubmissionplugins', 'assign'),
                new moodle_url('/mod/assign/admin_manage_plugins.php'));
    }

    /**
     * Search submission plugins for the specified string
     *
     * @param string $query The string to search for in question behaviours
     * @return array
     */
    public function search($query) {
        global $CFG;
        if ($result = parent::search($query)) {
            return $result;
        }

        $found = false;
        $textlib = textlib_get_instance();
        foreach (get_plugin_list('submission') as $name => $notused) {
            if (strpos($textlib->strtolower(get_string('pluginname', 'submission_' . $name)),
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


/**
 * Manage submission plugins
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_plugin_manager {

    private $pageurl; 
    private $error = ''; 

    /**
     * Constructor
     */
    public function __construct() {
        $this->pageurl = new moodle_url('/mod/assign/admin_manage_plugins.php');
    }


    /** 
     * Return a list of plugins sorted by the order defined in the admin interface
     * 
     * @return array of installed plugins
     */
    private function get_sorted_plugins_list() {
        $assignment = new assignment();

        return $assignment->get_submission_plugins();
    }
    
    /**
     * format icon for link
     * 
     * @global object $OUTPUT
     * @param string $action
     * @param string $plugintype
     * @param string $icon
     * @param string $alt
     * @return mixed
     */
    private function format_icon_link($action, $plugintype, $icon, $alt) {
        global $OUTPUT;

        return $OUTPUT->action_icon(new moodle_url('/mod/assign/admin_manage_plugins.php',
                array('action' => $action, 'plugin'=> $plugintype, 'sesskey' => sesskey())),
                new pix_icon($icon, $alt, 'moodle', array('title' => $alt)),
                null, array('title' => $alt)) . ' ';
    }

    
    /**
     * display plugin table
     * @global object $OUTPUT 
     */
    private function view_plugins_table() {
        global $OUTPUT;
        // Set up the table.
        $this->view_header();
        $table = new flexible_table('submissionpluginsadminttable');
        $table->define_baseurl($this->pageurl);
        $table->define_columns(array('pluginname', 'version', 'hideshow', 'order',
                'delete', 'settings'));
        $table->define_headers(array(get_string('submissionpluginname', 'assign'), 
                get_string('version'), get_string('hideshow', 'assign'),
                get_string('order'), get_string('delete'), get_string('settings')));
        $table->set_attribute('id', 'submissionplugins');
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
                        array('section' => 'submission_' . $plugin->get_type())), get_string('settings'));
            } else {
                $row[] = '&nbsp;';
            }
            $table->add_data($row);
        }
    
        $table->finish_output();
        $this->view_footer();
    }

    /**
     * display header
     * @global object $OUTPUT 
     */
    private function view_header() {
        global $OUTPUT;
        admin_externalpage_setup('managesubmissionplugins');
        // Print the page heading.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('managesubmissionplugins', 'assign'));
    }
    
    /**
     * display footer
     * 
     * @global object $OUTPUT 
     */
    private function view_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }

    /**
     *  plugin permission check
     * 
     */
    private function check_permissions() {
        // Check permissions.
        require_login();
        $systemcontext = get_context_instance(CONTEXT_SYSTEM);
        require_capability('moodle/site:config', $systemcontext);
    }
    
    /**
     * delete an installed plugin
     * 
     * @global object $CFG
     * @global object $DB
     * @param object $plugin
     * @return string 
     */
    private function delete_plugin($plugin) {
        global $CFG, $DB;
        $confirm = optional_param('confirm', null, PARAM_BOOL);

        if ($confirm) {
            // Delete any configuration records.
            if (!unset_all_config_for_plugin('submission_' . $plugin->get_type())) {
                $this->error = $OUTPUT->notification(get_string('errordeletingconfig', 'admin', 'submission_' . $plugin->get_type()));
            }

    
            unset_config('disabled', 'submission_' . $plugin->get_type());
            unset_config('sortorder', 'submission_' . $plugin->get_type());

            $DB->delete_records('assign_plugin_config', array('plugin'=>$plugin->get_type()));

            // Then the tables themselves
            drop_plugin_tables('submission_' . $plugin->get_type(), $CFG->dirroot . '/mod/assign/submission/' .$plugin->get_type(). '/db/install.xml', false);

            // Remove event handlers and dequeue pending events
            events_uninstall('submission_' . $plugin->get_type());

            return 'plugindeleted';
        } else {
            return 'confirmdelete';
        }
        
    }
    
    /**
     * view the deleted plugin
     * 
     * @global object $OUTPUT
     * @param object $plugin 
     */
    private function view_plugin_deleted($plugin) {
        global $OUTPUT;
        $this->view_header();
        echo $OUTPUT->heading(get_string('deletingplugin', 'assign', $plugin->get_name()));
        echo $this->error;
        echo $OUTPUT->notification(get_string('plugindeletefiles', 'moodle', array('name'=>$plugin->get_name(), 'directory'=>('/mod/assign/submission/'.$plugin->get_type()))));
        echo $OUTPUT->continue_button($this->pageurl);
        $this->view_footer();
    }

    /**
     * confirm when the plugin wants to be deleted
     * @global object $OUTPUT
     * @param object $plugin 
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
     * hide/disable an installed plugin
     * @param object $plugin
     * @return string 
     */
    private function hide_plugin($plugin) {
        $plugin->hide();
        return 'view';
    }
    
    /**
     * show an installed plugin
     * @param object $plugin
     * @return string
     */
    private function show_plugin($plugin) {
        $plugin->show();
        return 'view';
    }
    
    /**
     * move an installed plugin to a folder
     * 
     * @param object $plugin
     * @param string $dir
     * @return string 
     */
    private function move($plugin, $dir) {
        $plugin->move($dir);
        return 'view';
    }

    /**
     * execute an installed plugin
     * 
     * @param string $action
     * @param string $plugintype 
     */
    public function execute($action = null, $plugintype = null) {
        if ($action == null) {
            $action = 'view';
        }

        $this->check_permissions();

        $plugin = null;
        if ($plugintype != null) {
            $assignment = new assignment();
            $plugin = $assignment->get_submission_plugin_by_type($plugintype);        
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
}
