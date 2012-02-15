<?php

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

