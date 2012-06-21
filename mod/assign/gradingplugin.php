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
 * This file contains the function for grading_plugin abstract class
 *
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Include assignmentplugin.php */
require_once($CFG->dirroot.'/mod/assign/assignmentplugin.php');

/**
 * Abstract class for grading_plugin inherited from assign_plugin abstract class.
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class assign_grading_plugin extends assign_plugin {

    /**
     * return subtype name of the plugin
     *
     * @return string
     */
    public function get_subtype() {
        return 'assigngrading';
    }

    /**
     * return a list of the batch operations that can be performed by this plugin.
     * This is used to construct the "with selected" form at the bottom of the grading table.
     * If clicked batch_operation() in this plugin will be called with the action and a list of submissions as parameters
     *
     * @return array - Array of ($action => $description)
     */
    public function get_batch_operations() {
        return array();
    }

    /**
     * Called when this menu item is chosen from the grading actions menu
     *
     * @param string The chosen action
     * @return string - Output
     */
    public function batch_operation($action, $userids) {
        return '';
    }

    /**
     * return a list of the single operations that can be performed by this plugin.
     * This is used to construct the "with selected" form at the bottom of the grading table.
     * If clicked single_operation() in this plugin will be called with the action and a list of submissions as parameters
     *
     * @return array - Array of ($action => $description)
     */
    public function get_single_operations() {
        return array();
    }

    /**
     * Called when this menu item is chosen from the single operations menu
     *
     * @param string The chosen action
     * @return string - Output
     */
    public function single_operation($action, $userid) {
        return '';
    }

    /**
     * return a list of the grading actions that can be performed by this plugin.
     * This is used to construct the navigation menu at the top of the grading table.
     * If clicked grading_action() in this plugin will be called with the action as a parameter
     *
     * @return array - Array of ($action => $description)
     */
    public function get_grading_actions() {
        return array();
    }

    /**
     * Called when this menu item is chosen from the grading actions menu
     *
     * @param string $action The chosen action
     * @return string - Output
     */
    public function grading_action($action) {
        return '';
    }

    /**
     * Called to return a custom status message about this user
     *
     * @param int $userid The user id to report the status of
     * @return string - Output
     */
    public function get_status_message($userid) {
        return '';
    }

}
