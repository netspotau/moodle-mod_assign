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
class grading_options_form implements renderable {
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
    /** @var stdClass $user */
    protected $user = null;
    /** @var assignment $assignment */
    protected $assignment = null;
    
    /**
     * Constructor
     * @param stdClass $user
     * @param assignment $assignment
     */
    public function __construct(stdClass $user, assignment $assignment) {
        $this->set_user($user);
        $this->set_assignment($assignment);
    }
    
    /**
     * Returns assignment
     *
     * @return assignment $assignment
     */
    public function get_assignment() {
        return $this->assignment;
    }

    /**
     * Set the assignment
     *
     * @param assignment $assignment
     */
    public function set_assignment(assignment $assignment) {
        if (!$assignment) {
            throw new coding_exception('Assignment may not be null');
        }
        $this->assignment = $assignment;
    }
    
    /**
     * Returns user
     *
     * @return stdClass $user
     */
    public function get_user() {
        return $this->user;
    }

    /**
     * Set the user
     *
     * @param stdClass $user 
     */
    public function set_user($user) {
        if (!$user) {
            throw new coding_exception('User may not be null');
        }
        $this->user = $user;
    }
}

/*
 * Implements a renderable feedback plugin feedback
 */
class feedback_plugin_feedback implements renderable {
    const SUMMARY                = 10;
    const FULL                   = 20;

    /** @var assignment $assignment */
    protected $assignment = null;
    /** @var assignment_submission_plugin $plugin */
    protected $plugin = null;
    /** @var stdClass $grade */
    protected $grade = null;
    /** @var string $view */
    protected $view = self::SUMMARY;
    
    /**
     * feedback for a single plugin
     *
     * @param assignment $assignment
     * @param assignment_feedback_plugin $plugin
     * @param stdClass $grade
     * @param string view one of feedback_plugin::SUMMARY or feedback_plugin::FULL
     */
    public function __construct(assignment $assignment, assignment_feedback_plugin $plugin, stdClass $grade, $view) {
        $this->set_assignment($assignment);
        $this->set_plugin($plugin);
        $this->set_grade($grade);
        $this->set_view($view);
    }
    
    /**
     * Returns assignment info
     *
     * @return assignment
     */
    public function get_assignment() {
        return $this->assignment;
    }

    /**
     * Set the assignment info (may not be null)
     *
     * @param assignment $assignment
     */
    public function set_assignment(assignment $assignment) {
        if (!$assignment) {
            throw new coding_exception('Assignment may not be null');
        }
        $this->assignment = $assignment;
    }
    
    /**
     * Returns grade info
     *
     * @return stdClass
     */
    public function get_grade() {
        return $this->grade;
    }

    /**
     * Set the grade info (may not be null)
     *
     * @param stdClass $grade
     */
    public function set_grade(stdClass $grade) {
        $this->grade = $grade;
    }

    /**
     * Returns plugin info
     *
     * @return assignment_feedback_plugin
     */
    public function get_plugin() {
        return $this->plugin;
    }

    /**
     * Set the plugin info (may not be null)
     *
     * @param assignment_feedback_plugin $plugin
     */
    public function set_plugin(assignment_feedback_plugin $plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * Returns view
     *
     * @return int
     */
    public function get_view() {
        return $this->view;
    }

    /**
     * Set the view
     *
     * @param int $view
     */
    public function set_view($view) {
        if (in_array($view, array(self::SUMMARY, self::FULL))) {
            $this->view = $view;
        } else {
            throw new coding_exception('Unknown submission view type.');
        }
    }
}

/*
 * Implements a renderable submission plugin submission
 */
class submission_plugin_submission implements renderable {
    const SUMMARY                = 10;
    const FULL                   = 20;

    /** @var assignment $assignment */
    protected $assignment = null;
    /** @var assignment_submission_plugin $plugin */
    protected $plugin = null;
    /** @var stdClass $submission */
    protected $submission = null;
    /** @var string $view */
    protected $view = self::SUMMARY;
    
    /**
     * Constructor
     * @param assignment $assignment
     * @param assignment_submission_plugin $plugin
     * @param stdClass $submission
     * @param string $view one of submission_plugin::SUMMARY, submission_plugin::FULL
     */
    public function __construct(assignment $assignment, assignment_submission_plugin $plugin, stdClass $submission, $view) {
        $this->set_assignment($assignment);
        $this->set_plugin($plugin);
        $this->set_submission($submission);
        $this->set_view($view);
    }
    
    /**
     * Returns assignment info
     *
     * @return assignment
     */
    public function get_assignment() {
        return $this->assignment;
    }

