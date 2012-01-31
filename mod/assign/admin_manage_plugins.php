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
 * @package    moodlecore
 * @subpackage questionbank
 * @copyright  2008 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once('locallib.php');

// Check permissions.
require_login();
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/site:config', $systemcontext);

admin_externalpage_setup('managesubmissionplugins');
$thispageurl = new moodle_url('/mod/assign/admin_manage_plugins.php');

/*
// Get some data we will need - question counts and which types are needed.
$counts = $DB->get_records_sql("
        SELECT qtype, COUNT(1) as numquestions, SUM(hidden) as numhidden
        FROM {question} GROUP BY qtype", array());
$needed = array();
foreach ($qtypes as $qtypename => $qtype) {
    if (!isset($counts[$qtypename])) {
        $counts[$qtypename] = new stdClass;
        $counts[$qtypename]->numquestions = 0;
        $counts[$qtypename]->numhidden = 0;
    }
    $needed[$qtypename] = $counts[$qtypename]->numquestions > 0;
    $counts[$qtypename]->numquestions -= $counts[$qtypename]->numhidden;
}
$needed['missingtype'] = true; // The system needs the missing question type.
foreach ($qtypes as $qtypename => $qtype) {
    foreach ($qtype->requires_qtypes() as $reqtype) {
        $needed[$reqtype] = true;
    }
}
foreach ($counts as $qtypename => $count) {
    if (!isset($qtypes[$qtypename])) {
        $counts['missingtype']->numquestions += $count->numquestions - $count->numhidden;
        $counts['missingtype']->numhidden += $count->numhidden;
    }
}

// Work of the correct sort order.
$config = get_config('question');
$sortedqtypes = array();
foreach ($qtypes as $qtypename => $qtype) {
    $sortedqtypes[$qtypename] = $qtype->local_name();
}
$sortedqtypes = question_bank::sort_qtype_array($sortedqtypes, $config);

*/
// Process actions ============================================================

if ((optional_param('action', '', PARAM_PLUGIN) == 'hide') && confirm_sesskey() && 
        ($plugintype = optional_param('plugin', '', PARAM_PLUGIN))) {
    $assignment = new assignment();
    $plugin = $assignment->get_submission_plugin_by_type($plugintype);        
    if ($plugin) {
        $plugin->hide();
    }
}
if ((optional_param('action', '', PARAM_PLUGIN) == 'show') && confirm_sesskey() && 
        ($plugintype = optional_param('plugin', '', PARAM_PLUGIN))) {
    $assignment = new assignment();
    $plugin = $assignment->get_submission_plugin_by_type($plugintype);        
    if ($plugin) {
        $plugin->show();
    }
}

if ((optional_param('action', '', PARAM_PLUGIN) == 'moveup') && confirm_sesskey() && 
        ($plugintype = optional_param('plugin', '', PARAM_PLUGIN))) {
    $assignment = new assignment();
    $plugin = $assignment->get_submission_plugin_by_type($plugintype);        
    if ($plugin) {
        $plugin->move('up');
    }
}
if ((optional_param('action', '', PARAM_PLUGIN) == 'movedown') && confirm_sesskey() && 
        ($plugintype = optional_param('plugin', '', PARAM_PLUGIN))) {
    $assignment = new assignment();
    $plugin = $assignment->get_submission_plugin_by_type($plugintype);        
    if ($plugin) {
        $plugin->move('down');
    }
}

// Delete.
if ((optional_param('action', '', PARAM_PLUGIN) == 'delete') && confirm_sesskey() && 
        ($plugintype = optional_param('plugin', '', PARAM_PLUGIN))) {

    $assignment = new assignment();
    $plugin = $assignment->get_submission_plugin_by_type($plugintype);        

    // If not yet confirmed, display a confirmation message.
    if (!optional_param('confirm', '', PARAM_BOOL)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deletepluginareyousure', 'assign', $plugin->get_name()));
        echo $OUTPUT->confirm(get_string('deletepluginareyousuremessage', 'assign', $plugin->get_name()),
                new moodle_url($thispageurl, array('action' => 'delete', 'plugin'=>$plugin->get_type(), 'confirm' => 1)),
                $thispageurl);
        echo $OUTPUT->footer();
        exit;
    }

    // Do the deletion.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deletingplugin', 'assign', $plugin->get_name()));

    // Delete any configuration records.
    if (!unset_all_config_for_plugin('submission_' . $plugin->get_type())) {
        echo $OUTPUT->notification(get_string('errordeletingconfig', 'admin', 'submission_' . $plugin->get_type()));
    }

    
    unset_config($plugin->get_type() . '_disabled', 'submission');
    unset_config($plugin->get_type() . '_sortorder', 'submission');

    // Then the tables themselves
    drop_plugin_tables($plugin->get_type(), $CFG->dirroot . '/mod/assign/submission/' .$plugin->get_type(). '/db/install.xml', false);

    // Remove event handlers and dequeue pending events
    events_uninstall('submission_' . $plugin->get_type());

    echo $OUTPUT->continue_button($thispageurl);
    echo $OUTPUT->footer();
    exit;
}

// End of process actions ==================================================

// Print the page heading.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managesubmissionplugins', 'assign'));

// Set up the table.
$table = new flexible_table('submissionpluginsadminttable');
$table->define_baseurl($thispageurl);
$table->define_columns(array('pluginname', 'version', 'hideshow', 'order',
        'delete', 'settings'));
$table->define_headers(array(get_string('submissionpluginname', 'assign'), 
        get_string('version'), get_string('hideshow', 'assign'),
        get_string('order'), get_string('delete'), get_string('settings')));
$table->set_attribute('id', 'submissionplugins');
$table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthwide');
$table->setup();

function format_icon_link($action, $plugintype, $icon, $alt) {
    global $OUTPUT;

    return $OUTPUT->action_icon(new moodle_url('/mod/assign/admin_manage_plugins.php',
            array('action' => $action, 'plugin'=> $plugintype, 'sesskey' => sesskey())),
            new pix_icon($icon, $alt, 'moodle', array('title' => $alt)),
            null, array('title' => $alt)) . ' ';
}

$assignment = new assignment();

$plugins = $assignment->get_submission_plugins();

foreach ($plugins as $plugin) {
    $row = array();
    $row[] = $plugin->get_name();
    $row[] = $plugin->get_version();

    if ($plugin->is_visible()) {
        $row[] = format_icon_link('hide', $plugin->get_type(), 'i/hide', get_string('disable'));
    } else {
        $row[] = format_icon_link('show', $plugin->get_type(), 'i/show', get_string('enable'));
    }

    $move_links = '';
    if (!$plugin->is_first()) {
        $move_links .= format_icon_link('moveup', $plugin->get_type(), 't/up', get_string('up'));
    } else {
        $move_links .= $OUTPUT->spacer(array('width'=>15));
    }
    if (!$plugin->is_last()) {
        $move_links .= format_icon_link('movedown', $plugin->get_type(), 't/down', get_string('down'));
    }
    $row[] = $move_links;

    if ($plugin->get_version() != '') {
        $row[] = format_icon_link('delete', $plugin->get_type(), 't/delete', get_string('delete'));
    } else {
        $row[] = '&nbsp;';
    }   
    if ($plugin->get_version() != '' && $plugin->has_admin_settings()) {
        $row[] = html_writer::link(new moodle_url('/admin/settings.php',
                array('section' => 'submission_' . $plugin->get_type())), get_string('settings'));
    } else {
        $row[] = '&nbsp;';
    }
    
    $table->add_data($row);
}

$table->finish_output();

echo $OUTPUT->footer();
