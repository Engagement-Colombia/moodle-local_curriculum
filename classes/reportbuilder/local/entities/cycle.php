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
 * Cycle entity
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
use core_reportbuilder\local\filters\number;
use lang_string;
use context_system;

/**
 * Cycle entity class
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cycle extends base {

    /**
     * Database tables that this entity uses
     *
     * @return array
     */
    protected function get_default_tables(): array {
        return [
            'local_curriculum_cycles',
        ];
    }

    /**
     * Entity title
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('cycle', 'local_curriculum');
    }

    /**
     * Initialise the entity
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $cyclealias = $this->get_table_alias('local_curriculum_cycles');

        // Filters.
        $this->add_filter((new filter(
            text::class,
            'name',
            new lang_string('name'),
            $this->get_entity_name(),
            "{$cyclealias}.name"
        )));

        $this->add_filter((new filter(
            number::class,
            'stage',
            new lang_string('stage', 'local_curriculum'),
            $this->get_entity_name(),
            "{$cyclealias}.stage"
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
        $cyclealias = $this->get_table_alias('local_curriculum_cycles');

        // ID.
        $columns[] = (new column(
            'id',
            new lang_string('id', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_fields("{$cyclealias}.id")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_INTEGER);

        // Version ID.
        $columns[] = (new column(
            'versionid',
            null,
            $this->get_entity_name()
        ))
        ->add_fields("{$cyclealias}.versionid")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_INTEGER);

        // Name.
        $columns[] = (new column(
            'name',
            new lang_string('name'),
            $this->get_entity_name()
        ))
        ->add_fields("{$cyclealias}.name")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TEXT);

        // Description.
        $columns[] = (new column(
            'description',
            new lang_string('description'),
            $this->get_entity_name()
        ))
        ->add_fields("{$cyclealias}.description")
        ->set_is_sortable(false)
        ->set_type(column::TYPE_TEXT)
        ->add_callback(static function($value) {
            return format_text($value, FORMAT_MOODLE, ['context' => context_system::instance()]);
        });

        // Duration.
        $columns[] = (new column(
            'duration',
            new lang_string('durationdays', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_fields("{$cyclealias}.duration")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_INTEGER);

        // Stage.
        $columns[] = (new column(
            'stage',
            new lang_string('stage', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_fields("{$cyclealias}.stage")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_INTEGER);

        // Item count.
        $columns[] = (new column(
            'itemcount',
            new lang_string('itemcount', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_field("(SELECT COUNT(1) FROM {local_curriculum_cycle_items} ci WHERE ci.cycleid = {$cyclealias}.id)", 'itemcount')
        ->set_type(column::TYPE_INTEGER)
        ->set_is_sortable(true);

        return $columns;
    }
}
