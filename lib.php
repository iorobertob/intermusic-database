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
 * Library of interface functions and constants.
 *
 * @package     mod_inter
 * @copyright   2019 LMTA <roberto.becerra@lmta.lt>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function inter_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_inter into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_inter_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function inter_add_instance($moduleinstance, $mform = null) {
    global $CFG, $DB;

    require_once("$CFG->libdir/resourcelib.php");
    require_once("$CFG->dirroot/mod/inter/locallib.php");
    require_once("$CFG->libdir/resourcelib.php");
    
    //TODO: print this cmid to see what is it?
    $cmid = $moduleinstance->coursemodule;
    file_print("CMID:", true);
    file_print($cmid);

    $moduleinstance->timecreated = time();

    $courseid = $moduleinstance->course;

    // This line in the end helped saving the file
    inter_set_display_options($moduleinstance);

    $id = $DB->insert_record('inter', $moduleinstance);

    //=====================  STORE FILE, TAKEN FROM 'RESOURCE' MODULE =============
    // we need to use context now, so we need to make sure all needed info is already in db
    
    $DB->set_field('course_modules', 'instance', $id, array('id'=>$cmid));
    
    // $file_url = inter_set_mainfile($moduleinstance);
    
    $completiontimeexpected = !empty($moduleinstance->completionexpected) ? $moduleinstance->completionexpected : null;
    
    \core_completion\api::update_completion_date_event($cmid, 'inter', $id, $completiontimeexpected);
    //=====================  STORE FILE, TAKEN FROM 'RESOURCE' MODULE ============


    //===================== GENERATE SERIALIZED ARRAY FFROM POSTER DATA OBTAINED VIA API FROM RESOURCESPACE ============
    $data_array = [];
    $big_array  = []; 
    file_print("courseid:", true);
    file_print($courseid);
    $big_array  = get_poster_list_array($data_array, $courseid, $moduleinstance);
    $serialized_array = serialize($big_array);
    file_print("CMID", true);
    file_print($cmid);
    file_print($serialized_array);

    //Store in DB
    $DB->set_field('inter', 'serial_data', $serialized_array, array('id'=>$id));
    //===================== GENERATE SERIALIZED ARRAY FFROM POSTER DATA OBTAINED VIA API FROM RESOURCESPACE ============

    return $id;
}

/**
 * Updates an instance of the mod_inter in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_inter_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function inter_update_instance($moduleinstance, $mform = null) {
    global $CFG, $DB;

    require_once("$CFG->libdir/resourcelib.php");
    require_once("$CFG->dirroot/mod/inter/locallib.php");

    $courseid = $moduleinstance->course;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;
    $moduleinstance->revision++;

    // $moduleinstance->name = 'test change lalala';

    inter_set_display_options($moduleinstance);

    $DB->update_record('inter', $moduleinstance);

    // inter_set_mainfile($moduleinstance);

    $completiontimeexpected = !empty($moduleinstance->completionexpected) ? $moduleinstance->completionexpected : null;
    \core_completion\api::update_completion_date_event($moduleinstance->coursemodule, 'inter', $moduleinstance->id, $completiontimeexpected);

    // return $DB->update_record('inter', $moduleinstance);

    //===================== GENERATE SERIALIZED ARRAY FFROM POSTER DATA OBTAINED VIA API FROM RESOURCESPACE ============
    $data_array = [];
    $big_array  = []; 
    // file_print("CMID:", TRUE);
    // file_print($courseid);
    // $big_array  = get_poster_list_array($data_array, $courseid, $moduleinstance)
    //===================== GENERATE SERIALIZED ARRAY FFROM POSTER DATA OBTAINED VIA API FROM RESOURCESPACE ============
    return true;
}

/**
 * Removes an instance of the mod_inter from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function inter_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('inter', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('inter', array('id' => $id));

    return true;
}

/**
 * Returns the lists of all browsable file areas within the given module context.
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}.
 *
 * @package     mod_inter
 * @category    files
 *
 * @param stdClass $course.
 * @param stdClass $cm.
 * @param stdClass $context.
 * @return string[].
 */
