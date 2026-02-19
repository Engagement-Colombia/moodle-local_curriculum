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
 * Admin settings for local_curriculum plugin.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create curriculum section under Courses.
    $ADMIN->add(
        'courses',
        new admin_category(
            'local_curriculum',
            new lang_string('pluginname', 'local_curriculum')
        )
    );

    // Add custom fields configuration page.
    $ADMIN->add(
        'local_curriculum',
        new admin_externalpage(
            'local_curriculum_customfield',
            new lang_string('customfieldtitle', 'local_curriculum'),
            new moodle_url('/local/curriculum/customfield.php'),
            'local/curriculum:configurecustomfields'
        )
    );

    // Add manage page.
    $ADMIN->add(
        'local_curriculum',
        new admin_externalpage(
            'local_curriculum_manage',
            new lang_string('manage_title', 'local_curriculum'),
            new moodle_url('/local/curriculum/manage.php'),
            'local/curriculum:manage'
        )
    );

    // Add tree page.
    $ADMIN->add(
        'local_curriculum',
        new admin_externalpage(
            'local_curriculum_tree',
            new lang_string('tree_title', 'local_curriculum'),
            new moodle_url('/local/curriculum/tree.php'),
            'local/curriculum:manage'
        )
    );
}
