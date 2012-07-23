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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    core
 * @subpackage lib
 * @copyright  Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Utitily class for importing of CSV files.
 * @copyright Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   moodlecore
 */
class csv_import_reader {
    /**
     * @var int import identifier
     */
    private $_iid;
    /**
     * @var string which script imports?
     */
    private $_type;
    /**
     * @var string|null Null if ok, error msg otherwise
     */
    private $_error;
    /**
     * @var string csvencode - encoded delimiter
     */
    private $_csvencode;
    /**
     * @var string enclosure - string delimiter
     */
    private $_enclosure;
    /**
     * @var string csvdelimiter - delimiter character
     */
    private $_csvdelimiter;
    /**
     * @var array cached columns
     */
    private $_columns;

    /**
     * @var int number of columns
     */
    private $_colcount;

    /**
     * @var function user function for validating columns
     */
    private $_columnvalidation;

    /**
     * @var object file handle used during import
     */
    private $_fpin;

    /**
     * @var object file handle used during export
     */
    private $_fpout;

    /**
     * Constructor
     *
     * @param int $iid import identifier
     * @param string $type which script imports?
     */
    function csv_import_reader($iid, $type) {
        $this->_iid  = $iid;
        $this->_type = $type;
    }

    /**
     * Check for delimiter, end of line or end of file
     *
     * @param string $content passed by ref for memory reasons, unset after return
     * @param string $index character index into content
     * @return bool true if end of string
     */
    private function parse_csv_is_end_of_raw_string(&$content, &$index) {
        if (strlen($content) <= $index
            || substr($content, $index, 1) === "\n"
            || substr($content, $index, 1) === $this->_csvdelimiter) {
            return true;
        }
        return false;
    }

    /**
     * This (should) be faster than strtok for parsing tokens. It also does will correctly
     * return empty strings instead of skipping them.
     *
     * @param string $content passed by ref for memory reasons, unset after return
     * @param string $delimiter separator character
     * @param string $index character index into content
     * @return string the input string up until one of the delimiters or end of string
     */
    private function get_string_until(&$content, $delimiters, $index) {
        $i = $index;
        while ($i < strlen($content) && strpos($delimiters, substr($content, $i, 1)) === false) {
            $i+=1;
        }
        return substr($content, $index, $i - $index);
    }

    /**
     * Break this csv string into
     * subField ::= (any char except double quote or EOF)*
     *
     * @param string $content passed by ref for memory reasons, unset after return
     * @param string $index character index into content
     * @param string $field the field to return
     * @return bool false if error, length of field if ok; use get_error() to get error string
     */
    private function parse_csv_sub_field(&$content, &$index, &$field) {
        $field = $this->get_string_until($content, '"', $index);
        $index += strlen($field);
        return strlen($field);
    }

    /**
     * Break this csv string into
     * escapedField ::= subField ('"' '"' subField)*
     *
     * @param string $content passed by ref for memory reasons, unset after return
     * @param string $index character index into content
     * @param string $field the field to return
     * @return bool false if error, length of field if ok; use get_error() to get error string
     */
    private function parse_csv_escaped_field(&$content, &$index, &$field) {

        $unescapedfield = '';
        if ($this->parse_csv_sub_field($content, $index, $unescapedfield) === false) {
            return false;
        }
        $field .= $unescapedfield;

        while ((strlen($content) > ($index + 1)) && (substr($content, $index, 2) == '""')) {
            $field .= '"';
            $index += 2;

            $unescapedfield = '';
            if ($this->parse_csv_sub_field($content, $index, $unescapedfield) === false) {
                return false;
            }
            $field .= $unescapedfield;
        }
        return strlen($field);
    }

    /**
     * Break this csv string into
     * quotedField ::= '"' escapedField '"'
     *
     * @param string $content passed by ref for memory reasons, unset after return
     * @param string $index character index into content
     * @param string $field the field to return
     * @return bool false if error, length of field if ok; use get_error() to get error string
     */
    private function parse_csv_quoted_field(&$content, &$index, &$field) {
        // Skip the opening double quote.
        $index += 1;
        if ($this->parse_csv_escaped_field($content, $index, $field) === false) {
            return false;
        }

        // Check for incorrect termination of quoted string.
        if (strlen($content) <= $index || substr($content, $index, 1) !== $this->_enclosure) {
            die("incorrect termination of quoted string:" . strlen($content) . " => " . $index . " => " . substr($content, $index) . " => " . $field);
            $this->_error = get_string('csvloaderror', 'error');
            return false;
        }
        // Move past the ending quote.
        $index += 1;

        return strlen($field);
    }


