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
 * Report for curriculum programs
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_curriculum\reportbuilder\local\systemreports;

use core_reportbuilder\system_report;
use core_reportbuilder\local\report\action;
use local_curriculum\reportbuilder\local\entities\program;
use moodle_url;
use pix_icon;
use context_system;
use lang_string;

/**
 * System report for curriculum programs
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class programs extends system_report {
    /**
     * Initialise the report
     */
    protected function initialise(): void {
        global $PAGE;

        // We need to ensure page context is always set, as required by output and string formatting.
        $PAGE->set_context($this->get_context());

        $this->set_default_no_results_notice(new lang_string('noprograms', 'local_curriculum'));

        // Our main entity, it contains all of the column definitions that we need.
        $entity = new program();
        $entitymainalias = $entity->get_table_alias('local_curriculum_programs');

        $this->set_main_table('local_curriculum_programs', $entitymainalias);
        $this->add_entity($entity);
        $this->add_base_fields("{$entitymainalias}.id");

        $this->add_columns_from_entities([
            'program:name',
            'program:status',
            'program:timecreated',
            'program:timemodified',
            'program:versioncount',
        ]);

        $this->add_filters_from_entities(['program:name', 'program:status']);

        // Versions.
        $this->add_action(new action(
            new moodle_url('/local/curriculum/manage.php', ['parentid' => ':id', 'ptype' => 'version']),
            new pix_icon('i/course', get_string('manageversions', 'local_curriculum')),
            []
        ));

        // Actions.
        // Edit.
        $this->add_action(new action(
            new moodle_url('/local/curriculum/manage.php', ['id' => ':id', 'action' => 'edit']),
            new pix_icon('t/edit', get_string('edit')),
            []
        ));

        // Delete.
        $deleteaction = new action(
            new moodle_url('/local/curriculum/manage.php', ['id' => ':id', 'action' => 'delete']),
            new pix_icon('t/delete', get_string('delete')),
            [
                'onclick' => 'return confirm("' . get_string('confirmdeleteprogram', 'local_curriculum') . '");',
                'class' => 'text-danger',
            ]
        );
        $deleteaction->add_callback(function ($row) use ($deleteaction) {
            // Delete action is only available if there are no versions.
            if (!empty($row->versioncount)) {
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
