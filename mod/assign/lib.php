<?PHP

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
 * This file contains the moodle hooks for the assign module. 
 * It delegates most functions to the assignment class.
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Adds an assignment instance
 *
 * This is done by calling the add_instance() method of the assignment type class
 * @global stdClass CFG
 * @param stdClass $data
 * @param mod_assign_mod_form $form
 * @return int The instance id of the new assignment 
 */
function assign_add_instance(stdClass $data, mod_assign_mod_form $form) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assign/locallib.php');

    $assignment = new assignment(context_module::instance($data->coursemodule), null, null);
    return $assignment->add_instance($data, true);
}

/**
 * delete an assignment instance 
 * @global stdClass CFG
 * @param int $id
 * @return bool 
 */
function assign_delete_instance($id) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    $cm = get_coursemodule_from_instance('assign', $id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    $assignment = new assignment($context, null, null);
    return $assignment->delete_instance();
}

/**
 * Update an assignment instance
 *
 * This is done by calling the update_instance() method of the assignment type class
 * @global stdClass CFG
 * @param stdClass $data
 * @param mod_assign_mod_form $form
 * @return object
 */
function assign_update_instance(stdClass $data, mod_assign_mod_form $form) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    $context = context_module::instance($data->coursemodule);
    $assignment = new assignment($context, null, null);
    return $assignment->update_instance($data);
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function assign_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_ADVANCED_GRADING:        return true;

        default: return null;
    }
}

/**
 * Lists all gradable areas for the advanced grading methods gramework
 *
 * @return array('string'=>'string') An array with area names as keys and descriptions as values
 */
function assign_grading_areas_list() {
    return array('submissions'=>get_string('submissions', 'assign'));
}


/**
 * extend an assigment navigation settings   
 * 
 * @global moodle_page $PAGE
 * @param settings_navigation $settings
 * @param navigation_node $navref
 * @return void
 */
function assign_extend_settings_navigation(settings_navigation $settings, navigation_node $navref) {
    global $PAGE;     

    $cm = $PAGE->cm;
    if (!$cm) {
        return;
    }

    $context = $cm->context;
    $course = $PAGE->course;
        

    if (!$course) {
        return;
    }

    
   // Link to gradebook
   if (has_capability('gradereport/grader:view', $cm->context) && has_capability('moodle/grade:viewall', $cm->context)) {
       $link = new moodle_url('/grade/report/grader/index.php', array('id' => $course->id));
       $node = $navref->add(get_string('viewgradebook', 'assign'), $link, navigation_node::TYPE_SETTING);
   }

   // Link to download all submissions
   if (has_capability('mod/assign:grade', $context)) {
       $link = new moodle_url('/mod/assign/view.php', array('id' => $cm->id,'action'=>'downloadall'));
       $node = $navref->add(get_string('downloadall', 'assign'), $link, navigation_node::TYPE_SETTING);
   }

}

/**
 * Serves assignment submissions and other files.
 *
 * @global stdClass USER
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function assign_pluginfile($course, $cm, context $context, $filearea, $args, $forcedownload) {
    global $USER, $DB;
    
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);
    $itemid = (int)array_shift($args);
    if (strpos($filearea, "submission") === 0) {
        $record = $DB->get_record('assign_submission', array('id'=>$itemid), 'userid', MUST_EXIST);
        $userid = $record->userid;
    } else if (strpos($filearea, "feedback") === 0) {
        $record = $DB->get_record('assign_grades', array('id'=>$itemid), 'userid', MUST_EXIST);
        $userid = $record->userid;
    }
    // check is users submission or has grading permission
    if ($USER->id != $userid and !has_capability('mod/assign:grade', $context)) {
        return false;
    }

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/mod_assign/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, true); // download MUST be forced - security!
}


/**
 * Add a get_coursemodule_info function in case any assignment type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param $coursemodule object The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses will know about (most noticeably, an icon).
 */
function assign_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    if (! $assignment = $DB->get_record('assign', array('id'=>$coursemodule->instance),
            'id, name, alwaysshowdescription, allowsubmissionsfromdate, intro, introformat')) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $assignment->name;
    if ($coursemodule->showdescription) {
        if ($assignment->alwaysshowdescription || time() > $assignment->allowsubmissionsfromdate) {
            // Convert intro to html. Do not filter cached version, filters run at display time.
            $result->content = format_module_intro('assign', $assignment, $coursemodule->id, false);
        }
    }
    return $result;
}

/**
 * Print an overview of all assignments
 * for the courses.
 *
 */
