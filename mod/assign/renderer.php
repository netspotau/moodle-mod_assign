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

/**
 * An assign file class that extends rendererable class and
 * is used by the assign module.
 *
 * @package mod-assign
 * @copyright
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class assign_files implements renderable {
    public $context;
    public $dir;
    public $portfolioform;
    public $cm;
    public $course;
    
    
    /**
     * The constructor 
     * 
     * @global object $CFG
     * @global object $USER
     * @param object $context
     * @param int $sid
     * @param string $filearea 
     */
    public function __construct($context, $sid, $filearea='submission') {
        global $CFG,$USER;
        $this->context = $context;
        list($context, $course, $cm) = get_context_info_array($context->id);
        $this->cm = $cm;
        $this->course = $course;
        $fs = get_file_storage();
        $this->dir = $fs->get_area_tree($this->context->id, 'mod_assign', $filearea, $sid);
        
         $files = $fs->get_area_files($this->context->id, 'mod_assign', $filearea, $sid, "timemodified", false);
        
        if (!empty($CFG->enableportfolios)) {
            require_once($CFG->libdir . '/portfoliolib.php');
           // $files = $fs->get_area_files($this->context->id, 'mod_assign', $filearea, $sid, "timemodified", false);
            if (count($files) >= 1 && has_capability('mod/assign:exportownsubmission', $this->context)) {
                $button = new portfolio_add_button();
                $button->set_callback_options('assign_portfolio_caller', array('cmid' => $this->cm->id, 'sid'=>$sid, 'area'=>$filearea), '/mod/assign/portfolio_callback.php');
                $button->reset_formats();
                $this->portfolioform = $button->to_html(PORTFOLIO_ADD_TEXT_LINK);
            }
           
        }
        
         // plagiarism check if it is enabled
        $output = '';        
        if (!empty($CFG->enableplagiarism)) {
            require_once($CFG->libdir . '/plagiarismlib.php');
            
            // for plagiarism_get_links
            $assignment = new assignment($this->context);
            foreach ($files as $file) {

               $output .= plagiarism_get_links(array('userid' => $sid,
                   'file' => $file,
                   'cmid' => $this->cm->id,
                   'course' => $this->course,
                   'assignment' => $assignment->get_instance()));
                
               $output .= '<br />';
            }
        }
        
       $this->preprocess($this->dir, $filearea);
    }
    
    /**
     * preprocessing 
     * 
     * @global object $CFG
     * @param array $dir
     * @param string $filearea 
     */
    public function preprocess($dir, $filearea) {
        global $CFG;
        foreach ($dir['subdirs'] as $subdir) {
            $this->preprocess($subdir, $filearea);
        }
        foreach ($dir['files'] as $file) {
            $file->portfoliobutton = '';
            if (!empty($CFG->enableportfolios)) {
                $button = new portfolio_add_button();
                if (has_capability('mod/assign:exportownsubmission', $this->context)) {
                    $button->set_callback_options('assign_portfolio_caller', array('cmid' => $this->cm->id, 'fileid' => $file->get_id()), '/mod/assign/portfolio_callback.php');
                    $button->set_format_by_file($file);
                    $file->portfoliobutton = $button->to_html(PORTFOLIO_ADD_ICON_LINK);
                }
            }
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/'.$this->context->id.'/mod_assign/'.$filearea.'/'.$file->get_itemid(). $file->get_filepath().$file->get_filename(), true);
            $filename = $file->get_filename();
            $file->fileurl = html_writer::link($url, $filename);
        }
    }
}
