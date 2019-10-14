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
function do_api_search($string, $function)
{
    $config      = get_config('resourcespace');
    $url         = get_config('resourcespace', 'resourcespace_api_url');
    $private_key = get_config('resourcespace', 'api_key');
    $user        = get_config('resourcespace', 'api_user');
    // Set the private API key for the user (from the user account page) and the user we're accessing 

    // Formulate the query
    $query="user=" . $user . "&function=".$function."&param1=".$string."&param2=&param3=&param4=&param5=&param6=";

    // Sign the query using the private key
    $sign=hash("sha256",$private_key . $query);

    // Make the request and output the JSON results.
    // $results=json_decode(file_get_contents("https://resourcespace.lmta.lt/api/?" . $query . "&sign=" . $sign));
    // $results=json_decode(file_get_contents($url . $query . "&sign=" . $sign));
    // $results=file_get_contents($url . $query . "&sign=" . $sign);
    $results=json_decode(file_get_contents($url . $query . "&sign=" . $sign), TRUE);
    // print_r($results);
    
    $result = [];
    $result[0] = "https://resourcespace.lmta.lt/api/?" . $query . "&sign=" . $sign;
    $result[1] = $results;

    return $result;
}


/**
 * Initialise Resourcespace API variables
 */
// function init_resourcespace()
// {
//     $this->config          = get_config('resourcespace');
//     $this->resourcespace_api_url = get_config('resourcespace', 'resourcespace_api_url');
//     $this->api_key         = get_config('resourcespace', 'api_key');
//     $this->api_user        = get_config('resourcespace', 'api_user');
//     $this->enable_help     = get_config('resourcespace', 'enable_help');
//     $this->enable_help_url = get_config('resourcespace', 'enable_help_url');
// }


function get_metadata_from_api($resourcespace_id)
{
    global $PAGE, $DB, $CFG;
    $prefix = $CFG->prefix;

    $result = do_api_search($resourcespace_id, 'get_resource_field_data');


    file_print('METADATA:',TRUE);
    file_print(implode(",", $result[1][0]));
    file_print($result[1][0][0]);
    file_print($result[1][0]["ref"]);
    file_print($result[0]);
    $i = 0;
    foreach($result[1] as $row)
    {
        file_print('['.$i.'] = '.$row[0]["name"]);
        $i++;
    }
}


/** 
 * Get an array with the list of Poster Plugins instances whether in the current course or on the whole platform
 * @param array $data_array the empty array where all the data will be stored. The same array is returned. 
 * @param array $moduleinstance the instance of this plugin that contains metadata we use to decide how to build the db query.
 */
function get_poster_list_array($data_array, $course, $moduleinstance)
{
    global $PAGE, $DB, $CFG;
    $prefix = $CFG->prefix;


    // If flag is on, create a list about all posters in the platform
    // otherwise, only on the posters on the current course. If global, the course number
    // is not specified
    $query_poster_id  = "SELECT id, visible FROM ".$prefix."modules WHERE name = 'poster'";
    $result_poster_id = inter_mysql_query($query_poster_id , "select");
    $poster_id        = mysqli_fetch_array($result_poster_id)[0];

    if ($moduleinstance->platformwide === "0")
    {
        $data_array[0] = array ("Title", "Surtitle", "Composer", "List", "Language", "Content");
        $courseid = $PAGE->course->id;
        // $data          = $DB->get_records('poster', ['course'=>strval($courseid)], $sort='', $fields='*', $limitfrom=0, $limitnum=0);
        $query         = "SELECT id, name, surtitle, author, numbering, language, rs_id FROM ".$prefix."poster WHERE course = '".$courseid."'";
        $query_modules = "SELECT id, instance FROM ".$prefix."course_modules WHERE (course = '".$courseid."' AND module ='".$poster_id."' AND deletioninprogress ='0' )";
    }
    if ($moduleinstance->platformwide === "1")
    {
        $data_array[0] = array ("Title", "Surtitle", "Composer", "List", "Language", "Course", "Content");
        // $data          = $DB->get_records('poster', ['course'=>'6'] , $sort='', $fields='*', $limitfrom=0, $limitnum=0);
        $query         = "SELECT id, name, surtitle, author, numbering, language, rs_id FROM ".$prefix."poster";
        $query_modules = "SELECT id, instance, course FROM ".$prefix."course_modules WHERE (module ='".$poster_id."' AND deletioninprogress ='0' )";
    }

    //////////////////////////. NEW QUERY //////////////////////
    $result_poster = inter_mysql_query($query , "select");
    $posters_array = [];
    $posters_id    = [];
    $i = 0;
    while($row = mysqli_fetch_array($result_poster))
    {
        // TODO: HERE MAKE THE API CALL 
        // row[0] = id , row[1] = name ...
        $posters_array[$i] = array($row[1], $row[2], $row[3], $row[4], $row[5]);
        $posters_id   [$i] = $row[0];

        // $row[6] is the RS ID
        $metadata_array = get_metadata_from_api($row[6]);

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
                                    '<a href=\''.$CFG->wwwroot.'/mod/poster/view.php?id=' .$row[0]. '\'>Poster</a>');
        }
        $i = $i + 1;
    } 

    return $data_array;
}
// Create an HTML table from the data contained in the Poster of Intermusic
function inter_build_html_table($course, $moduleinstance, $the_big_array)
{ 
   
    // The nested arrays to hold all the arrays
    // $data_array    = [];
    // $the_big_array = []; 

    // // This line is to replace the csv data with the poster module data
    // $the_big_array = get_poster_list_array($data_array, $course, $moduleinstance);
    
    $datatables = 'https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css';
    $build = "<!DOCTYPE html>";
    $build .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$datatables."\" >";
    $build .= "<script src=\"https://code.jquery.com/jquery-3.3.1.js\"></script>";
    $build .= "<script src=\"https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js\"></script>";
    $build .= "<script src=\"sha256.js\"></script>";
    $build .= "<script src=\"colResizable-1.6.js\"></script>";
    $build .= "<script src=\"js_utilities.js\"></script>";
  
    ///////////////  TABLE //////////////////////////////////////////
    $build .= "<table class=\"display\" id=\"intermusic\" style=\"table-layout:fixed; width:80%\" ><thead><th>";

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


































