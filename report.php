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
 * Curriculum cycle users report page.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/curriculum:viewreport', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/curriculum/report.php'));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('reportcycleusers', 'local_curriculum'));
$PAGE->set_heading(get_string('reportcycleusers', 'local_curriculum'));

$report = \core_reportbuilder\system_report_factory::create(
    \local_curriculum\reportbuilder\local\systemreports\cycle_users::class,
    $context
);

echo $OUTPUT->header();
echo $report->output();
echo $OUTPUT->footer();
