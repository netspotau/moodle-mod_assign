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


/**
 * A custom renderer class that extends the plugin_renderer_base and
 * is used by the assign module.
 *
 * @package mod_assign
 * @subpackage assignfeedback_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class assignfeedback_file_renderer extends plugin_renderer_base {
    
    /**
     * rendering importer form
     * 
     * @param assignfeedback_file_importer_form $form
     * @return string
     */
    function render_assignfeedback_file_importer_form(assignfeedback_file_importer_form $form) {
        $o = '';

        $this->page->navbar->add(get_string('uploadfeedbackzip', 'assignfeedback_file'));

        $this->page->set_title(get_string('confirmimport', 'assignfeedback_file'));
        $this->page->set_heading($form->plugin->get_assignment()->get_instance()->name);
        $this->page->requires->css('/mod/assign/feedback/file/styles.css');

        $o .= $this->output->header();
        $o .= $this->output->heading($form->plugin->get_assignment()->get_instance()->name);
        
        $o .= $this->output->container_start('importerhelp');
        $o .= $this->output->box_start('generalbox');
        $o .= format_string(get_string('importerhelp', 'assignfeedback_file'), array('context'=>$form->plugin->get_assignment()->get_context()));
        $o .= $this->output->box_end();
        $o .= $this->output->container_end();

        $o .= $this->moodleform($form);

        $o .= $this->output->footer();

        return $o;
    }
    
    /**
     * rendering importer summary
     * 
     * @param importer_summary $summary
     * @return string
     */
    function render_importer_summary(importer_summary $summary) {
        $o = '';

        $this->page->navbar->add(get_string('uploadfeedbackzip', 'assignfeedback_file'));

        $this->page->set_title(get_string('importsummary', 'assignfeedback_file'));
        $this->page->set_heading($summary->get_assignment()->get_instance()->name);
        $this->page->requires->css('/mod/assign/feedback/file/styles.css');

        $o .= $this->output->header();
        $o .= $this->output->heading($summary->get_assignment()->get_instance()->name);
        
        $o .= $this->output->container_start('importsummary');
        $o .= $this->output->box_start('generalbox');
        $o .= format_string(get_string('importsummarydetails', 'assignfeedback_file', $summary->get_files_sent()), array('context'=>$summary->get_assignment()->get_context()));
        $o .= $this->output->box_end();

        // and a link back to the grading table
        
        $o .= $this->output->single_button(new moodle_url('/mod/assign/view.php', array('id' => $summary->get_assignment()->get_course_module()->id, 'action'=>'grading')), get_string('back'));
        $o .= $this->output->container_end();
        $o .= $this->output->footer();

        return $o;
    }


    /**
     * rendering upload zip form
     * 
     * @param assignfeedback_file_uploadzip_form $form
     * @return string
     */
    function render_assignfeedback_file_uploadzip_form(assignfeedback_file_uploadzip_form $form) {
        $o = '';

        $this->page->navbar->add(get_string('uploadfeedbackzip', 'assignfeedback_file'));

        $this->page->set_title(get_string('uploadfeedbackzip', 'assignfeedback_file'));
        $this->page->set_heading($form->plugin->get_assignment()->get_instance()->name);
        $this->page->requires->css('/mod/assign/feedback/file/styles.css');

        $o .= $this->output->header();
        $o .= $this->output->heading($form->plugin->get_assignment()->get_instance()->name);
        
        $o .= $this->output->container_start('uploadziphelp');
        $o .= $this->output->box_start('generalbox');
        $o .= format_string(get_string('uploadziphelp', 'assignfeedback_file'), array('context'=>$form->plugin->get_assignment()->get_context()));
        $o .= $this->output->box_end();
        $o .= $this->output->container_end();

        $o .= $this->moodleform($form);

        $o .= $this->output->footer();

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


/**
 * A simple class that holds the number of files imported
 *
 * @package mod_assign
 * @subpackage assignfeedback_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class importer_summary implements renderable {
    /** @var int filessent */
    var $filessent;
    /** @var assignment assignment */
    var $assignment;


    /**
     * Constructor
     * @param int $filessent
     * @param assignment $assignment
     */
    function __construct($filessent, $assignment) {
        $this->filessent = $filessent;
        $this->assignment = $assignment;
    }

    /**
     * The number of files sent by the import
     * @return int
     */
    public function get_files_sent() {
        return $this->filessent;
    }
    
    /**
     * The assignment instance
     * @return assignment
     */
    public function get_assignment() {
        return $this->assignment;
    }
}
