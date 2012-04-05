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
 * This file contains the definition for the renderable classes for the assignment
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Implements a renderable submissions table
 */
class users_submissions_table implements renderable {
    /** @var array of user submissions. Each data row contains user, submission and grade as stdClass objects */
    protected $data = null;
    
    /**
     * Constructor
     * @param array of ('user'=>stdClass, 'grade'=>stdClass|null, 'submission'=>stdClass|null)
     */
    public function __construct(stdClass $data) {
        $this->set_data($data);
    }
    
    /**
     * Returns data
     *
     * @return array
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Set the data
     *
     * @param array $data
     */
    public function set_data($data) {
        if (!$data) {
            throw new coding_exception('Data may not be null');
        }
        $this->data = $data;
    }
}

/*
 * Implements a renderable grading options form
 */
class assign_form implements renderable {
    /** @var moodleform $form is the edit submission form */
    var $form = null;
    /** @var string $classname is the name of the class to assign to the container */
    var $classname = '';
    
    /*
     * Constructor
     * @param moodleform $form
     */
    public function __construct($classname, moodleform $form) {
        $this->classname = $classname;
        $this->form = $form;
    }
    
}

/*
 * Implements a renderable grading options form
 */
class grading_actions_form implements renderable {
    /** @var moodleform $form is the edit submission form */
    protected $form = null;

    /*
     * Constructor
     * @param moodleform $form
     */
    public function __construct(moodleform $form) {
        $this->set_form($form);
    }

    /**
     * Returns form
     *
     * @return moodleform $form
     */
    public function get_form() {
        return $this->form;
    }

    /**
     * Set the form
     *
     * @param moodleform $form
     * @return void
     */
    public function set_form(moodleform $form) {
        if (!$form) {
            throw new coding_exception('Form may not be null');
        }
        $this->form = $form;
    }
}

/*
 * Implements a renderable edit submission form
 */
class edit_submission_form implements renderable {
    /** @var moodleform $form is the edit submission form */
    protected $form = null;
    
    /*
     * Constructor
     * @param moodleform $form
     */
    public function __construct(moodleform $form) {
        $this->set_form($form);
    }
    
    /**
     * Returns form
     *
     * @return moodleform $form
     */
    public function get_form() {
        return $this->form;
    }

    /**
     * Set the form
     *
     * @param moodleform $form
     * @return void
     */
    public function set_form(moodleform $form) {
        if (!$form) {
            throw new coding_exception('Form may not be null');
        }
        $this->form = $form;
    }
}

/*
 * Implements a renderable grading form
 */
class grading_form implements renderable {
    /** @var moodleform $form */
    protected $form = null;
    
    /**
     * A grading form is a moodleform setup to grade a user submission
     * @param moodleform $form
     */
    public function __construct(moodleform $form) {
        $this->set_form($form);
    }
    
    
    /**
     * Returns form
     *
     * @return moodleform
     */
    public function get_form() {
        return $this->form;
    }

    /**
     * Set the form
     *
     * @param moodleform $form
     * @return void
     */
    public function set_form(moodleform $form) {
        $this->form = $form;
    }
    
}

/*
 * Implements a renderable user summary
 */
class user_summary implements renderable {
    /** @var stdClass $user suitable for rendering with user_picture and fullname(). Must contain firstname, lastname, id and picture fields */
    public $user = null;
    /** @var int $courseid */
    public $courseid;
    /** @var bool $viewfullnames */
    public $viewfullnames = false;
    
    /**
     * Constructor
     * @param stdClass $user
     * @param int $courseid
     * @param bool $viewfullnames
     */
    public function __construct(stdClass $user, $courseid, $viewfullnames) {
        $this->user = $user;
        $this->courseid = $courseid;
        $this->viewfullnames = $viewfullnames;
    }
}

/*
 * Implements a renderable feedback plugin feedback
 */
class feedback_plugin_feedback implements renderable {
    const SUMMARY                = 10;
    const FULL                   = 20;

    /** @var assignment_submission_plugin $plugin */
    var $plugin = null;
    /** @var stdClass $grade */
    var $grade = null;
    /** @var string $view */
    var $view = self::SUMMARY;
    /** @var int $coursemoduleid */
    var $coursemoduleid = 0;
    /** @var string returnaction The action to take you back to the current page */
    var $returnaction = '';
    /** @var array returnparams The params to take you back to the current page */
    var $returnparams = array();
    
