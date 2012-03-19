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
    var $_iid;
    /**
     * @var string which script imports?
     */
    var $_type;
    /**
     * @var string|null Null if ok, error msg otherwise
     */
    var $_error;
    /**
     * @var string csvencode - encoded delimiter
     */
    var $_csvencode;
    /**
     * @var string csvdelimiter - delimiter character
     */
    var $_csvdelimiter;
    /**
     * @var array cached columns
     */
    var $_columns;

    /**
     * @var int number of columns
     */
    var $_colcount;

    /**
     * @var function user function for validating columns
     */
    var $_columnvalidation;

    /**
     * @var object file handle used during import
     */
    var $_fpin;
    
    /**
     * @var object file handle used during export
     */
    var $_fpout;

    /**
     * Contructor
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
     * @return bool TRUE if end of string
     */
    private function parse_csv_is_end_of_raw_string(&$content, &$index) {
        if (strlen($content) <= $index 
            || substr($content, $index, 1) === "\n"
            || substr($content, $index, 1) === $this->_csvdelimiter) {
            return TRUE;
        }
        return FALSE;
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
        while ($i < strlen($content) && strpos($delimiters, substr($content, $i, 1)) === FALSE) {
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
     * @return bool FALSE if error, length of field if ok; use get_error() to get error string
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
     * @return bool FALSE if error, length of field if ok; use get_error() to get error string
     */
    private function parse_csv_escaped_field(&$content, &$index, &$field) {

        $unescapedfield = '';
        if ($this->parse_csv_sub_field($content, $index, $unescapedfield) === FALSE) {
            return FALSE;
        }
        $field .= $unescapedfield;

        while ((strlen($content) > ($index + 1)) && (substr($content, $index, 2) == '""')) {
            $field .= '"';
            $index += 2;
            
            $unescapedfield = '';
            if ($this->parse_csv_sub_field($content, $index, $unescapedfield) === FALSE) {
                return FALSE;
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
     * @return bool FALSE if error, length of field if ok; use get_error() to get error string
     */
    private function parse_csv_quoted_field(&$content, &$index, &$field) {
        // skip the opening double quote
        $index += 1;
        if ($this->parse_csv_escaped_field($content, $index, $field) === FALSE) {
            return FALSE;
        }

        // check for incorrect termination of quoted string
        if (strlen($content) <= $index || substr($content, $index, 1) !== "\"") {
            die("incorrect termination of quoted string:" . strlen($content) . " => " . $index . " => " . substr($content, $index) . " => " . $field);
            $this->_error = get_string('csvloaderror', 'error');
            return FALSE;
        }
        // move past the ending quote
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
     * @return bool FALSE if error, length of field if ok; use get_error() to get error string
     */
    private function parse_csv_simple_field(&$content, &$index, &$field) {
        // check for empty
        if ($this->parse_csv_is_end_of_raw_string($content, $index)) {
            $field = '';
            return strlen($field);
        }

        $field = $this->get_string_until($content, "\n\"" . $this->_csvdelimiter, $index);
        $index += strlen($field);

        // check for double quote (error state)
        if (substr($content, $index, 1) === "\"") {
            die("found quote in simple string");
            $this->_error = get_string('csvloaderror', 'error');
            return FALSE;
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
     * @return bool FALSE if error, length of field if ok; use get_error() to get error string
     */
    private function parse_csv_raw_string(&$content, &$index, &$field) {
        // check for empty
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
     * @return bool FALSE if error, count of fields if ok; use get_error() to get error string
     */
    private function parse_csv_string_list(&$content, &$index, &$fields) {
        $morestrings = TRUE;
        do {
            $rawstring = '';
            if ($this->parse_csv_raw_string($content, $index, $rawstring) === FALSE) {
                return FALSE;
            }
            $fields[] = $rawstring;    

            // move past the delimiter if more strings to parse
            if ((strlen($content) > $index) && (substr($content, $index, 1) === $this->_csvdelimiter)) {
                $index += 1;
            } else {
                $morestrings = FALSE;
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
     * @return bool FALSE if error, count of fields if ok; use get_error() to get error string
     */
    private function parse_csv_record(&$content, &$index, &$fields) {
        if ($this->parse_csv_string_list($content, $index, $fields) === FALSE) {
            return FALSE;
        }
        if (!strlen($content) <= $index) {
            if (substr($content, $index, 1) == "\n") {
                // move to next record
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
     * @return bool FALSE if error, count of data lines if ok; use get_error() to get error string
     */
    private function parse_csv_content(&$content) {
        $index = 0;
        $linenum = 0;
        if (strlen($content) < 1) {
            $this->_error = get_string('csvloaderror', 'error');
            return FALSE;
        }

        while ($index < strlen($content)) {
            $line = array();
            if ($this->parse_csv_record($content, $index, $line) === FALSE) {
                return FALSE;
            }
            // ignore blank lines
            if (count($line) > 1 || $line[0] != "") {
                if ($linenum == 0) {
                    if ($this->columns_read($line) === FALSE) {
                        return FALSE;
                    }
                } else {
                    if ($this->line_read($line) === FALSE) {
                        return FALSE;
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
            return FALSE;
        }
        if ($this->_columnvalidation) {
            $result = $this->_columnvalidation($columns);
            if ($result !== TRUE) {
                $this->_error = $result;
                return FALSE;
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
            // this is critical!!
            $this->_error = get_string('csvweirdcolumns', 'error');
            $this->cleanup();
            return FALSE;
        }
        fwrite($this->_fpout, rawurlencode(serialize($line))."\n");
    }

    /**
     * Parse this content
     *
     * @global object
     * @global object
     * @param string $content passed by ref for memory reasons, unset after return
     * @param string $encoding content encoding
     * @param string $delimiter_name separator (comma, semicolon, colon, cfg)
     * @param string $column_validation name of function for columns validation, must have one param $columns
     * @return bool FALSE if error, count of data lines if ok; use get_error() to get error string
     */
    function load_csv_content(&$content, $encoding, $delimitername, $columnvalidation=null) {
        global $USER, $CFG;

        $this->close();
        $this->_error = null;
        $this->_columnvalidation = $columnvalidation;

        $textlib = textlib_get_instance();

        $content = $textlib->convert($content, $encoding, 'utf-8');
        // remove Unicode BOM from first line
        $content = $textlib->trim_utf8_bom($content);
        // Fix mac/dos newlines
        $content = preg_replace('!\r\n?!', "\n", $content);

        // ok ready to begin, first look at CSV grammar
        //
        // csvRecord ::= csvStringList ("\n" | 'EOF')
        // csvStringList ::= rawString (',' rawString)*
        // rawString ::= simpleField | quotedField 
        // simpleField ::= (any char except \n, EOF, comma or double quote)+
        // quotedField ::= '"' escapedField '"'
        // escapedField ::= subField ('"' '"' subField)*
        // subField ::= (any char except double quote or EOF)*
        
        $this->_csvdelimiter = csv_import_reader::get_delimiter($delimitername);
        // do we need this?
        $this->_csvencode    = csv_import_reader::get_encoded_delimiter($delimitername);

        // open file for writing
        $filename = $CFG->dataroot.'/temp/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid;
        $this->_fpout = fopen($filename, "w");

        $numlines = $this->parse_csv_content($content);
        if ($numlines === FALSE) {
            $this->cleanup();
            return FALSE;
        }

        // close the export file
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

        $filename = $CFG->dataroot.'/temp/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid;
        if (!file_exists($filename)) {
            return FALSE;
        }
        $fp = fopen($filename, "r");
        $line = fgets($fp);
        fclose($fp);
        if ($line === FALSE) {
            return FALSE;
        }
        $this->_columns = unserialize(rawurldecode($line));
        return $this->_columns;
    }

    /**
     * Init iterator.
     *
     * @global object
     * @global object
     * @return bool Success
     */
    function init() {
        global $CFG, $USER;

        if (!empty($this->_fpin)) {
            $this->close();
        }
        $filename = $CFG->dataroot.'/temp/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid;
        if (!file_exists($filename)) {
            return FALSE;
        }
        if (!$this->_fpin = fopen($filename, "r")) {
            return FALSE;
        }
        //skip header
        return (fgets($this->_fpin) !== FALSE);
    }

    /**
     * Get next line
     *
     * @return mixed FALSE, or an array of values
     */
    function next() {
        if (empty($this->_fpin) or feof($this->_fpin)) {
            return FALSE;
        }
        if ($ser = fgets($this->_fpin)) {
            $r = unserialize(rawurldecode($ser));
            return $r;
        } else {
            return FALSE;
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
     * @global object
     * @global object
     * @param boolean $full TRUE means do a full cleanup - all sessions for current user, FALSE only the active iid
     */
    function cleanup($full=FALSE) {
        global $USER, $CFG;

        if ($full) {
            @remove_dir($CFG->dataroot.'/temp/csvimport/'.$this->_type.'/'.$USER->id);
        } else {
            @unlink($CFG->dataroot.'/temp/csvimport/'.$this->_type.'/'.$USER->id.'/'.$this->_iid);
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
        }
    }

    /**
     * Get encoded delimiter character
     *
     * @global object
     * @param string separator name
     * @return string encoded delimiter char
     */
    function get_encoded_delimiter($delimitername) {
        global $CFG;
        if ($delimitername == 'cfg' and isset($CFG->CSV_ENCODE)) {
            return $CFG->CSV_ENCODE;
        }
        $delimiter = csv_import_reader::get_delimiter($delimitername);
        return '&#'.ord($delimiter);
    }

    /**
     * Create new import id
     *
     * @global object
     * @param string who imports?
     * @return int iid
     */
    function get_new_iid($type) {
        global $USER;

        $filename = make_temp_directory('temp/csvimport/'.$type.'/'.$USER->id);

        // use current (non-conflicting) time stamp
        $iiid = time();
        while (file_exists($filename.'/'.$iiid)) {
            $iiid--;
        }

        return $iiid;
    }
}
