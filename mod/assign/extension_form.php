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
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


/** Include locallib.php */
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/*
 * Assignment extension form
 *
 * @package   mod-assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assign_extension_form extends moodleform implements renderable {

    var $assignment;
    var $user;

    function definition() {
        $mform = $this->_form;

        list($assignment, $user, $data) = $this->_customdata;
        $this->assignment = $assignment;
        $this->user = $user;

        if ($this->assignment->get_instance()->allowsubmissionsfromdate) {
            $mform->addElement('static', 'allowsubmissionsfromdate', get_string('allowsubmissionsfromdate', 'assign'), userdate($this->assignment->get_instance()->allowsubmissionsfromdate));
        }
        if ($this->assignment->get_instance()->duedate) {
            $mform->addElement('static', 'duedate', get_string('duedate', 'assign'), userdate($this->assignment->get_instance()->duedate));
        }
        if ($this->assignment->get_instance()->cutoffdate) {
            $mform->addElement('static', 'cutoffdate', get_string('cutoffdate', 'assign'), userdate($this->assignment->get_instance()->cutoffdate));
        }
        $mform->addElement('date_time_selector', 'extensionduedate', get_string('extensionduedate', 'assign'), array('optional'=>true));
        $mform->setDefault('extensionduedate', NULL);
        $mform->addElement('hidden', 'id', $assignment->get_course_module()->id);
        $mform->addElement('hidden', 'userid', $user->id);
        $mform->addElement('hidden', 'action', 'saveextension');
        $this->add_action_buttons(true, get_string('savechanges', 'assign'));
        if ($data) {
            $this->set_data($data);
        }
    }
    
    /**
     * Perform minimal validation on the extension form
     * @param array $data
     * @param array $files
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($this->assignment->get_instance()->duedate && $data['extensionduedate']) {
            if ($this->assignment->get_instance()->duedate > $data['extensionduedate']) {
                $errors['extensionduedate'] = get_string('extensionnotafterduedate', 'assign');
            }
        }
        if ($this->assignment->get_instance()->allowsubmissionsfromdate && $data['extensionduedate']) {
            if ($this->assignment->get_instance()->allowsubmissionsfromdate > $data['extensionduedate']) {
                $errors['extensionduedate'] = get_string('extensionnotafterfromdate', 'assign');
            }
        }
        
        return $errors;
    }
}