    /**
     * Break this csv string into
     * simpleField ::= (any char except \n, EOF, delimiter or double quote)+
     *
     * @param string $content passed by ref for memory reasons, unset after return
     * @param string $index character index into content
     * @param string $field the field to return
     * @return bool false if error, length of field if ok; use get_error() to get error string
     */
    private function parse_csv_simple_field(&$content, &$index, &$field) {
        // Check for empty.
        if ($this->parse_csv_is_end_of_raw_string($content, $index)) {
            $field = '';
            return strlen($field);
        }

        $field = $this->get_string_until($content, "\n" . $this->_csvdelimiter . $this->_enclosure, $index);
        $index += strlen($field);

        // Check for double quote (error state).
        if (substr($content, $index, 1) === $this->_enclosure) {
            die("found quote in simple string");
            $this->_error = get_string('csvloaderror', 'error');
            return false;
        }
        return strlen($field);
    }

    /**
     * Break this csv string into
     * rawString ::= simpleField | quotedField
     *
     * @param string $content passed by ref for memory reasons, unset after return
     * @param string $index character index into content
     * @param string $field the field to return
     * @return bool false if error, length of field if ok; use get_error() to get error string
     */
    private function parse_csv_raw_string(&$content, &$index, &$field) {
        // Check for empty.
        if ($this->parse_csv_is_end_of_raw_string($content, $index)) {
            $field = '';
            return strlen($field);
        }
        if (substr($content, $index, 1) === '"') {
            return $this->parse_csv_quoted_field($content, $index, $field);
        } else {
            return $this->parse_csv_simple_field($content, $index, $field);
        }
    }

    /**
     * Break this csv string list into
     * csvStringList ::= rawString (',' rawString)*
     *
     * @param string $content passed by ref for memory reasons, unset after return
     * @param string $index character index into content
     * @param string $fields list of parsed fields to return
     * @return bool false if error, count of fields if ok; use get_error() to get error string
     */
    private function parse_csv_string_list(&$content, &$index, &$fields) {
        $morestrings = true;
        do {
            $rawstring = '';
            if ($this->parse_csv_raw_string($content, $index, $rawstring) === false) {
                return false;
            }
            $fields[] = $rawstring;

            // Move past the delimiter if more strings to parse.
            if ((strlen($content) > $index) && (substr($content, $index, 1) === $this->_csvdelimiter)) {
                $index += 1;
            } else {
                $morestrings = false;
            }
        } while ($morestrings);

        return count($fields);
    }

    /**
     * Break this csv line into a stringlist and end of line
     * This is more complicated than just splitting on delimiter because a field can contain escaped delimiters
     * csvRecord ::= csvStringList ("\n" | 'EOF')
     *
     * @param string $content passed by ref for memory reasons, unset after return
     * @param string $index character index into content
     * @param string $fields list of parsed fields to return
     * @return bool false if error, count of fields if ok; use get_error() to get error string
     */
    private function parse_csv_record(&$content, &$index, &$fields) {
        if ($this->parse_csv_string_list($content, $index, $fields) === false) {
            return false;
        }
        if (!strlen($content) <= $index) {
            if (substr($content, $index, 1) == "\n") {
                // Move to next record.
                $index += 1;
            }
        }
        return count($fields);
    }


    /**
     * Break this csv content into records
     * This is more complicated than just splitting on lines because a field can contain new line characters
     * csvFile ::= (csvRecord)* 'EOF'
     *
     * @param string $content passed by ref for memory reasons, unset after return
     * @param string $index character index into content
     * @param string $lines list of parsed records to return
     * @return bool false if error, count of data lines if ok; use get_error() to get error string
     */
    private function parse_csv_content(&$content) {
        $index = 0;
        $linenum = 0;
        if (strlen($content) < 1) {
            $this->_error = get_string('csvloaderror', 'error');
            return false;
        }

        while ($index < strlen($content)) {
            $line = array();
            if ($this->parse_csv_record($content, $index, $line) === false) {
                return false;
            }
            // Ignore blank lines.
            if (count($line) > 1 || $line[0] != "") {
                if ($linenum == 0) {
                    if ($this->columns_read($line) === false) {
                        return false;
                    }
                } else {
                    if ($this->line_read($line) === false) {
                        return false;
                    }
                }
            }
            $linenum += 1;
        }

        return $linenum - 1;
    }

