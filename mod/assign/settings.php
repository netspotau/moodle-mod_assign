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

require_once($CFG->dirroot . '/mod/assign/adminlib.php');
                                              
$ADMIN->add('modules', new admin_category('assignmentplugins',
         new lang_string('assignmentplugins', 'assign'), !$module->visible));
$ADMIN->add('assignmentplugins', new admin_category('assignsubmissionplugins',
        new lang_string('submissionplugins', 'assign'), !$module->visible));
$ADMIN->add('assignsubmissionplugins', new admin_page_manage_assignment_plugins('assignsubmission'));
$ADMIN->add('assignmentplugins', new admin_category('assignfeedbackplugins',
        new lang_string('feedbackplugins', 'assign'), !$module->visible));
$ADMIN->add('assignfeedbackplugins', new admin_page_manage_assignment_plugins('assignfeedback'));

assignment_plugin_manager::add_admin_assignment_plugin_settings('assignsubmission', $ADMIN, $settings, $module);
assignment_plugin_manager::add_admin_assignment_plugin_settings('assignfeedback', $ADMIN, $settings, $module);

foreach (get_plugin_list('assignfeedback') as $type => $notused) {
    $visible = !get_config('assignfeedback_' . $type, 'disabled');
    if ($visible) {
        $menu['assignfeedback_' . $type] = new lang_string('pluginname', 'assignfeedback_' . $type);
    }
}

$settings->add(new admin_setting_configcheckbox('mod_assign_submission_receipts',
        new lang_string('sendsubmissionreceipts', 'mod_assign'), new lang_string('sendsubmissionreceipts_help', 'mod_assign'), 1));

$settings->add(new admin_setting_configselect('mod_assign_feedback_plugin_for_gradebook', 
                      new lang_string('feedbackpluginforgradebook', 'mod_assign'),
                      new lang_string('feedbackplugin', 'mod_assign'), 'feedback_comments', $menu));

$settings->add(new admin_setting_configtextarea('assign/submission_statement', 
                      new lang_string('submissionstatement', 'mod_assign'),
                      new lang_string('submissionstatement_desc', 'mod_assign'), ''));

$settings->add(new admin_setting_configcheckbox('assign/require_submission_statement',
                      new lang_string('requiresubmissionstatement', 'mod_assign'), 
                      new lang_string('requiresubmissionstatement_desc', 'mod_assign'), 1));

