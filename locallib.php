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
        // echo("<script>console.log('333333333333 ".$file->get_filepath()."');</script>");
        // die;
	}
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
function inter_mysql_query()
{
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

	// sql code to create table
	$sql = "CREATE TABLE test_intermusic (
	        id INT(2)  PRIMARY KEY, 
	        firstname VARCHAR(30) NOT NULL,
	        lastname VARCHAR(30) NOT NULL,
	        email VARCHAR(50)
	        )";

	if ($conn->query($sql) === TRUE) {
	    echo "Table employees created successfully";
	    $conn->close();
	    return "Table employees created successfully";
	} else {
	    echo "Error creating table: " . $conn->error;
	    $conn->close();
	    return "Error creating table: " . $conn->error;
	}

	
   
	// return null ;

}