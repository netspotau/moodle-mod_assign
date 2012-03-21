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

/*
 * Upload grades form
 *
 * @package   mod-assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assign_uploadgrades_form extends moodleform implements renderable {

    /** @var assignment_feedback_plugin $plugin*/
    public $assignment;

    function definition() {
        $mform = $this->_form;
        
        list($assignment, $data) = $this->_customdata;
        // visible elements
        $this->assignment = $assignment;
        $fileoptions = array('subdirs'=>0,
                                'maxfiles'=>1,
                                'accepted_types'=>'*.csv',
                                'return_types'=>FILE_INTERNAL);

        $mform->addElement('filepicker', 'uploadgrades', get_string('gradesfile', 'assign'), $fileoptions);
        $mform->addRule('uploadgrades', null, 'required', null, 'client');
        $mform->addElement('checkbox', 'ignoremodified', get_string('ignoremodifieddate', 'assign'));

        $mform->addElement('hidden', 'id', $this->assignment->get_course_module()->id);
        $mform->addElement('hidden', 'action', 'uploadgradesfile');
        $mform->addElement('hidden', 'importid');
        $this->add_action_buttons(true, get_string('importgrades', 'assign'));

        if ($data) {
            $this->set_data($data);
        }
    }
}

/*
 * Import grades form
 *
 * @package   mod-assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assign_importgrades_form extends moodleform implements renderable {

    /** @var assignment $assignment */
    public $assignment;

    /** 
     * Print a user - but not if blind marking is enabled
     * @param stdClass user
     * @return string
     */
    private function showuser($user) {
        if (!$this->assignment->is_blind_marking()) {
            return fullname($user);
        } else {
            return get_string('hiddenuser', 'assign', $this->assignment->get_uniqueid_for_user($user->id));
        }
    }

    function definition() {
        $mform = $this->_form;
        
        list($assignment, $csvdata, $data) = $this->_customdata;
        // visible elements
        $this->assignment = $assignment;
        $importid = null;
        if ($data) {
            $importid = $data->importid;
        }
        $update = false;

        $ignoremodified = optional_param('ignoremodified', 0, PARAM_BOOL);

        if ($importid != null) {

            $gradeimporter = new mod_assign_gradeimporter($importid, $assignment);

            if ($csvdata) {
                $gradeimporter->parsecsv($csvdata);
            }
            if (!$gradeimporter->init()) {
                print_error('invalidgradeimport', 'assign');
                return;
            }
            
            $mform->addElement('header', 'importactions', get_string('importactions', 'assign'));
            $step = 0;
            while ($record = $gradeimporter->next()) {
                $user = $record->user;
                $grade = $record->grade;
                $modified = $record->modified;

                $usergrade = $assignment->get_user_grade($user->id, false);
                // Note: we lose the seconds when converting to user date format - so must not count seconds in comparision
                if (!$ignoremodified && ($usergrade && $usergrade->timemodified > ($modified + 60))) {
                    $mform->addElement('static', 'row' . $step, get_string('skiprecord', 'assign'), get_string('reason', 'assign', get_string('graderecentlymodified', 'assign', $this->showuser($user))));
                } else if (!$grade || $grade == '-' || $grade < 0) {
                    $mform->addElement('static', 'row' . $step, get_string('skiprecord', 'assign'), get_string('reason', 'assign', get_string('nogradeinimport', 'assign', $this->showuser($user))));
                } else {
                    $update = true;
                    $mform->addElement('static', 'row' . $step, get_string('updaterecord', 'assign'), get_string('gradeupdate', 'assign', array('grade'=>$grade, 'student'=>$this->showuser($user))));
                }
                    
                $step += 1;
            }
            
            $gradeimporter->close(false);
        }

        $mform->addElement('hidden', 'id', $this->assignment->get_course_module()->id);
        $mform->addElement('hidden', 'action', 'importgradesfile');
        $mform->addElement('hidden', 'importid');
        $mform->addElement('hidden', 'ignoremodified', $ignoremodified);
        if ($update) {
            $this->add_action_buttons(true, get_string('applychanges', 'assign'));
        } else {
            $mform->addElement('cancel');
            $mform->closeHeaderBefore('cancel');
        }

        if ($data) {
            $this->set_data($data);
        }
    }
}

