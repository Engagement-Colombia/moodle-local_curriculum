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

namespace local_curriculum\output;

use local_curriculum\local\curriculum;
use core_completion\progress;
use renderable;
use templatable;
use renderer_base;
use stdClass;
use completion_info;
use moodle_url;

/**
 * Dashboard renderable for the user curriculum view.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard implements renderable, templatable {
    /** @var int The user ID. */
    private int $userid;

    /**
     * Constructor.
     *
     * @param int $userid The user ID.
     */
    public function __construct(int $userid) {
        $this->userid = $userid;
    }

    /**
     * Export data for the template.
     *
     * @param renderer_base $output The renderer.
     * @return stdClass Data for the template.
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $DB;

        $data = new stdClass();
        $data->hasprograms = false;
        $data->currentprogram = null;
        $data->historyprograms = [];
        $data->hashistory = false;

        // Get all cycle assignments for this user with full hierarchy info.
        $sql = "SELECT cu.id AS assignmentid, cu.cycleid, cu.timestart, cu.timeend, cu.endreason,
                       c.versionid, c.name AS cyclename, c.stage, c.duration, c.description AS cycledescription,
                       v.programid, v.name AS versionname, v.startdate AS versionstart, v.enddate AS versionend,
                       p.name AS programname, p.description AS programdescription
                  FROM {local_curriculum_cycle_users} cu
                  JOIN {local_curriculum_cycles} c ON c.id = cu.cycleid
                  JOIN {local_curriculum_versions} v ON v.id = c.versionid
                  JOIN {local_curriculum_programs} p ON p.id = v.programid
                 WHERE cu.userid = :userid
              ORDER BY cu.timestart ASC";
        $assignments = $DB->get_records_sql($sql, ['userid' => $this->userid]);

        if (empty($assignments)) {
            return $data;
        }

        // Group assignments by program+version.
        $programgroups = [];
        foreach ($assignments as $assignment) {
            $key = $assignment->programid . '_' . $assignment->versionid;
            if (!isset($programgroups[$key])) {
                $programgroups[$key] = [
                    'programid' => $assignment->programid,
                    'programname' => $assignment->programname,
                    'programdescription' => $assignment->programdescription,
                    'versionid' => $assignment->versionid,
                    'versionname' => $assignment->versionname,
                    'versionstart' => $assignment->versionstart,
                    'versionend' => $assignment->versionend,
                    'assignments' => [],
                    'iscurrent' => false,
                    'endreason' => null,
                ];
            }
            $programgroups[$key]['assignments'][] = $assignment;
        }

        // Determine current vs history and endreason for each group.
        // A program is only "history" when ALL cycles in the version are completed
        // and none of the end reasons is "programchange". If the user has unfinished
        // cycles or any cycle ended due to a program change, the program stays current.
        foreach ($programgroups as $key => &$group) {
            $hascurrent = false;
            $endreason = null;
            $hasnonprogramchange = false;

            // Check all cycles in this version, not just the ones with assignments.
            $allcycleids = $DB->get_fieldset_select(
                'local_curriculum_cycles',
                'id',
                'versionid = :versionid',
                ['versionid' => $group['versionid']]
            );
            $totalcycles = count($allcycleids);

            $assignedended = 0;
            foreach ($group['assignments'] as $a) {
                if (empty($a->timeend)) {
                    $hascurrent = true;
                } else {
                    $assignedended++;
                    $endreason = $a->endreason;
                    if ($a->endreason !== curriculum::ENDREASON_PROGRAM_CHANGE) {
                        $hasnonprogramchange = true;
                    }
                }
            }

            // If there are unassigned cycles remaining, the program is still current.
            if ($assignedended < $totalcycles) {
                $hascurrent = true;
            }

            // If any cycle ended with a reason different from programchange,
            // the program should not go to history.
            if ($hasnonprogramchange) {
                $hascurrent = true;
            }

            $group['iscurrent'] = $hascurrent;
            $group['endreason'] = $endreason;
        }
        unset($group);

        // Build program data for each group.
        $currentprogram = null;
        $historyprograms = [];

        foreach ($programgroups as $group) {
            $programdata = $this->build_program_data($group);
            if ($group['iscurrent']) {
                $currentprogram = $programdata;
            } else {
                $historyprograms[] = $programdata;
            }
        }

        $data->hasprograms = ($currentprogram !== null || !empty($historyprograms));
        $data->currentprogram = $currentprogram;
        $data->historyprograms = $historyprograms;
        $data->hashistory = !empty($historyprograms);

        return $data;
    }

    /**
     * Build the data structure for a single program group.
     *
     * @param array $group The program group data.
     * @return stdClass The program data for the template.
     */
    private function build_program_data(array $group): stdClass {
        global $DB;

        $program = new stdClass();
        $program->programname = $group['programname'];
        $program->versionname = $group['versionname'];
        $program->iscurrent = $group['iscurrent'];
        $program->endreason = $group['endreason'];
        $program->endreasonlabel = '';

        if (!empty($group['endreason'])) {
            $program->endreasonlabel = get_string('endreason_' . $group['endreason'], 'local_curriculum');
        }

        // Get all cycles in this version ordered by stage.
        $allcycles = $DB->get_records('local_curriculum_cycles', ['versionid' => $group['versionid']], 'stage ASC');

        // Index user assignments by cycleid.
        $userassignments = [];
        foreach ($group['assignments'] as $a) {
            $userassignments[$a->cycleid] = $a;
        }

        $cycles = [];
        $totalcycles = count($allcycles);
        $completedcycles = 0;
        $stepindex = 0;

        foreach ($allcycles as $cycle) {
            $stepindex++;
            $assignment = $userassignments[$cycle->id] ?? null;

            $cycledata = new stdClass();
            $cycledata->cycleid = $cycle->id;
            $cycledata->cyclename = $cycle->name;
            $cycledata->stage = $cycle->stage;
            $cycledata->duration = $cycle->duration;
            $cycledata->stepnumber = $stepindex;
            $cycledata->isfirst = ($stepindex === 1);
            $cycledata->islast = ($stepindex === $totalcycles);

            // Determine cycle status.
            if ($assignment && !empty($assignment->timeend)) {
                $cycledata->status = 'completed';
                $cycledata->iscompleted = true;
                $cycledata->isactive = false;
                $cycledata->islocked = false;
                $cycledata->timestart = userdate($assignment->timestart);
                $cycledata->timeend = userdate($assignment->timeend);
                $completedcycles++;
            } else if ($assignment) {
                $cycledata->status = 'active';
                $cycledata->iscompleted = false;
                $cycledata->isactive = true;
                $cycledata->islocked = false;
                $cycledata->timestart = userdate($assignment->timestart);
                $cycledata->timeend = '';
            } else {
                $cycledata->status = 'locked';
                $cycledata->iscompleted = false;
                $cycledata->isactive = false;
                $cycledata->islocked = true;
                $cycledata->timestart = '';
                $cycledata->timeend = '';
            }

            // Get courses for this cycle.
            $coursesitems = curriculum::get_cycle_courses_with_items($cycle->id);
            $courses = [];
            $totalcourses = count($coursesitems);
            $completedcourses = 0;

            foreach ($coursesitems as $entry) {
                $course = $entry->course;
                $coursedata = new stdClass();
                $coursedata->courseid = $course->id;
                $coursedata->coursename = $course->fullname;
                $coursedata->courseurl = (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false);

                // Get completion info.
                $completion = new completion_info($course);
                $coursedata->completionenabled = $completion->is_enabled();

                if ($coursedata->completionenabled && !$cycledata->islocked) {
                    $iscomplete = $completion->is_course_complete($this->userid);
                    $percentage = progress::get_course_progress_percentage($course, $this->userid);

                    $coursedata->iscomplete = $iscomplete;
                    $coursedata->islocked = false;
                    $coursedata->progress = $percentage !== null ? round($percentage) : null;
                    $coursedata->hasprogress = ($percentage !== null);
                    $coursedata->progresswidth = $coursedata->progress ?? 0;
                    $coursedata->notstarted = ($percentage === null || $percentage == 0) && !$iscomplete;

                    if ($iscomplete) {
                        $coursedata->statusclass = 'completed';
                        $completedcourses++;
                    } else if ($percentage !== null && $percentage > 0) {
                        $coursedata->statusclass = 'inprogress';
                    } else {
                        $coursedata->statusclass = 'notstarted';
                    }
                } else {
                    $coursedata->iscomplete = false;
                    $coursedata->islocked = $cycledata->islocked;
                    $coursedata->progress = null;
                    $coursedata->hasprogress = false;
                    $coursedata->progresswidth = 0;
                    $coursedata->notstarted = !$cycledata->islocked;
                    $coursedata->statusclass = $cycledata->islocked ? 'locked' : 'notstarted';
                }

                $courses[] = $coursedata;
            }

            $cycledata->courses = $courses;
            $cycledata->hascourses = !empty($courses);
            $cycledata->totalcourses = $totalcourses;
            $cycledata->completedcourses = $completedcourses;
            if ($totalcourses > 0) {
                $cycledata->cycleprogress = round(($completedcourses / $totalcourses) * 100);
            } else {
                $cycledata->cycleprogress = 0;
            }

            $cycles[] = $cycledata;
        }

        // Determine which cycle should be visible by default:
        // 1. The first active cycle, or 2. The last completed cycle, or 3. The first cycle.
        $defaultvisibleindex = 0;
        foreach ($cycles as $i => $c) {
            if ($c->isactive) {
                $defaultvisibleindex = $i;
                break;
            }
            if ($c->iscompleted) {
                $defaultvisibleindex = $i;
            }
        }
        if (isset($cycles[$defaultvisibleindex])) {
            $cycles[$defaultvisibleindex]->isdefaultvisible = true;
        }

        // Calculate timeline data for the program.
        $totaldurationdays = 0;
        foreach ($allcycles as $c) {
            $totaldurationdays += (int) $c->duration;
        }

        // Find the earliest timestart among user assignments.
        $origintimestart = null;
        foreach ($group['assignments'] as $a) {
            if (!empty($a->timestart) && ($origintimestart === null || $a->timestart < $origintimestart)) {
                $origintimestart = (int) $a->timestart;
            }
        }

        $program->totaldurationdays = $totaldurationdays;
        $program->hastimeline = ($totaldurationdays > 0 && $origintimestart !== null);

        if ($program->hastimeline) {
            $now = time();
            $elapseddays = max(0, floor(($now - $origintimestart) / DAYSECS));
            $program->elapseddays = (int) $elapseddays;
            $program->remainingdays = max(0, $totaldurationdays - $elapseddays);
            $program->timeprogress = min(100, round(($elapseddays / $totaldurationdays) * 100));
            $program->timeoverdue = ($elapseddays > $totaldurationdays);

            // Calculate each cycle's proportion and offset in the timeline.
            $accumulatedoffset = 0;
            foreach ($cycles as $cycledata) {
                $cycledata->durationdays = (int) $cycledata->duration;
                $cycledata->durationpercent = round(($cycledata->durationdays / $totaldurationdays) * 100, 2);
                $cycledata->timeoffsetpercent = round(($accumulatedoffset / $totaldurationdays) * 100, 2);
                $accumulatedoffset += $cycledata->durationdays;
            }
        }

        $program->cycles = $cycles;
        $program->hascycles = !empty($cycles);
        $program->totalcycles = $totalcycles;
        $program->completedcycles = $completedcycles;
        if ($totalcycles > 0) {
            $cycleweight = 100 / $totalcycles;
            $overallprogress = 0;
            foreach ($cycles as $cycledata) {
                if ($cycledata->iscompleted) {
                    $overallprogress += $cycleweight;
                } else {
                    $overallprogress += $cycleweight * ($cycledata->cycleprogress / 100);
                }
            }
            $program->overallprogress = round($overallprogress);
        } else {
            $program->overallprogress = 0;
        }

        return $program;
    }
}
