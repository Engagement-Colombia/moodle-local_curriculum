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

namespace local_curriculum\local\pages;

/**
 * Class managepage
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class managepage {
    /**
     * Page key identifier.
     * @var string
     */
    public const PAGEKEY = '';

    /**
     * Action being performed on the page, if any.
     * @var string
     */
    public $action = '';

    /**
     * ID of the item being managed, if any.
     * @var int
     */
    public $id = 0;

    /**
     * ID of the parent item, if any (e.g. version ID for a program page).
     * @var int
     */
    public $parentid = 0;

    /**
     * Constructor
     */
    public function __construct() {
        $action = optional_param('action', '', PARAM_ALPHA);
        $id = optional_param('id', 0, PARAM_INT);
        $parentid = optional_param('parentid', 0, PARAM_INT);

        $this->action = $action;
        $this->id = $id;
        $this->parentid = $parentid;
    }

    /**
     * Get page title.
     *
     */
    public function get_title(): string {
        return get_string('pluginname', 'local_curriculum');
    }

    /**
     * Get the manage title for the page.
     *
     * @return string
     */
    public function get_managetitle(): string {
        return get_string('pluginname', 'local_curriculum');
    }

    /**
     * Process the current action before the page is rendered.
     */
    public function go_preaction(): void {
        // To be implemented by child classes.
    }

    /**
     * Process the current action
     */
    public function go_action(): void {
        // To be implemented by child classes.
    }

    /**
     * Get page parameters.
     *
     * @return array
     */
    public function get_pageparams(): array {
        return [];
    }
}
