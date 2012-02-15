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

require_once('locallib.php');

/**
 * Adds an assignment instance
 *
 * This is done by calling the add_instance() method of the assignment type class
 * @param object $form_data
 * @return object 
 */
function assign_add_instance($form_data) {
    $context = get_context_instance(CONTEXT_COURSE,$form_data->course);
    $ass = new assignment($context, $form_data);
    return $ass->add_instance();
}

/**
 * delete an assignment instance 
 * @param int $id
 * @return object 
 */
function assign_delete_instance($id) {
    if (!$cm = get_coursemodule_from_instance('assign', $id)) {
        return false;
    }
    if (!$context = get_context_instance(CONTEXT_MODULE, $cm->id)) {
        return false;
    }

    $ass = new assignment($context);
    return $ass->delete_instance();
}

/**
 * Update an assignment instance
 *
 * This is done by calling the update_instance() method of the assignment type class
 * @param object $form_data
 * @return object
 */
function assign_update_instance($form_data) {
    $context = get_context_instance(CONTEXT_MODULE,$form_data->coursemodule);
    $ass = new assignment($context, $form_data);
    return $ass->update_instance();
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
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_ADVANCED_GRADING:        return true;

        default: return null;
    }
}

/**
 * Lists all gradable areas for the advanced grading methods gramework
 *
 * @return array
 */
function assign_grading_areas_list() {
    global $PAGE;     

    $cm = $PAGE->cm;
    $context = $cm->context;

    $ass = new assignment($context);
    return $ass->grading_areas_list();
}


/**
 * extend an assigment navigation settings   
 * 
 * @global object $PAGE
 * @param object $settings
 * @param navigation_node $navref
 * @return object
 */
function assign_extend_settings_navigation($settings, navigation_node $navref) {
    global $PAGE;     

    $cm = $PAGE->cm;
    $context = $cm->context;

    $ass = new assignment($context);
    return $ass->extend_settings_navigation($navref);
}

/**
 * Serves assignment submissions and other files.
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function assign_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB;

    
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }


    require_login($course, false, $cm);

    
    if (!$assignment = $DB->get_record('assign', array('id'=>$cm->instance))) {
        return false;
    }

    $assignmentinstance = new assignment($context);

    return $assignmentinstance->send_file($filearea, $args);
}

