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
 * TODO describe file manage
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_curriculum_manage');

$context = context_system::instance();
require_capability('local/curriculum:manage', $context);

$ptype = optional_param('ptype', 'program', PARAM_ALPHA);

if (!in_array($ptype, ['program', 'version', 'cycle', 'item'])) {
    throw new moodle_exception('error_invalidpage', 'local_curriculum');
}

$currentclass = 'local_curriculum\local\pages\\' . $ptype;
$currentpage = new $currentclass();

$currentpage->go_preaction();

$pageparams = array_merge(['ptype' => $ptype], $currentpage->get_pageparams());
$title = $currentpage->get_title();

$PAGE->set_url(new moodle_url('/local/curriculum/manage.php', $pageparams));
$PAGE->set_title(get_string('pluginname', 'local_curriculum'));
$PAGE->set_heading(get_string('pluginname', 'local_curriculum'));

echo $OUTPUT->header();

$titleh1 = $currentpage->get_managetitle();
echo $OUTPUT->heading($titleh1);

$currentpage->go_action();

echo $OUTPUT->footer();
