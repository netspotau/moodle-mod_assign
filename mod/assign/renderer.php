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
 * This file contains a renderer for the assignment class
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Include locallib.php */
require_once('locallib.php');


/**
 * A custom renderer class that extends the plugin_renderer_base and
 * is used by the assign module.
 *
 * @package mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class mod_assign_renderer extends plugin_renderer_base {
      
    /**
     * rendering assignment files 
     * 
     * @param object $context
     * @param int $userid
     * @param string $filearea
     * @return string
     */
    public function assign_files($context, $userid, $filearea='submission') {
        return $this->render(new assign_files($context, $userid, $filearea));
    }

    /**
     * rendering assignment files 
     * 
     * @param assign_files $tree
     * @return mixed
     */
    public function render_assign_files(assign_files $tree) {
        $module = array('name'=>'mod_assign_files', 'fullpath'=>'/mod/assign/assign.js', 'requires'=>array('yui2-treeview'));
        $this->htmlid = 'assign_files_tree_'.uniqid();
        $this->page->requires->js_init_call('M.mod_assign.init_tree', array(true, $this->htmlid));
        $html = '<div id="'.$this->htmlid.'">';
        $html .= $this->htmllize_tree($tree, $tree->dir);
        $html .= '</div>';

        if ($tree->portfolioform) {
            $html .= $tree->portfolioform;
        }
        return $html;
    }
    
    /**
     * Utility function to add a row of data to a table with 2 columns. Modified
     * the table param and does not return a value
     * 
     * @param object $t The table to append the row of data to
     * @param string $first The first column text
     * @param string $second The second column text
     * @return None
     */
    private function add_table_row_tuple(& $t, $first, $second) {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell2 = new html_table_cell($second);
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;
    }

    /**
     * render the header
     * 
     * @param assignment_header $header
     * @return string
     */
    public function render_assignment_header(assignment_header $header) {
        $o = '';

        if ($header->get_sub_page()) {
            $this->navbar->add($header->get_sub_page());
        }

        $this->page->set_title(get_string('pluginname', 'assign'));
        $this->page->set_heading($header->get_assignment()->get_instance()->name);

        $o .= $this->output->header();
        $o .= $this->output->heading($header->get_assignment()->get_instance()->name);

        if ($header->get_show_intro()) {
            if ($header->get_assignment()->get_instance()->alwaysshowdescription || 
                    time() > $header->get_assignment()->get_instance()->allowsubmissionsfromdate) {
                $o .= $this->output->box_start('generalbox boxaligncenter', 'intro');
                $o .= format_module_intro('assign', $header->get_assignment()->get_instance(), $header->get_assignment()->get_course_module()->id);
                $o .= $this->output->box_end();
            }
        }
        return $o;
    }
    
    /**
     * render a table containing the current status of the grading process
     * 
     * @param grading_summary $summary
     * @return string
     */
    public function render_grading_summary(grading_summary $summary) {
        // create a table for the data
        $o = '';
        $o .= $this->output->container_start('gradingsummary');
        $o .= $this->output->heading(get_string('gradingsummary', 'assign'), 3);
        $o .= $this->output->box_start('boxaligncenter gradingsummarytable');
        $t = new html_table();

        // status
        $this->add_table_row_tuple($t, get_string('numberofparticipants', 'assign'), 
                                   $summary->get_assignment()->count_enrolled_users_with_capability('mod/assign:submit'));

        // drafts
        if ($summary->get_assignment()->get_instance()->submissiondrafts) {
            $this->add_table_row_tuple($t, get_string('numberofdraftsubmissions', 'assign'), 
                                       $summary->get_assignment()->count_submissions_with_status(ASSIGN_SUBMISSION_STATUS_DRAFT));
       }

        // submitted for grading
        $this->add_table_row_tuple($t, get_string('numberofsubmittedassignments', 'assign'), 
                                       $summary->get_assignment()->count_submissions_with_status(ASSIGN_SUBMISSION_STATUS_SUBMITTED));

        $time = time();
        if ($summary->get_assignment()->get_instance()->duedate) {
            // due date
            // submitted for grading
            $duedate = $summary->get_assignment()->get_instance()->duedate;
            $this->add_table_row_tuple($t, get_string('duedate', 'assign'), 
                                       userdate($duedate));

            // time remaining
            $due = '';
            if ($duedate - $time <= 0) {
                $due = get_string('assignmentisdue', 'assign');
            } else {
                $due = format_time($duedate - $time);
            }
            $this->add_table_row_tuple($t, get_string('timeremaining', 'assign'), $due);
        }

        // all done - write the table
        $o .= html_writer::table($t);
        $o .= $this->output->box_end();
        
        // link to the grading page
        $o .= $this->output->single_button(new moodle_url('/mod/assign/view.php',
            array('id' => $summary->get_assignment()->get_course_module()->id ,'action'=>'grading')), get_string('viewgrading', 'assign'), 'get');

        // close the container and insert a spacer
        $o .= $this->output->container_end();

        return $o;
    }

    /**
     * render a table containing the current status of the submission
     * 
     * @param submission_status $status
     * @return string
     */
    public function render_submission_status(submission_status $status) {
        $o = '';
        $o .= $this->output->container_start('submissionstatus');
        $o .= $this->output->heading(get_string('submissionstatusheading', 'assign'), 3);
        $time = time();
        if ($status->get_view() == $status::STUDENT_VIEW && 
            !$status->get_assignment()->is_any_submission_plugin_enabled()) {

            $o .= $this->output->box_start('generalbox boxaligncenter nosubmissionrequired');
            $o .= get_string('noonlinesubmissions', 'assign');
            $o .= $this->output->box_end();
        }

        if ($status->get_assignment()->get_instance()->allowsubmissionsfromdate &&
                $time <= $status->get_assignment()->get_instance()->allowsubmissionsfromdate) {
            $o .= $this->output->box_start('generalbox boxaligncenter submissionsalloweddates');
            $o .= get_string('allowsubmissionsfromdatesummary', 'assign', userdate($this->instance->allowsubmissionsfromdate));
            $o .= $this->output->box_end();
        } 
        $o .= $this->output->box_start('boxaligncenter submissionsummarytable');

        $t = new html_table();

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('submissionstatus', 'assign'));
        if ($status->get_submission()) {
            $cell2 = new html_table_cell(get_string('submissionstatus_' . $status->get_submission()->status, 'assign'));
        } else {
            $cell2 = new html_table_cell(get_string('nosubmission', 'assign'));
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;
        
        // status
        if ($status->is_locked()) {
            $row = new html_table_row();
            $cell1 = new html_table_cell();
            $cell2 = new html_table_cell(get_string('submissionslocked', 'assign'));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // grading status
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('gradingstatus', 'assign'));

        if ($status->is_graded()) {
            $cell2 = new html_table_cell(get_string('graded', 'assign'));
        } else {
            $cell2 = new html_table_cell(get_string('notgraded', 'assign'));
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        
        $duedate = $status->get_assignment()->get_instance()->duedate;
        if ($duedate >= 1) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('duedate', 'assign'));
            $cell2 = new html_table_cell(userdate($duedate));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
            
            // time remaining
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timeremaining', 'assign'));
            if ($duedate - $time <= 0) {
                if (!$status->get_submission() || $status->get_submission()->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED &&
                    $status->get_submission()->status != ASSIGN_SUBMISSION_STATUS_LOCKED) {
                    $cell2 = new html_table_cell(get_string('overdue', 'assign', format_time($time - $duedate)));
                } else {
                    if ($status->get_submission()->timemodified > $duedate) {
                        $cell2 = new html_table_cell(get_string('submittedlate', 'assign', format_time($status->get_submission()->timemodified - $duedate)), 'latesubmission');
                    } else {
                        $cell2 = new html_table_cell(get_string('submittedearly', 'assign', format_time($status->get_submission()->timemodified - $duedate)), 'submittedearly');
                    }
                }
            } else {
                $cell2 = new html_table_cell(format_time($duedate - $time));
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        } 

        // last modified 
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('timemodified', 'assign'));
        if ($status->get_submission()) {
            $cell2 = new html_table_cell(userdate($status->get_submission()->timemodified));
        } else {
            $cell2 = new html_table_cell();
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        if ($status->get_submission()) {
            foreach ($status->get_assignment()->get_submission_plugins() as $plugin) {
                if ($plugin->is_enabled() && $plugin->is_visible()) {
                    $row = new html_table_row();
                    $cell1 = new html_table_cell($plugin->get_name());
                    //$cell2 = new html_table_cell($this->format_plugin_summary_with_link($plugin, $status->get_submission(), $return_action, $return_params));
                    $plugin_submission = new submission_plugin_submission($status->get_assignment(), $plugin, $status->get_submission(), submission_plugin_submission::SUMMARY);
                    $cell2 = new html_table_cell($this->render($plugin_submission));
                    $row->cells = array($cell1, $cell2);
                    $t->data[] = $row;
                }
            }
        }
        
        $o .= html_writer::table($t);
        $o .= $this->output->box_end();
        
        $o .= $this->output->container_end();
        return $o;
    }
    
    /**
     * render a submission plugin submission
     * 
     * @param submission_plugin_submission $submission_plugin
     * @return string
     */
    public function render_submission_plugin_submission(submission_plugin_submission $submission_plugin) {
        $o = '';

        if ($submission_plugin->get_view() == submission_plugin_submission::SUMMARY) {
            $icon = $this->output->pix_icon('t/preview', get_string('view' . $submission_plugin->get_plugin()->get_subtype(), 'mod_assign'));
            $link = $this->output->action_link(
                                new moodle_url('/mod/assign/view.php', 
                                               array('id' => $submission_plugin->get_assignment()->get_course_module()->id, 
                                                     'sid'=>$submission_plugin->get_submission()->id, 
                                                     'plugin'=>$submission_plugin->get_plugin()->get_type(), 
                                                     'action'=>'viewplugin' . $submission_plugin->get_plugin()->get_subtype(), 
                                                     'returnaction'=>$submission_plugin->get_assignment()->get_return_action(), 
                                                     'returnparams'=>http_build_query($submission_plugin->get_assignment()->get_return_params()))), 
                                $icon);
            $link .= $this->output->spacer(array('width'=>15));
            
            $o .= $link . $submission_plugin->get_plugin()->view_summary($submission_plugin->get_submission());
        }

        return $o;
    }

    

        
    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     * 
     * @global object $CFG
     * @param object $tree
     * @param array $dir
     * @return string 
     */
    protected function htmllize_tree($tree, $dir) {
        global $CFG;
        $yuiconfig = array();
        $yuiconfig['type'] = 'html';

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }

        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $image = $this->output->pix_icon("f/folder", $subdir['dirname'], 'moodle', array('class'=>'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.$image.' '.s($subdir['dirname']).'</div> '.$this->htmllize_tree($tree, $subdir).'</li>';
        }

        foreach ($dir['files'] as $file) {
            $filename = $file->get_filename();
            $icon = mimeinfo("icon", $filename);
            if ($CFG->enableplagiarism) {
                require_once($CFG->libdir.'/plagiarismlib.php');
                $plagiarsmlinks = plagiarism_get_links(array('userid'=>$file->get_userid(), 'file'=>$file, 'cmid'=>$tree->cm->id, 'course'=>$tree->course));
            } else {
                $plagiarsmlinks = '';
            }
            $image = $this->output->pix_icon("f/$icon", $filename, 'moodle', array('class'=>'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.$image.' '.$file->fileurl.' '.$plagiarsmlinks.$file->portfoliobutton.'</div></li>';
        }

        $result .= '</ul>';

        return $result;
    }
}

