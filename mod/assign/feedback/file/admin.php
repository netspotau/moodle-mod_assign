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
 * Allows the admin to manage assignment plugins
 *
 * @package     mod_assign
 * @subpackage  assignfeedback_file
 * @copyright   2012 NetSpot {@link http://www.netspot.com.au}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Include config.php */
require_once(dirname(__FILE__) . '/../../../../config.php');
/** Include adminlib.php */
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/assign/feedback/file/admin_form.php');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_capability('moodle/site:config', $systemcontext);
$PAGE->requires->css('/mod/assign/feedback/file/styles.css');

admin_externalpage_setup('assignfeedback_file_admin');
// Print the page heading.
$fs = get_file_storage();

$adminform = new assignfeedback_file_admin_form();

$data = $adminform->get_data();
if ($data) {
    $draftid = (int)$data->templatefeedbackfile;
    $context = context_user::instance($USER->id);
    $count = count($fs->get_area_files($context->id, 'user', 'draft', $draftid));
    if ($count || isset($data->delete)) { 
        $fs->delete_area_files($systemcontext->id, 'mod_assign', 'template_feedback_file', 1);
    }
    if (!isset($data->delete)) {
        $adminform->save_stored_file('templatefeedbackfile', $systemcontext->id, 'mod_assign', 'template_feedback_file', 1, '/');
    }
    $fs->delete_area_files($context->id, 'user', 'draft', $draftid);
}
    
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uploadtemplatefeedbackfile', 'assignfeedback_file'));

echo $OUTPUT->container_start('currenttemplatefeedbackfile');
echo $OUTPUT->box_start('generalbox');

$files = $fs->get_area_files($systemcontext->id, 'mod_assign', 'template_feedback_file', 1);
$templatefileexists = false;
echo get_string('templatefeedbackfile', 'assignfeedback_file') . ': ';
foreach ($files as $file) {
    if ($file->get_filename() != ".") {
        $icon = mimeinfo("icon", $file->get_filename());
        $image = $OUTPUT->pix_icon("f/$icon", $file->get_filename(), 'moodle', array('class'=>'icon'));
        $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/'.$systemcontext->id.'/mod_assign/template_feedback_file/' . $file->get_itemid() . '/'. $file->get_filepath().$file->get_filename(), true);

        echo $OUTPUT->action_link($url, $image . $file->get_filename());
        $templatefileexists = true;
    }
}
if (!$templatefileexists) {
    echo get_string('notemplatefeedbackfile', 'assignfeedback_file');
}
echo $OUTPUT->box_end();
echo $OUTPUT->container_end();

$adminform->display();


echo $OUTPUT->footer();
