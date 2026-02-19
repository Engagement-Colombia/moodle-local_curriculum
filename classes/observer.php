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

namespace local_curriculum;

/**
 * Event observers for local_curriculum
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Event observer for core\event\user_created
     *
     * @param \core\event\user_created $event
     */
    public static function user_created(\core\event\user_created $event): void {
        self::operate($event);
    }

    /**
     * Event observer for core\event\user_updated
     *
     * @param \core\event\user_updated $event
     */
    public static function user_updated(\core\event\user_updated $event): void {
        self::operate($event);
    }

    /**
     * Event observer for core\event\user_deleted
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        self::operate($event);
    }

    /**
     * Common code to operate on user events.
     *
     * @param \core\event\base $event
     * @return void
     */
    private static function operate(\core\event\base $event): void {
        global $DB;

        $data = $event->get_data();
        $user = $DB->get_record('user', ['id' => $data['objectid']]);

        if (!$user) {
            return;
        }

        $curriculums = $DB->get_records('user_info_field', ['datatype' => 'curriculum']);
        if (!$curriculums) {
            return;
        }

        $ids = array_keys($curriculums);
        [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $select = "fieldid $insql AND userid = :userid";
        $params = ['userid' => $user->id] + $inparams;

        $usercycles = $DB->get_records('local_curriculum_cycle_users', ['userid' => $user->id]);

        $usercurriculums = $DB->get_records_select('user_info_data', $select, $params);
        foreach ($usercurriculums as $curriculum) {
            if (empty($curriculum->data)) {
                continue;
            }

            $curriculumid = intval($curriculum->data);
            if (empty($curriculumid)) {
                continue;
            }
        }
    }
}
