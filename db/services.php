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
 * External functions and service definitions.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_curriculum_get_versions' => [
        'classname' => '\local_curriculum\external\get_versions',
        'classpath' => '',
        'description' => 'Get versions for a given program.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_curriculum_get_cycles' => [
        'classname' => '\local_curriculum\external\get_cycles',
        'classpath' => '',
        'description' => 'Get cycles for a given version.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_curriculum_get_items' => [
        'classname' => '\local_curriculum\external\get_items',
        'classpath' => '',
        'description' => 'Get items for a given cycle.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
