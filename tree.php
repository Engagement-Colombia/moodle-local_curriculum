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
 * Children are loaded on demand via AJAX when expanding a node.
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

// Only load programs initially. Children are loaded via AJAX.
$programs = $DB->get_records('local_curriculum_programs', null, 'name ASC');

if (empty($programs)) {
    echo $OUTPUT->notification(get_string('noprograms', 'local_curriculum'));
} else {
    $handler = \local_curriculum\customfield\program_handler::create();
    $programsdata = [];

    foreach ($programs as $program) {
        // Load custom fields for the program.
        $fields = $handler->get_editable_fields($program->id);
        $fielddata = \core_customfield\api::get_instance_fields_data($fields, $program->id);

        $customfields = [];
        foreach ($fielddata as $data) {
            $customfields[] = [
                'name' => $data->get_field()->get('name'),
                'value' => $data->export_value(),
            ];
        }

        $programsdata[] = [
            'id' => $program->id,
            'name' => $program->name,
            'description' => $program->description,
            'customfields' => $customfields,
        ];
    }

    $templatedata = [
        'programs' => $programsdata,
        'manageurl' => $CFG->wwwroot,
    ];

    echo $OUTPUT->render_from_template('local_curriculum/tree', $templatedata);
}

echo $OUTPUT->footer();
