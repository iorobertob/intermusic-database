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

    $result = null;

    if ($process != 'select')
    {
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
    else
    {
        if ($result = $conn->query($sql) ) {
            echo "Successfull query: ".$process;
            $conn->close();
            // return "Table  created successfully";
            echo("<script>console.log('Successfull query ');</script>");
            return $result;
        } else {
            echo "Error in Query: : ".$process." " . $conn->error;
            $conn->close();
            // return "Error creating table: " . $conn->error;
            echo("<script>console.log('Error in Query: " . $conn->error."');</script>");
            return false;
            // die;
        }
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


function inter_build_html_table($file_url, $course)
{
    global $PAGE, $DB;


    //////////////////////////. NEW QUERY //////////////////////
    $courseid = $PAGE->course->id;
    echo "<script> console.log(".$courseid.");</script>";
    $modinfo = get_fast_modinfo($courseid);

    // $data = $DB->get_record('poster', ['course' => '23']);
    $data = $DB->get_records('poster', ['course'=>'49'], $sort='', $fields='*', $limitfrom=0, $limitnum=0);

    
    $query  = "SELECT id, name FROM mdl_poster WHERE course = '49'";
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
    $query = "SELECT id, instance FROM mdl_course_modules WHERE (course = '49' AND module ='32' AND deletioninprogress ='0' )";
    $result_courses = inter_mysql_query($query , "select");
    
   
    $i = 1;
    $data_array = [];
    $data_array[0] = array ("PIECE", "CONTENT");
    while($row = mysqli_fetch_array($result_courses))
    {
        // echo "<script> console.log('RESULT: ' + '".$row[0]."');</script>";
        // print_r($row);
        $key = array_search($row[1], $posters_id); 
        $data_array[$i] = array($posters_array[$key] , '<a href=\'https://intermusic.lmta.lt/mod/poster/view.php?id=' .$row[0]. '\'>Poster</a>');
        $i = $i + 1;
    } 

    // $length = count($result_courses);
    // for($i = 0; $i < $length; $i++)
    // {   
    //     $row = mysqli_fetch_array($result_courses);
    //     //[0]->[name] [instance]
    //     //[1]->[name] [instance]...
    //     // $data_array[i] = array( $result_poster[$result_courses[i][1]][1], $result_courses[i][0]);

    //     $key = array_search($row[1], $posters_id); 

    //     $data_array[i] = array($posters_array[$key] , $row[0]);
    //     echo "<script> console.log('DATA ARRAY[i] : ' + '".$data_array[i][0]."');</script>";

    // }

    // echo "<script> console.log('DATA ARRAY : ' + '".$data_array."');</script>";
    
    $length = sizeof($posters_array);

    // $this->config = get_config('resourcespace');
    $resourcespace_api_url = 'https://resourcespace.lmta.lt/api/?';
    $api_key  = '9885aec8ea7eb2fb8ee45ff110773a5041030a7bdf7abb761c9e682de7f03045';
    $api_user = 'admin';
    // $this->enable_help = get_config('resourcespace', 'enable_help');
    // $this->enable_help_url = get_config('resourcespace', 'enable_help_url');
    //////////////////////////. NEW QUERY //////////////////////    

    // Detect line breaks, otherwise fgetcsv will return all rows
    ini_set('auto_detect_line_endings', true);
    header('Content-Type: text/html; charset=utf-8');

    // The nested array to hold all the arrays
    $the_big_array = []; 

    // Open the file for reading
    // if (($h = fopen("{$file_url}", "r")) !== FALSE) 
    if (($h = fopen($file_url, "r")) !== FALSE) 
    {
        // Each line in the file is converted into an individual array that we call $data
        // The items of the array are comma separated
        while (($data = fgetcsv($h, 1000, ";")) !== FALSE) 
        {
            // Each individual array is being pushed into the nested array
            $the_big_array[] = $data;       
        }

      // Close the file
      fclose($h);
    }
    echo '<pre>'; print_r($data); echo '</pre>';
    echo "<script> console.log(".$data.");</script>";
    echo "<script> console.log('".$file_url."');</script>";

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


        ///////////////////
        // foreach($row as $item)
        // {
        //     $build .= "<td>{$item}</td>";
        // }
        // $col0 = $row[0];
        // $col1 = $row[1];
        // $col2 = $row[2];
        // $build .= "<td>{$col0}</td><td>$col1</td><td><a href=\"{$col2}\">Go...</a></td>";

        // for ( $j = 3; $j < sizeof($row); $j++)
        // {
        //     $item = $row[$j];
        //     $build .= "<td>{$item}</td>";
        // }



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


        ///////////////////
        $build .= '</tr>';
    }
    
    $build .= '</tbody></table>';


    $build .= "<script>
                $(document).ready(function() 
                {

                $('#intermusic').colResizable();
                $('#intermusic').DataTable();

                });
                </script>";


    // $build .= "<script>
    //             $(document).ready(function() 
    //             {

    //             $('#intermusic').DataTable({
    //                 'autoWidth': true
    //                 });

    //             });
    //             </script>";

    // $build .= "<script>
    //             $(document).ready(function() 
    //             {

    //             $('#intermusic').DataTable({
    //                 'autoWidth': false
    //                 });

    //             $('#intermusic').colResizable();

    //             });
    //             </script>";

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

    // $build .=  "<script>  
    //                 var tables = document.getElementsByTagName('table');
    //                 for (var i=0; i<tables.length;i++){
    //                  resizableGrid(tables[i]);
    //                 }

    //                 function resizableGrid(table) {
    //                      var row = table.getElementsByTagName('tr')[0],
    //                      cols = row ? row.children : undefined;
    //                      if (!cols) return;
                         
    //                      table.style.overflow = 'hidden';
                         
    //                      var tableHeight = table.offsetHeight;
                         
    //                      for (var i=0;i<cols.length;i++){
    //                       var div = createDiv(tableHeight);
    //                       cols[i].appendChild(div);
    //                       cols[i].style.position = 'relative';
    //                       setListeners(div);
    //                      }

    //                      function setListeners(div){
    //                       var pageX,curCol,nxtCol,curColWidth,nxtColWidth;

    //                       div.addEventListener('mousedown', function (e) {
    //                        curCol = e.target.parentElement;
    //                        nxtCol = curCol.nextElementSibling;

    //                        pageX = e.pageX; 
                         
    //                        var padding = paddingDiff(curCol);
                         
    //                        curColWidth = curCol.offsetWidth - padding;
    //                        if (nxtCol)
    //                         nxtColWidth = nxtCol.offsetWidth - padding;
    //                       });

    //                       div.addEventListener('mouseover', function (e) {
    //                        e.target.style.borderRight = '2px solid #101010';
    //                       })

    //                       div.addEventListener('mouseout', function (e) {
    //                        e.target.style.borderRight = '';
    //                       })

    //                       document.addEventListener('mousemove', function (e) {
    //                            if (curCol) {
    //                             var diffX = e.pageX - pageX;
    //                             console.log(curCol);
    //                             console.log(nxtCol);
    //                             console.log('moving');
                             
    //                             if (nxtCol){
    //                             nxtCol.style.width = (nxtColWidth - (diffX))+'px';
    //                             //nxtCol.setAttribute('style','width:'+(nxtColWidth - (diffX))+'px');
    //                              console.log('moved' + diffX);
    //                             }

    //                             curCol.style.width = (curColWidth + diffX)+'px';
    //                             //curCol.setAttribute('style','width:'+ (nxtColWidth + (diffX)) + 'px');
    //                             //curCol.style.width = '200px';
    //                            }
    //                           });

    //                       document.addEventListener('mouseup', function (e) { 
    //                        curCol = undefined;
    //                        nxtCol = undefined;
    //                        pageX = undefined;
    //                        nxtColWidth = undefined;
    //                        curColWidth = undefined
    //                       });
    //                      }
                         
    //                      function createDiv(height){
    //                       var div = document.createElement('div');
    //                       div.style.top = 0;
    //                       div.style.right = 0;
    //                       div.style.width = '5px';
    //                       div.style.position = 'absolute';
    //                       div.style.cursor = 'col-resize';
    //                       div.style.userSelect = 'none';
    //                       div.style.height = height + 'px';
    //                       return div;
    //                      }
                         
    //                      function paddingDiff(col){
                         
    //                       if (getStyleVal(col,'box-sizing') == 'border-box'){
    //                        return 0;
    //                       }
                         
    //                       var padLeft = getStyleVal(col,'padding-left');
    //                       var padRight = getStyleVal(col,'padding-right');
    //                       return (parseInt(padLeft) + parseInt(padRight));

    //                      }

    //                      function getStyleVal(elm,css){
    //                       return (window.getComputedStyle(elm, null).getPropertyValue(css))
    //                      }
    //                     };
    //             </script>";


    /////////////////////// NEW PLUGIN //////////////////////
    // $build = "<!DOCTYPE html>";
    // $build .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$datatables."\" >";
    // $build .= "<script src=\"https://code.jquery.com/jquery-3.3.1.js\"></script>";
    // $build .= "<script src=\"js_utilities.js\"></script>";
    // $build .= "<script src=\"sha256.js\"></script>";

    // $build .= "<br><br><br>";
    // $build .= '<div class="topnav">
    //                 <input id="search" type="text" placeholder="Search.." name="search">
    //                 <button type="submit" onclick="submitMe(\'search\')" ><i class="fa fa-search"></i></button>
    //             </div>';

    // $build .= '</tbody></table>';

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

    return $build;
}


