    /**
     * Called after the first line is read to save the list of column headers
     *
     * @param array $columns The list of column names
     */
    private function columns_read($columns) {
        $this->_colcount = count($columns);
        if ($this->_colcount < 1) {
            $this->_error = get_string('csvloaderror', 'error');
            return false;
        }
        if ($this->_columnvalidation) {
            $result = $this->_columnvalidation($columns);
            if ($result !== true) {
                $this->_error = $result;
                return false;
            }
        }
        $this->_columns = $columns; // cached columns
        fwrite($this->_fpout, rawurlencode(serialize($columns))."\n");
    }

    /**
     * Called after the each line is read to save the list of column data
     *
     * @param array $columns The list of column data
     */
    private function line_read($line) {
        foreach ($line as $key=>$value) {
            $line[$key] = str_replace($this->_csvencode, $this->_csvdelimiter, trim($value));
        }
        if (count($line) !== $this->_colcount) {
            // This is critical.
            $this->_error = get_string('csvweirdcolumns', 'error');
            $this->cleanup();
            return false;
        }
        fwrite($this->_fpout, rawurlencode(serialize($line))."\n");
    }

    /**
     * Parse this content
     *
     * @param string $content passed by ref for memory reasons, unset after return
     * @param string $encoding content encoding
     * @param string $delimiter_name separator (comma, semicolon, colon, cfg)
     * @param string $column_validation name of function for columns validation, must have one param $columns
     * @param string $enclosure field wrapper. One character only.
     * @return bool false if error, count of data lines if ok; use get_error() to get error string
     */
    function load_csv_content(&$content, $encoding, $delimiter_name, $column_validation=null, $enclosure='"') {
        global $USER, $CFG;

        $this->close();
        $this->_error = null;
        $this->_columnvalidation = $columnvalidation;

        $content = textlib::convert($content, $encoding, 'utf-8');
        // remove Unicode BOM from first line
        $content = textlib::trim_utf8_bom($content);
        // Fix mac/dos newlines
        $content = preg_replace('!\r\n?!', "\n", $content);

        // Ok ready to begin, first look at CSV grammar.
        //
        // csvRecord ::= csvStringList ("\n" | 'EOF')
        // csvStringList ::= rawString (',' rawString)*
        // rawString ::= simpleField | quotedField
        // simpleField ::= (any char except \n, EOF, comma or double quote)+
        // quotedField ::= '"' escapedField '"'
        // escapedField ::= subField ('"' '"' subField)*
        // subField ::= (any char except double quote or EOF)*

        $this->_csvdelimiter = self::get_delimiter($delimitername);
        $this->_csvencode    = self::get_encoded_delimiter($delimitername);
        $this->_enclosure = $enclosure;

        // Open file for writing.
        $filename = $CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid;
        $this->_fpout = fopen($filename, "w");

        $numlines = $this->parse_csv_content($content);
        if ($numlines === false) {
            $this->cleanup();
            return false;
        }

        // Close the export file.
        if (!empty($this->_fpout)) {
            fclose($this->_fpout);
            $this->_fpout = null;
        }
        return $numlines;
    }

    /**
     * Returns list of columns
     *
     * @return array
     */
    function get_columns() {
        if (isset($this->_columns)) {
            return $this->_columns;
        }

        global $USER, $CFG;

        $filename = $CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid;
        if (!file_exists($filename)) {
            return false;
        }
        $fp = fopen($filename, "r");
        $line = fgetcsv($fp);
        fclose($fp);
        if ($line === false) {
            return false;
        }
        $this->_columns = unserialize(rawurldecode($line));
        return $this->_columns;
    }

    /**
     * Init iterator.
     *
     * @return bool Success
     */
    function init() {
        global $CFG, $USER;

        if (!empty($this->_fpin)) {
            $this->close();
        }
        $filename = $CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid;
        if (!file_exists($filename)) {
            return false;
        }
        if (!$this->_fpin = fopen($filename, "r")) {
            return false;
        }
        // Skip header.
        return (fgets($this->_fpin) !== false);
    }

