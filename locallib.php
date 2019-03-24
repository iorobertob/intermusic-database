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
 * Plugin internal classes, functions and constants are defined here.
 *
 * @package     mod_inter
 * @copyright   2019 LMTA <roberto.becerra@lmta.lt>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/inter/lib.php");

/**
 * Handle the \core\event\something_else_happened event.
 *
 * @param object $event The event object.
 */
function local_test_locallib_function($event) {
    return;
}

function inter_set_mainfile($data) {
    global $DB;
    $fs = get_file_storage();
    $cmid = $data->coursemodule;
    $draftitemid = $data->files;

    $context = context_module::instance($cmid);
    if ($draftitemid) {
        $options = array('subdirs' => true, 'embed' => false);
        if ($data->display == RESOURCELIB_DISPLAY_EMBED) {
            $options['embed'] = true;
        }
        file_save_draft_area_files($draftitemid, $context->id, 'mod_inter', 'content', 0, $options);
    }
    $files = $fs->get_area_files($context->id, 'mod_inter', 'content', 0, 'sortorder', false);
    if (count($files) == 1) {
        // only one file attached, set it as main file automatically
        $file = reset($files);
        file_set_sortorder($context->id, 'mod_inter', 'content', 0, $file->get_filepath(), $file->get_filename(), 1);

	}

    $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false);
    return $url;
}


/**
 * Display embedded moduleinstance file.
 * @param object $moduleinstance module instance 
 * @param object $cm
 * @param object $course
 * @param stored_file $file main file
 * @return does not return
 */
function inter_display_embed($moduleinstance, $cm, $course, $file) {
    global $CFG, $PAGE, $OUTPUT;

    $clicktoopen = inter_get_clicktoopen($file, $moduleinstance->revision);

    $context = context_module::instance($cm->id);
    $path = '/'.$context->id.'/mod_inter/content/'.$moduleinstance->revision.$file->get_filepath().$file->get_filename();
    $fullurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);
    $moodleurl = new moodle_url('/pluginfile.php' . $path);

    $mimetype = $file->get_mimetype();
    $title    = $moduleinstance->name;

    $extension = resourcelib_get_extension($file->get_filename());

    $mediamanager = core_media_manager::instance($PAGE);
    $embedoptions = array(
        core_media_manager::OPTION_TRUSTED => true,
        core_media_manager::OPTION_BLOCK => true,
    );

    if (file_mimetype_in_typegroup($mimetype, 'web_image')) {  // It's an image
        $code = resourcelib_embed_image($fullurl, $title);

    } else if ($mimetype === 'application/pdf') {
        // PDF document
        $code = resourcelib_embed_pdf($fullurl, $title, $clicktoopen);

    } else if ($mediamanager->can_embed_url($moodleurl, $embedoptions)) {
        // Media (audio/video) file.
        $code = $mediamanager->embed_url($moodleurl, $title, 0, 0, $embedoptions);

    } else {
        // We need a way to discover if we are loading remote docs inside an iframe.
        $moodleurl->param('embed', 1);

        // anything else - just try object tag enlarged as much as possible
        $code = resourcelib_embed_general($moodleurl, $title, $clicktoopen, $mimetype);
    }

    // resource_print_header($moduleinstance, $cm, $course);
    // resource_print_heading($moduleinstance, $cm, $course);

    echo $code;

    // resource_print_intro($moduleinstance, $cm, $course);

    echo $OUTPUT->footer();
    die;
}


/**
 * Internal function - create click to open text with link.
 */
function inter_get_clicktoopen($file, $revision, $extra='') {
    global $CFG;

    $filename = $file->get_filename();

    $path = '/'.$file->get_contextid().'/mod_inter/content/'.$revision.$file->get_filepath().$file->get_filename();

    $fullurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);

    $string = get_string('clicktoopen2', 'inter', "<a href=\"$fullurl\" $extra>$filename</a>");

    return $string;
}

/**
 * File browsing support class
 */
class inter_content_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}



/**
 * Custom LMTA function - execute an arbitrary mysql query 
 */
function inter_mysql_query($sql, $process)
{
	global $CFG;
	
	$servername = $CFG->dbhost;
	$username   = $CFG->dbuser;
	$password   = $CFG->dbpass;
	$dbname     = $CFG->dbname;

	// checking connection
	$conn = new mysqli($servername, $username, $password, $dbname);
	// Check connection
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}

	if ($conn->query($sql) === TRUE) {
	    echo "Successfull query: ".$process;
	    $conn->close();
	    // return "Table  created successfully";
        echo("<script>console.log('Successfull query ');</script>");
        return true;
	} else {
	    echo "Error in Query: : ".$process." " . $conn->error;
	    $conn->close();
	    // return "Error creating table: " . $conn->error;
        echo("<script>console.log('Error in Query: " . $conn->error."');</script>");
        return false;
	    // die;
	}

}

