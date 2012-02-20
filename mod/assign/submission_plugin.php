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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the definition for the abstract class for submission_plugin
 *
 * This class provides all the functionality for submission plugins.
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('assignment_plugin.php');

/*
 * Abstract base class for submission plugin types.
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class submission_plugin extends assignment_plugin {
    
    /**
     * return subtype name of the plugin
     * 
     * @return string
     */
    public function get_subtype() {
        return 'submission';
    }

    /**
     * This plugin accepts submissions from a student
     * The comments plugin has no submission component so should not be counted 
     * when determining whether to show the edit submission link.
     * @return boolean
     */
    public function allow_submissions() {
        return true;
    }
     
    /**
     * Upgrade the submission from the old assignment to the new one
     * 
     * @param object $oldassignment The data record for the old assignment
     * @param object $oldsubmission The data record for the old submission
     * @param string $log Record upgrade messages in the log
     * @return boolean true or false - false will trigger a rollback
     */
    public function upgrade_submission($oldassignment, $oldsubmission, $submission, & $log) {
        $log = $log . ' ' . get_string('upgradenotimplemented', 'mod_assign', array('type'=>$this->type, 'subtype'=>$this->get_subtype()));
        return false;
    }
}
