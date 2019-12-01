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

// For debugging purposes
require_once("$CFG->dirroot/mod/inter/io_print.php");

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
 * @param array $data_array is the data array to fill ---- DELETE
 * @param $courseid ID of the course where this list is, used to build mysql queries TODO: change that to native DB calls
 * @param $moduleinstance An instance of the current Inter list that contains information to refer in the API and DB calls
 * @return array $big_array The data coming back from either ResourceSpace or local moodle metadata. 
 */
function save_serialized_metadata($courseid, $moduleinstance, $id)
{
    global $DB, $CFG;
    $big_array  = []; 
    // $big_array  = get_poster_list_array($data_array, $courseid, $moduleinstance);
    $big_array  = get_poster_list_array($courseid, $moduleinstance, $id);
    $serialized_array = serialize($big_array);
    //Store in DB
    $DB->set_field('inter', 'serial_data', $serialized_array, array('id'=>$id));
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
 * Do an API requeuest with 
 */
// function do_api_search($string, $function)
// {
//     $config      = get_config('resourcespace');
//     $url         = get_config('resourcespace', 'resourcespace_api_url');
//     $private_key = get_config('resourcespace', 'api_key');
//     $user        = get_config('resourcespace', 'api_user');
//     // Set the private API key for the user (from the user account page) and the user we're accessing 

//     // Formulate the query
//     $query="user=" . $user . "&function=".$function."&param1=".$string."&param2=&param3=&param4=&param5=&param6=";

//     // Sign the query using the private key
//     $sign=hash("sha256",$private_key . $query);

//     // Make the request and output the JSON results.
//     $results=json_decode(file_get_contents($url . $query . "&sign=" . $sign), TRUE);
    
//     $result = [];
//     $result[0] = "https://resourcespace.lmta.lt/api/?" . $query . "&sign=" . $sign;
//     $result[1] = $results;

//     return $result;
// }


/**
 * Get the data via API call and compare its metadata with the one indicated in the current Inter list instance
 */
// function get_metadata_from_api($resourcespace_id, $moduleinstance, $list_metadata)
// {
//     global $PAGE, $DB, $CFG;
//     $prefix = $CFG->prefix;

//     $result = do_api_search($resourcespace_id, 'get_resource_field_data');

//     $new_list_metadata = [];
//     for($i = 0; $i <= sizeof($list_metadata); $i++)
//     {
//         foreach($result[1] as $row)
//         {
//             if ($row["title"] === $list_metadata[$i])
//             {
//                 $new_list_metadata[$i] = $row["value"];
//             }
//         }
//     } 
//     return $new_list_metadata;
// }


/** 
 * Get an array with the list of Poster Plugins instances whether in the current course or on the whole platform
 * @param array $data_array the empty array where all the data will be stored. The same array is returned. 
 * @param array $moduleinstance the instance of this plugin that contains metadata we use to decide how to build the db query.
 */
function get_poster_list_array($courseid, $moduleinstance)
{
    global $PAGE, $DB, $CFG;
    $prefix = $CFG->prefix;

    // This is the metadata we want to pull from Resourcespace, the user inputs the correct metadata fields
    // TODO: catch the case when metadatas are incorrect or there is no metadata whatsoever. 
    $list_metadata[0] = ($moduleinstance->meta1 != "" ? $moduleinstance->meta1 : "Composer");
    $list_metadata[1] = ($moduleinstance->meta2 != "" ? $moduleinstance->meta2 : "Title");
    $list_metadata[2] = ($moduleinstance->meta3 != "" ? $moduleinstance->meta3 : "Surtitle");
    $list_metadata[3] = ($moduleinstance->meta4 != "" ? $moduleinstance->meta4 : "List");
    $list_metadata[4] = ($moduleinstance->meta5 != "" ? $moduleinstance->meta5 : "1st Line");
    $list_metadata[5] = ($moduleinstance->meta6 != "" ? $moduleinstance->meta6 : "Language");

    // If flag is on, create a list about all posters in the platform
    // otherwise, only on the posters on the current course. If global, the course number
    // is not specified
    $query_poster_id  = "SELECT id, visible FROM ".$prefix."modules WHERE name = 'poster'";
    $result_poster_id = inter_mysql_query($query_poster_id , "select");
    $poster_id        = mysqli_fetch_array($result_poster_id)[0];

    $data_array = [];
    if ($moduleinstance->platformwide === "0")
    {
        $data_array[0] = array ($list_metadata[0], $list_metadata[1], $list_metadata[2], $list_metadata[3], $list_metadata[4], $list_metadata[5], "Content");
        $query         = "SELECT id, meta1, meta2, meta3, meta4, meta5, meta6, rs_id FROM ".$prefix."poster WHERE course = '".$courseid."'";
        $query_modules = "SELECT id, instance FROM ".$prefix."course_modules WHERE (course = '".$courseid."' AND module ='".$poster_id."' AND deletioninprogress ='0' AND visible = '1' )";
    }
    if ($moduleinstance->platformwide === "1")
    {
        $data_array[0] = array ($list_metadata[0], $list_metadata[1], $list_metadata[2], $list_metadata[3], $list_metadata[4], $list_metadata[5], "Course", "Content");
        $query         = "SELECT id, meta1, meta2, meta3, meta4, meta5, meta6, rs_id FROM ".$prefix."poster";
        $query_modules = "SELECT id, instance, course FROM ".$prefix."course_modules WHERE (module ='".$poster_id."' AND deletioninprogress ='0' AND visible = '1' )";
    }

    //////////////////////////. NEW QUERY //////////////////////
    $result_poster = inter_mysql_query($query , "select");
    $posters_array = [];
    $posters_id    = [];
    $i = 0;
    while($row = mysqli_fetch_array($result_poster))
    {
        // API CALL ,  $row[6] is the RS ID
        // $metadata_array = get_metadata_from_api($row[6], $moduleinstance, $list_metadata);

        // // row[0] = id , row[1] = name ...
        // $metadata_array[0] = ($metadata_array[0] != "" ? $metadata_array[0] : $row[1]); // Start from 1 because row has data from query where 0 is ID. 
        // $metadata_array[1] = ($metadata_array[1] != "" ? $metadata_array[1] : $row[2]); 
        // $metadata_array[2] = ($metadata_array[2] != "" ? $metadata_array[2] : $row[3]); 
        // $metadata_array[3] = ($metadata_array[3] != "" ? $metadata_array[3] : $row[4]); 
        // $metadata_array[4] = ($metadata_array[4] != "" ? $metadata_array[4] : $row[5]); 

        // $posters_array = $metadata_array;
        $posters_array[$i] = array($row[1], $row[2], $row[3], $row[4], $row[5], $row[6]);
        poster_print("ARRAY");
        poster_print($row[1]);
        // $posters_array[$i] = array("TEST", "TEST", "TEST", "TEST", "TEST", "TEST");

        // $posters_array[$i] = array($metadata_array[0], $metadata_array[1], $metadata_array[2], $metadata_array[3], $metadata_array[4]);
        // $posters_array_test[$i] = array($metadata_array[1], $metadata_array[2], $metadata_array[3], $metadata_array[4], $metadata_array[5]);
        
        // TODO: This should be in the same array as the posters_array, now its like this because of the array_search function
        // otherwise that function has to look in an array column. Have to research on that.
        $posters_id   [$i] = $row[0];

        $i = $i + 1;
    } 

    // Query for the module instances of poster an see which course they are
    $result_courses = inter_mysql_query($query_modules , "select");

    $i = 1;
    while($row = mysqli_fetch_array($result_courses))
    {
        $key = array_search($row[1], $posters_id); 

        if ($moduleinstance->platformwide === "1")
        {
            $query_shortname = "SELECT id, shortname language FROM ".$prefix."course WHERE id = '".$row[2]."'";
            $result_shortname = inter_mysql_query($query_shortname , "select");
            $shortname        = mysqli_fetch_array($result_shortname)[1];

            // TODO: HERE TO ADD METADATA INSTEAD OF THE POSTER ARRAY COMING FORM DB IN MOODLE
            $data_array[$i] = array($posters_array[$key][0] , 
                                    $posters_array[$key][1] , 
                                    $posters_array[$key][2] , 
                                    $posters_array[$key][3] , 
                                    $posters_array[$key][4] ,
                                    $posters_array[$key][5] ,
                                    '<a href=\''.$CFG->wwwroot.'/course/view.php?id=' .$row[2]. '\'>'.$shortname.'</a>',
                                    '<a href=\''.$CFG->wwwroot.'/mod/poster/view.php?id=' .$row[0]. '\'>Poster</a>');
        }
        else
        {
            $data_array[$i] = array($posters_array[$key][0] , 
                                    $posters_array[$key][1] , 
                                    $posters_array[$key][2] , 
                                    $posters_array[$key][3] , 
                                    $posters_array[$key][4] ,
                                    $posters_array[$key][5] ,
                                    '<a href=\''.$CFG->wwwroot.'/mod/poster/view.php?id=' .$row[0]. '\'>Poster</a>');
        }
        $i = $i + 1;
    } 

    return $data_array;
}
// Create an HTML table from the data contained in the Poster of Intermusic
function inter_build_html_table($course, $moduleinstance, $the_big_array)
{ 
    
    $datatables = 'https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css';
    $build = "<!DOCTYPE html>";
    $build .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$datatables."\" >";
    $build .= "<script src=\"https://code.jquery.com/jquery-3.3.1.js\"></script>";
    $build .= "<script src=\"https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js\"></script>";
    $build .= "<script src=\"sha256.js\"></script>";
    $build .= "<script src=\"colResizable-1.6.js\"></script>";
    $build .= "<script src=\"js_utilities.js\"></script>";
  
    ///////////////  TABLE //////////////////////////////////////////
    // $build .= "<table class=\"display\" id=\"intermusic\" style=\"table-layout:fixed; width:80%\" ><thead><th>";
    $build .= "<table class=\"display\" id=\"intermusic\" style=\"table-layout:fixed;\" ><thead><th>";

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

    ///////////////  JAVASCRIPT SCRIPTS /////////////////////////////
    $build .= "<script>
                $(document).ready(function() 
                {

                $('#intermusic').colResizable();
                $('#intermusic').DataTable( {
                    fixedHeader: true,
                    scrollY: '500px',
                    scrollCollapse: true,
                    autoWidth: true
                } );

                });
                </script>";

    // With JQuery 
    // $build .= "<script>
    //             $(document).ready(function() 
    //             {

    //                 var table_intermusic = document.getElementById('intermusic');
    //                 //$('#intermusic').colResizable();
    //                 //table_intermusic.colResizable();

    //             });
    //             </script>";


    $build .= "<script src=\"resize.js\"></script>";

    ///////////////  JAVASCRIPT SCRIPTS /////////////////////////////

    return $build;
}


































