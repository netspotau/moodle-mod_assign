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
 * This file contains the definition for the library class for online comment
 *  submission plugin 
 * 
 * This class provides all the functionality for the new assign module.
 *
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 defined('MOODLE_INTERNAL') || die();
 
 /** Include comment core lib.php */
 require_once($CFG->dirroot . '/comment/lib.php');
 /** Include submission_plugin.php to avaid AJAX error */
 require_once($CFG->dirroot . '/mod/assign/submission_plugin.php');
 
/*
 * library class for comment submission plugin extending submission plugin
 * base class
 * 
 * @package   mod-assign
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_comments extends submission_plugin {

    /** @var object the assignment record that contains the global settings for this assign instance */
    private $instance;

   /**
    * get the name of the online comment submission plugin
    * @return string 
    */   
    public function get_name() {
        return get_string('pluginname', 'submission_comments');
    }
      
  
     /**
      * display AJAX based comment in the submission status table 
      * 
      * @param object $submission
      * @return string 
      */
   public function view_summary($submission) {
        
      
       
       // need to used this innit() otherwise it shows up undefined !
       // require js for commenting
       comment::init();
       
       $options = new stdClass();
       
        $options->area    = 'submission_comments';
        
        $options->course    = $this->assignment->get_course();
        
        $options->context = $this->assignment->get_context();
        $options->itemid  = $submission->id;
       
        $options->component = 'submission_comments';
        $options->showcount = true;
      
     
        $options->displaycancel = true;
        
        $comment = new comment($options);
        $comment->set_view_permission(true);
       
        
        return $comment->output(true);
     
    }
    
    
   
}

         /**
          *
          * callback method for data validation---- required method 
          * for AJAXmoodle based comment API
          * 
          * @param object $options
          * @return bool
          */
	 function submission_comments_comment_validate($options){

	     return true;

	}


     
          /**
           * permission control method for submission plugin ---- required method 
           * for AJAXmoodle based comment API
           * 
           * @param object $options
           * @return array
           */
	  function submission_comments_comment_permissions($options){
           
	    return array('post'=>true,'view'=>true );

	 }
    
  