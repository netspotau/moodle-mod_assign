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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/** config.php */
require_once('../../config.php');
/** Include locallib.php */
require_once('locallib.php');

$id = required_param('id', PARAM_INT);  // Course Module ID

$url = new moodle_url('/mod/assign/view.php');


$cm = null;
$assignment = null;
$course = null;

if (!$cm = get_coursemodule_from_id('assign', $id)) {
    print_error('invalidcoursemodule');
}

if (!$assignment = $DB->get_record('assign', array('id' => $cm->instance))) {
    print_error('invalidid', 'assign');
}

if (!$course = $DB->get_record('course', array('id' => $assignment->course))) {
    print_error('coursemisconf', 'assign');
}
$url->param('id', $id);


require_login($course, true, $cm);
$PAGE->set_url($url);
$PAGE->requires->js('/mod/assign/assign.js');
$PAGE->requires->css('/mod/assign/style.css');


$context = get_context_instance(CONTEXT_MODULE,$cm->id);
   
$ass = new assignment($context,$assignment,$cm,$course);
$ass->view(optional_param('action', '', PARAM_TEXT));
