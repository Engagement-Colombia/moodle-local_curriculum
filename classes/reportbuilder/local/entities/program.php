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
 * Program entity
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
use core_reportbuilder\local\filters\boolean_select;
use lang_string;
use context_system;

/**
 * Program entity class
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class program extends base {
    /**
     * Database tables that this entity uses
     *
     * @return array
     */
    protected function get_default_tables(): array {
        return [
            'local_curriculum_programs',
            'local_curriculum_versions',
        ];
    }

    /**
     * Entity title
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('program', 'local_curriculum');
    }

    /**
     * Initialise the entity
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $programalias = $this->get_table_alias('local_curriculum_programs');

        // Filters.
        $this->add_filter((new filter(
            text::class,
            'name',
            new lang_string('name'),
            $this->get_entity_name(),
            "{$programalias}.name"
        )));

        $this->add_filter((new filter(
            boolean_select::class,
            'status',
            new lang_string('status_enabled', 'local_curriculum'),
            $this->get_entity_name(),
            "{$programalias}.status"
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
        $programalias = $this->get_table_alias('local_curriculum_programs');

        // ID.
        $columns[] = (new column(
            'id',
            new lang_string('id', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_fields("{$programalias}.id")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_INTEGER);

        // Name.
        $columns[] = (new column(
            'name',
            new lang_string('name'),
            $this->get_entity_name()
        ))
        ->add_fields("{$programalias}.name")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TEXT);

        // Description.
        $columns[] = (new column(
            'description',
            new lang_string('description'),
            $this->get_entity_name()
        ))
        ->add_fields("{$programalias}.description")
        ->set_is_sortable(false)
        ->set_type(column::TYPE_TEXT)
        ->add_callback(static function($value) {
            return format_text($value, FORMAT_MOODLE, ['context' => context_system::instance()]);
        });

        // Status.
        $columns[] = (new column(
            'status',
            new lang_string('status'),
            $this->get_entity_name()
        ))
        ->add_fields("{$programalias}.status")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_INTEGER)
        ->add_callback(function($value) {
            if ($value == 1) {
                return get_string('status_enabled', 'local_curriculum');
            }
            return get_string('status_disabled', 'local_curriculum');
        });

        // Time created.
        $columns[] = (new column(
            'timecreated',
            new lang_string('timecreated', 'core'),
            $this->get_entity_name()
        ))
        ->add_fields("{$programalias}.timecreated")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TIMESTAMP)
        ->add_callback([format::class, 'userdate']);

        // Time modified.
        $columns[] = (new column(
            'timemodified',
            new lang_string('timemodified', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_fields("{$programalias}.timemodified")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TIMESTAMP)
        ->add_callback([format::class, 'userdate']);

        // Additional columns from related tables can be added here, e.g. version count, latest version date, etc.

        // Version count.
        $columns[] = (new column(
            'versioncount',
            new lang_string('versioncount', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_field("(SELECT COUNT(1) FROM {local_curriculum_versions} v WHERE v.programid = {$programalias}.id)", 'versioncount')
        ->set_type(column::TYPE_INTEGER)
        ->set_is_sortable(true);

        return $columns;
    }
}