    /**
     * feedback for a single plugin
     *
     * @param assignment $assignment
     * @param assignment_feedback_plugin $plugin
     * @param stdClass $grade
     * @param string view one of feedback_plugin::SUMMARY or feedback_plugin::FULL
     */
    public function __construct(assignment_feedback_plugin $plugin, stdClass $grade, $view, $coursemoduleid, $returnaction, $returnparams) {
        $this->plugin = $plugin;
        $this->grade = $grade;
        $this->view = $view;
        $this->coursemoduleid = $coursemoduleid;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
    }
    
}

/*
 * Implements a renderable submission plugin submission
 */
class submission_plugin_submission implements renderable {
    const SUMMARY                = 10;
    const FULL                   = 20;

    /** @var assignment_submission_plugin $plugin */
    var $plugin = null;
    /** @var stdClass $submission */
    var $submission = null;
    /** @var string $view */
    var $view = self::SUMMARY;
    /** @var int $coursemoduleid */
    var $coursemoduleid = 0;
    /** @var string returnaction The action to take you back to the current page */
    var $returnaction = '';
    /** @var array returnparams The params to take you back to the current page */
    var $returnparams = array();
    

    
    /**
     * Constructor
     * @param assignment_submission_plugin $plugin
     * @param stdClass $submission
     * @param string $view one of submission_plugin::SUMMARY, submission_plugin::FULL
     * @param int $coursemoduleid - the course module id
     * @param string $returnaction The action to return to the current page
     * @param array $returnparams The params to return to the current page
     */
    public function __construct(assignment_submission_plugin $plugin, stdClass $submission, $view, $coursemoduleid, $returnaction, $returnparams) {
        $this->plugin = $plugin;
        $this->submission = $submission;
        $this->view = $view;
        $this->coursemoduleid = $coursemoduleid;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
    }
}

/**
 * Renderable feedback status
 */
class feedback_status implements renderable {
    
    /** @var stding $gradefordisplay the student grade rendered into a format suitable for display */
    var $gradefordisplay = '';
    /** @var mixed the graded date (may be null) */
    var $gradeddate = 0;
    /** @var mixed the grader (may be null) */
    var $grader = null;
    /** @var array feedbackplugins - array of feedback plugins */
    var $feedbackplugins = array();
    /** @var stdClass assign_grade record */
    var $grade = null;
    /** @var int coursemoduleid */
    var $coursemoduleid = 0;
    /** @var string returnaction */
    var $returnaction = '';
    /** @var array returnparams */
    var $returnparams = array();

    /**
     * Constructor
     * @param string $gradefordisplay
     * @param mixed $gradeddate
     * @param mixed $grader
     * @param array $feedbackplugins
     * @param mixed $grade
     */
    public function __construct($gradefordisplay, $gradeddate, $grader, $feedbackplugins, $grade, $coursemoduleid, $returnaction, $returnparams) {
        $this->gradefordisplay = $gradefordisplay;
        $this->gradeddate = $gradeddate;
        $this->grader = $grader;
        $this->feedbackplugins = $feedbackplugins;
        $this->grade = $grade;
        $this->coursemoduleid = $coursemoduleid;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
    }
    
}

/**
 * Renderable submission status
 */
class submission_status implements renderable {
    const STUDENT_VIEW     = 10;
    const GRADER_VIEW      = 20;
    
    /** @var int allowsubmissionsfromdate */
    var $allowsubmissionsfromdate = 0;
    /** @var bool alwaysshowdescription */
    var $alwaysshowdescription = false;
    /** @var stdClass the submission info (may be null) */
    var $submission = null;
    /** @var bool submissionsenabled */
    var $submissionsenabled = false;
    /** @var bool locked */
    var $locked = false;
    /** @var bool graded */
    var $graded = false;
    /** @var int duedate */
    var $duedate = 0;
    /** @var array submissionplugins - the list of submission plugins */
    var $submissionplugins = array();
    /** @var string returnaction */
    var $returnaction = '';
    /** @var string returnparams */
    var $returnparams = array();
    /** @var int coursemoduleid */
    var $coursemoduleid = 0;
    /** @var int the view (submission_status::STUDENT_VIEW OR submission_status::GRADER_VIEW) */
    var $view = self::STUDENT_VIEW;
    /** @var bool canedit */
    var $canedit = false;
    /** @var bool cansubmit */
    var $cansubmit = false;