    /**
     * Set the assignment info (may not be null)
     *
     * @param assignment $assignment
     */
    public function set_assignment(assignment $assignment) {
        $this->assignment = $assignment;
    }
    
    /**
     * Returns submission info
     *
     * @return stdClass
     */
    public function get_submission() {
        return $this->submission;
    }

    /**
     * Set the submission info (may not be null)
     *
     * @param stdClass $submission
     */
    public function set_submission(stdClass $submission) {
        $this->submission = $submission;
    }

    /**
     * Returns plugin info
     *
     * @return assignment_submission_plugin
     */
    public function get_plugin() {
        return $this->plugin;
    }

    /**
     * Set the plugin info (may not be null)
     *
     * @param assignment_submission_plugin $plugin
     */
    public function set_plugin(assignment_submission_plugin $plugin) {
        if (!$plugin) {
            throw new coding_exception('Plugin may not be null');
        }
        $this->plugin = $plugin;
    }
    
    /**
     * Returns view
     *
     * @return int
     */
    public function get_view() {
        return $this->view;
    }

    /**
     * Set the view
     *
     * @param int $view
     */
    public function set_view($view) {
        if (in_array($view, array(self::SUMMARY, self::FULL))) {
            $this->view = $view;
        } else {
            throw new coding_exception('Unknown submission view type.');
        }
    }
}

/**
 * Renderable feedback status
 */
class feedback_status implements renderable {
    const STUDENT_VIEW     = 10;
    const GRADER_VIEW      = 20;
    
    /** @var stdClass the grade info (may be null) */
    protected $grade = null;
    /** @var assignment the assignment info (may not be null) */
    protected $assignment = null;
    /** @var int $view */
    protected $view = self::STUDENT_VIEW;

    /**
     * Constructor
     * @param assignment $assignment
     * @param mixed stdClass|null $grade
     * @param int $view
     */
    public function __construct($assignment, $grade, $view) {
        $this->set_assignment($assignment);
        $this->set_grade($grade);
        $this->set_view($view);
    }
    
    /**
     * Returns submission view type
     *
     * @return int
     */
    public function get_view() {
        return $this->view;
    }

    /**
     * Sets the submission view type
     *
     * @param int $view
     */
    public function set_view($view) {
        if (in_array($view, array(self::STUDENT_VIEW, self::GRADER_VIEW))) {
            $this->view = $view;
        } else {
            throw new coding_exception('Unknown view type.');
        }
    }
    
    /**
     * Returns grade info
     *
     * @return mixed stdClass|null $grade
     */
    public function get_grade() {
        return $this->grade;
    }

    /**
     * Set the grade info (may be null)
     *
     * @param mixed stdClass|null $grade
     */
    public function set_grade($grade) {
        $this->grade = $grade;
    }

    /**
     * Returns assignment info
     *
     * @return assignment
     */
    public function get_assignment() {
        return $this->assignment;
    }

    /**
     * Set the assignment info (may not be null)
     *
     * @param assignment $assignment
     */
    public function set_assignment(assignment $assignment) {
        $this->assignment = $assignment;
    }

}

/**
 * Renderable submission status
 */
class submission_status implements renderable {
    const STUDENT_VIEW     = 10;
    const GRADER_VIEW      = 20;
    
    /** @var stdClass the submission info (may be null) */
    protected $submission = null;
    /** @var assignment the assignment info (may not be null) */
    protected $assignment = null;
    /** @var int the view (submission_status::STUDENT_VIEW OR submission_status::GRADER_VIEW) */
    protected $view = self::STUDENT_VIEW;
    /** @var bool locked */
    protected $locked = false;
    /** @var bool graded */
    protected $graded = false;
    /** @var bool show_edit */
    protected $canedit = false;
    /** @var bool show_submit */
    protected $cansubmit = false;
    /** @var int extensionduedate */
    protected $extensionduedate = null;

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
    public function __construct($assignment, $submission, $locked, $graded, $view, $canedit, $cansubmit, $extensionduedate) {
        $this->set_assignment($assignment);
        $this->set_submission($submission);
        $this->set_locked($locked);
        $this->set_graded($graded);
        $this->set_view($view);
        $this->set_can_edit($canedit);
        $this->set_can_submit($cansubmit);
        $this->set_extensionduedate($extensionduedate);
    }
    
    /**
     * Returns true if the we should show the edit submission link
     *
     * @return bool
     */
    public function can_edit() {
        return $this->canedit;
    }

    /**
     * Sets the canedit link of the submission
     *
     * @param bool $canedit
     */
    public function set_can_edit($canedit) {
        $this->canedit = $canedit;
    }
    