    /**
     * Get next line
     *
     * @return mixed false, or an array of values
     */
    function next() {
        if (empty($this->_fpin) or feof($this->_fpin)) {
            return false;
        }
        if ($ser = fgets($this->_fpin)) {
            $r = unserialize(rawurldecode($ser));
            return $r;
        } else {
            return false;
        }
    }

    /**
     * Release iteration related resources
     *
     * @return void
     */
    function close() {
        if (!empty($this->_fpin)) {
            fclose($this->_fpin);
            $this->_fpin = null;
        }
    }

    /**
     * Get last error
     *
     * @return string error text of null if none
     */
    function get_error() {
        return $this->_error;
    }

    /**
     * Cleanup temporary data
     *
     * @param boolean $full true means do a full cleanup - all sessions for current user, false only the active iid
     */
    function cleanup($full=false) {
        global $USER, $CFG;

        if ($full) {
            @remove_dir($CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id);
        } else {
            @unlink($CFG->tempdir.'/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid);
        }
    }

    /**
     * Get list of cvs delimiters
     *
     * @return array suitable for selection box
     */
    static function get_delimiter_list() {
        global $CFG;
        $delimiters = array('comma'=>',', 'semicolon'=>';', 'colon'=>':', 'tab'=>'\\t');
        if (isset($CFG->CSV_DELIMITER) and strlen($CFG->CSV_DELIMITER) === 1 and !in_array($CFG->CSV_DELIMITER, $delimiters)) {
            $delimiters['cfg'] = $CFG->CSV_DELIMITER;
        }
        return $delimiters;
    }

    /**
     * Get delimiter character
     *
     * @param string separator name
     * @return string delimiter char
     */
    static function get_delimiter($delimitername) {
        global $CFG;
        switch ($delimitername) {
            case 'colon':     return ':';
            case 'semicolon': return ';';
            case 'tab':       return "\t";
            case 'cfg':       if (isset($CFG->CSV_DELIMITER)) { return $CFG->CSV_DELIMITER; } // no break; fall back to comma
            case 'comma':     return ',';
            default :         return ',';  // If anything else comes in, default to comma.
        }
        // This is the default.
        return ',';
    }

    /**
     * Get encoded delimiter character
     *
     * @param string separator name
     * @return string encoded delimiter char
     */
    function get_encoded_delimiter($delimitername) {
        global $CFG;
        if ($delimitername == 'cfg' and isset($CFG->CSV_ENCODE)) {
            return $CFG->CSV_ENCODE;
        }
        $delimiter = self::get_delimiter($delimitername);
        return '&#'.ord($delimiter);
    }

    /**
     * Create new import id
     *
     * @param string who imports?
     * @return int iid
     */
    static function get_new_iid($type) {
        global $USER;

        $filename = make_temp_directory('temp/csvimport/'.$type.'/'.$USER->id);

        // Use current (non-conflicting) time stamp.
        $iiid = time();
        while (file_exists($filename.'/'.$iiid)) {
            $iiid--;
        }

        return $iiid;
    }
}

/**
 * Utility class for exporting of CSV files.
 * @copyright 2012 Adrian Greeve
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   core
 * @category  csv
 */
class csv_export_writer {
    /**
     * @var string $delimiter  The name of the delimiter. Supported types(comma, tab, semicolon, colon, cfg)
     */
    var $delimiter;
    /**
     * @var string $csvenclosure  How fields with spaces and commas are enclosed.
     */
    var $csvenclosure;
    /**
     * @var string $mimetype  Mimetype of the file we are exporting.
     */
    var $mimetype;
    /**
     * @var string $filename  The filename for the csv file to be downloaded.
     */
    var $filename;
    /**
     * @var string $path  The directory path for storing the temporary csv file.
     */
    var $path;
    /**
     * @var resource $fp  File pointer for the csv file.
     */
    protected $fp;

    /**
     * Constructor for the csv export reader
     *
     * @param string $delimiter      The name of the character used to seperate fields. Supported types(comma, tab, semicolon, colon, cfg)
     * @param string $enclosure      The character used for determining the enclosures.
     * @param string $mimetype       Mime type of the file that we are exporting.
     */
    public function __construct($delimiter = 'comma', $enclosure = '"', $mimetype = 'application/download') {
        $this->delimiter = $delimiter;
        // Check that the enclosure is a single character.
        if (strlen($enclosure) == 1) {
            $this->csvenclosure = $enclosure;
        } else {
            $this->csvenclosure = '"';
        }
        $this->filename = "Moodle-data-export.csv";
        $this->mimetype = $mimetype;
    }

