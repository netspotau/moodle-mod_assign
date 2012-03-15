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
 * This file contains the importer to batch import feedback files
 * 
 * This class provides all the functionality for the new assign module.
 *
 * @package   mod_assign
 * @subpackage   assignfeedback_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Include formslib.php */
require_once ($CFG->libdir.'/formslib.php');

/*
 * library class for feedback file importer
 * 
 * @package   mod_assign
 * @subpackage   feedback_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignfeedback_file_importer_form extends moodleform implements renderable {

    /** @var assignment_feedback_plugin $plugin*/
    public $plugin;

    /** @var string $unzippedfilesdir */
    public $unzippedfilesdir;

    /** @var array $participants */
    private $participants;

    /** @var list of content hashes for existing files */
    private $hashcache;

    /**
     * Get a list of all the content hashes for existing content for this user
     *
     * @param int userid
     */
    function cache_existing_user_content_hashes($userid) {
        $submission = $this->plugin->get_assignment()->get_user_submission($userid, false);
        $hashlist = array(); 
        if ($submission) {
            foreach ($this->plugin->get_assignment()->get_submission_plugins() as $plugin) {
                if ($plugin->is_visible() && $plugin->is_enabled()) {
                    $pluginfiles = $plugin->get_files($submission);
                    foreach ($pluginfiles as $name => $file) {
                        $hash = '';
                        if (is_array($file)) {
                            $hash = sha1($file[0]); 
                        } else {
                            $hash = $file->get_contenthash();
                        }
                        $hashlist[] = $hash;
                    }
                }
            }
        }
        $grade = $this->plugin->get_assignment()->get_user_grade($userid, false);
        foreach ($this->plugin->get_assignment()->get_feedback_plugins() as $plugin) {
            if ($plugin->is_visible() && $plugin->is_enabled()) {
                $pluginfiles = $plugin->get_files($grade);
                foreach ($pluginfiles as $name => $file) {
                    $hash = '';
                    if (is_array($file)) {
                        $hash = sha1($file[0]); 
                    } else {
                        $hash = $file->get_contenthash();
                    }
                    $hashlist[] = $hash;
                }
            }
        }
        $this->hashcache[$userid] = $hashlist;
    }
    
    /**
     * Is this file new or modified since downloading?
     *
     * @param string $uploadfilename The name of the file from the zip
     * @param stdClass $user The user who owns the file
     * @param string $originalfilename The name of the file in moodle
     * @param mixed stored_file | null If this is an update - remember the original file record
     * @return bool
     */
    function file_modified($uploadpath, $user, $originalfilename, &$filerecord) {
        $fs = get_file_storage();

        // check the hashes
        $newhash = sha1_file($uploadpath);

        // get a list of files for this student
        if (!array_key_exists($user->id, $this->hashcache)) {
            $this->cache_existing_user_content_hashes($user->id);
        }

        if (in_array($newhash, $this->hashcache[$user->id])) {
            // file matches existing file
            return false;
        }

        // file exists
        return true;
    }

    /**
     * Is this a valid filename for import?
     *
     * @param string filename
     * @return bool
     */
    function is_valid_file($file, & $fileowner, & $plugin, & $filename) {
        if (strpos($file, ".") == 0) {
            return false;
        }

        if (!$this->participants) {
            $currentgroup = groups_get_activity_group($this->plugin->get_assignment()->get_course_module(), true);
            $users = $this->plugin->get_assignment()->list_participants($currentgroup, false);

            $this->participants = array();
            foreach ($users as $user) {
                // build the prefix 
                $prefix = clean_filename(str_replace('_', '', fullname($user)) . '_' . $user->id . '_');
                $this->participants[$prefix] = $user;
            }
        }

        foreach ($this->participants as $prefix => $user) {
            if (strpos($file, $prefix) === 0) {
                $shortfilename = substr($file, strlen($prefix));
                $filenamefields = explode('_', $shortfilename);
                if (count($filenamefields) < 3) {
                    continue;
                }
                $plugin = $filenamefields[0] . '_' . $filenamefields[1];
                $filename = substr($shortfilename, strlen($plugin) + 1);
                $fileowner = $user;
                
                return true;
            }
        }

        // filename pattern is fullname(user)_userid_plugintype
        // get a list of all participants
        return false;
    }

    function definition() {
        $mform = $this->_form;
        $this->hashcache = array();
        
        list($plugin, $tmpdir) = $this->_customdata;
        // visible elements
        $this->plugin = $plugin;
        $this->unzippedfilesdir = $tmpdir;
        $count = 0;

        if (!is_dir($tmpdir)) {
            throw new coding_exception('Expected a directory');
        }
        $optionsnew = array();
        $optionsnew[1] = get_string('sendnewfileasfeedback', 'assignfeedback_file');
        $optionsnew[0] = get_string('skip', 'assignfeedback_file');
        $optionsupdate = array();
        $optionsupdate[1] = get_string('replacefileasfeedback', 'assignfeedback_file');
        $optionsupdate[0] = get_string('skip', 'assignfeedback_file');
        foreach (scandir($tmpdir) as $file) {
            $user = null;
            $plugin = '';
            $filename = '';
            $filerecord = null;
            if ($this->is_valid_file($file, $user, $plugin, $filename)) {
                if ($this->file_modified($tmpdir . '/' . $file, $user, $filename, $filerecord)) {
                    $mform->addElement('header', 'fileheader' . $count, get_string('stepnumber', 'assignfeedback_file', $count+1));
                    $mform->addElement('static', 'filelabel' . $count, get_string('file'), $file);
                    $mform->addElement('static', 'userlabel' . $count, get_string('user'), fullname($user));
                    if ($filerecord) {
                        $mform->addElement('select', 'file' . $count, '', $optionsupdate);
                    } else {
                        $mform->addElement('select', 'file' . $count, '', $optionsnew);
                    }
                    $mform->setDefault('file' . $count, 1);
                    $count += 1;
                }
            }
        }

        $mform->addElement('hidden', 'id', $this->plugin->get_assignment()->get_course_module()->id);
        $mform->addElement('hidden', 'action', 'plugingradingpage');
        $mform->addElement('hidden', 'gradingaction', 'import');
        $mform->addElement('hidden', 'plugin', 'file');
        if ($count == 0) {
            $mform->addElement('header', 'nofilesheader', get_string('nofeedbackfilesheader', 'assignfeedback_file'));
            $mform->addElement('static', 'nofiles', '', get_string('nofeedbackfiles', 'assignfeedback_file'));
            $this->add_action_buttons(false, get_string('cancel'));
        } else {
            $this->add_action_buttons(true, get_string('submit'));
        }
    }

    /**
     * Process files - the importer form has been submitted time to send some feedback files
     *
     * @return int
     */
    function process_files() {
        // setup the cache

        $data = $this->get_data();
        $count = 0;
        $filessent = 0;
        $fs = get_file_storage();
        if ($data) {
            foreach (scandir($this->unzippedfilesdir) as $file) {
                $user = null;
                $plugin = '';
                $filename = '';
                $filerecord = null;
                if ($this->is_valid_file($file, $user, $plugin, $filename)) {
                    if ($this->file_modified($this->unzippedfilesdir . '/' . $file, $user, $filename, $filerecord)) {
                        $filenum = 'file' . $count;
                        if ($data->$filenum) {
                            $filessent += 1;

                            $grade = $this->plugin->get_assignment()->get_user_grade($user->id, true);

                            $newfile = new stdClass();
                            $newfile->contextid = $this->plugin->get_assignment()->get_context()->id;
                            $newfile->component = 'mod_assign';
                            $newfile->filearea  = ASSIGN_FILEAREA_FEEDBACK_FILES;
                            $newfile->itemid    = $grade->id;
                            $newfile->filepath  = '/';
                            $newfile->filename  = $filename;
                            $newfile->userid    = $user->id;

                            if ($filerecord) {
                                $filerecord->delete();
                            }
    
                            $fs->create_file_from_pathname($newfile, $this->unzippedfilesdir . '/' . $file);
                            
                        }
                        $count+= 1;
                    }
                }
            }
        }

        return $filessent;
    }

}
