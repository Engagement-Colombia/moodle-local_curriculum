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
 * Library functions for local_curriculum.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add curriculum link to the user profile navigation.
 *
 * @param core_user\output\myprofile\tree $tree The navigation tree.
 * @param stdClass $user The user object.
 * @param bool $iscurrentuser Whether the user is the current user.
 * @param stdClass|null $course The course object.
 */
function local_curriculum_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    $params = [];
    if (!$iscurrentuser) {
        if (!has_capability('local/curriculum:viewother', context_system::instance())) {
            return;
        }
        $params['userid'] = $user->id;
    } else {
        if (!has_capability('local/curriculum:view', context_system::instance())) {
            return;
        }
    }

    $url = new moodle_url('/local/curriculum/index.php', $params);
    $node = new core_user\output\myprofile\node(
        'miscellaneous',
        'curriculum',
        get_string('mycurriculum', 'local_curriculum'),
        null,
        $url,
    );
    $tree->add_node($node);
}
