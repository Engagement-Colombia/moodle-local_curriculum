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
 * Report for curriculum cycle items
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_curriculum\reportbuilder\local\systemreports;

use core_reportbuilder\system_report;
use core_reportbuilder\local\report\action;
use local_curriculum\reportbuilder\local\entities\item;
use moodle_url;
use pix_icon;
use context_system;
use lang_string;

defined('MOODLE_INTERNAL') || die();

/**
 * System report for curriculum items
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class items extends system_report {

    /**
     * Initialise the report
     */
    protected function initialise(): void {
        global $PAGE;

        // We need to ensure page context is always set, as required by output and string formatting.
        $PAGE->set_context($this->get_context());

        // Our main entity, it contains all of the column definitions that we need.
        $entity = new item();
        $itemalias = $entity->get_table_alias('local_curriculum_cycle_items');

        $this->set_main_table('local_curriculum_cycle_items', $itemalias);
        $this->add_entity($entity);

        // Filter by cycle ID.
        $this->add_base_condition_simple("{$itemalias}.cycleid", $this->get_parameter('cycleid', 0, PARAM_INT));

        $this->add_columns_from_entities([
            'item:coursecode',
            'item:grouptemplate',
            'item:conditions'
        ]);

        $this->add_filters_from_entities([
            'item:coursecode'
        ]);

        // Action: Edit.
        $this->add_action(new action(
            new moodle_url('/local/curriculum/manage.php', [
                'id' => ':id',
                'action' => 'edit',
                'ptype' => 'item',
                'cycleid' => ':cycleid'
            ]),
            new pix_icon('t/edit', get_string('edit')),
            [],
            true
        ));
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
