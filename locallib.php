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


// TODO: Safe Delete, not used in List 
// function inter_set_mainfile($data) {
//     global $DB;
//     $fs = get_file_storage();
//     $cmid = $data->coursemodule;
//     $draftitemid = $data->files;

//     $context = context_module::instance($cmid);
//     if ($draftitemid) 
//     {
//         $options = array('subdirs' => true, 'embed' => false);
//         if ($data->display == RESOURCELIB_DISPLAY_EMBED) 
//         {
//             $options['embed'] = true;
//         }
//         file_save_draft_area_files($draftitemid, $context->id, 'mod_inter', 'content', 0, $options);
//     }
//     $files = $fs->get_area_files($context->id, 'mod_inter', 'content', 0, 'sortorder', false);
//     if (count($files) == 1) 
//     {
//         // only one file attached, set it as main file automatically
//         $file = reset($files);
//         file_set_sortorder($context->id, 'mod_inter', 'content', 0, $file->get_filepath(), $file->get_filename(), 1);

//         $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false);
// 	}
//     else
//     {
//         $url = "no file";
//     }
    
//     return $url;
// }


/**
 * File browsing support class
 */
// class inter_content_file_info extends file_info_stored {
//     public function get_parent() {
//         if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
//             return $this->browser->get_file_info($this->context);
//         }
//         return parent::get_parent();
//     }
//     public function get_visible_name() {
//         if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
//             return $this->topvisiblename;
//         }
//         return parent::get_visible_name();
//     }
// }


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

    $result = null;

    if ($process != 'select')
    {
        if ($conn->query($sql) === TRUE) {
            $conn->close();
            return true;
        } else {
            $conn->close();
            return false;
        }
    }
    else
    {
        if ($result = $conn->query($sql) ) {
            $conn->close();
            return $result;
        } else {
            $conn->close();
            return false;
        }
    }
}


