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
    /** @var string End reason: program was changed in user profile. */
    const ENDREASON_PROGRAM_CHANGE = 'programchange';

    /** @var string End reason: user completed all courses in the cycle. */
    const ENDREASON_COMPLETED = 'completed';

    /** @var string End reason: user account was deleted. */
    const ENDREASON_USER_DELETED = 'userdeleted';

    /** @var string End reason: cycle was homologated. */
    const ENDREASON_HOMOLOGATED = 'homologated';

    /** @var int Program ID. */
    private int $id;

    /** @var array Static cache of active versions keyed by program ID. */
    private static array $activeversions = [];

    /** @var array Static cache of first cycles keyed by version ID. */
    private static array $firstcycles = [];

    /** @var array Static cache of user active cycles keyed by "programid_userid". */
    private static array $useractivecycles = [];

    /** @var array Static cache of cycle courses keyed by cycle ID. */
    private static array $cyclecourses = [];

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
     * A version is active when:
     *   - Its start date is not in the future (startdate <= now).
     *   - It has no end date (NULL or 0) or the end date is in the future (enddate > now).
     *
     * When multiple versions qualify, the one with the most recent start date is returned.
     * Results are cached statically per program ID.
     *
     * @return \stdClass|null The active version record, or null if none found.
     */
    public function get_active_version(): ?\stdClass {
        if (!array_key_exists($this->id, self::$activeversions)) {
            global $DB;
            $now = time();
            $sql = "SELECT *
                      FROM {local_curriculum_versions}
                     WHERE programid = :programid
                       AND startdate <= :now1
                       AND (enddate IS NULL OR enddate = 0 OR enddate > :now2)
                  ORDER BY startdate DESC";
            $versions = $DB->get_records_sql($sql, [
                'programid' => $this->id,
                'now1' => $now,
                'now2' => $now,
            ], 0, 1);
            self::$activeversions[$this->id] = !empty($versions) ? reset($versions) : null;
        }

        return self::$activeversions[$this->id];
    }

    /**
     * Get all program IDs that have at least one active version.
     *
     * Uses the same active version criteria as get_active_version().
     *
     * @return array List of program ID integers.
     */
    public static function get_active_program_ids(): array {
        global $DB;
        $now = time();
        $sql = "SELECT DISTINCT v.programid
                  FROM {local_curriculum_versions} v
                 WHERE v.startdate <= :now1
                   AND (v.enddate IS NULL OR v.enddate = 0 OR v.enddate > :now2)";
        $records = $DB->get_records_sql($sql, ['now1' => $now, 'now2' => $now]);
        return array_map(function ($r) {
            return (int) $r->programid;
        }, $records);
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

    /**
     * Assign a user to a cycle.
     *
     * Centralizes the creation of cycle_users records. Invalidates the static
     * cache for the user so subsequent calls reflect the new assignment.
     *
     * @param int $userid The user ID.
     * @param int $cycleid The cycle ID.
     * @param int $timestart The start timestamp for the assignment.
     * @return int The ID of the new cycle_users record.
     */
    public static function assign_user_to_cycle(int $userid, int $cycleid, int $timestart): int {
        global $DB;

        $record = new \stdClass();
        $record->cycleid = $cycleid;
        $record->userid = $userid;
        $record->timestart = $timestart;
        $record->timeend = null;
        $record->endreason = null;

        $id = $DB->insert_record('local_curriculum_cycle_users', $record);

        // Enrol the user in all courses linked to this cycle.
        self::enrol_user_in_cycle_courses($userid, $cycleid, $timestart);

        // Invalidate cached active cycles for this user across all programs.
        foreach (self::$useractivecycles as $key => $value) {
            if (str_ends_with($key, '_' . $userid) || str_starts_with($key, $userid . '_')) {
                unset(self::$useractivecycles[$key]);
            }
        }

        return $id;
    }

    /**
     * Enrol a user in all courses linked to a cycle via the curriculum enrolment plugin.
     *
     * Uses the enrol_curriculum plugin. If a course does not have a curriculum
     * enrolment instance, one is created automatically (provided the plugin is enabled).
     * Users already enrolled are not affected.
     *
     * @param int $userid The user ID.
     * @param int $cycleid The cycle ID.
     * @param int $timestart The enrolment start timestamp.
     */
    private static function enrol_user_in_cycle_courses(int $userid, int $cycleid, int $timestart): void {
        global $DB;

        $courses = self::get_cycle_courses($cycleid);
        if (empty($courses)) {
            return;
        }

        $enrolplugin = enrol_get_plugin('curriculum');
        if (!$enrolplugin) {
            return;
        }

        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);

        foreach ($courses as $course) {
            $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'curriculum']);

            if (!$instance) {
                $instanceid = $enrolplugin->add_instance($course);
                $instance = $DB->get_record('enrol', ['id' => $instanceid]);
            }

            if (!$instance) {
                continue;
            }

            if (!is_enrolled(\context_course::instance($course->id), $userid)) {
                $enrolplugin->enrol_user($instance, $userid, $studentroleid, $timestart);
            }
        }
    }

    /**
     * Get the courses that belong to a cycle.
     *
     * Courses are matched by the coursecode field in cycle items. The coursecode
     * can be an exact course idnumber or a pattern using % as wildcard (SQL LIKE).
     *
     * Results are cached statically per cycle ID.
     *
     * @param int $cycleid The cycle ID.
     * @return array List of course records matching the cycle items.
     */
    public static function get_cycle_courses(int $cycleid): array {
        global $DB;

        if (array_key_exists($cycleid, self::$cyclecourses)) {
            return self::$cyclecourses[$cycleid];
        }

        $items = $DB->get_records('local_curriculum_cycle_items', ['cycleid' => $cycleid]);

        if (empty($items)) {
            self::$cyclecourses[$cycleid] = [];
            return [];
        }

        $courses = [];
        $found = [];

        foreach ($items as $item) {
            if (empty($item->coursecode)) {
                continue;
            }

            if (strpos($item->coursecode, '%') !== false) {
                // Split by % to escape each segment, then rejoin with % as wildcard.
                $segments = explode('%', $item->coursecode);
                $escaped = array_map(function ($s) use ($DB) {
                    return $DB->sql_like_escape($s);
                }, $segments);
                $pattern = implode('%', $escaped);

                $likesql = $DB->sql_like('idnumber', ':pattern');
                $sql = "SELECT * FROM {course} WHERE $likesql";
                $matches = $DB->get_records_sql($sql, ['pattern' => $pattern]);
            } else {
                // Exact match.
                $matches = $DB->get_records('course', ['idnumber' => $item->coursecode]);
            }

            foreach ($matches as $course) {
                if (!isset($found[$course->id])) {
                    $found[$course->id] = true;
                    $courses[] = $course;
                }
            }
        }

        self::$cyclecourses[$cycleid] = $courses;
        return $courses;
    }
}
