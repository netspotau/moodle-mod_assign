<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once('locallib.php');
class mod_assign_mod_form extends moodleform_mod {
    protected $_assignmentinstance = null;

    function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('assignmentname', 'assign'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(true, get_string('description', 'assign'));
          
        $ctx = null;
        if ($this->current && $this->current->coursemodule) {
            $cm = get_coursemodule_from_instance('assign', $this->current->id);
            if ($cm) {
                $ctx = get_context_instance(CONTEXT_MODULE, $cm->id);
            }
        }
        $instance = new assignment($ctx);
        
        $instance->add_settings($mform);
        
        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

}

class mod_assign_submission_form extends moodleform {

    function definition() {
        $mform = $this->_form;

        list($assignment, $data) = $this->_customdata;

        $assignment->add_submission_form_elements($mform, $data);

        $this->add_action_buttons(false, get_string('savechanges', 'assign'));
        if ($data) {
            $this->set_data($data);
        }
    }
}

/**
 * @package   mod-assign
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assign_grade_form extends moodleform {         
    function definition() {
        $mform = $this->_form;
        $instance = $this->_customdata;       
        // visible elements
        $grademenu = make_grades_menu($instance['scale']);
        $grademenu['-1'] = get_string('nograde');

        $mform->addElement('select', 'grade', get_string('grade').':', $grademenu);
        $mform->setType('grade', PARAM_INT);
        
        $data = null;
        if (isset($instance['data'])) {
            $data = $instance['data'];
            $data->feedback_editor['text'] = $data->feedback;
            $data->feedback_editor['format'] = $data->feedbackformat;
            $data = file_prepare_standard_filemanager($data, 'feedbackfiles', $instance['options'], $instance['context'], 'mod_assign', ASSIGN_FILEAREA_SUBMISSION_FEEDBACK, $instance['userid']);
            $this->set_data($data);
        }
        
        $mform->addElement('editor', 'feedback_editor', get_string('feedbackcomments', 'assign'));
        $mform->setType('feedback_editor', PARAM_RAW); // to be cleaned before display

        $mform->addElement('filemanager', 'feedbackfiles_filemanager', get_string('uploadafile'), null, $instance['options']);
        // hidden params
        $mform->addElement('hidden', 'contextid', $instance['contextid']);
        $mform->setType('contextid', PARAM_INT);
        $mform->addElement('hidden', 'id', $instance['cm']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $instance['userid']);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'rownum', $instance['rownum']);
        $mform->setType('rownum', PARAM_INT);
        
        $mform->addElement('hidden', 'action', 'submitgrade');
        $mform->setType('action', PARAM_ALPHA);
         
        $buttonarray=array();
            
        $buttonarray[] = &$mform->createElement('submit', 'saveandshownext', get_string('savenext','assign')); 
        $buttonarray[] = &$mform->createElement('submit', 'nosaveandnext', get_string('nosavebutnext', 'assign'));
        $buttonarray[] = &$mform->createElement('submit', 'savegrade', get_string('savechanges', 'assign'));           
        $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton', get_string('cancel','assign'));     
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');            
               ///- use this to get the last userid/row number to hide the next and save$show next button 
              // var_dump($instance['last']);
              /// related to the view_grade_form function
       
        if (!empty($instance['last'])== true ){
            $mform->removeElement('buttonar');
            $buttonarray=array();          
            $buttonarray[] = &$mform->createElement('submit', 'savegrade', get_string('savechanges', 'assign'));
            $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton', get_string('cancel','assign'));     
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');                                     
      }            
  }
          
}

/**
 * @package   mod-assign
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assign_grading_options_form extends moodleform {
    function definition() {
        $mform = $this->_form;
        $instance = $this->_customdata;

        $mform->addElement('header', 'general', get_string('gradingoptions', 'assign'));
        // visible elements
        $options = array(-1=>'All',10=>'10', 20=>'20', 50=>'50', 100=>'100');
        $autosubmit = array('onchange'=>'form.submit();');
        $mform->addElement('select', 'perpage', get_string('assignmentsperpage', 'assign'), $options, $autosubmit);
        $options = array(''=>get_string('filternone', 'assign'), ASSIGN_FILTER_SUBMITTED=>get_string('filtersubmitted', 'assign'), ASSIGN_FILTER_REQUIRE_GRADING=>get_string('filterrequiregrading', 'assign'));
        $mform->addElement('select', 'filter', get_string('filter', 'assign'), $options, $autosubmit);
    
 //       $mform->_attributes['id'] = 'gradingoptions';

        // hidden params
        $mform->addElement('hidden', 'contextid', $instance['contextid']);
        $mform->setType('contextid', PARAM_INT);
        $mform->addElement('hidden', 'id', $instance['cm']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $instance['userid']);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'action', 'saveoptions');
        $mform->setType('action', PARAM_ALPHA);

        // buttons
        $this->add_action_buttons(false, get_string('updatetable', 'assign'));
    }
}

