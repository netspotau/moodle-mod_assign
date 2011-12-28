<?php

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);  // Course Module ID

$url = new moodle_url('/mod/assign/view.php');


$cm = null;
$assignment = null;
$course = null;

if (!$cm = get_coursemodule_from_id('assign', $id)) {
    print_error('invalidcoursemodule');
}

if (!$assignment = $DB->get_record("assign", array("id" => $cm->instance))) {
    print_error('invalidid', 'assign');
}

if (!$course = $DB->get_record("course", array("id" => $assignment->course))) {
    print_error('coursemisconf', 'assign');
}
$url->param('id', $id);
$PAGE->set_url($url);


require_login($course, true, $cm);


$context = get_context_instance(CONTEXT_MODULE,$cm->id);
   

$ass = new assign_base($context,$assignment);
$ass->view();
