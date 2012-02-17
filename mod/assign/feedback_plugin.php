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
 * This file contains the function for feedback_plugin abstract class
 *
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Include assignment_plugin.php */
require_once('assignment_plugin.php');

/*
 * Abstract class for feedback_plugin inherited from assignment_plugin
 * abstract class.
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class feedback_plugin extends assignment_plugin {

    /**
     * return subtype name of the plugin
     * 
     * @return string
     */
    public function get_subtype() {
        return 'feedback';
    }
    
    /**
     * Upgrade the feedback from the old assignment to the new one
     * 
     * @param object $oldsubmission The data record for the old submission
     * @param string $log Record upgrade messages in the log
     * @return boolean true or false - false will trigger a rollback
     */
    public function upgrade_feedback($oldsubmission, $grade, & $log) {
        $log = $log . ' ' . get_string('upgradenotimplemented', 'mod_assign', array('type'=>$this->type, 'subtype'=>$this->get_subtype()));
        return false;
    }
}
