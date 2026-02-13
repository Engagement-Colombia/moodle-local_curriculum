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

use core_reportbuilder\system_report_factory;
use local_curriculum\reportbuilder\local\systemreports\versions as versionsreport;
use local_curriculum\local\controller;

/**
 * Class version
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class version extends managepage {
    /**
     * Page key identifier.
     * @var string
     */
    public const PAGEKEY = 'version';

    /**
     * Get page title.
     *
     * @return string
     */
    public function get_title(): string {
        return get_string('versionstitle', 'local_curriculum');
    }

    /**
     * Get the manage title for the page.
     *
     * @return string
     */
    public function get_managetitle(): string {
        global $DB;
        $version = $DB->get_field('local_curriculum_versions', 'name', ['id' => $this->parentid], MUST_EXIST);
        return get_string('manage_' . self::PAGEKEY, 'local_curriculum') . ': ' . format_string($version);
    }

    /**
     * Process the current action before the page is rendered.
     */
    public function go_preaction(): void {
        global $DB;

        $context = \context_system::instance();

        switch ($this->action) {
            case 'add':
                $form = new \local_curriculum\form\version_form(null, ['context' => $context]);
                $programid = $this->parentid;
                if ($form->is_cancelled()) {
                    if (!$programid) {
                        if (empty($this->id)) {
                            throw new \moodle_exception('error_invalidid', 'local_curriculum');
                        }
                        $programid = $DB->get_field('local_curriculum_versions', 'programid', ['id' => $this->id]);
                    }
                    $redirecturl = new \moodle_url(
                        '/local/curriculum/manage.php',
                        ['ptype' => self::PAGEKEY, 'parentid' => $programid]
                    );
                    redirect($redirecturl);

                } else if ($data = $form->get_data()) {
                    $record = new \stdClass();
                    $record->name = trim($data->name);
                    $record->startdate = $data->startdate;
                    $record->enddate = $data->enddate;
                    $record->programid = $data->programid;
                    $record->timemodified = time();

                    if (!empty($data->id)) {
                        $record->id = $data->id;
                        $DB->update_record('local_curriculum_versions', $record);
                        $versionid = $data->id;
                        $created = false;
                    } else {
                        $record->timecreated = time();
                        $versionid = $DB->insert_record('local_curriculum_versions', $record);
                        $created = true;
                    }

                    if ($created) {
                        \local_curriculum\event\version_created::create([
                            'objectid' => $versionid,
                            'context' => $context,
                        ])->trigger();
                    } else {
                        \local_curriculum\event\version_updated::create([
                            'objectid' => $versionid,
                            'context' => $context,
                        ])->trigger();
                    }

                    // Redirect back to the version list.
                    $redirecturl = new \moodle_url(
                        '/local/curriculum/manage.php',
                        [
                            'ptype' => self::PAGEKEY,
                            'parentid' => $record->programid,
                        ]
                    );
                    redirect($redirecturl);
                }
                break;
            case 'delete':
                $id = required_param('id', PARAM_INT);
                $version = $DB->get_record('local_curriculum_versions', ['id' => $id], '*', MUST_EXIST);

                // Check cycles.
                $cycleslinked = $DB->record_exists('local_curriculum_cycles', ['versionid' => $version->id]);
                if ($cycleslinked) {
                    throw new \moodle_exception('error_cannotdeleteversion', 'local_curriculum');
                }

                $DB->delete_records('local_curriculum_versions', ['id' => $id]);
                $fs = get_file_storage();
                $fs->delete_area_files($context->id, 'local_curriculum', 'version_description', $id);

                \local_curriculum\event\version_deleted::create([
                    'objectid' => $id,
                    'context' => $context,
                ])->trigger();

                $redirecturl = new \moodle_url(
                    '/local/curriculum/manage.php',
                    ['ptype' => self::PAGEKEY, 'parentid' => $version->programid]
                );
                redirect($redirecturl);
                break;
        }
    }

    /**
     * Process the current action
     */
    public function go_action(): void {
        global $OUTPUT, $DB;

        $context = \context_system::instance();

        switch ($this->action) {
            case 'add':
            case 'edit':
                $id = $this->id;
                if ($this->action === 'edit' && !$id) {
                    throw new \moodle_exception('error_invalidid', 'local_curriculum');
                }

                $form = new \local_curriculum\form\version_form(null, ['context' => $context]);

                if ($id) {
                    $record = $DB->get_record('local_curriculum_versions', ['id' => $id], '*', MUST_EXIST);
                    $form->set_data($record);
                } else {
                    $data = new \stdClass();
                    $data->programid = $this->parentid;
                    $form->set_data($data);
                }

                echo $form->render();
                break;
            default:
                $report = system_report_factory::create(
                    versionsreport::class,
                    $context,
                    '',
                    '',
                    0,
                    ['programid' => $this->parentid]
                );
                echo $report->output();

                $params = ['action' => 'add', 'ptype' => self::PAGEKEY, 'parentid' => $this->parentid];
                $newbuttonurl = new \moodle_url('/local/curriculum/manage.php', $params);

                echo $OUTPUT->single_button(
                    $newbuttonurl,
                    new \lang_string('addelement', 'local_curriculum'),
                    'get'
                );

                $params = ['ptype' => program::PAGEKEY];
                $backbuttonurl = new \moodle_url('/local/curriculum/manage.php', $params);

                echo $OUTPUT->single_button(
                    $backbuttonurl,
                    get_string('back', 'local_curriculum'),
                    'get'
                );
        }
    }
}
