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
use local_curriculum\reportbuilder\local\systemreports\programs as programsreport;
use local_curriculum\local\controller;

/**
 * Class programs
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class program extends managepage {
    /**
     * Page key identifier.
     * @var string
     */
    public const PAGEKEY = 'program';

    /**
     * Get page title.
     *
     * @return string
     */
    public function get_title(): string {
        return get_string('programstitle', 'local_curriculum');
    }

    /**
     * Get the manage title for the page.
     *
     * @param int $parentid
     * @return string
     */
    public function get_managetitle(): string {
        return get_string('programstitle', 'local_curriculum');
    }

    /**
     * Process the current action before the page is rendered.
     */
    public function go_preaction(): void {
        global $DB;

        $context = \context_system::instance();

        switch ($this->action) {
            case 'add':
                $form = new \local_curriculum\form\program_form(null, ['context' => $context]);
                if ($form->is_cancelled()) {
                    // Redirect back to the program list.
                    $redirecturl = new \moodle_url('/local/curriculum/manage.php', ['ptype' => self::PAGEKEY]);
                    redirect($redirecturl);
                } else if ($data = $form->get_data()) {
                    $record = new \stdClass();
                    $record->name = trim($data->name);
                    $record->status = !empty($data->status);
                    $record->timemodified = time();

                    if (!empty($data->id)) {
                        $record->id = $data->id;
                        $DB->update_record('local_curriculum_programs', $record);
                        $programid = $data->id;
                        $created = false;
                    } else {
                        $record->timecreated = time();
                        $programid = $DB->insert_record('local_curriculum_programs', $record);
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
                        'program_description',
                        $programid
                    );

                    $update = new \stdClass();
                    $update->id = $programid;
                    $update->description = $data->description;
                    $update->descriptionformat = $data->descriptionformat ?? FORMAT_HTML;
                    $DB->update_record('local_curriculum_programs', $update);

                    if ($created) {
                        \local_curriculum\event\program_created::create([
                            'objectid' => $programid,
                            'context' => $context,
                        ])->trigger();
                    } else {
                        \local_curriculum\event\program_updated::create([
                            'objectid' => $programid,
                            'context' => $context,
                        ])->trigger();
                    }

                    // Redirect back to the program list.
                    $redirecturl = new \moodle_url('/local/curriculum/manage.php', ['ptype' => self::PAGEKEY]);
                    redirect($redirecturl);
                }
                break;
            case 'delete':
                $id = required_param('id', PARAM_INT);

                // Check if the program exists.
                $program = $DB->get_record('local_curriculum_programs', ['id' => $id], '*', MUST_EXIST);

                // Check if the program does not has versions linked to it.
                $versionslinked = $DB->record_exists('local_curriculum_versions', ['programid' => $program->id]);
                if ($versionslinked) {
                    throw new \moodle_exception('error_cannotdeleteprogram', 'local_curriculum');
                }

                $DB->delete_records('local_curriculum_programs', ['id' => $id]);
                $fs = get_file_storage();
                $fs->delete_area_files($context->id, 'local_curriculum', 'program_description', $id);

                \local_curriculum\event\program_deleted::create([
                    'objectid' => $id,
                    'context' => $context,
                ])->trigger();

                // Redirect back to the program list.
                $redirecturl = new \moodle_url('/local/curriculum/manage.php', ['ptype' => self::PAGEKEY]);
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

                $form = new \local_curriculum\form\program_form(null, ['context' => $context]);

                if ($id) {
                    $record = $DB->get_record('local_curriculum_programs', ['id' => $id], '*', MUST_EXIST);
                    $editoroptions = controller::get_editoroptions();
                    $record = file_prepare_standard_editor(
                        $record,
                        'description',
                        $editoroptions,
                        $context,
                        'local_curriculum',
                        'program_description',
                        $record->id
                    );
                    $form->set_data($record);
                }

                echo $form->render();
                break;
            default:
                $report = system_report_factory::create(programsreport::class, $context, '', '', 0, ['id' => $this->parentid]);
                echo $report->output();

                $params = ['action' => 'add', 'ptype' => self::PAGEKEY];
                $newbuttonurl = new \moodle_url('/local/curriculum/manage.php', $params);

                echo $OUTPUT->single_button(
                    $newbuttonurl,
                    new \lang_string('addelement', 'local_curriculum'),
                    'get'
                );
        }
    }
}
