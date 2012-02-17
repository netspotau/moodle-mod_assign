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
 * Defines the renderer for the assignment upgrade helper plugin.
 *
 * @package    tool
 * @subpackage assignmentupgrade
 * @copyright  2012 NetSpot
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Renderer for the assignment upgrade helper plugin.
 *
 * @copyright  2012 NetSpot
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_assignmentupgrade_renderer extends plugin_renderer_base {

    /**
     * Render the index page.
     * @param string $detected information about what sort of site was detected.
     * @param array $actions list of actions to show on this page.
     * @return string html to output.
     */
    public function index_page($detected, array $actions) {
        $output = '';
        $output .= $this->header();
        $output .= $this->heading(get_string('pluginname', 'tool_assignmentupgrade'));
        $output .= $this->box($detected);
        $output .= html_writer::start_tag('ul');
        foreach ($actions as $action) {
            $output .= html_writer::tag('li',
                    html_writer::link($action->url, $action->name) . ' - ' .
                    $action->description);
        }
        $output .= html_writer::end_tag('ul');
        $output .= $this->footer();
        return $output;
    }

    /**
     * Render a page that is just a simple message.
     * @param string $message the message to display.
     * @return string html to output.
     */
    public function simple_message_page($message) {
        $output = '';
        $output .= $this->header();
        $output .= $this->heading($message);
        $output .= $this->back_to_index();
        $output .= $this->footer();
        return $output;
    }

    /**
     * Render the list of assignments that still need to be upgraded page.
     * @param array $assignments of data about assignments.
     * @param int $numveryoldattemtps only relevant before upgrade.
     * @return string html to output.
     */
    public function assignment_list_page(tool_assignmentupgrade_assignment_list $assignments) {
        $output = '';
        $output .= $this->header();
        $output .= $this->heading($assignments->title);
        $output .= $this->box($assignments->intro);

        $table = new html_table();
        $table->head = $assignments->get_col_headings();

        $rowcount = 0;
        foreach ($assignments->assignmentlist as $assignmentinfo) {
            $table->data[$rowcount] = $assignments->get_row($assignmentinfo);
            if ($class = $assignments->get_row_class($assignmentinfo)) {
                $table->rowclasses[$rowcount] = $class;
            }
            $rowcount += 1;
        }
        $table->data[] = $assignments->get_total_row();
        $output .= html_writer::table($table);

        $output .= $this->back_to_index();
        $output .= $this->footer();
        return $output;
    }
    
    /**
     * Render the result of an assignment conversion
     * @param object $assignmentsummary data about the assignment to upgrade.
     * @return string html to output.
     */
    public function convert_assignment_result($assignmentsummary, $success, $log) {
        $output = '';
        $output .= $this->header();
        $output .= $this->heading(get_string('conversioncomplete', 'tool_assignmentupgrade'));

        if (!$success) {
            $output .= get_string('conversionfailed', 'tool_assignmentupgrade', $log);
            
        }


        $output .= $this->footer();
        return $output;
    }

    /**
     * Render the are-you-sure page to confirm a manual upgrade.
     * @param object $assignmentsummary data about the assignment to upgrade.
     * @return string html to output.
     */
    public function convert_assignment_are_you_sure($assignmentsummary) {
        $output = '';
        $output .= $this->header();
        $output .= $this->heading(get_string('areyousure', 'tool_assignmentupgrade'));

        $params = array('id' => $assignmentsummary->id, 'confirmed' => 1, 'sesskey' => sesskey());
        $output .= $this->confirm(get_string('areyousuremessage', 'tool_assignmentupgrade', $assignmentsummary),
                new single_button(tool_assignmentupgrade_url('upgradesingle', $params), get_string('yes')),
                tool_assignmentupgrade_url('listnotupgraded'));

        $output .= $this->footer();
        return $output;
    }

    /**
     * Render a link in a div, such as the 'Back to plugin main page' link.
     * @param $url the link URL.
     * @param $text the link text.
     * @return string html to output.
     */
    public function end_of_page_link($url, $text) {
        return html_writer::tag('div', html_writer::link($url ,$text),
                array('class' => 'mdl-align'));
    }

    /**
     * Output a link back to the plugin index page.
     * @return string html to output.
     */
    public function back_to_index() {
        return $this->end_of_page_link(tool_assignmentupgrade_url('index'),
                get_string('backtoindex', 'tool_assignmentupgrade'));
    }
}
