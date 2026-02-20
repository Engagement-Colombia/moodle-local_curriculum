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

namespace local_curriculum\task;

use local_curriculum\local\curriculum;

/**
 * Scheduled task to activate user cycles based on elapsed duration.
 *
 * Runs daily to check all users enrolled in curriculum programs and creates
 * cycle_users records for cycles that should be active but have no assignment yet.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activate_user_cycles extends \core\task\scheduled_task {
    /**
     * Return the task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_activateusercycles', 'local_curriculum');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        $programids = curriculum::get_active_program_ids();

        foreach ($programids as $programid) {
            $this->process_program($programid);
        }
    }

    /**
     * Process a single program: find enrolled users and activate pending cycles.
     *
     * @param int $programid The program ID.
     */
    private function process_program(int $programid): void {
        global $DB;

        // Get all distinct users who have at least one cycle assignment in this program.
        $sql = "SELECT DISTINCT cu.userid
                  FROM {local_curriculum_cycle_users} cu
                  JOIN {local_curriculum_cycles} c ON c.id = cu.cycleid
                  JOIN {local_curriculum_versions} v ON v.id = c.versionid
                 WHERE v.programid = :programid";
        $users = $DB->get_records_sql($sql, ['programid' => $programid]);

        if (empty($users)) {
            return;
        }

        $curriculum = new curriculum($programid);

        foreach ($users as $user) {
            $this->activate_cycles_for_user($curriculum, $user->userid);
        }
    }

    /**
     * Activate pending cycles for a specific user in a program.
     *
     * @param curriculum $curriculum The curriculum instance.
     * @param int $userid The user ID.
     */
    private function activate_cycles_for_user(curriculum $curriculum, int $userid): void {
        $activecycles = $curriculum->get_user_active_cycles($userid);

        foreach ($activecycles as $cycle) {
            // Only create assignment for cycles that don't have one yet.
            if ($cycle->userassignment !== null) {
                continue;
            }

            curriculum::assign_user_to_cycle($userid, $cycle->id, $cycle->activationtime);
        }
    }
}
