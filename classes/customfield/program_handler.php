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
 * Program handler for custom fields
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_curriculum\customfield;

use core_customfield\handler;
use core_customfield\field_controller;

/**
 * Program handler for custom fields.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class program_handler extends handler {
    /**
     * @var program_handler The singleton instance of this handler.
     */
    protected static $singleton;

    /**
     * Returns a singleton.
     *
     * @param int $itemid
     * @return handler
     */
    public static function create(int $itemid = 0): handler {
        if (static::$singleton === null) {
            self::$singleton = new static(0);
        }
        return self::$singleton;
    }

    /**
     * Run reset code after unit tests to reset the singleton usage.
     */
    public static function reset_caches(): void {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('This feature is only intended for use in unit tests');
        }

        static::$singleton = null;
    }

    /**
     * The current user can configure custom fields on this component.
     *
     * @return bool true if the current can configure custom fields, false otherwise
     */
    public function can_configure(): bool {
        return has_capability('local/curriculum:configurecustomfields', $this->get_configuration_context());
    }

    /**
     * The current user can edit custom fields on the given program.
     *
     * @param field_controller $field
     * @param int $instanceid id of the program to test edit permission
     * @return bool true if the current can edit custom field, false otherwise
     */
    public function can_edit(field_controller $field, int $instanceid = 0): bool {
        return has_capability('local/curriculum:editprogram', $this->get_instance_context($instanceid));
    }

    /**
     * The current user can view custom fields on the given program.
     *
     * @param field_controller $field
     * @param int $instanceid id of the program to test view permission
     * @return bool true if the current can view custom field, false otherwise
     */
    public function can_view(field_controller $field, int $instanceid): bool {
        return has_capability('local/curriculum:editprogram', $this->get_instance_context($instanceid));
    }

    /**
     * Context that should be used for new categories created by this handler.
     *
     * @return \context the context for configuration
     */
    public function get_configuration_context(): \context {
        return \context_system::instance();
    }

    /**
     * URL for configuration of the fields on this handler.
     *
     * @return \moodle_url The URL to configure custom fields for this component
     */
    public function get_configuration_url(): \moodle_url {
        return new \moodle_url('/local/curriculum/customfield.php');
    }

    /**
     * Returns the context for the data associated with the given instanceid.
     *
     * @param int $instanceid id of the record to get the context for
     * @return \context the context for the given record
     */
    public function get_instance_context(int $instanceid = 0): \context {
        return \context_system::instance();
    }
}
