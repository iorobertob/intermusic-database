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
 * The main mod_inter configuration form.
 *
 * @package     mod_inter
 * @copyright   2019 LMTA <roberto.becerra@lmta.lt>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_inter
 * @copyright  2019 LMTA <roberto.becerra@lmta.lt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_inter_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('intername', 'inter'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'intername', 'mod_inter');


        ///////////////////////////////////// METADATA FIELDS ////////////////////////////////
        // Add the poster surtitle field.
        $mform->addElement('text', 'meta1', get_string('meta1', 'mod_inter'), array('size' => '64'));
        $mform->setType('meta1', PARAM_TEXT);
        $mform->addRule('meta1', get_string('maximumchars', 'core', 255), 'maxlength', 255, 'client');
        $mform->setDefault('meta1', get_config('mod_inter','meta1' ) );

        // Add the poster author field.
        $mform->addElement('text', 'meta2', get_string('meta2', 'mod_inter'), array('size' => '64'));
        $mform->setType('meta2', PARAM_TEXT);
        $mform->addRule('meta2', get_string('maximumchars', 'core', 255), 'maxlength', 255, 'client');
        $mform->setDefault('meta2', get_config('mod_inter','meta2' ) );

        // Add the poster surtitle field.
        $mform->addElement('text', 'meta3', get_string('meta3', 'mod_inter'), array('size' => '64'));
        $mform->setType('meta3', PARAM_TEXT);
        $mform->addRule('meta3', get_string('maximumchars', 'core', 255), 'maxlength', 255, 'client');
        $mform->setDefault('meta3', get_config('mod_inter','meta3' ) );

        // Add the poster surtitle field.
        $mform->addElement('text', 'meta4', get_string('meta4', 'mod_inter'), array('size' => '64'));
        $mform->setType('meta4', PARAM_TEXT);
        $mform->addRule('meta4', get_string('maximumchars', 'core', 255), 'maxlength', 255, 'client');
        $mform->setDefault('meta4', get_config('mod_inter','meta4' ) );

        // Add the poster surtitle field.
        $mform->addElement('text', 'meta5', get_string('meta5', 'mod_inter'), array('size' => '64'));
        $mform->setType('meta5', PARAM_TEXT);
        $mform->addRule('meta5', get_string('maximumchars', 'core', 255), 'maxlength', 255, 'client');
        $mform->setDefault('meta5', get_config('mod_inter','meta5' ) );

        // Add the poster surtitle field.
        $mform->addElement('text', 'meta6', get_string('meta6', 'mod_inter'), array('size' => '64'));
        $mform->setType('meta6', PARAM_TEXT);
        $mform->addRule('meta6', get_string('maximumchars', 'core', 255), 'maxlength', 255, 'client');
        $mform->setDefault('meta6', get_config('mod_inter','meta6' ) );

        // Add the poster surtitle field.
        $mform->addElement('text', 'meta7', get_string('meta7', 'mod_inter'), array('size' => '64'));
        $mform->setType('meta7', PARAM_TEXT);
        $mform->addRule('meta7', get_string('maximumchars', 'core', 255), 'maxlength', 255, 'client');
        $mform->setDefault('meta7', get_config('mod_inter','meta7' ) );
        ///////////////////////////////////// METADATA FIELDS ////////////////////////////////


        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }


        //========================   FILE PIKCER ==========================================
        $element = $mform->getElement('introeditor');
        $attributes = $element->getAttributes();
        $attributes['rows'] = 5;
        $element->setAttributes($attributes);
        $filemanager_options = array();
        $filemanager_options['accepted_types'] = '*';
        $filemanager_options['maxbytes'] = 0;
        $filemanager_options['maxfiles'] = -1;
        $filemanager_options['mainfile'] = true;
        //========================   FILE PIKCER ==========================================

        $mform->addElement('advcheckbox', 'platformwide', get_string('interplatformwide', 'inter'), '', array('group' => 1), array(0, 1));

        // Adding the rest of mod_inter settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }


    function data_preprocessing(&$default_values) {

        if (!empty($default_values['displayoptions'])) {
            $displayoptions = unserialize($default_values['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $default_values['printintro'] = $displayoptions['printintro'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $default_values['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $default_values['popupheight'] = $displayoptions['popupheight'];
            }
            if (!empty($displayoptions['showsize'])) {
                $default_values['showsize'] = $displayoptions['showsize'];
            } else {
                // Must set explicitly to 0 here otherwise it will use system
                // default which may be 1.
                $default_values['showsize'] = 0;
            }
            if (!empty($displayoptions['showtype'])) {
                $default_values['showtype'] = $displayoptions['showtype'];
            } else {
                $default_values['showtype'] = 0;
            }
            if (!empty($displayoptions['showdate'])) {
                $default_values['showdate'] = $displayoptions['showdate'];
            } else {
                $default_values['showdate'] = 0;
            }
        }
    }
}
