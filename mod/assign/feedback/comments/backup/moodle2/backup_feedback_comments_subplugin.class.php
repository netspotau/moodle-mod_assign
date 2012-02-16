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
 * @package    assign_feedback
 * @subpackage comments
 * @copyright  
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();


/**
 * Provides the information to backup comments feedback
 *
 * This just records the text and format
 */
class backup_feedback_comments_subplugin extends backup_subplugin {

    /**
     * Returns the subplugin information to attach to submission element
     */
    protected function define_grade_subplugin_structure() {

        // create XML elements
        $subplugin = $this->get_subplugin_element(); // virtual optigroup element
        $subplugin_wrapper = new backup_nested_element($this->get_recommended_name());
        $subplugin_element = new backup_nested_element('feedback_comments', null, array('commenttext', 'commentformat', 'grade'));

        // connect XML elements into the tree
        $subplugin->add_child($subplugin_wrapper);
        $subplugin_wrapper->add_child($subplugin_element);

        // set source to populate the data
        $subplugin_element->set_source_table('assign_feedback_comments', array('grade' => backup::VAR_PARENTID));

        return $subplugin;
    }
}
