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
 * Cycle user entity for report builder.
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
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\select;
use lang_string;
use local_curriculum\local\curriculum;

/**
 * Cycle user entity class.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cycle_user extends base {
    /**
     * Database tables that this entity uses.
     *
     * @return array
     */
    protected function get_default_tables(): array {
        return [
            'local_curriculum_cycle_users',
        ];
    }

    /**
     * Entity title.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('cycleuser', 'local_curriculum');
    }

    /**
     * Initialise the entity.
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $alias = $this->get_table_alias('local_curriculum_cycle_users');

        // Filter: timestart.
        $this->add_filter((new filter(
            date::class,
            'timestart',
            new lang_string('timestart', 'local_curriculum'),
            $this->get_entity_name(),
            "{$alias}.timestart"
        )));

        // Filter: timeend.
        $this->add_filter((new filter(
            date::class,
            'timeend',
            new lang_string('timeend', 'local_curriculum'),
            $this->get_entity_name(),
            "{$alias}.timeend"
        )));

        // Filter: endreason.
        $this->add_filter((new filter(
            select::class,
            'endreason',
            new lang_string('endreason', 'local_curriculum'),
            $this->get_entity_name(),
            "{$alias}.endreason"
        ))
        ->set_options([
            '' => get_string('choosedots'),
            curriculum::ENDREASON_COMPLETED => get_string('endreason_completed', 'local_curriculum'),
            curriculum::ENDREASON_PROGRAM_CHANGE => get_string('endreason_programchange', 'local_curriculum'),
            curriculum::ENDREASON_USER_DELETED => get_string('endreason_userdeleted', 'local_curriculum'),
            curriculum::ENDREASON_HOMOLOGATED => get_string('endreason_homologated', 'local_curriculum'),
        ]));

        return $this;
    }

    /**
     * Returns list of all available columns.
     *
     * @return array
     */
    protected function get_all_columns(): array {
        $columns = [];
        $alias = $this->get_table_alias('local_curriculum_cycle_users');

        // Time start.
        $columns[] = (new column(
            'timestart',
            new lang_string('timestart', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_joins($this->get_joins())
        ->add_fields("{$alias}.timestart")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TIMESTAMP)
        ->add_callback([format::class, 'userdate']);

        // Time end.
        $columns[] = (new column(
            'timeend',
            new lang_string('timeend', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_joins($this->get_joins())
        ->add_fields("{$alias}.timeend")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TIMESTAMP)
        ->add_callback(static function ($value) {
            return $value ? userdate($value) : '';
        });

        // End reason.
        $columns[] = (new column(
            'endreason',
            new lang_string('endreason', 'local_curriculum'),
            $this->get_entity_name()
        ))
        ->add_joins($this->get_joins())
        ->add_fields("{$alias}.endreason")
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TEXT)
        ->add_callback(static function ($value) {
            if (empty($value)) {
                return '';
            }
            $reasons = [
                curriculum::ENDREASON_COMPLETED => get_string('endreason_completed', 'local_curriculum'),
                curriculum::ENDREASON_PROGRAM_CHANGE => get_string('endreason_programchange', 'local_curriculum'),
                curriculum::ENDREASON_USER_DELETED => get_string('endreason_userdeleted', 'local_curriculum'),
                curriculum::ENDREASON_HOMOLOGATED => get_string('endreason_homologated', 'local_curriculum'),
            ];
            return $reasons[$value] ?? $value;
        });

        return $columns;
    }
}