    /**
     * constructor
     *
     * @param assignment $assignment
     * @param mixed stdClass|null $submission
     * @param bool $locked
     * @param bool $graded
     * @param int $view
     * @param bool $canedit
     * @param bool $cansubmit
     */
    public function __construct($allowsubmissionsfromdate, $alwaysshowdescription, $submission, $submissionsenabled,
                                $locked, $graded, $duedate, $submissionplugins, $returnaction, $returnparams, 
                                $coursemoduleid, $view, $canedit, $cansubmit) {
        $this->allowsubmissionsfromdate = $allowsubmissionsfromdate;
        $this->alwaysshowdescription = $alwaysshowdescription;
        $this->submission = $submission;
        $this->submissionsenabled = $submissionsenabled;
        $this->locked = $locked;
        $this->graded = $graded;
        $this->duedate = $duedate;
        $this->submissionplugins = $submissionplugins;
        $this->returnaction = $returnaction;
        $this->returnparams = $returnparams;
        $this->coursemoduleid = $coursemoduleid;
        $this->view = $view;
        $this->canedit = $canedit;
        $this->cansubmit = $cansubmit;
    }
    
}

/**
 * Renderable header
 */
class assignment_header implements renderable {
    /** @var stdClass the assign record  */
    var $assign = null;
    /** @var bool $showintro - show or hide the intro */
    var $showintro = false;
    /** @var int coursemoduleid - The course module id */
    var $coursemoduleid = 0;
    /** @var string $subpage optional subpage (extra level in the breadcrumbs) */
    var $subpage = '';
    
    /**
     * Constructor
     * 
     * @param stdClass $assignment  - the assign database record
     * @param bool $showintro  - show or hide the intro
     * @param int $coursemoduleid  - the course module id
     * @param string $subpage  - an optional sub page in the navigation
     */
    public function __construct(stdClass $assign, $showintro, $coursemoduleid, $subpage='') {
        $this->assign = $assign;
        $this->showintro = $showintro;
        $this->coursemoduleid = $coursemoduleid;
        $this->subpage = $subpage;
    }
}

/**
 * Renderable grading summary
 */
class grading_summary implements renderable {
    /** @var int participantcount - The number of users who can submit to this assignment */
    var $participantcount = 0;
    /** @var bool submissiondraftsenabled - Allow submission drafts */
    var $submissiondraftsenabled = false;
    /** @var int submissiondraftscount - The number of submissions in draft status */
    var $submissiondraftscount = 0;
    /** @var bool submissionsenabled - Allow submissions */
    var $submissionsenabled = false;
    /** @var int submissionssubmittedcount - The number of submissions in submitted status */
    var $submissionssubmittedcount = 0;
    /** @var int duedate - The assignment due date (if one is set) */
    var $duedate = 0;
    /** @var int coursemoduleid - The assignment course module id */
    var $coursemoduleid = 0;
    
    /**
     * constructor
     *
     * @param assignment $assignment
     */
    public function __construct($participantcount, $submissiondraftsenabled, $submissiondraftscount, $submissionsenabled, $submissionssubmittedcount, $duedate, $coursemoduleid) {
        $this->participantcount = $participantcount;
        $this->submissiondraftsenabled = $submissiondraftsenabled;
        $this->submissiondraftscount = $submissiondraftscount;
        $this->submissionsenabled = $submissionsenabled;
        $this->submissionssubmittedcount = $submissionssubmittedcount;
        $this->duedate = $duedate;
        $this->coursemoduleid = $coursemoduleid;
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
    /** @var context $context */
    public $context;
    /** @var string $context */
    public $dir;
    /** @var MoodleQuickForm $portfolioform */
    public $portfolioform;
    /** @var stdClass $cm course module */
    public $cm;
    /** @var stdClass $course */
    public $course;
    
    
    /**
     * The constructor 
     * 
     * @global stdClass $CFG
     * @param context $context
     * @param int $sid
     * @param string $filearea 
     */
    public function __construct(context $context, $sid, $filearea) {
        global $CFG;
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
            $assignment = new assignment($this->context, null, null);
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
     * preprocessing the file list to add the portfolio links if required
     * 
     * @global stdClass $CFG
     * @param array $dir
     * @param string $filearea 
     * @return void
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
