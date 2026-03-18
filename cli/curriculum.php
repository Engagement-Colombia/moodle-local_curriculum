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
 * CLI tool for local_curriculum management.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_curriculum\local\curriculum;

// Available actions — add new entries here as you build features.
$actions = [
    'status'           => 'Show general statistics (programs, versions, cycles, users)',
    'check-completions' => 'Check active cycles and close those with all courses completed (--userid=ID optional)',
];

[$options, $unrecognized] = cli_get_params(
    [
        'help'      => false,
        'action'    => '',
        'userid'    => 0,
    ],
    [
        'h' => 'help',
        'a' => 'action',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$actionlist = '';
foreach ($actions as $key => $desc) {
    $actionlist .= "  {$key}\t{$desc}\n";
}

$help = <<<EOT
Curriculum CLI — management tool for local_curriculum.

Usage:
  php local/curriculum/cli/curriculum.php --action=<action> [options]

Options:
  -h, --help            Print this help.
  -a, --action=ACTION   Action to execute (see list below).
      --userid=ID       Filter by user ID (optional, used by check-completions).

Available actions:
{$actionlist}

Example:
  \$ sudo -u www-data php local/curriculum/cli/curriculum.php --action=status

EOT;

if ($options['help'] || empty($options['action'])) {
    echo $help;
    exit(0);
}

$action = $options['action'];

if (!isset($actions[$action])) {
    cli_error("Unknown action '{$action}'.\n\n{$help}");
}

// Action handlers.
// Each case in the switch corresponds to an action. Add new cases as you implement new features.
switch ($action) {
    case 'status':
        $programs = $DB->count_records('local_curriculum_programs');
        $versions = $DB->count_records('local_curriculum_versions');
        $cycles   = $DB->count_records('local_curriculum_cycles');
        $items    = $DB->count_records('local_curriculum_cycle_items');
        $users    = $DB->count_records('local_curriculum_cycle_users');

        cli_heading('Curriculum status');
        cli_writeln("Programs : {$programs}");
        cli_writeln("Versions : {$versions}");
        cli_writeln("Cycles   : {$cycles}");
        cli_writeln("Items    : {$items}");
        cli_writeln("Users    : {$users}");
        break;

    case 'check-completions':
        $userid = (int) $options['userid'];

        // Get all active cycle_users (timeend IS NULL), optionally filtered by userid.
        $params = [];
        $userwhere = '';
        if ($userid) {
            $userwhere = ' AND cu.userid = :userid';
            $params['userid'] = $userid;
        }

        $sql = "SELECT cu.id, cu.userid, cu.cycleid, u.username, u.firstname, u.lastname
                  FROM {local_curriculum_cycle_users} cu
                  JOIN {user} u ON u.id = cu.userid AND u.deleted = 0
                 WHERE cu.timeend IS NULL{$userwhere}
              ORDER BY cu.userid, cu.cycleid";
        $activecycles = $DB->get_records_sql($sql, $params);

        if (empty($activecycles)) {
            cli_writeln('No active (open) cycles found.');
            break;
        }

        cli_heading('Checking active cycles for completions');
        cli_writeln("Active cycle assignments found: " . count($activecycles));
        cli_writeln('');

        $closed = 0;
        foreach ($activecycles as $ac) {
            $label = "[user={$ac->userid} {$ac->username}] [cycle={$ac->cycleid}]";

            $coursesitems = curriculum::get_cycle_courses_with_items($ac->cycleid);
            if (empty($coursesitems)) {
                cli_writeln("{$label} — no courses linked to cycle, skipping.");
                continue;
            }

            $totalcourses = count($coursesitems);
            $completed = curriculum::is_cycle_completed_by_user($ac->userid, $ac->cycleid);

            if ($completed) {
                curriculum::complete_user_cycle($ac->userid, $ac->cycleid);
                cli_writeln("{$label} — all {$totalcourses} course(s) completed ✓ → cycle CLOSED.");
                $closed++;
            } else {
                // Count how many are actually completed for informational output.
                $courseids = array_map(fn($e) => $e->course->id, $coursesitems);
                [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
                $inparams['uid'] = $ac->userid;
                $donecount = $DB->count_records_sql(
                    "SELECT COUNT(id) FROM {course_completions}
                      WHERE userid = :uid AND course {$insql} AND timecompleted IS NOT NULL",
                    $inparams
                );
                cli_writeln("{$label} — {$donecount}/{$totalcourses} course(s) completed, cycle stays open.");
            }
        }

        cli_writeln('');
        cli_writeln("Done. Cycles closed: {$closed}");
        break;
}

exit(0);
