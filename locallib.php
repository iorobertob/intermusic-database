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

// mb_detect_order('ASCII, UTF-8, ISO-8859-1');
// // tell the browser that we are using UTF-8
// header('content-type: text/plain; charset=UTF-8');

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
function local_test_locallib_function($event) 
{
    return;
}

/**
 * Get metadata either with an API call or from local moodle modules metadata, serialise it and commit it to DB
 *
 * @param $courseid ID of the course where this list is, used to build mysql queries TODO: change that to native DB calls
 * @param $moduleinstance An instance of the current Inter list that contains information to refer in the API and DB calls
 * @return array $big_array The data coming back from either ResourceSpace or local moodle metadata. 
 */
function save_serialized_metadata($courseid, $moduleinstance, $id)
{
    global $DB, $CFG;

    $big_array  = []; 
    $big_array  = get_mposter_list_array($courseid, $moduleinstance, $id);
    $serialized_array = serialize($big_array);

    //Store in DB
    // $serialized_array = utf8_encode($serialized_array);
    $DB->set_field('inter', 'serial_data', $serialized_array, array('id'=>$id));
    
}

/**
 * Custom INTERMUSIC function - execute an arbitrary mysql query 
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

    // Charset handling, make sure we are using utf8
    /* change character set to utf8 */
    if (!$conn->set_charset("utf8")) {
        //Error loading character set utf8
        exit();
    } 

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


/** 
 * Get an array with the list of Poster Plugins instances whether in the current course or on the whole platform
 * @param array $data_array the empty array where all the data will be stored. The same array is returned. 
 * @param array $moduleinstance the instance of this plugin that contains metadata we use to decide how to build the db query.
 */
