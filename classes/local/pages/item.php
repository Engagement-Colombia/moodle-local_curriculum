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
use local_curriculum\reportbuilder\local\systemreports\items as itemsreport;

/**
 * Class item
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item extends managepage {
    /**
     * Page key identifier.
     * @var string
     */
    public const PAGEKEY = 'item';

    /**
     * Item constructor.
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
        return get_string('itemstitle', 'local_curriculum');
    }

    /**
     * Get the manage title for the page.
     *
     * @param int $id
     * @return string
     */
    public function get_managetitle(int $id): string {
        global $DB;

        // Items might not have a name column, let's check item_form or entity.
        // item_form has 'coursecode' and 'grouptemplate'.
        // entity has 'coursecode' and 'grouptemplate'.
        // Let's use coursecode as title.
        $item = $DB->get_field('local_curriculum_items', 'coursecode', ['id' => $id], MUST_EXIST);
        return get_string('manage_' . self::PAGEKEY, 'local_curriculum') . ': ' . format_string($item);
    }

    /**
     * Process the current action before the page is rendered.
     */
    public function go_preaction(): void {
        global $DB;

        $context = \context_system::instance();

        switch ($this->action) {
            case 'add':
                $form = new \local_curriculum\form\item_form(null, ['context' => $context]);
                if ($form->is_cancelled()) {
                    $cycleid = $this->parentid;
                    if (!$cycleid && $this->id) {
                        $cycleid = $DB->get_field('local_curriculum_items', 'cycleid', ['id' => $this->id]);
                    }
                    $redirecturl = new \moodle_url('/local/curriculum/manage.php', ['ptype' => self::PAGEKEY, 'parentid' => $cycleid]);
                    redirect($redirecturl);

                } else if ($data = $form->get_data()) {
                    $record = new \stdClass();
                    $record->coursecode = trim($data->coursecode);
                    $record->grouptemplate = trim($data->grouptemplate);
                    $record->conditions = $data->conditions;
                    $record->cycleid = $data->cycleid;
                    $record->timemodified = time();

                    if (!empty($data->id)) {
                        $record->id = $data->id;
                        $DB->update_record('local_curriculum_items', $record);
                        $itemid = $data->id;
                        $created = false;
                    } else {
                        $record->timecreated = time();
                        $itemid = $DB->insert_record('local_curriculum_items', $record);
                        $created = true;
                    }

                    if ($created) {
                        \local_curriculum\event\item_created::create([
                            'objectid' => $itemid,
                            'context' => $context,
                        ])->trigger();
                    } else {
                        \local_curriculum\event\item_updated::create([
                            'objectid' => $itemid,
                            'context' => $context,
                        ])->trigger();
                    }

                    // Redirect back to the item list.
                    $redirecturl = new \moodle_url('/local/curriculum/manage.php', ['ptype' => self::PAGEKEY, 'parentid' => $record->cycleid]);
                    redirect($redirecturl);
                }
                break;
            case 'delete':
                $id = required_param('id', PARAM_INT);
                $item = $DB->get_record('local_curriculum_items', ['id' => $id], '*', MUST_EXIST);

                // No child dependencies check for items.

                $DB->delete_records('local_curriculum_items', ['id' => $id]);

                \local_curriculum\event\item_deleted::create([
                    'objectid' => $id,
                    'context' => $context,
                ])->trigger();

                $redirecturl = new \moodle_url('/local/curriculum/manage.php', ['ptype' => self::PAGEKEY, 'parentid' => $item->cycleid]);
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

                $form = new \local_curriculum\form\item_form(null, ['context' => $context]);

                if ($id) {
                    $record = $DB->get_record('local_curriculum_items', ['id' => $id], '*', MUST_EXIST);
                    $form->set_data($record);
                } else {
                    $data = new \stdClass();
                    $data->cycleid = $this->parentid;
                    $form->set_data($data);
                }

                echo $form->render();
                break;
            default:
                $report = system_report_factory::create(itemsreport::class, $context, '', '', 0, ['cycleid' => $this->parentid]);
                echo $report->output();

                $params = ['action' => 'add', 'ptype' => self::PAGEKEY, 'parentid' => $this->parentid];
                $newbuttonurl = new \moodle_url('/local/curriculum/manage.php', $params);

                echo $OUTPUT->single_button(
                    $newbuttonurl,
                    new \lang_string('addelement', 'local_curriculum'),
                    'get'
                );

                $params = ['ptype' => cycle::PAGEKEY, 'id' => $this->parentid];
                $backbuttonurl = new \moodle_url('/local/curriculum/manage.php', $params);

                echo $OUTPUT->single_button(
                    $backbuttonurl,
                    get_string('back', 'local_curriculum'),
                    'get'
                );
        }
    }
}
