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
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/mod/assign/feedback/file/locallib.php');

/**
 * Assignment grading options form
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assign_batch_set_marking_workflow_state_form extends moodleform {
    /**
     * Define this form - called by the parent constructor
     */
    public function definition() {
        global $COURSE, $USER;

        $mform = $this->_form;
        $params = $this->_customdata;

        $mform->addElement('header', '', get_string('batchsetmarkingworkflowstateforusers', 'assign', count($params['users'])));
        $mform->addElement('static', 'userslist', get_string('selectedusers', 'assign'), $params['usershtml']);

        $options = $params['markingworkflowstates'];
        $mform->addElement('select', 'markingworkflowstate', get_string('markingworkflowstate', 'assign'), $options);

        $mform->addElement('hidden', 'id', $params['cm']);
        $mform->addElement('hidden', 'action', 'setbatchmarkingworkflowstate');
        $mform->addElement('hidden', 'selectedusers', implode(',', $params['users']));
        $this->add_action_buttons(true, get_string('savechanges'));

    }

}

