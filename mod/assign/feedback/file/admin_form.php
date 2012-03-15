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
 * This file contains the submission form used by the assign module.
 *
 * @package    mod_assign
 * @subpackage assignfeedback_file
 * @copyright  2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


require_once($CFG->libdir . '/formslib.php');

/*
 * Admin upload template feedback file form
 *
 * @package    mod-assign
 * @subpackage assignfeedback_file
 * @copyright  2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignfeedback_file_admin_form extends moodleform {

    function definition() {
        $mform = $this->_form;
        $mform->addElement('header', get_string('uploadtemplatefeedbackfile', 'assignfeedback_file'));

        $mform->addElement('filepicker', 'templatefeedbackfile', get_string('newtemplatefeedbackfile', 'assignfeedback_file'));

        $group=array();
        $group[] =& $mform->createElement('submit', 'delete', get_string('delete'));
        $group[] =& $mform->createElement('submit', 'save', get_string('savechanges'));
        
        $mform->addGroup($group);
    }
}

