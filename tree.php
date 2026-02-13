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
 * Curriculum tree view - displays programs, versions, cycles, and items in a hierarchical structure.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_curriculum_manage');

$context = context_system::instance();
require_capability('local/curriculum:manage', $context);

$url = new moodle_url('/local/curriculum/tree.php', []);
$PAGE->set_url($url);
$PAGE->set_context($context);

$PAGE->set_heading(get_string('tree_title', 'local_curriculum'));
$PAGE->requires->js_call_amd('local_curriculum/tree', 'init');

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_curriculum/managenav', ['wwwroot' => $CFG->wwwroot, 'istree' => true]);

// Build hierarchical tree data from database.
$programs = $DB->get_records('local_curriculum_programs', null, 'name ASC');

if (empty($programs)) {
    echo $OUTPUT->notification(get_string('noprograms', 'local_curriculum'));
} else {
    $treedata = [];

    foreach ($programs as $program) {
        $programnode = [
            'id' => $program->id,
            'name' => $program->name,
            'description' => $program->description,
            'status' => $program->status,
            'type' => 'program',
            'versions' => [],
        ];

        $versions = $DB->get_records('local_curriculum_versions',
            ['programid' => $program->id], 'name ASC');

        foreach ($versions as $version) {
            $versionnode = [
                'id' => $version->id,
                'name' => $version->name,
                'startdate' => $version->startdate,
                'enddate' => $version->enddate,
                'type' => 'version',
                'cycles' => [],
            ];

            $cycles = $DB->get_records('local_curriculum_cycles',
                ['versionid' => $version->id], 'stage ASC, name ASC');

            foreach ($cycles as $cycle) {
                $cyclenode = [
                    'id' => $cycle->id,
                    'name' => $cycle->name,
                    'description' => $cycle->description,
                    'duration' => $cycle->duration,
                    'stage' => $cycle->stage,
                    'type' => 'cycle',
                    'items' => [],
                ];

                $items = $DB->get_records('local_curriculum_cycle_items',
                    ['cycleid' => $cycle->id], 'id ASC');

                foreach ($items as $item) {
                    $itemnode = [
                        'id' => $item->id,
                        'coursecode' => $item->coursecode,
                        'grouptemplate' => $item->grouptemplate,
                        'conditions' => $item->conditions,
                        'validity' => $item->validity,
                        'type' => 'item',
                    ];
                    $cyclenode['items'][] = $itemnode;
                }

                $versionnode['cycles'][] = $cyclenode;
            }

            $programnode['versions'][] = $versionnode;
        }

        $treedata[] = $programnode;
    }

    // Prepare template data.
    $templatedata = [
        'programs' => $treedata,
        'manageurl' => $CFG->wwwroot,
    ];

    // Render tree template.
    echo $OUTPUT->render_from_template('local_curriculum/tree', $templatedata);
}

echo $OUTPUT->footer();

