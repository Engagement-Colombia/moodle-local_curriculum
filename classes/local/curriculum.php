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

namespace local_curriculum\local;

/**
 * Class curriculum
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class curriculum {
    /** @var int Program ID. */
    private int $id;

    /** @var array Static cache of active versions keyed by program ID. */
    private static array $activeversions = [];

    /** @var array Static cache of first cycles keyed by version ID. */
    private static array $firstcycles = [];

    /** @var array Static cache of user active cycles keyed by "programid_userid". */
    private static array $useractivecycles = [];

    /**
     * Constructor.
     *
     * @param int $id Program ID from local_curriculum_programs.
     */
    public function __construct(int $id) {
        $this->id = $id;
    }

    /**
     * Get the active version for this program.
     *
     * The active version is the one without an end date (enddate IS NULL).
     * Results are cached statically per program ID.
     *
     * @return \stdClass|null The active version record, or null if none found.
     */
    public function get_active_version(): ?\stdClass {
        if (!array_key_exists($this->id, self::$activeversions)) {
            global $DB;
            $version = $DB->get_record_select(
                'local_curriculum_versions',
                'programid = :programid AND (enddate IS NULL OR enddate = 0)',
                ['programid' => $this->id],
                '*',
                IGNORE_MULTIPLE
            );
            self::$activeversions[$this->id] = $version ?: null;
        }

        return self::$activeversions[$this->id];
    }

    /**
     * Get the first cycle of the active version.
     *
     * The first cycle is the one with the lowest stage value.
     * Results are cached statically per version ID.
     *
     * @return \stdClass|null The first cycle record, or null if no active version or no cycles.
     */
    public function get_first_cycle(): ?\stdClass {
        $version = $this->get_active_version();
        if (!$version) {
            return null;
        }

        if (!array_key_exists($version->id, self::$firstcycles)) {
            global $DB;
            $cycle = $DB->get_records(
                'local_curriculum_cycles',
                ['versionid' => $version->id],
                'stage ASC',
                '*',
                0,
                1
            );
            self::$firstcycles[$version->id] = !empty($cycle) ? reset($cycle) : null;
        }

        return self::$firstcycles[$version->id];
    }

    /**
     * Get the active cycles for a user in this program.
     *
     * A cycle becomes active based on the accumulated duration of previous cycles
     * starting from the user's first assignment in the program. The activation time
     * for each cycle is calculated as:
     *   first_timestart + sum(duration of all previous cycles in stage order)
     *
     * A cycle is considered active if:
     *   - Its calculated activation time <= now.
     *   - The user has not completed it (no timeend in cycle_users).
     *
     * Each returned element contains the cycle record with additional properties:
     *   - userassignment: the cycle_users record if exists, or null if the cycle
     *     is newly activated and has no assignment yet.
     *   - activationtime: the calculated timestamp when this cycle becomes active.
     *
     * Results are cached statically per program and user combination.
     *
     * @param int $userid The user ID.
     * @return array List of active cycle objects.
     */
    public function get_user_active_cycles(int $userid): array {
        $cachekey = $this->id . '_' . $userid;

        if (array_key_exists($cachekey, self::$useractivecycles)) {
            return self::$useractivecycles[$cachekey];
        }

        global $DB;

        // Find the user's first assignment in any cycle of this program to determine the version and origin time.
        $sql = "SELECT cu.*, c.versionid
                  FROM {local_curriculum_cycle_users} cu
                  JOIN {local_curriculum_cycles} c ON c.id = cu.cycleid
                  JOIN {local_curriculum_versions} v ON v.id = c.versionid
                 WHERE cu.userid = :userid
                   AND v.programid = :programid
              ORDER BY cu.timestart ASC";
        $firstassignment = $DB->get_records_sql($sql, ['userid' => $userid, 'programid' => $this->id], 0, 1);

        if (empty($firstassignment)) {
            self::$useractivecycles[$cachekey] = [];
            return [];
        }

        $firstassignment = reset($firstassignment);
        $versionid = $firstassignment->versionid;
        $origintimestart = $firstassignment->timestart;

        // Get all cycles for this version ordered by stage.
        $cycles = $DB->get_records('local_curriculum_cycles', ['versionid' => $versionid], 'stage ASC');

        if (empty($cycles)) {
            self::$useractivecycles[$cachekey] = [];
            return [];
        }

        // Get all user assignments for cycles in this version.
        $cycleids = array_keys($cycles);
        [$insql, $inparams] = $DB->get_in_or_equal($cycleids, SQL_PARAMS_NAMED);
        $inparams['userid'] = $userid;
        $userassignments = $DB->get_records_select(
            'local_curriculum_cycle_users',
            "userid = :userid AND cycleid $insql",
            $inparams,
            '',
            '*'
        );

        // Index assignments by cycleid.
        $assignmentsbycycle = [];
        foreach ($userassignments as $assignment) {
            $assignmentsbycycle[$assignment->cycleid] = $assignment;
        }

        $now = time();
        $activecycles = [];
        $accumulatedduration = 0;

        foreach ($cycles as $cycle) {
            $activationtime = $origintimestart + ($accumulatedduration * DAYSECS);

            if ($activationtime > $now) {
                // This cycle and all subsequent ones are not yet activated.
                break;
            }

            $assignment = $assignmentsbycycle[$cycle->id] ?? null;

            // Active if not completed (no assignment yet, or assignment without timeend).
            if (!$assignment || empty($assignment->timeend)) {
                $cycle->userassignment = $assignment;
                $cycle->activationtime = $activationtime;
                $activecycles[] = $cycle;
            }

            $accumulatedduration += $cycle->duration;
        }

        self::$useractivecycles[$cachekey] = $activecycles;
        return $activecycles;
    }
}