function inter_get_file_areas($course, $cm, $context) {
    // return array();
    $areas = array();
    $areas['content'] = get_string('resourcecontent', 'inter');
    return $areas;
}

/**
 * File browsing support for mod_inter file areas.
 *
 * @package     mod_inter
 * @category    files
 *
 * @param file_browser $browser.
 * @param array $areas.
 * @param stdClass $course.
 * @param stdClass $cm.
 * @param stdClass $context.
 * @param string $filearea.
 * @param int $itemid.
 * @param string $filepath.
 * @param string $filename.
 * @return file_info Instance or null if not found.
 */
function inter_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
     global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // students can not peak here!
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'content') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_inter', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_inter', 'content', 0);
            } else {
                // not found
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/inter/locallib.php");
        return new inter_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, true, false);
    }

    // note: resource_intro handled in file_browser automatically
    return null;
}

/**
 * Serves the files from the mod_inter file areas.
 *
 * @package     mod_inter
 * @category    files
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param stdClass $context The mod_inter's context.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments (itemid, path).
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 */
function inter_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = array()) {
    global $DB, $CFG;

    // if ($context->contextlevel != CONTEXT_MODULE) {
    //     send_file_not_found();
    // }

    // require_login($course, true, $cm);
    // send_file_not_found();

    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/inter:view', $context)) {
        return false;
    }

    if ($filearea !== 'content') {
        // intro is handled automatically in pluginfile.php
        return false;
    }

    array_shift($args); // ignore revision - designed to prevent caching problems only

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = rtrim("/$context->id/mod_inter/$filearea/0/$relativepath", '/');
    do {
        if (!$file = $fs->get_file_by_hash(sha1($fullpath))) {
            if ($fs->get_file_by_hash(sha1("$fullpath/."))) {
                if ($file = $fs->get_file_by_hash(sha1("$fullpath/index.htm"))) {
                    break;
                }
                if ($file = $fs->get_file_by_hash(sha1("$fullpath/index.html"))) {
                    break;
                }
                if ($file = $fs->get_file_by_hash(sha1("$fullpath/Default.htm"))) {
                    break;
                }
            }
            $instance = $DB->get_record('inter', array('id'=>$cm->instance), 'id, legacyfiles', MUST_EXIST);
            if ($instance->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
                return false;
            }
            if (!$file = resourcelib_try_file_migration('/'.$relativepath, $cm->id, $cm->course, 'mod_inter', 'content', 0)) {
                return false;
            }
            // file migrate - update flag
            $instance->legacyfileslast = time();
            $DB->update_record('inter', $instance);
        }
    } while (false);

    // should we apply filters?
    $mimetype = $file->get_mimetype();
    if ($mimetype === 'text/html' or $mimetype === 'text/plain' or $mimetype === 'application/xhtml+xml') {
        $filter = $DB->get_field('inter', 'filterfiles', array('id'=>$cm->instance));
        $CFG->embeddedsoforcelinktarget = true;
    } else {
        $filter = 0;
    }

    // finally send the file
    send_stored_file($file, null, $filter, $forcedownload, $options);
    // send_stored_file($file, null, $filter, false, $options);
    
}

/**
 * Updates display options based on form input.
 *
 * Shared code used by resource_add_instance and resource_update_instance.
 *
 * @param object $data Data object
 */
function inter_set_display_options($data) {
    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    if (!empty($data->showsize)) {
        $displayoptions['showsize'] = 1;
    }
    if (!empty($data->showtype)) {
        $displayoptions['showtype'] = 1;
    }
    if (!empty($data->showdate)) {
        $displayoptions['showdate'] = 1;
    }
    $data->displayoptions = serialize($displayoptions);
}
