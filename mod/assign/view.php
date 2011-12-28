<?php

require_once("../../config.php");

$id = optional_param('id', 0, PARAM_INT);  // Course Module ID
$a  = optional_param('a', 0, PARAM_INT);   // Assignment ID

$url = new moodle_url('/mod/assign/view.php');
$PAGE->set_url($url);
require_login($course, true, $cm);

echo "View";
