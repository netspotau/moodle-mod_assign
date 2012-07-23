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
 * @package   assignfeedback_offline
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


/** Include formslib.php */
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/assign/feedback/offline/importgradeslib.php');

/*
 * Import grades form
 *
 * @package   assignfeedback_offline
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignfeedback_offline_import_grades_form extends moodleform implements renderable {

    /**
     * Create this grade import form
     */
    function definition() {
        global $CFG, $PAGE;

        $mform = $this->_form;
        $params = $this->_customdata;

        $renderer = $PAGE->get_renderer('assign');

        // visible elements
        $assignment = $params['assignment'];
        $csvdata = $params['csvdata'];
        $gradeimporter = $params['gradeimporter'];
        $update = false;

        $ignoremodified = $params['ignoremodified'];
        $draftid = $params['draftid'];

        if (!$gradeimporter) {
            print_error('invalidarguments');
            return;
        }

        if ($csvdata) {
            $gradeimporter->parsecsv($csvdata);
        }
        if (!$gradeimporter->init()) {
            print_error('invalidgradeimport', 'assignfeedback_offline', $CFG->wwwroot.'/mod/assign/view.php?action=viewpluginpage&pluginsubtype=assignfeedback&plugin=offline&pluginaction=uploadgrades&id='.$assignment->get_course_module()->id);
            return;
        }

        $mform->addElement('header', 'importgrades', get_string('importgrades', 'assignfeedback_offline'));

        $updates = array();
        while ($record = $gradeimporter->next()) {
            $user = $record->user;
            $grade = $record->grade;
            $modified = $record->modified;
            $userdesc = fullname($user);
            if ($assignment->is_blind_marking()) {
                $userdesc = get_string('hiddenuser', 'assign') . $assignment->get_unique_id_for_user($user->id);
            }

            $usergrade = $assignment->get_user_grade($user->id, false);
            // Note: we lose the seconds when converting to user date format - so must not count seconds in comparision
            $skip = false;

            $stalemodificationdate = ($usergrade && $usergrade->timemodified > ($modified + 60));

            if ($usergrade && $usergrade->grade == $grade) {
                // skip - grade not modified
                $skip = true;
            } else if (!isset($grade) || $grade == '-' || $grade < 0) {
                // skip - grade has no value
                $skip = true;
            } else if (!$ignoremodified && $stalemodificationdate) {
                // skip - grade has been modified
                $skip = true;
            } else if ($assignment->grading_disabled($user->id)) {
                // skip grade is locked
                $skip = true;
            }

            if (!$skip) {
                $update = true;
                $updates[] = get_string('gradeupdate', 'assignfeedback_offline',
                                            array('grade'=>$grade, 'student'=>$userdesc));
            }

            if ($ignoremodified || !$stalemodificationdate) {
                foreach ($record->feedback as $feedback) {
                    $plugin = $feedback['plugin'];
                    $field = $feedback['field'];
                    $newvalue = $feedback['value'];
                    $description = $feedback['description'];
                    $oldvalue = '';
                    if ($usergrade) {
                        $oldvalue = $plugin->get_editor_text($field, $usergrade->id);
                    }
                    if ($newvalue != $oldvalue) {
                        $update = true;
                        $updates[] = get_string('feedbackupdate', 'assignfeedback_offline',
                                                    array('text'=>$newvalue, 'field'=>$description, 'student'=>$userdesc));
                    }
                }
            }

        }
        $gradeimporter->close(false);

        if ($update) {
            $mform->addElement('html', $renderer->list_block_contents(array(), $updates));
        } else {
            $mform->addElement('html', get_string('nochanges', 'assignfeedback_offline'));
        }

        $mform->addElement('hidden', 'id', $assignment->get_course_module()->id);
        $mform->addElement('hidden', 'action', 'viewpluginpage');
        $mform->addElement('hidden', 'confirm', 'true');
        $mform->addElement('hidden', 'plugin', 'offline');
        $mform->addElement('hidden', 'pluginsubtype', 'assignfeedback');
        $mform->addElement('hidden', 'pluginaction', 'uploadgrades');
        $mform->addElement('hidden', 'importid', $gradeimporter->importid);
        $mform->addElement('hidden', 'ignoremodified', $ignoremodified);
        $mform->addElement('hidden', 'draftid', $draftid);
        if ($update) {
            $this->add_action_buttons(true, get_string('confirm'));
        } else {
            $mform->addElement('cancel');
            $mform->closeHeaderBefore('cancel');
        }

    }
}