/**
 * Custom LMTA function - Create a databse from a csv file using the module's instance's id. 
 */
function inter_create_database_from_csv($file_url, $id)
{
    // Detect line breaks, otherwise fgetcsv will return all rows
    ini_set('auto_detect_line_endings', true);


    // The nested array to hold all the arrays
    $the_big_array = []; 

    // Open the file for reading
    if (($h = fopen("{$file_url}", "r")) !== FALSE) 
    {
        // The first line in the file is converted into an individual array that we call $data
        // The items of the array are comma separated
        $data = fgetcsv($h, 10000, ",");

      // Close the file
      fclose($h);
    }

    return build_table($data, $id, $file_url);

}

/**
 * Custom LMTA function - Create a table with column titles contained in $data and $id to be integrated in its name
 */
function build_table($data, $id, $file_url)
{

    $tablename = "inter_database_".$id;
    $query = "CREATE TABLE inter_database_.$id. (id INT NOT NULL AUTO_INCREMENT, ";
    $query = "CREATE TABLE ".$tablename." (id INT NOT NULL AUTO_INCREMENT, ";

    for( $i = 1; $i<sizeof($data); $i++ ) {
        $query .= "`".$data[$i]."` VARCHAR(255) NOT NULL, ";
    }
    $query .= "PRIMARY KEY (id));";
    echo("<script>console.log('CREATE TABLE:  ".$query."');</script>");

    if (inter_mysql_query($query, "Create table"))
    {
        // Fill table
        $result = fill_data_from_csv($file_url, $tablename, $data);
    }

    if ($result)
    {
        return true;
    }
    else
    {
        return false;
    }


}


/**
 * Custom LMTA function - Fill table with csv cata
 * param $file_url  -  the path to the file to get data from 
 * param $tablename - previously built table name with the unique id number of this module's intance
 * param $data      - array containig the list of the headers of this file
 */
function fill_data_from_csv($file_url, $tablename, $data)
{
    // $query = "LOAD DATA LOCAL INFILE '".$file_url."' INTO TABLE ".$tablename." FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 1 ROWS (id, first_name, last_name, email, transactions, @account_creation)SET account_creation  = STR_TO_DATE(@account_creation, '%m/%d/%y');";

    $query = "LOAD DATA LOCAL INFILE '".$file_url."' INTO TABLE ".$tablename." FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n' IGNORE 1 ROWS (id, ";
    $query = "LOAD DATA LOCAL INFILE '".$file_url."' INTO TABLE ".$tablename." FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\\r' IGNORE 1 ROWS;";

    // for( $i = 1; $i<sizeof($data); $i++ ) {
    //     $query .= "`".$data[$i]."`, ";
    // }


    // $query.= ");";
    //echo("<script>console.log('FILL TABLE:  ".$query."');</script>");
    return inter_mysql_query($query, "Fill table ");

}


function inter_build_html_table($file_url)
{
    // Detect line breaks, otherwise fgetcsv will return all rows
    ini_set('auto_detect_line_endings', true);


    // The nested array to hold all the arrays
    $the_big_array = []; 

    // Open the file for reading
    if (($h = fopen("{$file_url}", "r")) !== FALSE) 
    {
        // Each line in the file is converted into an individual array that we call $data
        // The items of the array are comma separated
        while (($data = fgetcsv($h, 1000, ",")) !== FALSE) 
        {
            // Each individual array is being pushed into the nested array
            $the_big_array[] = $data;       
        }

      // Close the file
      fclose($h);
    }
    
    $datatables = 'https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css';
    $build = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$datatables."\" >";
    $build .= "<script src=\"https://code.jquery.com/jquery-3.3.1.js\"></script>";
    $build .= "<script src=\"https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js\"></script>";
    // $build = '//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css'
    // $build = '<table><thead><th>item 1</th><th>item 2</th><th>item 3</th></thead><tbody>';

    $build .= "<table id=\"intermusic\"><thead><th>";

    for( $i = 0; $i<sizeof($the_big_array[0])-1; $i++ )
    {
        $build .= $the_big_array[0][$i].'</th><th>';  
    }
    $build .= $the_big_array[0][sizeof($the_big_array[0])-1].'</th></thead><tbody>';
    // item 1</th><th>item 2</th><th>item 3</th></thead><tbody>';
    
    foreach($the_big_array as $row)
    {
        $build .= '<tr>';
        foreach($row as $item)
        {
            $build .= "<td>{$item}</td>";
        }
        $build .= '</tr>';
    }
    
    $build .= '</tbody></table>';

    $build .= "<script>
                $(document).ready(function() 
                {
                $('#intermusic').DataTable();
                } );
                </script>";

    return $build;
}


































