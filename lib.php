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
 * @package     mod_csvtable
 * @copyright   2021 Ideas-Block <roberto@ideas-block.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function csvtable_supports($feature) {
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
 * Saves a new instance of the mod_csvtable into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_csvtable_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function csvtable_add_instance($moduleinstance, $mform = null) {
    global $CFG, $DB;

    require_once("$CFG->libdir/resourcelib.php");
    require_once("$CFG->dirroot/mod/csvtable/locallib.php");

    require_once("$CFG->libdir/resourcelib.php");
    
    $cmid = $moduleinstance->coursemodule;
    $moduleinstance->timecreated = time();
    $moduleinstance->revision = 1;
    
    $id = $DB->insert_record('csvtable', $moduleinstance);
    $moduleinstance->id = $id;

    // This line in the end helped saving the file
    csvtable_set_display_options($moduleinstance);

    //=====================  STORE FILE, TAKEN FROM 'RESOURCE' MODULE =============
    // we need to use context now, so we need to make sure all needed info is already in db
    
    $DB->set_field('course_modules', 'instance', $id, array('id'=>$cmid));
    
    $file_url = csvtable_set_mainfile($moduleinstance);
    
    $completiontimeexpected = !empty($moduleinstance->completionexpected) ? $moduleinstance->completionexpected : null;
    
    \core_completion\api::update_completion_date_event($cmid, 'csvtable', $id, $completiontimeexpected);
    //=====================  STORE FILE, TAKEN FROM 'RESOURCE' MODULE =============

    return $id;
}

/**
 * Updates an instance of the mod_csvtable in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_csvtable_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function csvtable_update_instance($moduleinstance, $mform = null) {
    global $CFG, $DB;

    require_once("$CFG->libdir/resourcelib.php");
    require_once("$CFG->dirroot/mod/csvtable/locallib.php");

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    $revision = $DB->get_record('csvtable', array('id'=>$moduleinstance->id), '*', MUST_EXIST)->revision;
    $revision ++;
    $moduleinstance->revision = $revision;

    csvtable_set_display_options($moduleinstance);

    $DB->update_record('csvtable', $moduleinstance);

    csvtable_set_mainfile($moduleinstance);

    $completiontimeexpected = !empty($moduleinstance->completionexpected) ? $moduleinstance->completionexpected : null;
    \core_completion\api::update_completion_date_event($moduleinstance->coursemodule, 'csvtable', $moduleinstance->id, $completiontimeexpected);

    return true;
}

/**
 * Removes an instance of the mod_csvtable from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function csvtable_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('csvtable', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('csvtable', array('id' => $id));

    return true;
}

/**
 * Returns the lists of all browsable file areas within the given module context.
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}.
 *
 * @package     mod_csvtable
 * @category    files
 *
 * @param stdClass $course.
 * @param stdClass $cm.
 * @param stdClass $context.
 * @return string[].
 */
function csvtable_get_file_areas($course, $cm, $context) {
    $areas = array();
    $areas['content'] = get_string('resourcecontent', 'csvtable');
    return $areas;
}

/**
 * File browsing support for mod_csvtable file areas.
 *
 * @package     mod_csvtable
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
function csvtable_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
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
        if (!$storedfile = $fs->get_file($context->id, 'mod_csvtable', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_csvtable', 'content', 0);
            } else {
                // not found
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/csvtable/locallib.php");
        return new csvtable_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, true, false);
    }

    // note: resource_intro handled in file_browser automatically
    return null;
}

/**
 * Serves the files from the mod_csvtable file areas.
 *
 * @package     mod_csvtable
 * @category    files
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param stdClass $context The mod_csvtable's context.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments (itemid, path).
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 */
function csvtable_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = array()) {
    global $DB, $CFG;

    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/csvtable:view', $context)) {
        return false;
    }

    if ($filearea !== 'content') {
        // intro is handled automatically in pluginfile.php
        return false;
    }

    array_shift($args); // ignore revision - designed to prevent caching problems only

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = rtrim("/$context->id/mod_csvtable/$filearea/0/$relativepath", '/');
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
            $instance = $DB->get_record('csvtable', array('id'=>$cm->instance), 'id, legacyfiles', MUST_EXIST);
            if ($instance->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
                return false;
            }
            if (!$file = resourcelib_try_file_migration('/'.$relativepath, $cm->id, $cm->course, 'mod_csvtable', 'content', 0)) {
                return false;
            }
            // file migrate - update flag
            $instance->legacyfileslast = time();
            $DB->update_record('csvtable', $instance);
        }
    } while (false);

    // should we apply filters?
    $mimetype = $file->get_mimetype();
    if ($mimetype === 'text/html' or $mimetype === 'text/plain' or $mimetype === 'application/xhtml+xml') {
        $filter = $DB->get_field('csvtable', 'filterfiles', array('id'=>$cm->instance));
        $CFG->embeddedsoforcelinktarget = true;
    } else {
        $filter = 0;
    }

    // finally send the file
    send_stored_file($file, null, $filter, $forcedownload, $options);
}

/**
 * Updates display options based on form input.
 *
 * Shared code used by resource_add_instance and resource_update_instance.
 *
 * @param object $data Data object
 */
function csvtable_set_display_options($data) {
    global $DB;
    $displayoptions = array();
    $display = $DB->get_record('csvtable', array('id'=>$data->id), '*', MUST_EXIST)->display;
    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
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
