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
 * User curriculum dashboard.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$userid = optional_param('userid', 0, PARAM_INT);

require_login();

$title = '';
if ($userid) {
    require_capability('local/curriculum:viewother', context_system::instance());
    $user = core_user::get_user($userid, '*', MUST_EXIST);
    $title = get_string('curriculumforuser', 'local_curriculum', ['name' => fullname($user)]);
} else {
    require_capability('local/curriculum:view', context_system::instance());
    $user = $USER;
    $title = get_string('mycurriculum', 'local_curriculum');
}

$url = new moodle_url('/local/curriculum/index.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mycurriculum', 'local_curriculum'));
$PAGE->set_heading($title);

echo $OUTPUT->header();

$dashboard = new \local_curriculum\output\dashboard($user->id);
$renderer = $PAGE->get_renderer('local_curriculum');
echo $renderer->render_dashboard($dashboard);

echo $OUTPUT->footer();
