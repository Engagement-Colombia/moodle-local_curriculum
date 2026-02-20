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
 * External function to get items for a cycle.
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
 * External function to get items for a given cycle.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_items extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cycleid' => new external_value(PARAM_INT, 'The cycle ID'),
        ]);
    }

    /**
     * Returns items for the given cycle.
     *
     * @param int $cycleid The cycle ID.
     * @return array List of items.
     */
    public static function execute(int $cycleid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['cycleid' => $cycleid]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/curriculum:manage', $context);

        $items = $DB->get_records('local_curriculum_cycle_items', ['cycleid' => $params['cycleid']], 'id ASC');

        $result = [];
        foreach ($items as $item) {
            $result[] = [
                'id' => (int) $item->id,
                'coursecode' => $item->coursecode,
                'grouptemplate' => $item->grouptemplate ?? '',
                'validity' => (int) $item->validity,
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
                'id' => new external_value(PARAM_INT, 'Item ID'),
                'coursecode' => new external_value(PARAM_TEXT, 'Course code'),
                'grouptemplate' => new external_value(PARAM_TEXT, 'Group template'),
                'validity' => new external_value(PARAM_INT, 'Validity in days'),
            ])
        );
    }
}