    /**
     * Returns true if the we should show the submit submission link
     *
     * @return bool
     */
    public function can_submit() {
        return $this->cansubmit;
    }

    /**
     * Sets the cansubmit status of the submission
     *
     * @param bool $cansubmit
     */
    public function set_can_submit($cansubmit) {
        $this->cansubmit = $cansubmit;
    }
    
    
    /**
     * Returns true if the submission is graded in the gradebook
     *
     * @return bool
     */
    public function is_graded() {
        return $this->graded;
    }

    /**
     * Sets the graded status of the submission
     *
     * @param bool $graded
     */
    public function set_graded($graded = false) {
        $this->graded = $graded;
    }
    
    /**
     * Returns true if the submission is locked in the gradebook
     *
     * @return bool
     */
    public function is_locked() {
        return $this->locked;
    }

    /**
     * Sets the locked status of the submission
     *
     * @param bool $locked
     */
    public function set_locked($locked = false) {
        $this->locked = $locked;
    }
    
    /**
     * Returns submission view type
     *
     * @return string
     */
    public function get_view() {
        return $this->view;
    }

    /**
     * Sets the submission view type
     *
     * @param int $view
     */
    public function set_view($view) {
        if (in_array($view, array(self::STUDENT_VIEW, self::GRADER_VIEW))) {
            $this->view = $view;
        } else {
            throw new coding_exception('Unknown submission view type.');
        }
    }
    
    /**
     * Returns submission info
     *
     * @return mixed stdClass|null
     */
    public function get_submission() {
        return $this->submission;
    }

    /**
     * Set the submission info (may be null)
     *
     * @param mixed stdClass|null $submission
     */
    public function set_submission($submission) {
        $this->submission = $submission;
    }

    /**
     * Returns assignment info
     *
     * @return assignment
     */
    public function get_assignment() {
        return $this->assignment;
    }

    /**
     * Set the assignment info (may not be null)
     *
     * @param assignment $assignment
     */
    public function set_assignment(assignment $assignment) {
        $this->assignment = $assignment;
    }

    /**
     * Returns extensionduedate info
     *
     * @return mixed int|null
     */
    public function get_extensionduedate() {
        return $this->extensionduedate;
    }

    /**
     * Set the extensionduedate 
     *
     * @param mixed int|null $extensionduedate
     */
    public function set_extensionduedate($extensionduedate) {
        $this->extensionduedate = $extensionduedate;
    }
}

/**
 * Renderable header
 */
class assignment_header implements renderable {
    /** @var assignment the assignment info (may not be null) */
    protected $assignment = null;
    /** @var bool $showintro - show or hide the intro */
    protected $showintro = false;
    /** @var string $subpage optional subpage (extra level in the breadcrumbs) */
    protected $subpage = '';
    
    /**
     * Constructor
     * 
     * @param assignment $assignment 
     * @param bool $showintro 
     * @param string $subpage 
     */
    public function __construct(assignment $assignment, $showintro, $subpage='') {
        $this->set_assignment($assignment);
        $this->set_show_intro($showintro);
        $this->set_sub_page($subpage);
    }
    
    /**
     * Returns the sub page for the navigation menu
     *
     * @return string
     */
    public function get_sub_page() {
        return $this->subpage;
    }

    /**
     * Set the current assignment sub page
     *
     * @param string $subpage
     */
    public function set_sub_page($subpage) {
        $this->subpage = $subpage;
    }

    /**
     * Returns assignment info
     *
     * @return assignment
     */
    public function get_assignment() {
        return $this->assignment;
    }

    /**
     * Set the assignment info (may not be null)
     *
     * @param assignment $assignment
     */
    public function set_assignment(assignment $assignment) {
        $this->assignment = $assignment;
    }

    /**
     * Returns show intro
     *
     * @return bool
     */
    public function get_show_intro() {
        return $this->showintro;
    }

    /**
     * Set the show intro flag
     *
     * @param bool show_intro
     */
    public function set_show_intro($showintro) {
        $this->showintro = $showintro;
    }
    
}

/**
 * Renderable grading summary
 */
class grading_summary implements renderable {
    /** @var assignment the assignment info (may not be null) */
    protected $assignment = null;
    
    /**
     * constructor
     *
     * @param assignment $assignment
     */
    public function __construct(assignment $assignment) {
        $this->set_assignment($assignment);
    }
    
    /**
     * Returns assignment info
     *
     * @return assignment
     */
    public function get_assignment() {
        return $this->assignment;
    }

    /**
     * Set the assignment info (may not be null)
     *
     * @param assignment $assignment
     * @return void
     */
    public function set_assignment(assignment $assignment) {
        $this->assignment = $assignment;
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
