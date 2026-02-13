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
 * Version entity
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_curriculum\reportbuilder\local\entities;

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\filters\text;
use lang_string;

/**
 * Version entity class
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class version extends base {
    /**
     * Database tables that this entity uses
     *
     * @return array
     */
    protected function get_default_tables(): array {
        return [
            'local_curriculum_versions',
        ];
    }

    /**
     * Entity title
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('version', 'local_curriculum');
    }

    /**
     * Initialise the entity
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // Filters.
        $this->add_filter((new filter(
            text::class,
            'name',
            new lang_string('name'),
            $this->get_entity_name(),
            "{$this->get_table_alias('local_curriculum_versions')}.name"
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
        $versionalias = $this->get_table_alias('local_curriculum_versions');

        // ID.
        $columns[] = (new column(
            'id',
            new lang_string('id', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_fields("{$versionalias}.id")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_INTEGER);

        // Program ID.
        $columns[] = (new column(
            'programid',
            null,
            $this->get_entity_name()
        ))
        ->add_fields("{$versionalias}.programid")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_INTEGER);

        // Name.
        $columns[] = (new column(
            'name',
            new lang_string('name'),
            $this->get_entity_name()
        ))
        ->add_fields("{$versionalias}.name")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TEXT);

        // Start Date.
        $columns[] = (new column(
            'startdate',
            new lang_string('startdate', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_fields("{$versionalias}.startdate")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TIMESTAMP)
        ->add_callback([format::class, 'userdate']);

        // End Date.
        $columns[] = (new column(
            'enddate',
            new lang_string('enddate', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_fields("{$versionalias}.enddate")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TIMESTAMP)
        ->add_callback([format::class, 'userdate']);

        // Cycles count.
        $columns[] = (new column(
            'cyclecount',
            new lang_string('cyclecount', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_field("(SELECT COUNT(1) FROM {local_curriculum_cycles} c WHERE c.versionid = {$versionalias}.id)", 'cyclecount')
        ->set_type(column::TYPE_INTEGER)
        ->set_is_sortable(true);

        return $columns;
    }
}
