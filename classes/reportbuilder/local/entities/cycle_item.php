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
 * Cycle item entity
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_curriculum\reportbuilder\local\entities;

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\number;
use lang_string;

/**
 * Cycle item entity class
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cycle_item extends base {
    /**
     * Database tables that this entity uses
     *
     * @return array
     */
    protected function get_default_tables(): array {
        return [
            'local_curriculum_cycle_items',
        ];
    }

    /**
     * Entity title
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('item', 'local_curriculum');
    }

    /**
     * Initialise the entity
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $itemaias = $this->get_table_alias('local_curriculum_cycle_items');

        // Filters.
        $this->add_filter((new filter(
            text::class,
            'coursecode',
            new lang_string('coursecode', 'local_curriculum'),
            $this->get_entity_name(),
            "{$itemaias}.coursecode"
        )));

        $this->add_filter((new filter(
            text::class,
            'grouptemplate',
            new lang_string('grouptemplate', 'local_curriculum'),
            $this->get_entity_name(),
            "{$itemaias}.grouptemplate"
        )));

        $this->add_filter((new filter(
            number::class,
            'validity',
            new lang_string('validitydays', 'local_curriculum'),
            $this->get_entity_name(),
            "{$itemaias}.validity"
        )));

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return array
     */
    protected function get_all_columns(): array {
        $columns = [];
        $itemalias = $this->get_table_alias('local_curriculum_cycle_items');

        // ID.
        $columns[] = (new column(
            'id',
            new lang_string('id', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_fields("{$itemalias}.id")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_INTEGER);

        // Cycle ID.
        $columns[] = (new column(
            'cycleid',
            null,
            $this->get_entity_name()
        ))
        ->add_fields("{$itemalias}.cycleid")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_INTEGER);

        // Course code.
        $columns[] = (new column(
            'coursecode',
            new lang_string('coursecode', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_fields("{$itemalias}.coursecode")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TEXT);

        // Group template.
        $columns[] = (new column(
            'grouptemplate',
            new lang_string('grouptemplate', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_fields("{$itemalias}.grouptemplate")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TEXT);

        // Conditions.
        $columns[] = (new column(
            'conditions',
            new lang_string('conditions', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_fields("{$itemalias}.conditions")
        ->set_is_sortable(false)
        ->set_type(column::TYPE_TEXT);

        // Validity.
        $columns[] = (new column(
            'validity',
            new lang_string('validitydays', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_fields("{$itemalias}.validity")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_INTEGER);

        return $columns;
    }
}
