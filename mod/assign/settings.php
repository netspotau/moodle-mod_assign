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
 * This file adds the settings pages to the navigation menu
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once('adminlib.php');

$ADMIN->add('modules', new admin_category('assignmentplugins',
        get_string('assignmentplugins', 'assign'), !$module->visible));
$ADMIN->add('assignmentplugins', new admin_category('submissionplugins',
        get_string('submissionplugins', 'assign'), !$module->visible));

$ADMIN->add('submissionplugins', new admin_page_manage_assignment_plugins('submission'));

$ADMIN->add('assignmentplugins', new admin_category('feedbackplugins',
        get_string('feedbackplugins', 'assign'), !$module->visible));

$ADMIN->add('feedbackplugins', new admin_page_manage_assignment_plugins('feedback'));


assignment_plugin_manager::add_admin_assignment_plugin_settings('submission', $ADMIN, $settings, $module);
assignment_plugin_manager::add_admin_assignment_plugin_settings('feedback', $ADMIN, $settings, $module);

