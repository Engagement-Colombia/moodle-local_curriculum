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

use local_curriculum\local\curriculum;

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
        $userid = $data['objectid'];
        $user = $DB->get_record('user', ['id' => $userid]);

        if (!$user) {
            return;
        }

        $curriculums = $DB->get_records('user_info_field', ['datatype' => 'curriculum']);
        if (!$curriculums) {
            return;
        }

        // Get the program IDs for all active (not ended) user cycle assignments.
        $sql = "SELECT cu.id, cu.cycleid, v.programid
                  FROM {local_curriculum_cycle_users} cu
                  JOIN {local_curriculum_cycles} c ON c.id = cu.cycleid
                  JOIN {local_curriculum_versions} v ON v.id = c.versionid
                 WHERE cu.userid = :userid
                   AND cu.timeend IS NULL";
        $activecycles = $DB->get_records_sql($sql, ['userid' => $userid]);

        // Index active programs that the user already has cycles for.
        $activeprogramids = [];
        foreach ($activecycles as $ac) {
            $activeprogramids[$ac->programid] = true;
        }

        // Collect programs currently assigned in the user profile.
        $profileprogramids = [];

        // For user_deleted, the profile is gone so profileprogramids stays empty,
        // which causes all active cycles to be closed below.
        if ($event->eventname !== '\\core\\event\\user_deleted') {
            $ids = array_keys($curriculums);
            [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $select = "fieldid $insql AND userid = :userid";
            $params = ['userid' => $userid] + $inparams;

            $usercurriculums = $DB->get_records_select('user_info_data', $select, $params);
            foreach ($usercurriculums as $uc) {
                $programid = intval($uc->data);
                if (empty($programid)) {
                    continue;
                }

                $profileprogramids[$programid] = true;

                // If the user has no active cycles for this program, start the first cycle.
                if (!isset($activeprogramids[$programid])) {
                    self::start_first_cycle($programid, $userid);
                }
            }
        }

        // Close active cycles for programs no longer in the user profile.
        $now = time();
        foreach ($activecycles as $ac) {
            if (!isset($profileprogramids[$ac->programid])) {
                $DB->set_field('local_curriculum_cycle_users', 'timeend', $now, ['id' => $ac->id]);
            }
        }
    }

    /**
     * Start the first cycle of a program for a user.
     *
     * @param int $programid The program ID.
     * @param int $userid The user ID.
     */
    private static function start_first_cycle(int $programid, int $userid): void {
        global $DB;

        $curriculum = new curriculum($programid);
        $firstcycle = $curriculum->get_first_cycle();
        if (!$firstcycle) {
            return;
        }

        $record = new \stdClass();
        $record->cycleid = $firstcycle->id;
        $record->userid = $userid;
        $record->timestart = time();
        $record->timeend = null;

        $DB->insert_record('local_curriculum_cycle_users', $record);
    }
}
