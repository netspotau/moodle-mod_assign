<?php

defined('MOODLE_INTERNAL') || die;

/**
 * Manage question behaviours page
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_page_managesubmissions extends admin_externalpage {
    /**
     * Constructor
     */
    public function __construct() {
        global $CFG;
        parent::__construct('managesubmissionplugins', get_string('managesubmissionplugins', 'assign'),
                new moodle_url('/mod/assign/admin_manage_plugins.php'));
    }

    /**
     * Search question behaviours for the specified string
     *
     * @param string $query The string to search for in question behaviours
     * @return array
     */
    public function search($query) {
        global $CFG;
        if ($result = parent::search($query)) {
            return $result;
        }

        $found = false;
        $textlib = textlib_get_instance();
        foreach (get_plugin_list('submission') as $name => $notused) {
            if (strpos($textlib->strtolower(get_string('pluginname', 'submission_' . $name)),
                    $query) !== false) {
                $found = true;
                break;
            }
        }
        if ($found) {
            $result = new stdClass();
            $result->page     = $this;
            $result->settings = array();
            return array($this->name => $result);
        } else {
            return array();
        }
    }
}