function get_mposter_list_array($courseid, $moduleinstance)
{
    global $PAGE, $DB, $CFG;
    $prefix = $CFG->prefix;

    // This is the metadata we want to pull from Resourcespace, the user inputs the correct metadata fields
    $list_metadata[0] = $moduleinstance->meta1;
    $list_metadata[1] = $moduleinstance->meta2;
    $list_metadata[2] = $moduleinstance->meta3;
    $list_metadata[3] = $moduleinstance->meta4;
    $list_metadata[4] = $moduleinstance->meta5;
    $list_metadata[5] = $moduleinstance->meta6;
    $list_metadata[6] = $moduleinstance->meta7;

    // If flag is on, create a list about all mposters in the platform
    // otherwise, only on the mposters on the current course. If global, the course number
    // is not specified
    $query_mposter_id  = "SELECT id, visible FROM ".$prefix."modules WHERE name = 'mposter'";
    $result_mposter_id = inter_mysql_query($query_mposter_id , "select");
    $mposter_id        = mysqli_fetch_array($result_mposter_id)[0];

    $data_array = [];
    if ($moduleinstance->platformwide === "0")
    {
        $data_array[0] = array ($list_metadata[0], $list_metadata[1], $list_metadata[2], $list_metadata[3], $list_metadata[4], $list_metadata[5], $list_metadata[6], "Content");
        $query         = "SELECT id, meta_value1, meta_value2, meta_value3, meta_value4, meta_value5, meta_value6, meta_value7, rs_id FROM ".$prefix."mposter WHERE course = '".$courseid."'";
        $query_modules = "SELECT id, instance FROM ".$prefix."course_modules WHERE (course = '".$courseid."' AND module ='".$mposter_id."' AND deletioninprogress ='0' AND visible = '1' )";
    }
    if ($moduleinstance->platformwide === "1")
    {
        $data_array[0] = array ($list_metadata[0], $list_metadata[1], $list_metadata[2], $list_metadata[3], $list_metadata[4], $list_metadata[5], $list_metadata[6], "Content", "Course");
        $query         = "SELECT id, meta_value1, meta_value2, meta_value3, meta_value4, meta_value5, meta_value6, meta_value7, rs_id FROM ".$prefix."mposter";
        $query_modules = "SELECT id, instance, course FROM ".$prefix."course_modules WHERE (module ='".$mposter_id."' AND deletioninprogress ='0' AND visible = '1' )";
    }

    //////////////////////////. NEW QUERY //////////////////////
    $result_mposter = inter_mysql_query($query , "select");
    $mposters_array = [];
    $mposters_id    = [];
    $i = 0;
    while($row = mysqli_fetch_array($result_mposter))
    {
        $mposters_array[$i] = array($row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7]);
        $mposters_id   [$i] = $row[0];
        $i = $i + 1;
    } 

    // Query for the module instances of mposter an see which course they are
    $result_courses = inter_mysql_query($query_modules , "select");

    $i = 1;
    while($row = mysqli_fetch_array($result_courses))
    {
        $key = array_search($row[1], $mposters_id); 

        if ($moduleinstance->platformwide === "1")
        {
            $query_shortname = "SELECT id, shortname language FROM ".$prefix."course WHERE id = '".$row[2]."'";
            $result_shortname = inter_mysql_query ($query_shortname , "select");
            $shortname        = mysqli_fetch_array($result_shortname) [1];

            $data_array[$i] = array($mposters_array[$key][0] , 
                                    $mposters_array[$key][1] , 
                                    $mposters_array[$key][2] , 
                                    $mposters_array[$key][3] , 
                                    $mposters_array[$key][4] ,
                                    $mposters_array[$key][5] ,
                                    $mposters_array[$key][6] ,
                                    '<a href=\''.$CFG->wwwroot.'/mod/mposter/view.php?id=' .$row[0]. ' \'>Open</a>',
                                    '<a href=\''.$CFG->wwwroot.'/course/view.php?id=' .$row[2]. '\'>'. $shortname .'</a>');
        }
        else
        {
            $data_array[$i] = array($mposters_array[$key][0] , 
                                    $mposters_array[$key][1] , 
                                    $mposters_array[$key][2] , 
                                    $mposters_array[$key][3] , 
                                    $mposters_array[$key][4] ,
                                    $mposters_array[$key][5] ,
                                    $mposters_array[$key][6],
                                    '<a href=\''.$CFG->wwwroot.'/mod/mposter/view.php?id=' .$row[0]. ' \'>Open</a>');
        }
        $i = $i + 1;
    } 

    return $data_array;
}
// Create an HTML table from the data contained in the Poster of Intermusic
function inter_build_html_table($course, $moduleinstance, $the_big_array)
{ 
    
    $datatables = 'https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css';
    $datatables_responsive = 'https://cdn.datatables.net/responsive/2.2.3/css/responsive.dataTables.min.css';
    $build = "<!DOCTYPE html>";
    $build .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$datatables."\" >";
    $build .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$datatables_responsive."\" >";
    $build .= "<script src=\"https://code.jquery.com/jquery-3.3.1.js\"></script>";
    $build .= "<script src=\"https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js\"></script>";
    $build .= "<script src=\"https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js\"></script>";
    $build .= "<script src=\"colResizable-1.6.js\"></script>";
    $build .= "<script src=\"js_utilities.js\"></script>";
  
    ///////////////  TABLE //////////////////////////////////////////
    $build .= "<table class=\"display  dataTable collapsed dtr-inline\" id=\"the_table\"  ><thead><th>";
    // $build .= "<table class=\"display nowrap dataTable collapsed dtr-inline\" id=\"the_table\"  ><thead><th>";

    for( $i = 0; $i<sizeof($the_big_array[0])-1; $i++ )
    {
        $build .= $the_big_array[0][$i].'</th><th>';  
    }
    $build .= $the_big_array[0][sizeof($the_big_array[0])-1].'</th></thead><tbody>';
    
    for ( $i = 1; $i < sizeof($the_big_array); $i++)
    {
        $row = $the_big_array[$i];
        $build .= '<tr>';

        for ( $j = 0; $j < sizeof($row); $j++)
        {
            $item = $row[$j];
            // If there is an URL in the data
            if (filter_var($item, FILTER_VALIDATE_URL)) { 
                // make a button
                $build .= "<td><a href=\"{$item}\"><button>Go...</button></a></td>";
            }
            // Any data, not an URL
            else{
                $build .= "<td>{$item}</td>";
            }
        }
        $build .= '</tr>';
    }
    $build .= '</tbody></table>';
    ///////////////  TABLE //////////////////////////////////////////

    ///////////////  JAVASCRIPT  /////////////////////////////
    $build .= "<script>
                $(document).ready(function() 
                {

                // $('#the_table').colResizable();
                $('#the_table')
                    // .addClass( 'nowrap' )
                    .DataTable( {
                    fixedHeader: true,
                    scrollY: '500px',
                    // scrollCollapse: true,
                    // autoWidth: true,
                    responsive: true
                } );

                });
                </script>";
    ///////////////  JAVASCRIPT  /////////////////////////////

    return $build;
}


































