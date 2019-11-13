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
 * A scheduled task for forum cron.
 *
 * @package    mod_inter
 * @copyright  2019 Roberto Becerra <roberto.becerra@lmta.lt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_inter\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/inter/lib.php');
require_once($CFG->dirroot . '/mod/inter/locallib.php');

/**
 * The main scheduled task for the forum.
 *
 * @package    mod_inter
 * @copyright  2019 Roberto Becerra <roberto.becerra@lmta.lt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_list_cron extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('listcrontask', 'mod_inter');
    }

    /**
     * Execute the scheduled task.
     */
    public function execute() {
        global $CFG, $DB;

        $inter_instances = $DB->get_records("inter", null, $sort='', $fields='*');
        
        foreach($inter_instances as $moduleinstance)
        {
            save_serialized_metadata($moduleinstance->course, $moduleinstance, $moduleinstance->id);
        }
    }

}