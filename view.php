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
 * Prints an instance of mod_csvtable.
 *
 * @package     mod_csvtable
 * @copyright   2021 Ideas-Block <roberto@ideas-block.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

require_once("$CFG->dirroot/mod/csvtable/locallib.php");

global $DB, $CFG;

// Course_module ID, or
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$i  = optional_param('i', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('csvtable', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('csvtable', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($i) {
    $moduleinstance = $DB->get_record('csvtable', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('csvtable', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', mod_csvtable));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_csvtable\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('csvtable', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/csvtable/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));

//=============================  GET FILE===================================
$fs = get_file_storage();

$files = $fs->get_area_files($modulecontext->id, 'mod_csvtable', 'content', 0, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!

if (count($files) < 1) {
    resource_print_filenotfound($moduleinstance, $cm, $course);
} else {
    $file = reset($files);
    $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false);

    $fileid  =  $file->get_id();  
    $fileid  =  $file->get_contenthash(); 
    $fileurl = $CFG->dataroot."/filedir/".substr($fileid, 0,2)."/".substr($fileid, 2,2)."/".$fileid;

    unset($files);
}
//=============================  /GET FILE    ===================================

$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

echo (csvtable_build_html_table($fileurl, $course, $moduleinstance->separationchar, $moduleinstance->name));

echo $OUTPUT->footer();






























