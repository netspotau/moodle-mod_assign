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
require_once($CFG->dirroot . '/mod/assign/locallib.php');


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
     * @param context $context
     * @param int $userid
     * @param string $filearea
     * @return string
     */
    public function assign_files(context $context, $userid, $filearea) {
        return $this->render(new assign_files($context, $userid, $filearea));
    }

    /**
     * rendering assignment files 
     * 
     * @param assign_files $tree
     * @return string
     */
    public function render_assign_files(assign_files $tree) {
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
     * @param html_table $table The table to append the row of data to
     * @param string $first The first column text
     * @param string $second The second column text
     * @return void
     */
    private function add_table_row_tuple(html_table $table, $first, $second) {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell2 = new html_table_cell($second);
        $row->cells = array($cell1, $cell2);
        $table->data[] = $row;
    }
    
    /**
     * Render the grading options form
     * @param grading_options_form $form The grading options form to render
     * @return string
     */
    public function render_grading_options_form(grading_options_form $form) {
        $o = '';
        $o .= $this->output->box_start('boxaligncenter gradingoptionsform');
        $o .= $this->moodleform($form->get_form());
        $o .= $this->output->box_end();
        return $o;
    }
    
    /**
     * Render the grading form
     * @param grading_form $form The grading form to render
     * @return string
     */
    public function render_grading_form(grading_form $form) {
        $o = '';
        $o .= $this->output->heading(get_string('grade'), 3);
        $o .= $this->output->box_start('boxaligncenter gradingform');
        $o .= $this->moodleform($form->get_form());
        $o .= $this->output->box_end();
        return $o;
    }
    
    /**
     * Render the user summary
     * 
     * @param user_summary $summary The user summary to render
     * @return string
     */
    public function render_user_summary(user_summary $summary) {
        $o = '';

        if (!$summary->user) {
            return;
        }
        $o .= $this->output->container_start('usersummary');
        $o .= $this->output->box_start('boxaligncenter usersummarysection');
        $o .= $this->output->user_picture($summary->user);
        $o .= $this->output->spacer(array('width'=>30));
        $o .= $this->output->action_link(new moodle_url('/user/view.php', 
                                                        array('id' => $summary->user->id, 
                                                              'course'=>$summary->courseid)), 
                                                              fullname($summary->user, $summary->viewfullnames));
        $o .= $this->output->box_end();
        $o .= $this->output->container_end();
        
        return $o;
    }

    /**
     * Page is done - render the footer
     * 
     * @return void
     */
    public function render_footer() {
        return $this->output->footer();
    }

    /**
     * render the edit submission form
     *
     * @param edit_submission_form $form
     * @return string
     */
    public function render_edit_submission_form(edit_submission_form $editform) {
        $o = '';
    
        $o .= $this->output->container_start('editsubmission');
        $o .= $this->output->heading(get_string('submission', 'assign'), 3);
        $o .= $this->output->box_start('boxaligncenter editsubmissionform');

        $o .= $this->moodleform($editform->get_form());
        
        $o .= $this->output->box_end();
        $o .= $this->output->container_end();

        return $o;
    }

    /**
     * render the header
     * 
     * @param assignment_header $header
     * @return string
     */
    public function render_assignment_header(assignment_header $header) {
        $o = '';

        if ($header->subpage) {
            $this->page->navbar->add($header->subpage);
        }

        $this->page->set_title(get_string('pluginname', 'assign'));
        $this->page->set_heading($header->assign->name);

        $o .= $this->output->header();
        $o .= $this->output->heading($header->assign->name);

        if ($header->showintro) {
            $o .= $this->output->box_start('generalbox boxaligncenter', 'intro');
            $o .= format_module_intro('assign', $header->assign, $header->coursemoduleid);
            $o .= $this->output->box_end();
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
                                   $summary->participantcount);

        // drafts
        if ($summary->submissiondraftsenabled) {
            $this->add_table_row_tuple($t, get_string('numberofdraftsubmissions', 'assign'), 
                                       $summary->submissiondraftscount);
       }

        // submitted for grading
        if ($summary->submissionsenabled) {
            $this->add_table_row_tuple($t, get_string('numberofsubmittedassignments', 'assign'), 
                                       $summary->submissionssubmittedcount);
        }

        $time = time();
        if ($summary->duedate) {
            // due date
            // submitted for grading
            $duedate = $summary->duedate;
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
                                                          array('id' => $summary->coursemoduleid,
                                                                'action'=>'grading')), 
                                                          get_string('viewgrading', 'assign'), 
                                                          'get');

        // close the container and insert a spacer
        $o .= $this->output->container_end();

        return $o;
    }
    
    /**
     * render a table containing all the current grades and feedback
     * 
     * @global moodle_database $DB
     * @global stdClass $CFG
     * @param feedback_status $status
     * @return string
     */
    public function render_feedback_status(feedback_status $status) {
        global $DB, $CFG;
        $o = '';

        $o .= $this->output->container_start('feedback');
        $o .= $this->output->heading(get_string('feedback', 'assign'), 3);
        $o .= $this->output->box_start('boxaligncenter feedbacktable');
        $t = new html_table();
        
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('grade', 'assign'));
        $cell2 = new html_table_cell($status->gradefordisplay);
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;
        
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('gradedon', 'assign'));
        $cell2 = new html_table_cell(userdate($status->gradeddate));
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;
        
        if ($status->grader) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('gradedby', 'assign'));
            $cell2 = new html_table_cell($this->output->user_picture($status->grader) . $this->output->spacer(array('width'=>30)) . fullname($status->grader));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }
    
        foreach ($status->feedbackplugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible() && !$plugin->is_empty($status->grade)) {
                $row = new html_table_row();
                $cell1 = new html_table_cell($plugin->get_name());
                $pluginfeedback = new feedback_plugin_feedback($plugin, $status->grade, $status->coursemoduleid, $status->returnaction, $status->returnparams);
                $cell2 = new html_table_cell($this->render($pluginfeedback));
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
            }
        }
 

        $o .= html_writer::table($t);
        $o .= $this->output->box_end();
        
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

        if ($status->allowsubmissionsfromdate &&
                $time <= $status->allowsubmissionsfromdate) {
            $o .= $this->output->box_start('generalbox boxaligncenter submissionsalloweddates');
            if ($status->alwaysshowdescription) {
                $o .= get_string('allowsubmissionsfromdatesummary', 'assign', userdate($status->allowsubmissionsfromdate));
            } else {
                $o .= get_string('allowsubmissionsanddescriptionfromdatesummary', 'assign', userdate($status->allowsubmissionsfromdate));
            }
            $o .= $this->output->box_end();
        } 
        $o .= $this->output->box_start('boxaligncenter submissionsummarytable');

        $t = new html_table();

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('submissionstatus', 'assign'));
        if ($status->submission) {
            $cell2 = new html_table_cell(get_string('submissionstatus_' . $status->submission->status, 'assign'));
            $cell2->attributes = array('class'=>'submissionstatus' . $status->submission->status);
        } else {
            if (!$status->submissionsenabled) {
                $cell2 = new html_table_cell(get_string('noonlinesubmissions', 'assign'));
            } else {
                $cell2 = new html_table_cell(get_string('nosubmission', 'assign'));
            }
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;
        
        // status
        if ($status->locked) {
            $row = new html_table_row();
            $cell1 = new html_table_cell();
            $cell2 = new html_table_cell(get_string('submissionslocked', 'assign'));
            $cell2->attributes = array('class'=>'submissionlocked');
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // grading status
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('gradingstatus', 'assign'));

        if ($status->graded) {
            $cell2 = new html_table_cell(get_string('graded', 'assign'));
            $cell2->attributes = array('class'=>'submissiongraded');
        } else {
            $cell2 = new html_table_cell(get_string('notgraded', 'assign'));
            $cell2->attributes = array('class'=>'submissionnotgraded');
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        
        $duedate = $status->duedate;
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
                if (!$status->submission || $status->submission != ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                    if ($status->submissionsenabled) {
                        $cell2 = new html_table_cell(get_string('overdue', 'assign', format_time($time - $duedate)));
                        $cell2->attributes = array('class'=>'overdue');
                    } else {
                        $cell2 = new html_table_cell(get_string('duedatereached', 'assign'));
                    }
                } else {
                    if ($status->submission->timemodified > $duedate) {
                        $cell2 = new html_table_cell(get_string('submittedlate', 'assign', format_time($status->submission->timemodified - $duedate)));
                        $cell2->attributes = array('class'=>'latesubmission');
                    } else {
                        $cell2 = new html_table_cell(get_string('submittedearly', 'assign', format_time($status->submission->timemodified - $duedate)));
                        $cell2->attributes = array('class'=>'earlysubmission');
                    }
                }
            } else {
                $cell2 = new html_table_cell(format_time($duedate - $time));
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        } 

        // last modified 
        if ($status->submission) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timemodified', 'assign'));
            $cell2 = new html_table_cell(userdate($status->submission->timemodified));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            foreach ($status->submissionplugins as $plugin) {
                if ($plugin->is_enabled() && $plugin->is_visible() && !$plugin->is_empty($status->submission)) {
                    $row = new html_table_row();
                    $cell1 = new html_table_cell($plugin->get_name());
                    $pluginsubmission = new submission_plugin_submission($plugin, $status->submission, submission_plugin_submission::SUMMARY);
                    $cell2 = new html_table_cell($this->render($pluginsubmission));
                    $row->cells = array($cell1, $cell2);
                    $t->data[] = $row;
                }
            }
        }

        
        $o .= html_writer::table($t);
        $o .= $this->output->box_end();
    
        // links
        if ($status->canedit) {
            $o .= $this->output->single_button(new moodle_url('/mod/assign/view.php',
                array('id' => $status->coursemoduleid, 'action' => 'editsubmission')), get_string('editsubmission', 'assign'), 'get');
        }

        if ($status->cansubmit) {
            // submission.php test
            $o .= $this->output->single_button(new moodle_url('/mod/assign/view.php',
                    array('id' => $status->coursemoduleid, 'action'=>'submit')), get_string('submitassignment', 'assign'), 'get');
            $o .= $this->output->box_start('boxaligncenter submithelp');
            $o .= get_string('submitassignment_help', 'assign');
            $o .= $this->output->box_end();
        }
        
        $o .= $this->output->container_end();
        return $o;
    }
    
    /**
     * render a submission plugin submission
     * 
     * @param submission_plugin_submission $submissionplugin
     * @return string
     */
    public function render_submission_plugin_submission(submission_plugin_submission $submissionplugin) {
        $o = '';

        if ($submissionplugin->view == submission_plugin_submission::SUMMARY) {
            $icon = $this->output->pix_icon('t/preview', get_string('view' . substr($submissionplugin->plugin->get_subtype(), strlen('assign')), 'mod_assign'));
            $link = '';
            if ($submissionplugin->plugin->show_view_link($submissionplugin->submission)) {
                $link = $this->output->action_link(
                                new moodle_url('/mod/assign/view.php', 
                                               array('id' => $submissionplugin->coursemoduleid, 
                                                     'sid'=>$submissionplugin->submission->id, 
                                                     'plugin'=>$submissionplugin->plugin->get_type(), 
                                                     'action'=>'viewplugin' . $submissionplugin->plugin->get_subtype(), 
                                                     'returnaction'=>$submissionplugin->returnaction, 
                                                     'returnparams'=>http_build_query($submissionplugin->returnlinks))), 
                                $icon);
            
                $link .= $this->output->spacer(array('width'=>15));
            }
            
            $o .= $link . $submissionplugin->plugin->view_summary($submissionplugin->submission);
        }
        if ($submissionplugin->view == submission_plugin_submission::FULL) {
            $o .= $this->output->box_start('boxaligncenter submissionfull');
            $o .= $submissionplugin->plugin->view($submissionplugin->submission);
            $o .= $this->output->box_end();
        }

        return $o;
    }
    
    /**
     * render the grading table
     * 
     * @param grading_table $table
     * @return string
     */
    public function render_grading_table(grading_table $table) {
        $o = '';

        $o .= $this->output->box_start('boxaligncenter gradingtable');
        // need to get from prefs
        $o .= $this->flexible_table($table, $table->get_rows_per_page(), true);
        $o .= $this->output->box_end();

        $o .= $this->output->spacer(array('height'=>30));
        $contextname = $table->get_assignment_name();

        $o .= $this->output->container_start('gradingnavigation');
        $o .= $this->output->container_start('backlink');
        $o .= $this->output->action_link(new moodle_url('/mod/assign/view.php', array('id' => $table->get_course_module_id())), get_string('backto', '', $contextname));
        $o .= $this->output->container_end();
        if ($table->can_view_all_grades()) {
            $o .= $this->output->container_start('gradebooklink');
            $o .= $this->output->action_link(new moodle_url('/grade/report/grader/index.php', array('id' => $table->get_course_id())), get_string('viewgradebook', 'assign'));
            $o .= $this->output->container_end();
        }
        if ($table->submissions_enabled()) {
            $o .= $this->output->container_start('downloadalllink');
            $o .= $this->output->action_link(new moodle_url('/mod/assign/view.php', array('id' => $table->get_course_module_id(), 'action' => 'downloadall')), get_string('downloadall', 'assign'));
            $o .= $this->output->container_end();
        }

        $o .= $this->output->container_end();

        return $o;
    }

    /**
     * render a feedback plugin feedback
     * 
     * @param feedback_plugin_feedback $feedbackplugin
     * @return string
     */
    public function render_feedback_plugin_feedback(feedback_plugin_feedback $feedbackplugin) {
        $o = '';

        if ($feedbackplugin->view == feedback_plugin_feedback::SUMMARY) {
            $icon = $this->output->pix_icon('t/preview', get_string('view' . substr($feedbackplugin->plugin->get_subtype(), strlen('assign')), 'mod_assign'));
            $link = '';
            if ($feedbackplugin->plugin->show_view_link($feedbackplugin->grade)) {
                $link = $this->output->action_link(
                                new moodle_url('/mod/assign/view.php', 
                                               array('id' => $feedbackplugin->coursemoduleid, 
                                                     'gid'=>$feedbackplugin->grade->id, 
                                                     'plugin'=>$feedbackplugin->plugin->get_type(), 
                                                     'action'=>'viewplugin' . $feedbackplugin->plugin->get_subtype(), 
                                                     'returnaction'=>$feedbackplugin->returnaction, 
                                                     'returnparams'=>http_build_query($feedbackplugin->returnparams))), 
                                $icon);
                $link .= $this->output->spacer(array('width'=>15));
            }
            
            $o .= $link . $feedbackplugin->plugin->view_summary($feedbackplugin->grade);
        }
        if ($feedbackplugin->view == feedback_plugin_feedback::FULL) {
            $o .= $this->output->box_start('boxaligncenter feedbackfull');
            $o .= $feedbackplugin->plugin->view($feedbackplugin->grade);
            $o .= $this->output->box_end();
        }

        return $o;
    }
    

        
    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     * 
     * @global stdClass $CFG
     * @param assign_files $tree
     * @param array $dir
     * @return string 
     */
    protected function htmllize_tree(assign_files $tree, $dir) {
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

    /**
     * Helper method dealing with the fact we can not just fetch the output of flexible_table
     *
     * @param flexible_table $table
     * @return string HTML
     */
    protected function flexible_table(flexible_table $table, $rowsperpage, $displaylinks) {

        $o = '';
        ob_start();
        $table->out($rowsperpage, $displaylinks);
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }

    /**
     * Helper method dealing with the fact we can not just fetch the output of moodleforms
     *
     * @param moodleform $mform
     * @return string HTML
     */
    protected function moodleform(moodleform $mform) {

        $o = '';
        ob_start();
        $mform->display();
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }
    
}

