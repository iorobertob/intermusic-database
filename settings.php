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
 * Plugin administration pages are defined here.
 *
 * @package     mod_inter
 * @category    admin
 * @copyright   2019 LMTA <roberto.becerra@lmta.lt>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
   // https://docs.moodle.org/dev/Admin_settings
	require_once($CFG->dirroot.'/mod/inter/locallib.php');

    // Introductory explanation that all the settings are defaults for the add lesson form.
    $settings->add(new admin_setting_heading('mod_inter/intro', '', get_string('default_titles', 'inter')));

    $settings->add(new admin_setting_configtext('mod_inter/meta1', get_string('meta1', 'inter')." ".get_string('meta_title','inter'),
            '', "Composer", PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_inter/meta2', get_string('meta2', 'inter')." ".get_string('meta_title','inter'),
            '', "Title", PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_inter/meta3', get_string('meta3', 'inter')." ".get_string('meta_title','inter'),
            '', "Title - EN", PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_inter/meta4', get_string('meta4', 'inter')." ".get_string('meta_title','inter'),
            '', "Surtitle", PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_inter/meta5', get_string('meta5', 'inter')." ".get_string('meta_title','inter'),
            '', "Listing", PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_inter/meta6', get_string('meta6', 'inter')." ".get_string('meta_title','inter'),
            '', "1st Line", PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_inter/meta7', get_string('meta7', 'inter')." ".get_string('meta_title','inter'),
            '', "Text by", PARAM_TEXT));
}
