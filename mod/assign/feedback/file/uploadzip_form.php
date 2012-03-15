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
 * This file contains the forms to create and edit an instance of this module
 *
 * @package   mod_assign
 * @subpackage assignfeedback_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


/** Include formslib.php */
require_once ($CFG->libdir.'/formslib.php');

define('ASSIGN_FILEAREA_FEEDBACK_FILES_ZIP', 'feedback_zip');

/*
 * Upload feedback zip file form
 *
 * @package   mod-assign
 * @subpackage assignfeedback_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignfeedback_file_uploadzip_form extends moodleform implements renderable {

    /** @var assignment_feedback_plugin $plugin*/
    public $plugin;

    function definition() {
        $mform = $this->_form;
        
        list($plugin, $data) = $this->_customdata;
        // visible elements
        $this->plugin = $plugin;
        $fileoptions = array('subdirs'=>0,
                                'maxfiles'=>1,
                                'accepted_types'=>'*.zip',
                                'return_types'=>FILE_INTERNAL);

        $mform->addElement('filepicker', 'uploadzip', get_string('zipfile', 'assignfeedback_file'), $fileoptions);
        $mform->addRule('uploadzip', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id', $plugin->get_assignment()->get_course_module()->id);
        $mform->addElement('hidden', 'action', 'plugingradingpage');
        $mform->addElement('hidden', 'gradingaction', 'submitupload');
        $mform->addElement('hidden', 'plugin', 'file');
        $mform->addElement('hidden', 'importid');
        $this->add_action_buttons(true, get_string('processzip', 'assignfeedback_file'));

        if ($data) {
            $this->set_data($data);
        }
    }

}
