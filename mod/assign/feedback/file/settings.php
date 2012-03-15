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
 * This file defines the admin settings for this plugin
 *
 * @package   mod_assign
 * @subpackage   assignsubmission_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$visible = !get_config('assignfeedback_file', 'disabled');
$admin->add('assignfeedbackplugins', new admin_externalpage('assignfeedback_file_admin', 
                                                            get_string('uploadtemplatefeedbackfile', 'assignfeedback_file'), 
                                                            $CFG->wwwroot . '/mod/assign/feedback/file/admin.php',
                                                            'moodle/site:config', 
                                                             !$visible));
$settings = null;
