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
     * Check whether a user has completed all courses in a cycle.
     *
     * Uses get_cycle_courses_with_items() to resolve courses (supports wildcard patterns).
     * A course is considered completed when it has a record in course_completions with
     * timecompleted IS NOT NULL.
     *
     * @param int $userid The user ID.
     * @param int $cycleid The cycle ID.
     * @return bool True if all courses in the cycle are completed by the user.
     */
    public static function is_cycle_completed_by_user(int $userid, int $cycleid): bool {
        global $DB;

        $coursesitems = self::get_cycle_courses_with_items($cycleid);
        if (empty($coursesitems)) {
            return false;
        }

        $courseids = array_map(function ($entry) {
            return $entry->course->id;
        }, $coursesitems);

        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $inparams['userid'] = $userid;
        $sql = "SELECT COUNT(id)
                  FROM {course_completions}
                 WHERE userid = :userid
                   AND course $insql
                   AND timecompleted IS NOT NULL";
        $completedcount = $DB->count_records_sql($sql, $inparams);

        return $completedcount >= count($courseids);
    }

    /**
     * Check active cycles for a user given a completed course, and mark as completed if all courses are done.
     *
     * Finds all active (not ended) cycle assignments for the user, checks if the
     * given course belongs to each cycle, and if all courses in the cycle are
     * completed, marks the cycle as finished.
     *
     * @param int $userid The user ID.
     * @param int $courseid The completed course ID.
     */
    public static function check_and_complete_cycles(int $userid, int $courseid): void {
        global $DB;

        $sql = "SELECT cu.id, cu.cycleid
                  FROM {local_curriculum_cycle_users} cu
                 WHERE cu.userid = :userid
                   AND cu.timeend IS NULL";
        $activecycles = $DB->get_records_sql($sql, ['userid' => $userid]);

        foreach ($activecycles as $ac) {
            $coursesitems = self::get_cycle_courses_with_items($ac->cycleid);
            $found = false;
            foreach ($coursesitems as $entry) {
                if ((int) $entry->course->id === (int) $courseid) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                continue;
            }

            if (self::is_cycle_completed_by_user($userid, $ac->cycleid)) {
                self::complete_user_cycle($userid, $ac->cycleid);
            }
        }
    }

    /**
     * Mark a user's active cycle as completed.
     *
     * Finds the active cycle_users record (timeend IS NULL) for the given user
     * and cycle, and sets timeend and endreason.
     *
     * @param int $userid The user ID.
     * @param int $cycleid The cycle ID.
     * @param string $endreason The reason for ending the cycle.
     * @return bool True if the record was updated, false if no active record found.
     */
    public static function complete_user_cycle(int $userid, int $cycleid, string $endreason = self::ENDREASON_COMPLETED): bool {
        global $DB;

        $record = $DB->get_record('local_curriculum_cycle_users', [
            'userid' => $userid,
            'cycleid' => $cycleid,
            'timeend' => null,
        ]);

        if (!$record) {
            return false;
        }

        $record->timeend = time();
        $record->endreason = $endreason;
        $DB->update_record('local_curriculum_cycle_users', $record);

        return true;
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

        $coursesitems = self::get_cycle_courses_with_items($cycleid);
        if (empty($coursesitems)) {
            return;
        }

        $enrolplugin = enrol_get_plugin('curriculum');
        if (!$enrolplugin) {
            return;
        }

        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);

        foreach ($coursesitems as $entry) {
            $course = $entry->course;
            $item = $entry->item;

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

            if (!empty($item->grouptemplate)) {
                self::assign_user_to_group($userid, $course->id, $item->grouptemplate);
            }
        }
    }

    /**
     * Get the courses that belong to a cycle, along with their originating cycle item.
     *
     * Courses are matched by the coursecode field in cycle items. The coursecode
     * can be an exact course idnumber or a pattern using % as wildcard (SQL LIKE).
     *
     * Each returned element is an object with:
     *   - course: the course record.
     *   - item: the cycle item record that matched the course.
     *
     * A course appears only once; the first matching item takes precedence.
     * Results are cached statically per cycle ID.
     *
     * @param int $cycleid The cycle ID.
     * @return array List of objects with course and item properties.
     */
    public static function get_cycle_courses_with_items(int $cycleid): array {
        global $DB;

        if (array_key_exists($cycleid, self::$cyclecourses)) {
            return self::$cyclecourses[$cycleid];
        }

        $items = $DB->get_records('local_curriculum_cycle_items', ['cycleid' => $cycleid]);

        if (empty($items)) {
            self::$cyclecourses[$cycleid] = [];
            return [];
        }

        $coursesitems = [];
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
                    $entry = new \stdClass();
                    $entry->course = $course;
                    $entry->item = $item;
                    $coursesitems[] = $entry;
                }
            }
        }

        self::$cyclecourses[$cycleid] = $coursesitems;
        return $coursesitems;
    }

    /**
     * Resolve a group template string by replacing placeholders with user profile values.
     *
     * Supported placeholders (enclosed in curly braces):
     *   - Standard fields: {institution}, {department}, {city}, {country}, {lang}.
     *   - Custom profile fields: {profile_field_shortname}.
     *
     * @param string $template The group template string.
     * @param int $userid The user ID.
     * @return string The resolved group name.
     */
    private static function resolve_group_template(string $template, int $userid): string {
        global $CFG, $DB;

        // Get user record with fields needed for standard placeholders.
        $standardfields = ['institution', 'department', 'city', 'country', 'lang'];
        $user = $DB->get_record('user', ['id' => $userid], 'id, ' . implode(', ', $standardfields));
        if (!$user) {
            return $template;
        }

        foreach ($standardfields as $field) {
            $template = str_replace('{' . $field . '}', $user->$field ?? '', $template);
        }

        // Replace custom profile field placeholders.
        if (strpos($template, '{profile_field_') !== false) {
            require_once($CFG->dirroot . '/user/profile/lib.php');
            $profilefields = profile_user_record($userid);

            if ($profilefields) {
                preg_match_all('/\{profile_field_([^}]+)\}/', $template, $matches);
                foreach ($matches[1] as $shortname) {
                    $value = $profilefields->$shortname ?? '';
                    $template = str_replace('{profile_field_' . $shortname . '}', $value, $template);
                }
            }
        }

        return $template;
    }

    /**
     * Assign a user to a group in a course based on a group template.
     *
     * Resolves the template, checks if the group exists in the course,
     * creates it if necessary, and adds the user as a member.
     *
     * @param int $userid The user ID.
     * @param int $courseid The course ID.
     * @param string $grouptemplate The group template string.
     */
    private static function assign_user_to_group(int $userid, int $courseid, string $grouptemplate): void {
        global $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        $groupname = self::resolve_group_template($grouptemplate, $userid);
        if (empty($groupname)) {
            return;
        }

        $groupid = groups_get_group_by_name($courseid, $groupname);

        if (!$groupid) {
            $autocreate = get_config('local_curriculum', 'autocreategroups');
            if ($autocreate) {
                $data = new \stdClass();
                $data->courseid = $courseid;
                $data->name = $groupname;
                $groupid = groups_create_group($data);
            }

            self::notify_new_group_created($groupname, $courseid, !$autocreate);
        }

        if (!$groupid) {
            return;
        }

        groups_add_member($groupid, $userid);

        self::notify_teachers_new_group_member($userid, $courseid, $groupid, $groupname);
    }

    /**
     * Send a notification to teachers in a group when a new member is added.
     *
     * Finds all users with editingteacher or teacher role in the course that
     * are also members of the group, and sends them a Moodle notification.
     *
     * @param int $userid The new member's user ID.
     * @param int $courseid The course ID.
     * @param int $groupid The group ID.
     * @param string $groupname The group name.
     */
    private static function notify_teachers_new_group_member(int $userid, int $courseid, int $groupid, string $groupname): void {
        global $CFG, $DB;

        $course = $DB->get_record('course', ['id' => $courseid], 'id, fullname');
        $newuser = \core_user::get_user($userid);
        if (!$course || !$newuser) {
            return;
        }

        if (empty($CFG->coursecontact)) {
            return;
        }

        $roleids = explode(',', $CFG->coursecontact);
        $context = \context_course::instance($courseid);

        $teachers = get_role_users($roleids, $context, false, 'ra.id, u.*', 'u.lastname ASC', true, $groupid);

        if (empty($teachers)) {
            return;
        }

        $a = new \stdClass();
        $a->userfullname = fullname($newuser);
        $a->groupname = $groupname;
        $a->coursename = $course->fullname;
        $a->groupurl = (new \moodle_url('/group/members.php', ['group' => $groupid]))->out(false);

        $subject = get_string('newgroupmember_subject', 'local_curriculum', $a);
        $body = get_string('newgroupmember_body', 'local_curriculum', $a);

        $noreplyuser = \core_user::get_noreply_user();

        foreach ($teachers as $teacher) {
            $message = new \core\message\message();
            $message->component = 'local_curriculum';
            $message->name = 'newgroupmember';
            $message->userfrom = $noreplyuser;
            $message->userto = $teacher;
            $message->subject = $subject;
            $message->fullmessage = $body;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = '';
            $message->smallmessage = $subject;
            $message->notification = 1;
            $message->contexturl = (new \moodle_url('/group/members.php', ['group' => $groupid]))->out(false);
            $message->contexturlname = $a->groupname;
            $message->courseid = $courseid;

            message_send($message);
        }
    }

    /**
     * Send email notifications about a newly created group.
     *
     * Reads the configured list of email addresses and sends each one
     * a notification with the group and course details.
     *
     * @param string $groupname The name of the group.
     * @param int $courseid The course ID where the group was or should be created.
     * @param bool $pendingcreation Whether the group was not created and needs manual creation.
     */
    private static function notify_new_group_created(string $groupname, int $courseid, bool $pendingcreation = false): void {
        global $DB;

        $emails = get_config('local_curriculum', 'newgroupnotifyemails');
        if (empty($emails)) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $courseid], 'id, fullname');
        $a = new \stdClass();
        $a->groupname = $groupname;
        $a->courseid = $courseid;
        $a->coursename = $course ? $course->fullname : $courseid;
        $a->courseurl = (new \moodle_url('/group/index.php', ['id' => $courseid]))->out(false);

        $strprefix = $pendingcreation ? 'newgrouppending' : 'newgroupcreated';
        $subject = get_string($strprefix . '_subject', 'local_curriculum');
        $body = get_string($strprefix . '_body', 'local_curriculum', $a);

        $noreplyuser = \core_user::get_noreply_user();

        $emaillist = preg_split('/\r\n|\r|\n/', $emails);
        foreach ($emaillist as $email) {
            $email = trim($email);
            if (empty($email) || !validate_email($email)) {
                continue;
            }

            $recipient = $DB->get_record('user', ['email' => $email, 'deleted' => 0], '*', IGNORE_MULTIPLE);
            if (!$recipient) {
                $recipient = \core_user::get_noreply_user();
                $recipient->id = -1;
                $recipient->email = $email;
                $recipient->firstname = '';
                $recipient->lastname = '';
                $recipient->firstnamephonetic = '';
                $recipient->lastnamephonetic = '';
                $recipient->middlename = '';
                $recipient->alternatename = '';
                $recipient->maildisplay = 1;
                $recipient->mailformat = 1;
                $recipient->emailstop = 0;
            }

            email_to_user($recipient, $noreplyuser, $subject, $body);
        }
    }
}
