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
    $versions = $DB->get_records('local_curriculum_versions', [], 'name ASC');
    $cycles = $DB->get_records('local_curriculum_cycles', [], 'stage ASC, name ASC');
    $items = $DB->get_records('local_curriculum_cycle_items', [], 'id ASC');

    $handler = \local_curriculum\customfield\program_handler::create();
    foreach ($programs as $program) {
        $program->versions = [];

        // Load custom fields for the program.
        $fields = $handler->get_editable_fields($program->id);
        $fielddata = \core_customfield\api::get_instance_fields_data($fields, $program->id);

        $program->customfields = [];
        foreach ($fielddata as $data) {
            $program->customfields[] = [
                'name' => $data->get_field()->get('name'),
                'value' => $data->export_value()
            ];
        }

        $treedata[$program->id] = $program;
    }

    foreach ($versions as $version) {
        $version->cycles = [];
        $treedata[$version->programid]->versions[$version->id] = $version;
    }

    foreach ($cycles as $cycle) {
        $cycle->items = [];
        $version = $versions[$cycle->versionid];
        $treedata[$version->programid]->versions[$cycle->versionid]->cycles[$cycle->id] = $cycle;
    }

    foreach ($items as $item) {
        $cycle = $cycles[$item->cycleid];
        $version = $versions[$cycle->versionid];
        $treedata[$version->programid]->versions[$cycle->versionid]->cycles[$item->cycleid]->items[] = $item;
    }

    // Prepare template data.
    $templatedata = [
        'programs' => [],
        'manageurl' => $CFG->wwwroot,
    ];

    foreach ($treedata as $program) {
        $templatedata['programs'] = array_values($treedata); // Re-index for template.
        foreach ($templatedata['programs'] as &$prog) {
            $prog->versions = array_values($prog->versions);
            foreach ($prog->versions as &$ver) {
                $ver->cycles = array_values($ver->cycles);
                foreach ($ver->cycles as &$cyc) {
                    $cyc->items = array_values($cyc->items);
                }
            }
        }
    }

    // Render tree template.
    echo $OUTPUT->render_from_template('local_curriculum/tree', $templatedata);
}

echo $OUTPUT->footer();
