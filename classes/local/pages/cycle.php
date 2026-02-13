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
use local_curriculum\reportbuilder\local\systemreports\cycles as cyclesreport;
use local_curriculum\local\controller;

/**
 * Class cycle
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cycle extends managepage {
    /**
     * Page key identifier.
     * @var string
     */
    public const PAGEKEY = 'cycle';

    /**
     * Cycle constructor.
     */
    public function __construct() {
        parent::__construct();

        if (empty($this->parentid) && empty($this->id) && $this->action !== 'delete') {
            throw new \moodle_exception('error_invalidparentid', 'local_curriculum');
        }
    }

    /**
     * Get page title.
     *
     * @return string
     */
    public function get_title(): string {
        return get_string('cyclestitle', 'local_curriculum');
    }

    /**
     * Get the manage title for the page.
     *
     * @return string
     */
    public function get_managetitle(): string {
        global $DB;
        $cycle = $DB->get_field('local_curriculum_cycles', 'name', ['id' => $this->parentid], MUST_EXIST);
        return get_string('manage_' . self::PAGEKEY, 'local_curriculum') . ': ' . format_string($cycle);
    }

    /**
     * Process the current action before the page is rendered.
     */
    public function go_preaction(): void {
        global $DB;

        $context = \context_system::instance();

        switch ($this->action) {
            case 'add':
                $form = new \local_curriculum\form\cycle_form(null, ['context' => $context]);
                if ($form->is_cancelled()) {
                     $versionid = $this->parentid;
                    if (!$versionid && $this->id) {
                        $versionid = $DB->get_field('local_curriculum_cycles', 'versionid', ['id' => $this->id]);
                    }
                    $redirecturl = new \moodle_url('/local/curriculum/manage.php', ['ptype' => self::PAGEKEY, 'parentid' => $versionid]);
                    redirect($redirecturl);

                } else if ($data = $form->get_data()) {
                    $record = new \stdClass();
                    $record->name = trim($data->name);
                    $record->durationdays = $data->durationdays;
                    $record->stage = $data->stage;
                    $record->versionid = $data->versionid;
                    $record->timemodified = time();

                    if (!empty($data->id)) {
                        $record->id = $data->id;
                        $DB->update_record('local_curriculum_cycles', $record);
                        $cycleid = $data->id;
                        $created = false;
                    } else {
                        $record->timecreated = time();
                        $cycleid = $DB->insert_record('local_curriculum_cycles', $record);
                        $created = true;
                    }

                    // Save description with files.
                    $editoroptions = controller::get_editoroptions();

                    $data = file_postupdate_standard_editor(
                        $data,
                        'description',
                        $editoroptions,
                        $context,
                        'local_curriculum',
                        'cycle_description',
                        $cycleid
                    );

                    $update = new \stdClass();
                    $update->id = $cycleid;
                    $update->description = $data->description;
                    $update->descriptionformat = $data->descriptionformat ?? FORMAT_HTML;
                    $DB->update_record('local_curriculum_cycles', $update);

                    if ($created) {
                        \local_curriculum\event\cycle_created::create([
                            'objectid' => $cycleid,
                            'context' => $context,
                        ])->trigger();
                    } else {
                        \local_curriculum\event\cycle_updated::create([
                            'objectid' => $cycleid,
                            'context' => $context,
                        ])->trigger();
                    }

                    // Redirect back to the cycle list.
                    $redirecturl = new \moodle_url('/local/curriculum/manage.php', ['ptype' => self::PAGEKEY, 'parentid' => $record->versionid]);
                    redirect($redirecturl);
                }
                break;
            case 'delete':
                $id = required_param('id', PARAM_INT);
                $cycle = $DB->get_record('local_curriculum_cycles', ['id' => $id], '*', MUST_EXIST);

                // Check items.
                $itemslinked = $DB->record_exists('local_curriculum_items', ['cycleid' => $cycle->id]);
                if ($itemslinked) {
                    throw new \moodle_exception('error_cannotdeletecycle', 'local_curriculum');
                }

                $DB->delete_records('local_curriculum_cycles', ['id' => $id]);
                $fs = get_file_storage();
                $fs->delete_area_files($context->id, 'local_curriculum', 'cycle_description', $id);

                \local_curriculum\event\cycle_deleted::create([
                    'objectid' => $id,
                    'context' => $context,
                ])->trigger();

                $redirecturl = new \moodle_url('/local/curriculum/manage.php', ['ptype' => self::PAGEKEY, 'parentid' => $cycle->versionid]);
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
                $id = null;
                if ($this->action === 'edit') {
                    $id = required_param('id', PARAM_INT);
                }

                $form = new \local_curriculum\form\cycle_form(null, ['context' => $context]);

                if ($id) {
                    $record = $DB->get_record('local_curriculum_cycles', ['id' => $id], '*', MUST_EXIST);
                    $editoroptions = controller::get_editoroptions();
                    $record = file_prepare_standard_editor(
                        $record,
                        'description',
                        $editoroptions,
                        $context,
                        'local_curriculum',
                        'cycle_description',
                        $record->id
                    );
                    $form->set_data($record);
                } else {
                    $data = new \stdClass();
                    $data->versionid = $this->parentid;
                    $form->set_data($data);
                }

                echo $form->render();
                break;
            default:
                $report = system_report_factory::create(cyclesreport::class, $context, '', '', 0, ['versionid' => $this->parentid]);
                echo $report->output();

                $params = ['action' => 'add', 'ptype' => self::PAGEKEY, 'parentid' => $this->parentid];
                $newbuttonurl = new \moodle_url('/local/curriculum/manage.php', $params);

                echo $OUTPUT->single_button(
                    $newbuttonurl,
                    new \lang_string('addelement', 'local_curriculum'),
                    'get'
                );

                $params = ['ptype' => version::PAGEKEY, 'id' => $this->parentid];
                $backbuttonurl = new \moodle_url('/local/curriculum/manage.php', $params);

                echo $OUTPUT->single_button(
                    $backbuttonurl,
                    get_string('back', 'local_curriculum'),
                    'get'
                );
        }
    }
}
