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
 * Prints an instance of mod_inter.
 *
 * @package     mod_inter
 * @copyright   2019 LMTA <roberto.becerra@lmta.lt>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');


require_once("$CFG->dirroot/mod/inter/locallib.php");


global $DB;


// Course_module ID, or
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$i  = optional_param('i', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('inter', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('inter', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($i) {
    $moduleinstance = $DB->get_record('inter', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('inter', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', mod_inter));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_inter\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('inter', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/inter/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));

//=============================  GET FILE    ===================================
$fs = get_file_storage();
$files = $fs->get_area_files($modulecontext->id, 'mod_inter', 'content', 0, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
if (count($files) < 1) {
    resource_print_filenotfound($moduleinstance, $cm, $course);
    die;
} else {
    $file = reset($files);
    $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false);
    echo("<script>console.log('URL:  ".$fileurl."');</script>");

    $download_url = $fileurl->get_port() ? $fileurl->get_scheme() . '://' . $fileurl->get_host() . $fileurl->get_path() . ':' . $fileurl->get_port() : $fileurl->get_scheme() . '://' . $fileurl->get_host() . $fileurl->get_path();

    echo("<script>console.log('DOWNLOAD URL:  ".$download_url."');</script>");


    $hash_path = $fs->get_file_by_hash(sha1($fileurl));

    echo("<script>console.log('HASH   ".$hash_path."');</script>");

    // if ($file_extension == "csv")
    // {
        $records = inter_create_database_from_csv("/var/www/intermusicdata2019/filedir/64/99/64999ffcfc60de7b6a59217e92f6f2bfd9dabf71", $id);
    // die;
    unset($files);

}


//$records = inter_mysql_query();

//echo("<script>console.log('RECORDS:  ".$records."');</script>");
//inter_display_embed($resource, $cm, $course, $file);
//=============================  GET FILE   =====================================



$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

echo $OUTPUT->footer();






























