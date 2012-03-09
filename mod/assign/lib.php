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
 * @param mod_assign_mod_form $form_data
 * @return int The instance id of the new assignment 
 */
function assign_add_instance(stdClass $data, mod_assign_mod_form $form) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assign/locallib.php');

    $assignment = new assignment(context_module::instance($data->coursemodule));
    return $assignment->add_instance($data, true);
}

/**
 * delete an assignment instance 
 * @global stdClass CFG
 * @param int $id
 * @return object|bool 
 */
function assign_delete_instance($id) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    $cm = get_coursemodule_from_instance('assign', $id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    $assignment = new assignment($context);
    return $assignment->delete_instance();
}

/**
 * Update an assignment instance
 *
 * This is done by calling the update_instance() method of the assignment type class
 * @param mod_assign_mod_form $form_data
 * @return object
 */
function assign_update_instance(mod_assign_mod_form $form_data) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    $context = context_module::instance($form_data->coursemodule);
    $assignment = new assignment($context);
    return $ass->update_instance($form_data);
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
 * @global object $PAGE
 * @param object $settings
 * @param navigation_node $navref
 * @return void
 */
function assign_extend_settings_navigation($settings, navigation_node $navref) {
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
 * @global USER
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function assign_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $USER;
    
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }


    require_login($course, false, $cm);
    
    $userid = (int)array_shift($args);

    // check is users submission or has grading permission
    if ($USER->id != $userid and !has_capability('mod/assign:grade', $context)) {
        return false;
    }
        
    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/mod_assign/$filearea/$userid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, true); // download MUST be forced - security!
}

