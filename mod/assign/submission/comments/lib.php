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
 *
 * @package   mod_assign
 * @subpackage submission_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 defined('MOODLE_INTERNAL') || die();
 
 /** Include comment core lib.php */
 require_once($CFG->dirroot . '/comment/lib.php');
 /** Include submission_plugin.php */
 require_once($CFG->dirroot . '/mod/assign/submission_plugin.php');
 
/*
 * library class for comment submission plugin extending submission plugin
 * base class
 * 
 * @package   mod_assign
 * @subpackage submission_comments
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
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
    
  /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     * 
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
               
        if ($type == 'upload' && $version >= 2011112900) {
            return true;
        }
        return false;
    }
  
    
     /**
     * Upgrade the settings from the old assignment 
     * to the new plugin based one
     * 
     * @param data - the database for the old assignment instance
     * @param string log record log events here
     * @return bool was it a success?
     */
    public function upgrade_settings($oldcontext,$oldassignment, & $log) {
        // first upgrade settings (nothing to do)
        return true;
    }
     
    /**
     * Upgrade the submission from the old assignment to the new one
     * 
     * @param object $oldassignment The data record for the old oldassignment
     * @param object $oldsubmission The data record for the old submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade($oldcontext,$oldassignment, $oldsubmission, $submission, & $log) {
        global $DB;
    
     if ($oldsubmission->data1 != '') {
         
            // need to used this innit() otherwise it shows up undefined !
            // require js for commenting
            comment::init();

            $options = new stdClass();
            $options->area = 'submission_comments_upgrade';                     
            $options->course = $this->assignment->get_course();
            $options->context = $this->assignment->get_context();                     
            $options->itemid = $submission->id;
            $options->component = 'submission_comments';
            $options->showcount = true;
            $options->displaycancel = true;

            $comment = new comment($options);
            $comment->add($oldsubmission->data1);
            $comment->set_view_permission(true);


            return $comment->output(true);
        }

          
        return true;
    }
    
    
    
    
   
}


////////////////////////////////////////////////////////////////////////
// The call back functions OUTSIDE the submission_comments class     ///
///////////////////////////////////////////////////////////////////////

/**
 *
 * callback method for data validation---- required method 
 * for AJAXmoodle based comment API
 * 
 * @param object $options
 * @return bool
 */
function submission_comments_comment_validate($options) {

    return true;
}

/**
 * permission control method for submission plugin ---- required method 
 * for AJAXmoodle based comment API
 * 
 * @param object $options
 * @return array
 */
function submission_comments_comment_permissions($options) {

    return array('post' => true, 'view' => true);
}

/**
 * Callback to force the userid for all comments to be the userid of
 * the submission and NOT the global $USER->id. This
 * is required by the upgrade code. Note the comment area
 * is used to identify upgrades.
 * 
 * @global object $DB
 * @param object $comment
 */
function submission_comments_comment_add(& $comment, $param) {
    
    global $DB;
    if ($comment->commentarea == 'submission_comments_upgrade') {
        $submissionid = $comment->itemid;
        $submission = $DB->get_record('assign_submission', array('id' => $submissionid));

        $comment->userid = $submission->userid;
        $comment->commentarea = 'submission_comments';
    }
}

