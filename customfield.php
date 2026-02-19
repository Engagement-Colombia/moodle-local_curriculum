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
 * Custom fields configuration for programs
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_forum\local\managers\capability;

require('../../config.php');

$context = \context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/curriculum/customfield.php'));
$PAGE->set_title(get_string('pluginname', 'local_curriculum'));
$PAGE->set_heading(get_string('customfieldtitle', 'local_curriculum'));

require_login(null, false);

require_capability('local/curriculum:configurecustomfields', $context);
$handler = \local_curriculum\customfield\program_handler::create();

$output = $PAGE->get_renderer('core_customfield');
$fieldsconfig = new \core_customfield\output\management($handler);
echo $output->header();
echo $OUTPUT->render_from_template('local_curriculum/managenav', ['wwwroot' => $CFG->wwwroot]);
echo $output->render($fieldsconfig);
echo $output->footer();
