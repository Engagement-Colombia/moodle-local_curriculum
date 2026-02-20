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
 * External function to get cycles for a version.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_curriculum\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function to get cycles for a given version.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_cycles extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'versionid' => new external_value(PARAM_INT, 'The version ID'),
        ]);
    }

    /**
     * Returns cycles for the given version.
     *
     * @param int $versionid The version ID.
     * @return array List of cycles.
     */
    public static function execute(int $versionid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['versionid' => $versionid]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/curriculum:manage', $context);

        $cycles = $DB->get_records('local_curriculum_cycles', ['versionid' => $params['versionid']], 'stage ASC, name ASC');

        $result = [];
        foreach ($cycles as $cycle) {
            $result[] = [
                'id' => (int) $cycle->id,
                'name' => $cycle->name,
                'description' => $cycle->description ?? '',
                'duration' => (int) $cycle->duration,
                'stage' => (int) $cycle->stage,
            ];
        }

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Cycle ID'),
                'name' => new external_value(PARAM_TEXT, 'Cycle name'),
                'description' => new external_value(PARAM_RAW, 'Cycle description'),
                'duration' => new external_value(PARAM_INT, 'Duration in days'),
                'stage' => new external_value(PARAM_INT, 'Stage number'),
            ])
        );
    }
}
