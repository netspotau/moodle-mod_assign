<?php

defined('MOODLE_INTERNAL') || die;

    require_once('adminlib.php');

    $ADMIN->add('modules', new admin_category('submissionplugins',
            get_string('submissionplugins', 'assign'), !$module->visible));

    
    $ADMIN->add('submissionplugins', new admin_page_managesubmissions());



    $submission_plugins = get_plugin_list_with_file('submission', 'settings.php', false);
    $submission_pluginsbyname = array();
    foreach ($submission_plugins as $submission_plugin => $reportdir) {
        $strsubmission_pluginname = get_string('pluginname', 'submission_'.$submission_plugin);
        $submission_pluginsbyname[$strsubmission_pluginname] = $submission_plugin;
    }
    ksort($submission_pluginsbyname);

    $tmp_settings = $settings;
    foreach ($submission_pluginsbyname as $strsubmission_pluginname => $submission_plugin) {
        $submission_pluginname = $submission_plugin;

        $settings = new admin_settingpage('submission_'.$submission_pluginname,
                $strsubmission_pluginname, 'moodle/site:config', !$module->visible);
        if ($ADMIN->fulltree) {
            include($CFG->dirroot . "/mod/assign/submission/$submission_pluginname/settings.php");
        }
            
        $ADMIN->add('submissionplugins', $settings);
    }

    $settings = $tmp_settings;
    
