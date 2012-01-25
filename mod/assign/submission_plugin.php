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
    private $error = '';

    /**
     * Constructor for the abstract submission type class
     *
     * @param object $assignment 
     */
    public function __construct($assignment = null) {
        $this->assignment = $assignment;
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
     * Save any custom data for this student submission
     * 
     * @param object $mform - the data submitted from the form
     * @return boolean - on error the subtype should call set_error and return false.
     */
    public function save($mform) {
        return true;   
    }

    /**
     * Get any additional fields for the submission form for this assignment.
     * 
     * @param object $defaults - The list of default values for the settings added by this plugin
     * @return array - a list of form elements to include in the submission form
     */
    public function get_submission_form_elements() {
        return array();
    }

    /**
     * Should not output anything - return the result as a string so it can be consumed by webservices.
     * 
     * @return string - return a string representation of the submission in full
     */
    public function view() {
        return '';
    }
    
    /**
     * Should not output anything - return the result as a string so it can be consumed by webservices.
     * 
     * @return string - return a string representation of the submission in full
     */
    public function view_summary() {
        return '';
    }
}
