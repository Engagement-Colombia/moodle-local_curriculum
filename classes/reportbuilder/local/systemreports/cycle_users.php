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
 * System report for users assigned to curriculum cycles.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_curriculum\reportbuilder\local\systemreports;

use core_reportbuilder\system_report;
use core_reportbuilder\local\entities\user;
use local_curriculum\reportbuilder\local\entities\cycle_user;
use local_curriculum\reportbuilder\local\entities\cycle;
use local_curriculum\reportbuilder\local\entities\version;
use local_curriculum\reportbuilder\local\entities\program;
use context_system;
use lang_string;

/**
 * Cycle users system report.
 *
 * Shows users assigned to curriculum cycles along with program, version and cycle information.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cycle_users extends system_report {
    /**
     * Initialise the report.
     */
    protected function initialise(): void {
        global $PAGE;

        $PAGE->set_context($this->get_context());

        $this->set_default_no_results_notice(new lang_string('nocycleusers', 'local_curriculum'));

        // Main entity: cycle_user.
        $cycleuserentity = new cycle_user();
        $cualias = $cycleuserentity->get_table_alias('local_curriculum_cycle_users');

        $this->set_main_table('local_curriculum_cycle_users', $cualias);
        $this->add_entity($cycleuserentity);
        $this->add_base_fields("{$cualias}.id");

        // Join user entity.
        $userentity = new user();
        $useralias = $userentity->get_table_alias('user');
        $this->add_entity($userentity
            ->add_join("JOIN {user} {$useralias} ON {$useralias}.id = {$cualias}.userid"));

        // Join cycle entity.
        $cycleentity = new cycle();
        $cyclealias = $cycleentity->get_table_alias('local_curriculum_cycles');
        $cyclejoin = "JOIN {local_curriculum_cycles} {$cyclealias} ON {$cyclealias}.id = {$cualias}.cycleid";
        $this->add_entity($cycleentity->add_join($cyclejoin));

        // Join version entity (needs cycle join first).
        $versionentity = new version();
        $versionalias = $versionentity->get_table_alias('local_curriculum_versions');
        $versionjoin = "JOIN {local_curriculum_versions} {$versionalias}
                           ON {$versionalias}.id = {$cyclealias}.versionid";
        $this->add_entity($versionentity
            ->add_join($cyclejoin)
            ->add_join($versionjoin));

        // Join program entity (needs cycle + version joins first).
        $programentity = new program();
        $programalias = $programentity->get_table_alias('local_curriculum_programs');
        $this->add_entity($programentity
            ->add_join($cyclejoin)
            ->add_join($versionjoin)
            ->add_join("JOIN {local_curriculum_programs} {$programalias}
                           ON {$programalias}.id = {$versionalias}.programid"));

        // Columns.
        $this->add_columns_from_entities([
            'user:fullnamewithlink',
            'program:name',
            'version:name',
            'cycle:name',
            'cycle:stage',
            'cycle_user:timestart',
            'cycle_user:timeend',
            'cycle_user:endreason',
        ]);

        // Override column titles for disambiguation.
        $this->get_column('program:name')->set_title(new lang_string('program', 'local_curriculum'));
        $this->get_column('version:name')->set_title(new lang_string('version', 'local_curriculum'));
        $this->get_column('cycle:name')->set_title(new lang_string('cycle', 'local_curriculum'));

        // Default sorting.
        $this->set_initial_sort_column('user:fullnamewithlink', SORT_ASC);

        // Filters.
        $this->add_filters_from_entities([
            'user:fullname',
            'program:name',
            'version:name',
            'cycle:name',
            'cycle_user:timestart',
            'cycle_user:timeend',
            'cycle_user:endreason',
        ]);

        // Override filter titles for disambiguation.
        $this->get_filter('program:name')->set_header(new lang_string('program', 'local_curriculum'));
        $this->get_filter('version:name')->set_header(new lang_string('version', 'local_curriculum'));
        $this->get_filter('cycle:name')->set_header(new lang_string('cycle', 'local_curriculum'));

        $this->set_downloadable(true, get_string('reportcycleusers', 'local_curriculum'));
    }

    /**
     * Permission check.
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('local/curriculum:viewreport', context_system::instance());
    }
}