function assign_print_overview($courses, &$htmlarray) {
    global $USER, $CFG, $DB;

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$assignments = get_all_instances_in_courses('assign',$courses)) {
        return;
    }

    $assignmentids = array();

    // Do assignment_base::isopen() here without loading the whole thing for speed
    foreach ($assignments as $key => $assignment) {
        $time = time();
        if ($assignment->duedate) {
            if ($assignment->preventlatesubmissions) {
                $isopen = ($assignment->allowsubmissionsfromdate <= $time && $time <= $assignment->duedate);
            } else {
                $isopen = ($assignment->allowsubmissionsfromdate <= $time);
            }
        }
        if (empty($isopen) || empty($assignment->duedate)) {
            unset($assignments[$key]);
        } else {
            $assignmentids[] = $assignment->id;
        }
    }

    if (empty($assignmentids)){
        // no assignments to look at - we're done
        return true;
    }
    
    $strduedate = get_string('duedate', 'assign');
    $strduedateno = get_string('duedateno', 'assign');
    $strgraded = get_string('graded', 'assign');
    $strnotgradedyet = get_string('notgradedyet', 'assign');
    $strnotsubmittedyet = get_string('notsubmittedyet', 'assign');
    $strsubmitted = get_string('submitted', 'assign');
    $strassignment = get_string('modulename', 'assign');
    $strreviewed = get_string('reviewed','assign');


    // NOTE: we do all possible database work here *outside* of the loop to ensure this scales
    //
    list($sqlassignmentids, $assignmentidparams) = $DB->get_in_or_equal($assignmentids);

    // build up and array of unmarked submissions indexed by assignment id/ userid
    // for use where the user has grading rights on assignment
    $rs = $DB->get_recordset_sql("SELECT s.assignment as assignment, s.userid as userid, s.id as id, s.status as status, g.timemodified as timegraded
                            FROM {assign_submission} s LEFT JOIN {assign_grades} g ON s.userid = g.userid and s.assignment = g.assignment
                            WHERE g.timemodified = 0 OR s.timemodified > g.timemodified
                            AND s.assignment $sqlassignmentids", $assignmentidparams);

    $unmarkedsubmissions = array();
    foreach ($rs as $rd) {
        $unmarkedsubmissions[$rd->assignment][$rd->userid] = $rd->id;
    }
    $rs->close();

     
    // get all user submissions, indexed by assignment id
    $mysubmissions = $DB->get_records_sql("SELECT a.id AS assignment, g.timemodified AS timemarked, g.grader AS grader, g.grade AS grade, s.status AS status
                            FROM {assign} a LEFT JOIN {assign_grades} g ON g.assignment = a.id AND g.userid = ? LEFT JOIN {assign_submission} s ON s.assignment = a.id AND s.userid = ?
                            AND a.id $sqlassignmentids", array_merge(array($USER->id, $USER->id), $assignmentidparams));
      
    foreach ($assignments as $assignment) {
        $str = '<div class="assign overview"><div class="name">'.$strassignment. ': '.
               '<a '.($assignment->visible ? '':' class="dimmed"').
               'title="'.$strassignment.'" href="'.$CFG->wwwroot.
               '/mod/assign/view.php?id='.$assignment->coursemodule.'">'.
               $assignment->name.'</a></div>';
        if ($assignment->duedate) {
            $str .= '<div class="info">'.$strduedate.': '.userdate($assignment->duedate).'</div>';
        } else {
            $str .= '<div class="info">'.$strduedateno.'</div>';
        }
        $context = get_context_instance(CONTEXT_MODULE, $assignment->coursemodule);
        if (has_capability('mod/assign:grade', $context)) {

            // count how many people can submit
            $submissions = 0; // init
            if ($students = get_enrolled_users($context, 'mod/assign:view', 0, 'u.id')) {
                foreach ($students as $student) {
                    if (isset($unmarkedsubmissions[$assignment->id][$student->id])) {
                        $submissions++;
                    }
                }
            }

            if ($submissions) {
                $link = new moodle_url('/mod/assign/view.php', array('id'=>$assignment->coursemodule, 'action'=>'grading'));
                $str .= '<div class="details"><a href="'.$link.'">'.get_string('submissionsnotgraded', 'assign', $submissions).'</a></div>';
            }
        } if (has_capability('mod/assign:submit', $context)) {
            $str .= '<div class="details">';
            $str .= get_string('mysubmission', 'assign');
            $submission = $mysubmissions[$assignment->id];
            if (!$submission->status || $submission->status == 'draft') {
                $str .= $strnotsubmittedyet;
            } else {
                $str .= get_string('submissionstatus_' . $submission->status, 'assign');
            }
            if (!$submission->grade || $submission->grade < 0) {
                $str .= ', ' . get_string('notgraded', 'assign');                
            } else {
                $str .= ', ' . get_string('graded', 'assign');
            }
            $str .= '</div>';
        }
       $str .= '</div>';
        if (empty($htmlarray[$assignment->course]['assign'])) {
            $htmlarray[$assignment->course]['assign'] = $str;
        } else {
            $htmlarray[$assignment->course]['assign'] .= $str;
        }
    }
}

/** 
 * function to list the actions that correspond to a view of this module
 * This is used by the participation report
 * @return array
 */
function assign_get_view_actions() {
    return array('view submission', 'view feedback');
}

/** 
 * function to list the actions that correspond to a post of this module
 * This is used by the participation report
 * @return array
 */
function assign_get_post_actions() {
    return array('upload', 'submit', 'submit for grading');
}

/**
 * Call cron on the assign module
 */
function assign_cron() {
    global $CFG;

    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    //assignment::cron();    
    $plugins = get_plugin_list('assignsubmission');
    
    foreach ($plugins as $name => $plugin) {
        $disabled = get_config('assignsubmission_' . $name, 'disabled');
        if (!$disabled) {
            $class = 'assignment_submission_' . $name;
            require_once($CFG->dirroot . '/mod/assign/submission/' . $name . '/lib.php');
            $class::cron();
        }
    }
    $plugins = get_plugin_list('assignfeedback');
    
    foreach ($plugins as $name => $plugin) {
        $disabled = get_config('assignfeedback_' . $name, 'disabled');
        if (!$disabled) {
            $class = 'assignment_feedback_' . $name;
            require_once($CFG->dirroot . '/mod/assign/feedback/' . $name . '/lib.php');
            $class::cron();
        }
    }
}