// Create an HTML table from the data contained in the Poster of Intermusic
function inter_build_html_table($course, $moduleinstance)
{

    // TODO to arrange the tables prefix, now it is hardcoded. 
    global $PAGE, $DB;

    // If flag is on, create a list about all posters in the platform
    // otherwise, only on the posters on the current course. If global, the course number
    // is not specified
    if ($moduleinstance->platformwide === "0")
    {
        $courseid = $PAGE->course->id;
        $data = $DB->get_records('poster', ['course'=>strval($courseid)], $sort='', $fields='*', $limitfrom=0, $limitnum=0);
        // module 23 is poster
        $query  = "SELECT id, name FROM mdlwj_poster WHERE course = '".$courseid."'";
        // echo "<script>console.log('".$query."');</script>";
        $query_modules = "SELECT id, instance FROM mdlwj_course_modules WHERE (course = '".$courseid."' AND module ='23' AND deletioninprogress ='0' )";
        echo "<script>console.log('".$query_modules."');</script>";
    }
    if ($moduleinstance->platformwide === "1")
    {
        $data = $DB->get_records('poster', ['course'=>'6'] , $sort='', $fields='*', $limitfrom=0, $limitnum=0);
        // module 23 is poster
        $query  = "SELECT id, name FROM mdlwj_poster";
        echo "<script>console.log('".$query."');</script>";
        $query_modules = "SELECT id, instance FROM mdlwj_course_modules WHERE (module ='23' AND deletioninprogress ='0' )";
        echo "<script>console.log('".$query_modules."');</script>";
    }

    //////////////////////////. NEW QUERY //////////////////////
    

    // $data = $DB->get_record('poster', ['course' => '23']);
    // FIGURE OUT HOW TO GET THE COURSE ID 
    // $courseid = $PAGE->course->id;
    // $data = $DB->get_records('poster', ['course'=>'49'], $sort='', $fields='*', $limitfrom=0, $limitnum=0);

    // $query  = "SELECT id, name FROM mdl_poster WHERE course = '49'";
    $result_poster = inter_mysql_query($query , "select");
    $posters_array = [];
    $posters_id = [];
    $i = 0;
    while($row = mysqli_fetch_array($result_poster))
    {
        // row[0] = id , row[1] = name 
        $posters_array[$i] = $row[1];
        $posters_id   [$i] = $row[0];
        $i = $i + 1;
    } 

    // course 49 is Mastering Vocal Literature  and module 32 is posters
    // $query = "SELECT id, instance FROM mdl_course_modules WHERE (course = '49' AND module ='32' AND deletioninprogress ='0' )";
    $result_courses = inter_mysql_query($query_modules , "select");
    
   
    $i = 1;
    $data_array = [];

    

    // $data_array[0] = array ("PIECE", "CONTENT");
    while($row = mysqli_fetch_array($result_courses))
    {
        // print_r($row);
        $key = array_search($row[1], $posters_id); 
        $data_array[$i] = array($posters_array[$key] , '<a href=\'https://intermusic.lmta.lt/mod/poster/view.php?id=' .$row[0]. '\'>Poster</a>');
        $i = $i + 1;
    } 
    
    $length = sizeof($posters_array);

    // The nested array to hold all the arrays
    $the_big_array = []; 

    // This line is to replace the csv data with the poster module data
    $the_big_array = $data_array;
    
    $datatables = 'https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css';
    $build = "<!DOCTYPE html>";
    $build .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$datatables."\" >";
    $build .= "<script src=\"https://code.jquery.com/jquery-3.3.1.js\"></script>";
    $build .= "<script src=\"https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js\"></script>";
    $build .= "<script src=\"sha256.js\"></script>";
    
    $build .= "<script src=\"colResizable-1.6.js\"></script>";

    $build .= "<script src=\"js_utilities.js\"></script>";
    // $build = '//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css'
    // $build = '<table><thead><th>item 1</th><th>item 2</th><th>item 3</th></thead><tbody>';

    ////////////////// SEARCH BUTTON /////////////////////////////////
    $build .= '<div class="topnav">
                    <input id="search" type="text" placeholder="Search.." name="search">
                    <button type="submit" onclick="submitMe(\'search\')" ><i class="fa fa-search"></i></button>
                </div><br><br><br>';
    ///////////////// SEARCH BUTTON /////////////////////////////////


    ///////////////  TABLE //////////////////////////////////////////
    $build .= "<table class=\"display\" id=\"intermusic\" style=\"table-layout:fixed; width:80%\" ><thead><th>";

    for( $i = 0; $i<sizeof($the_big_array[0])-1; $i++ )
    {
        $build .= $the_big_array[0][$i].'</th><th>';  
    }
    $build .= $the_big_array[0][sizeof($the_big_array[0])-1].'</th></thead><tbody>';
    // item 1</th><th>item 2</th><th>item 3</th></thead><tbody>';
    
    for ( $i = 1; $i < sizeof($the_big_array); $i++)
    // foreach($the_big_array as $row)
    {
        $row = $the_big_array[$i];
        $build .= '<tr>';

        for ( $j = 0; $j < sizeof($row); $j++)
        {
            $item = $row[$j];
            // If there is an URL in the data
            if (filter_var($item, FILTER_VALIDATE_URL)) { 
                // make a button
                //$item = $row[$j];
                $build .= "<td><a href=\"{$item}\"><button>Go...</button></a></td>";
            }
            // Any data, not an URL
            else{
                //$item = $row[$j];
                $build .= "<td>{$item}</td>";
            }
        }
        $build .= '</tr>';
    }
    $build .= '</tbody></table>';
    ///////////////  TABLE //////////////////////////////////////////


    ///////////////  JAVASCRIPT SCRIPTS /////////////////////////////
    $build .= "<script>
                $(document).ready(function() 
                {

                $('#intermusic').colResizable();
                $('#intermusic').DataTable();

                });
                </script>";

    // With JQuery 
    $build .= "<script>
                $(document).ready(function() 
                {

                    var table_intermusic = document.getElementById('intermusic');
                    //$('#intermusic').colResizable();
                    //table_intermusic.colResizable();

                });
                </script>";


    $build .= "<script src=\"resize.js\"></script>";

    $build .= '<script>
                    function submitMe(id) {
                        var value = document.getElementById(id).value;
                        

                        var query = "user='.$api_user.'&function=search_public_collections&param1="+value

                        var sha256 = new jsSHA(\'SHA-256\', \'TEXT\');
                        sha256.update("'.$api_key.'" + query);
                        var hash = sha256.getHash("HEX");

                        var request_url = "'.$resourcespace_api_url.'" + query + "&sign=" + hash;
                        console.log(request_url);
                        alert(request_url);
                    }

                    String.prototype.hashCode = function() {
                        var hash = 0;
                        if (this.length == 0) {
                            return hash;
                        }
                        for (var i = 0; i < this.length; i++) {
                            var char = this.charCodeAt(i);
                            hash = ((hash<<5)-hash)+char;
                            hash = hash & hash; // Convert to 32bit integer
                        }
                        return hash;
                    }

                </script>';
    ///////////////  JAVASCRIPT SCRIPTS /////////////////////////////

    return $build;
}


































