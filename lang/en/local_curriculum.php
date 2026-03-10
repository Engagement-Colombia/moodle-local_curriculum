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
 * English language pack for Curriculum
 *
 * @package    local_curriculum
 * @category   string
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addelement'] = 'new';
$string['autocreategroups'] = 'Auto-create groups';
$string['autocreategroups_desc'] = 'If enabled, groups that do not exist in the course will be created automatically. If disabled, a notification will be sent to the configured email addresses instead.';
$string['back'] = 'Back';
$string['collapseall'] = 'Collapse all';
$string['completed'] = 'Completed';
$string['conditions'] = 'Conditions (one per line, format: field=value)';
$string['configure'] = 'Configure';
$string['confirmdeletecycle'] = 'Are you sure you want to delete this cycle? This action cannot be undone.';
$string['confirmdeleteitem'] = 'Are you sure you want to delete this item? This action cannot be undone.';
$string['confirmdeleteprogram'] = 'Are you sure you want to delete this program? This action cannot be undone.';
$string['confirmdeleteversion'] = 'Are you sure you want to delete this version? This action cannot be undone.';
$string['coursecode'] = 'Course code';
$string['courses'] = 'Courses';
$string['curriculum:configurecustomfields'] = 'Configure curriculum custom fields';
$string['curriculum:manage'] = 'Manage curriculum';
$string['curriculum:view'] = 'View curriculum';
$string['curriculum:viewother'] = 'View all curriculum';
$string['curriculum:viewreport'] = 'View curriculum report';
$string['curriculumforuser'] = 'Curriculum for {$a->name}';
$string['customfieldtitle'] = 'Program custom fields';
$string['cycle'] = 'Cycle';
$string['cyclecount'] = 'Cycles';
$string['cyclestitle'] = 'Curriculum cycles';
$string['cycleuser'] = 'Cycle assignment';
$string['day'] = 'Day';
$string['daysremaining'] = 'days remaining';
$string['daysunit'] = 'days';
$string['docs_pagenotfound'] = 'Page not found';
$string['docs_title'] = 'Documentation';
$string['durationdays'] = 'Duration (days)';
$string['enddate'] = 'End date';
$string['endreason'] = 'End reason';
$string['endreason_completed'] = 'Completed';
$string['endreason_homologated'] = 'Homologated';
$string['endreason_programchange'] = 'Program change';
$string['endreason_userdeleted'] = 'User deleted';
$string['error_cannotdeletecycle'] = 'Cannot delete cycle because it has associated items. Please delete the items first.';
$string['error_cannotdeleteitem'] = 'Cannot delete item because it has associated user enrollments. Please delete the user enrollments first.';
$string['error_cannotdeleteprogram'] = 'Cannot delete program because it has associated versions. Please delete the versions first.';
$string['error_cannotdeleteversion'] = 'Cannot delete version because it has associated cycles. Please delete the cycles first.';
$string['error_invalidid'] = 'Invalid ID';
$string['error_invalidpage'] = 'Invalid page type';
$string['eventcycle_created'] = 'Curriculum cycle created';
$string['eventcycle_deleted'] = 'Curriculum cycle deleted';
$string['eventcycle_updated'] = 'Curriculum cycle updated';
$string['eventitem_created'] = 'Curriculum item created';
$string['eventitem_deleted'] = 'Curriculum item deleted';
$string['eventitem_updated'] = 'Curriculum item updated';
$string['eventprogram_created'] = 'Curriculum program created';
$string['eventprogram_deleted'] = 'Curriculum program deleted';
$string['eventprogram_updated'] = 'Curriculum program updated';
$string['eventversion_created'] = 'Curriculum version created';
$string['eventversion_deleted'] = 'Curriculum version deleted';
$string['eventversion_updated'] = 'Curriculum version updated';
$string['expandall'] = 'Expand all';
$string['filtertree'] = 'Filter by keyword...';
$string['grouptemplate'] = 'Group template';
$string['id'] = 'ID';
$string['inprogress'] = 'In progress';
$string['item'] = 'Item';
$string['itemcount'] = 'Items';
$string['itemstitle'] = 'Curriculum items';
$string['loading'] = 'Loading...';
$string['locked'] = 'Locked';
$string['manage_cycle'] = 'Manage cycle';
$string['manage_item'] = 'Manage item';
$string['manage_title'] = 'Manage curriculum';
$string['manage_version'] = 'Manage version';
$string['managecycles'] = 'Manage cycles';
$string['manageitems'] = 'Manage items';
$string['manageversions'] = 'Manage versions';
$string['messageprovider:newgroupmember'] = 'New member added to group';
$string['mycurriculum'] = 'My curriculum';
$string['newgroupcreated_body'] = 'A new group "{$a->groupname}" has been created in the course "{$a->coursename}" by the curriculum plugin. You can view the course at: {$a->courseurl}';
$string['newgroupcreated_subject'] = 'New group created in course';
$string['newgroupmember_body'] = 'The user "{$a->userfullname}" has been added to the group "{$a->groupname}" in the course "{$a->coursename}". You can view the group members at: {$a->groupurl}';
$string['newgroupmember_subject'] = 'New member in group "{$a->groupname}"';
$string['newgroupnotifyemails'] = 'New group notification emails';
$string['newgroupnotifyemails_desc'] = 'List of email addresses (one per line) to notify when a new group is automatically created in a course.';
$string['newgrouppending_body'] = 'The group "{$a->groupname}" does not exist in the course "{$a->coursename}" and needs to be created manually. You can manage the course groups at: {$a->courseurl}';
$string['newgrouppending_subject'] = 'Group pending creation in course';
$string['nocurriculum'] = 'You are not enrolled in any curriculum program.';
$string['nocycles'] = 'No cycles found';
$string['nocycleusers'] = 'No users assigned to cycles';
$string['noitems'] = 'No items found';
$string['noprograms'] = 'No programs found';
$string['notstarted'] = 'Not started';
$string['noversions'] = 'No versions found';
$string['of'] = 'of';
$string['oftimeelapsed'] = 'of time elapsed';
$string['overallprogress'] = 'Overall progress';
$string['pluginname'] = 'Curriculum';
$string['program'] = 'Program';
$string['programhistory'] = 'Program history';
$string['programstitle'] = 'Curriculum programs';
$string['reportcycleusers'] = 'Curriculum cycle users';
$string['settings'] = 'Settings';
$string['stage'] = 'Stage';
$string['startdate'] = 'Start date';
$string['status'] = 'Status';
$string['status_disabled'] = 'Disabled';
$string['status_enabled'] = 'Enabled';
$string['task_activateusercycles'] = 'Activate user cycles in curriculum programs';
$string['timeend'] = 'End date';
$string['timemodified'] = 'Last modified';
$string['timeoverdue'] = 'Overdue';
$string['timestart'] = 'Start date';
$string['tree_title'] = 'Curriculum tree';
$string['validitydays'] = 'Validity (days)';
$string['version'] = 'Version';
$string['versioncount'] = 'Versions';
$string['versionstitle'] = 'Curriculum versions';
$string['view_master'] = 'Master view';
$string['view_tree'] = 'Tree view';