class mod_assign_gradeimporter {

    /** var string $importid - unique id for this import operation - must be passed between requests */
    private $importid;

    /** @var csv_import_reader $csvreader - the csv importer class */
    private $csvreader;
    
    /** @var assignment $assignment - the assignment class */
    private $assignment;

    /** @var int $gradeindex the column index containing the grades */
    private $gradeindex = -1;

    /** @var int $idindex the column index containing the unique id  */
    private $idindex = -1;

    /** @var int $modifiedindex the column index containing the last modified time */
    private $modifiedindex = -1;

    /** @var array $validusers only the enrolled users with the correct capability in this course */
    private $validusers;

    /**
     * Constructor
     * 
     * @param string importid
     */
    function __construct($importid, assignment $assignment) {
        $this->importid = $importid;
        $this->assignment = $assignment;
    }

    /**
     * Parse a csv file and save the content to a temp file
     * Should be called before init()
     * 
     * @return bool false is a failed import
     */
    function parsecsv($csvdata) {
        $this->csvreader = new csv_import_reader($this->importid, 'mod_assign');
        $this->csvreader->load_csv_content($csvdata, 'utf-8', 'comma');
    }

    /**
     * Initialise the import reader and locate the column indexes. 
     * 
     * @return bool false is a failed import
     */
    function init() {
        if ($this->csvreader == null) {
            $this->csvreader = new csv_import_reader($this->importid, 'mod_assign');
        }
        $this->csvreader->init();

        $columns = $this->csvreader->get_columns();

        $strgrade = get_string('grade');
        $strid = get_string('recordid', 'assign');
        $strmodified = get_string('lastmodifiedgrade', 'assign');

        if ($columns) {
            foreach ($columns as $index => $column) {
                if ($column == $strgrade) {
                    $this->gradeindex = $index;
                }
                if ($column == $strid) {
                    $this->idindex = $index;
                }
                if ($column == $strmodified) {
                    $this->modifiedindex = $index;
                }
            }
        }
            
        if ($this->idindex < 0 || $this->gradeindex < 0 || $this->modifiedindex < 0) {
            return false;
        }

        $groupmode = groups_get_activity_groupmode($this->assignment->get_course_module());
        $groupid = 0;   // All users
        $groupname = '';
        if ($groupmode) {
            $groupid = groups_get_activity_group($this->assignment->get_course_module(), true);
            $groupname = groups_get_group_name($groupid).'-';
        }
        $this->validusers = $this->assignment->list_participants($groupid, false);
    
        return true;
    }

    /**
     * Get the next row of data from the csv file (only the columns we care about)
     * 
     * @global moodle_database $DB
     * @return stdClass or false The stdClass is an object containing user, grade and lastmodified
     */
    function next() {
        global $DB;
        $result = new stdClass();
    
        while ($record = $this->csvreader->next()) {
            $idcolumn = $record[$this->idindex];
            $id = array_pop(explode(' ', $idcolumn));

            if ($userid = $this->assignment->get_user_for_uniqueid($id)) {
                if (array_key_exists($userid, $this->validusers)) {
                    $result->grade = $record[$this->gradeindex];
                    $result->modified = strtotime($record[$this->modifiedindex]);
                    $result->user = $this->validusers[$userid];
                    return $result;
                }
            }
        }

        // if we got here the csvreader had no more rows
        return false;
    }

    /**
     * Close the grade importer file and optionally delete any temp files
     * 
     * @param bool $delete
     */
    function close($delete) {
        $this->csvreader->close();
        if ($delete) {
            $this->csvreader->cleanup();
        }
    }
}
