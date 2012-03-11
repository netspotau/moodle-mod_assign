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
 * This file is the entry point to the assign module. All pages are rendered from here
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** config.php */
require_once('../../config.php');
/** Include locallib.php */
require_once($CFG->dirroot . '/mod/assign/locallib.php');


$id = required_param('id', PARAM_INT);  // Course Module ID
$url = new moodle_url('/mod/assign/view.php'); // Base URL

$cm = null;
$assignment = null;
$course = null;

// get the request parameters
$cm = get_coursemodule_from_id('assign', $id, 0, false, MUST_EXIST);

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);


$url->param('id', $id);

// Auth
require_login($course, true, $cm);
$PAGE->set_url($url);

$context = context_module::instance($cm->id);

require_capability('mod/assign:view', $context);
   
$assignment = new assignment($context,$cm,$course);

// Get the assignment to render the page
$assignment->view(optional_param('action', '', PARAM_TEXT));
