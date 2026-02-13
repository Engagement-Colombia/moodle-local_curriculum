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
 * Report for curriculum cycles
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_curriculum\reportbuilder\local\systemreports;

use core_reportbuilder\system_report;
use core_reportbuilder\local\report\action;
use local_curriculum\reportbuilder\local\entities\cycle;
use local_curriculum\local\pages\cycle as cyclepage;
use moodle_url;
use pix_icon;
use context_system;
use lang_string;

/**
 * System report for curriculum cycles
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cycles extends system_report {
    /**
     * Initialise the report
     */
    protected function initialise(): void {
        global $PAGE;

        // We need to ensure page context is always set, as required by output and string formatting.
        $PAGE->set_context($this->get_context());

        $this->set_default_no_results_notice(new lang_string('nocycles', 'local_curriculum'));

        // Our main entity, it contains all of the column definitions that we need.
        $entity = new cycle();
        $entitymainalias = $entity->get_table_alias('local_curriculum_cycles');

        $this->set_main_table('local_curriculum_cycles', $entitymainalias);
        $this->add_entity($entity);
        $this->add_base_fields("{$entitymainalias}.id");
        $this->add_base_fields("{$entitymainalias}.versionid");

        // Filter by version ID (essential for the master-detail view).
        $this->add_base_condition_simple("{$entitymainalias}.versionid", $this->get_parameter('versionid', 0, PARAM_INT));

        $this->add_columns_from_entities([
            'cycle:name',
            'cycle:duration',
            'cycle:stage',
            'cycle:description',
            'cycle:itemcount',
        ]);

        $this->add_filters_from_entities([
            'cycle:name',
            'cycle:stage',
        ]);

        // Action: Manage Items.
        $this->add_action(new action(
            new moodle_url('/local/curriculum/manage.php', [
                'parentid' => ':id',
                'ptype' => \local_curriculum\local\pages\cycle_item::PAGEKEY,
            ]),
            new pix_icon('i/settings', get_string('manageitems', 'local_curriculum')),
            []
        ));

        // Action: Edit.
        $this->add_action(new action(
            new moodle_url('/local/curriculum/manage.php', [
                'id' => ':id',
                'action' => 'edit',
                'ptype' => 'cycle',
                'parentid' => ':versionid',
            ]),
            new pix_icon('t/edit', get_string('edit')),
            []
        ));

        // Delete.
        $deleteaction = new action(
            new moodle_url('/local/curriculum/manage.php', ['id' => ':id', 'action' => 'delete', 'ptype' => cyclepage::PAGEKEY]),
            new pix_icon('t/delete', get_string('delete')),
            [
                'onclick' => 'return confirm("' . get_string('confirmdeletecycle', 'local_curriculum') . '");',
                'class' => 'text-danger',
            ]
        );
        $deleteaction->add_callback(function ($row) use ($deleteaction) {
            // Delete action is only available if there are no items.
            if (!empty($row->itemcount)) {
                return null;
            }

            return $deleteaction;
        });
        $this->add_action($deleteaction);
    }

    /**
     * Permission check
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('local/curriculum:manage', context_system::instance());
    }
}
