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
 * This file contains the upgrade code to upgrade from mod_assignment to mod_assign
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Include locallib.php */
require_once($CFG->dirroot.'/mod/assign/locallib.php');
/** Include accesslib.php */
require_once($CFG->libdir.'/accesslib.php');


/*
 * Class to manage upgrades from mod_assignment to mod_assign
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_upgrade_manager {
    
    /**
     * This function converts all of the base settings for an instance of
     * the old assignment to the new format. Then it calls each of the plugins
     * to see if they can help upgrade this assignment.
     * @param int old assignment id (don't rely on the old assignment type even being installed)
     * @param string log This string gets appended to during the conversion process
     * @return bool true or false
     */
    public function upgrade_assignment($oldassignmentid, $log, $delete=true) {
        global $DB, $CFG;
        // steps to upgrade an assignment
    
        global $DB, $CFG, $USER;
        // steps to upgrade an assignment
          
        // is the user the admin? admin check goes here 
        if (!is_siteadmin($USER->id)) {
              return false;
        }
        
        
        // first insert an assign instance to get the id
        $oldassignment = $DB->get_record('assignment', array('id'=>$oldassignmentid), '*', MUST_EXIST);

        $oldversion = get_config('assignment_' . $oldassignment->assignmenttype, 'version');

        $data = new stdClass();
        $data->course = $oldassignment->course;
        $data->name = $oldassignment->name;
        $data->intro = $oldassignment->intro;
        $data->introformat = $oldassignment->introformat;
        $data->sendnotifications = $oldassignment->emailteachers;
        $data->duedate = $oldassignment->timedue;
        $data->allowsubmissionsfromdate = $oldassignment->timeavailable;
        $data->grade = $oldassignment->grade;
        $data->submissiondrafts = $oldassignment->resubmit;
        $data->preventlatesubmissions = $oldassignment->preventlate;

        $newassignment = new assignment(null, null, null);
        
        if (!$newassignment->add_instance($data, false)) {
            $log = get_string('couldnotcreatenewassignmentinstance', 'mod_assign');
            return false;
        }

        // get the module details
        $oldmodule = $DB->get_record('modules', array('name'=>'assignment'), '*', MUST_EXIST);
        $oldcoursemodule = $DB->get_record('course_modules', array('module'=>$oldmodule->id, 'instance'=>$oldassignmentid), '*', MUST_EXIST);
        $oldcontext = context_module::instance($oldcoursemodule->id);
        
        $newmodule = $DB->get_record('modules', array('name'=>'assign'), '*', MUST_EXIST);
        $newcoursemodule = $this->duplicate_course_module($oldcoursemodule, $newmodule->id, $newassignment->get_instance()->id);
        if (!$newcoursemodule) {
            $log = get_string('couldnotcreatenewcoursemodule', 'mod_assign');
            return false;
        }

        // convert the base database tables (assignment, submission, grade) ignoring the 
        // unknown fields

        // from this point we want to rollback on failure
        $rollback = false;
        try {
            $newassignment->set_context(context_module::instance($newcoursemodule->id));
            // the course module has now been created - time to update the core tables
            $newassignment->copy_area_files_for_upgrade($oldcontext->id, 'mod_assignment', 'intro', 0, 
                                            $newassignment->get_context()->id, 'mod_assign', 'intro', 0);
        

            // get the plugins to do their bit
            foreach ($newassignment->get_submission_plugins() as $plugin) {
                if ($plugin->can_upgrade($oldassignment->assignmenttype, $oldversion)) {
                    $plugin->enable();
                    if (!$plugin->upgrade_settings($oldcontext, $oldassignment, $log)) {
                        $rollback = true;
                    }
                }
            }
            foreach ($newassignment->get_feedback_plugins() as $plugin) {
                if ($plugin->can_upgrade($oldassignment->assignmenttype, $oldversion)) {
                    $plugin->enable();
                    if (!$plugin->upgrade_settings($oldcontext, $oldassignment, $log)) {
                        $rollback = true;
                    }
                }
            }


            $oldsubmissions = $DB->get_records('assignment_submissions', array('assignment'=>$oldassignmentid));
            foreach ($oldsubmissions as $oldsubmission) {
                $submission = new stdClass();
                $submission->assignment = $newassignment->get_instance()->id;
                $submission->userid = $oldsubmission->userid;
                $submission->timecreated = $oldsubmission->timecreated;
                $submission->timemodified = $oldsubmission->timemodified;
                $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
                $submission->id = $DB->insert_record('assign_submission', $submission);
                if (!$submission->id) {
                    $log .= get_string('couldnotinsertsubmission', 'mod_assign', $submission->userid);
                    $rollback = true;
                }
                foreach ($newassignment->get_submission_plugins() as $plugin) {
                    if ($plugin->can_upgrade($oldassignment->assignmenttype, $oldversion)) {
                        if (!$plugin->upgrade($oldcontext, $oldassignment, $oldsubmission, $submission, $log)) {
                            $rollback = true;
                        }
                    }
                }
                if ($oldsubmission->timemarked) {
                    $grade = new stdClass();
                    $grade->assignment = $newassignment->get_instance()->id;
                    $grade->userid = $oldsubmission->userid;
                    $grade->grader = $oldsubmission->teacher;
                    $grade->timemodified = $oldsubmission->timemarked;
                    $grade->timecreated = $oldsubmission->timecreated;
                    // $grade->locked = $oldsubmission->locked;
                    $grade->grade = $oldsubmission->grade;
                    $grade->mailed = $oldsubmission->mailed;
                    $grade->id = $DB->insert_record('assign_grades', $grade);
                    if (!$grade->id) {
                        $log .= get_string('couldnotinsertgrade', 'mod_assign', $grade->userid);
                        $rollback = true;
                    }
                    foreach ($newassignment->get_feedback_plugins() as $plugin) {
                        if ($plugin->can_upgrade($oldassignment->assignmenttype, $oldversion)) {
                            if (!$plugin->upgrade($oldcontext, $oldassignment, $oldsubmission, $grade, $log)) {
                                $rollback = true;
                            }
                        }
                    }
                }
            }

            $newassignment->update_calendar();
            $newassignment->update_gradebook();
            
            // copy the grades from the old assignment to the new one
            $this->copy_grades_for_upgrade($oldassignment, $newassignment);

        } catch (Exception $exception) {
            $rollback = true;
            $log .= get_string('conversionexception', 'mod_assign', $exception->getMessage());
        }
    
        if ($rollback) {
            $newassignment->delete_instance();
            
            return false;
        }
        // all is well,
        // delete the old assignment (optional) (use object delete)
        if ($delete) {
            $cm = get_coursemodule_from_id('', $oldcoursemodule->id, $oldcoursemodule->course);
            if ($cm) {
                $this->delete_course_module($cm);
            }
        }
        rebuild_course_cache($oldcoursemodule->course);
        return true;
    }

    
    /**
     * Create a duplicate course module record so we can create the upgraded
     * assign module alongside the old assignment module.
     * 
     * @global stdClass $CFG
     * @global moodle_database $DB
     * @param stdClass $cm The old course module record
     * @param int $moduleid The id of the new assign module
     * @return mixed stdClass|bool The new course module record or FALSE
     */
    private function duplicate_course_module(stdClass $cm, $moduleid, $newinstanceid) {
        global $DB, $CFG;

        $newcm = new stdClass();
        $newcm->course           = $cm->course;
        $newcm->module           = $moduleid;
        $newcm->instance         = $newinstanceid;
        $newcm->visible          = $cm->visible;
        $newcm->section          = $cm->section;
        $newcm->score            = $cm->score;
        $newcm->indent           = $cm->indent;
        $newcm->groupmode        = $cm->groupmode;
        $newcm->groupingid       = $cm->groupingid;
        $newcm->groupmembersonly = $cm->groupmembersonly;
        $newcm->completion                = $cm->completion;
        $newcm->completiongradeitemnumber = $cm->completiongradeitemnumber;
        $newcm->completionview            = $cm->completionview;
        $newcm->completionexpected        = $cm->completionexpected;
        if(!empty($CFG->enableavailability)) {
            $newcm->availablefrom             = $cm->availablefrom;
            $newcm->availableuntil            = $cm->availableuntil;
            $newcm->showavailability          = $cm->showavailability;
        }
        $newcm->showdescription = $cm->showdescription;

        $newcmid = add_course_module($newcm);
        $newcm = get_coursemodule_from_id('', $newcmid, $cm->course);
        if (!$newcm) {
            return false;
        }
        $section = $DB->get_record("course_sections", array("id"=>$newcm->section));
        if (!$section) {
            return false;
        }

        $mod = new stdClass();
        $mod->course = $newcm->course;
        $mod->section = $section->section;
        $mod->coursemodule = $newcm->id;
        $mod->id = $newcm->id;
        $newcm->section = add_mod_to_section($mod, $cm);

        // make sure visibility is set correctly (in particular in calendar)
        // note: allow them to set it even without moodle/course:activityvisibility
        set_coursemodule_visible($newcm->id, $newcm->visible);

        return $newcm;
    }

    /**
     * This function deletes the old assignment course module after
     * it has been upgraded. This code is adapted from "course/mod.php".
     * 
     * @global stdClass $CFG
     * @global stdClass $USER
     * @global moodle_database $DB
     * @param stdClass $cm The course module to delete.
     * @return bool
     */
    private function delete_course_module($cm) {
        global $CFG, $USER, $DB;
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

        $coursecontext = context_course::instance($course->id);
        $modcontext = context_module::instance($cm->id);

        $modlib = "$CFG->dirroot/mod/$cm->modname/lib.php";

        if (file_exists($modlib)) {
            require_once($modlib);
        } else {
            print_error('modulemissingcode', '', '', $modlib);
        }

        $deleteinstancefunction = $cm->modname."_delete_instance";

        if (!$deleteinstancefunction($cm->instance)) {
            echo $OUTPUT->notification("Could not delete the $cm->modname (instance)");
        }

        // remove all module files in case modules forget to do that
        $fs = get_file_storage();
        $fs->delete_area_files($modcontext->id);

        if (!delete_course_module($cm->id)) {
            echo $OUTPUT->notification("Could not delete the $cm->modname (coursemodule)");
        }
        if (!delete_mod_from_section($cm->id, $cm->section)) {
            echo $OUTPUT->notification("Could not delete the $cm->modname from that section");
        }

        // Trigger a mod_deleted event with information about this module.
        $eventdata = new stdClass();
        $eventdata->modulename = $cm->modname;
        $eventdata->cmid       = $cm->id;
        $eventdata->courseid   = $course->id;
        $eventdata->userid     = $USER->id;
        events_trigger('mod_deleted', $eventdata);

        add_to_log($course->id, 'course', "delete mod",
                   "view.php?id=$cm->course",
                   "$cm->modname $cm->instance", $cm->id);

        return true;
    }

    /**
     * This function copies the grades from the old assignment module to this one.
     *
     * @global object CFG
     * @param stdClass $oldassignment old assignment data record
     * @param assignment $newassignment the new assignment class
     * @return bool true or false
     */
    public function copy_grades_for_upgrade($oldassignment, $newassignment) {
        global $CFG;

        require_once($CFG->libdir.'/gradelib.php');

        // get the old and new grade items
        $old_grade_items = grade_item::fetch_all(array('itemtype'=>'mod', 'itemmodule'=>'assignment', 'iteminstance'=>$oldassignment->id));
        if (!$old_grade_items) {
            return false;
        }
        $old_grade_item = array_pop($old_grade_items);
        if (!$old_grade_item) {
            return false;
        }
        $new_grade_items = grade_item::fetch_all(array('itemtype'=>'mod', 'itemmodule'=>'assign', 'iteminstance'=>$newassignment->get_instance()->id));
        if (!$new_grade_items) {
            return false;
        }
        $new_grade_item = array_pop($new_grade_items);
        if (!$new_grade_item) {
            return false;
        }

        $grade_grades = grade_grade::fetch_all(array('itemid'=>$old_grade_item->id));
        if ($grade_grades) {
            foreach ($grade_grades as $gradeid=>$grade) {
                $grade->itemid = $new_grade_item->id;
                grade_update('mod/assign', $newassignment->get_course()->id, 'mod', 'assign', $newassignment->get_instance()->id, 0, $grade, NULL);
            }
        }
        return true;
    }
    
}