    /**
     * Set the file path to the temporary file.
     */
    protected function set_temp_file_path() {
        global $USER, $CFG;
        make_temp_directory('csvimport/' . $USER->id);
        $path = $CFG->tempdir . '/csvimport/' . $USER->id. '/' . $this->filename;
        // Check to see if the file exists, if so delete it.
        if (file_exists($path)) {
            unlink($path);
        }
        $this->path = $path;
    }

    /**
     * Add data to the temporary file in csv format
     *
     * @param array $row  An array of values.
     */
    public function add_data($row) {
        if(!isset($this->path)) {
            $this->set_temp_file_path();
            $this->fp = fopen($this->path, 'w+');
        }
        $delimiter = csv_import_reader::get_delimiter($this->delimiter);
        fputcsv($this->fp, $row, $delimiter, $this->csvenclosure);
    }

    /**
     * Echos or returns a csv data line by line for displaying.
     *
     * @param bool $return  Set to true to return a string with the csv data.
     * @return string       csv data.
     */
    public function print_csv_data($return = false) {
        fseek($this->fp, 0);
        $returnstring = '';
        while (($content = fgets($this->fp)) !== false) {
            if (!$return){
                echo $content;
            } else {
                $returnstring .= $content;
            }
        }
        if ($return) {
            return $returnstring;
        }
    }

    /**
     * Set the filename for the uploaded csv file
     *
     * @param string $dataname    The name of the module.
     * @param string $extenstion  File extension for the file.
     */
    public function set_filename($dataname, $extension = '.csv') {
        $filename = clean_filename($dataname);
        $filename .= clean_filename('-' . gmdate("Ymd_Hi"));
        $filename .= clean_filename("-{$this->delimiter}_separated");
        $filename .= $extension;
        $this->filename = $filename;
    }

    /**
     * Output file headers to initialise the download of the file.
     */
    protected function send_header() {
        global $CFG;
        if (strpos($CFG->wwwroot, 'https://') === 0) { //https sites - watch out for IE! KB812935 and KB316431
            header('Cache-Control: max-age=10');
            header('Pragma: ');
        } else { //normal http - prevent caching at all cost
            header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
            header('Pragma: no-cache');
        }
        header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        header("Content-Type: $this->mimetype\n");
        header("Content-Disposition: attachment; filename=\"$this->filename\"");
    }

    /**
     * Download the csv file.
     */
    public function download_file() {
        $this->send_header();
        $this->print_csv_data();
        exit;
    }

    /**
     * Creates a file for downloading an array into a deliminated format.
     * This function is useful if you are happy with the defaults and all of your
     * information is in one array.
     *
     * @param string $filename    The filename of the file being created.
     * @param array $records      An array of information to be converted.
     * @param string $delimiter   The name of the delimiter. Supported types(comma, tab, semicolon, colon, cfg)
     * @param string $enclosure   How speical fields are enclosed.
     */
    public static function download_array($filename, array &$records, $delimiter = 'comma', $enclosure='"') {
        $csvdata = new csv_export_writer($delimiter, $enclosure);
        $csvdata->set_filename($filename);
        foreach ($records as $row) {
            $csvdata->add_data($row);
        }
        $csvdata->download_file();
    }

    /**
     * This will convert an array of values into a deliminated string.
     * Like the above function, this is for convenience.
     *
     * @param array $records     An array of information to be converted.
     * @param string $delimiter  The name of the delimiter. Supported types(comma, tab, semicolon, colon, cfg)
     * @param string $enclosure  How speical fields are enclosed.
     * @param bool $return       If true will return a string with the csv data.
     * @return string            csv data.
     */
    public static function print_array(array &$records, $delimiter = 'comma', $enclosure = '"', $return = false) {
        $csvdata = new csv_export_writer($delimiter, $enclosure);
        foreach ($records as $row) {
            $csvdata->add_data($row);
        }
        $data = $csvdata->print_csv_data($return);
        if ($return) {
            return $data;
        }
    }

    /**
     * Make sure that everything is closed when we are finished.
     */
    public function __destruct() {
        fclose($this->fp);
        unlink($this->path);
    }
}
