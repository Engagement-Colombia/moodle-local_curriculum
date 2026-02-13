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
use local_curriculum\reportbuilder\local\systemreports\cycle_items as cycleitemsreport;
use local_curriculum\local\controller;

/**
 * Class cycle_item
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cycle_item extends managepage {
    /**
     * Page key identifier.
     * @var string
     */
    public const PAGEKEY = 'cycle_item';

    /**
     * Constructor
     */
    public function __construct() {
        global $DB;

        parent::__construct();

        // For cycle items, the parentid is the cycleid, so we need to get the versionid from the cycle.
        if (empty($this->parentid)) {
            if (empty($this->id)) {
                throw new \moodle_exception('error_invalidid', 'local_curriculum');
            }
            $cycleid = $DB->get_field('local_curriculum_cycle_items', 'cycleid', ['id' => $this->id], MUST_EXIST);
            $this->parentid = $cycleid;
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
     * @return string
     */
    public function get_managetitle(): string {
        global $DB;
        $cycle = $DB->get_field('local_curriculum_cycles', 'name', ['id' => $this->parentid], MUST_EXIST);
        return get_string('manage_item', 'local_curriculum') . ': ' . format_string($cycle);
    }

    /**
     * Process the current action before the page is rendered.
     */
    public function go_preaction(): void {
        global $DB;

        $context = \context_system::instance();

        switch ($this->action) {
            case 'add':
                $form = new \local_curriculum\form\cycle_item_form(null, ['context' => $context]);
                $cycleid = $this->parentid;
                if ($form->is_cancelled()) {
                    if (!$cycleid) {
                        if (empty($this->id)) {
                            throw new \moodle_exception('error_invalidid', 'local_curriculum');
                        }
                        $cycleid = $DB->get_field('local_curriculum_cycle_items', 'cycleid', ['id' => $this->id]);
                    }
                    $redirecturl = new \moodle_url(
                        '/local/curriculum/manage.php',
                        ['ptype' => self::PAGEKEY, 'parentid' => $cycleid]
                    );
                    redirect($redirecturl);

                } else if ($data = $form->get_data()) {
                    $record = new \stdClass();
                    $record->coursecode = trim($data->coursecode);
                    $record->grouptemplate = !empty($data->grouptemplate) ? trim($data->grouptemplate) : null;
                    $record->conditions = !empty($data->conditions) ? trim($data->conditions) : null;
                    $record->validity = $data->validity ?? 0;
                    $record->cycleid = $data->cycleid;

                    if (!empty($data->id)) {
                        $record->id = $data->id;
                        $DB->update_record('local_curriculum_cycle_items', $record);
                        $itemid = $data->id;
                        $created = false;
                    } else {
                        $itemid = $DB->insert_record('local_curriculum_cycle_items', $record);
                        $created = true;
                    }

                    if ($created) {
                        \local_curriculum\event\cycle_item_created::create([
                            'objectid' => $itemid,
                            'context' => $context,
                        ])->trigger();
                    } else {
                        \local_curriculum\event\cycle_item_updated::create([
                            'objectid' => $itemid,
                            'context' => $context,
                        ])->trigger();
                    }

                    // Redirect back to the item list.
                    $redirecturl = new \moodle_url(
                        '/local/curriculum/manage.php',
                        [
                            'ptype' => self::PAGEKEY,
                            'parentid' => $record->cycleid,
                        ]
                    );
                    redirect($redirecturl);
                }
                break;
            case 'delete':
                $id = required_param('id', PARAM_INT);
                $item = $DB->get_record('local_curriculum_cycle_items', ['id' => $id], '*', MUST_EXIST);

                // Check if item has users linked to it.
                $userslinked = $DB->record_exists('local_curriculum_cycle_users', ['cycleid' => $item->cycleid]);
                if ($userslinked) {
                    throw new \moodle_exception('error_cannotdeleteitem', 'local_curriculum');
                }

                $DB->delete_records('local_curriculum_cycle_items', ['id' => $id]);

                \local_curriculum\event\cycle_item_deleted::create([
                    'objectid' => $id,
                    'context' => $context,
                ])->trigger();

                $redirecturl = new \moodle_url(
                    '/local/curriculum/manage.php',
                    ['ptype' => self::PAGEKEY, 'parentid' => $item->cycleid]
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

                $form = new \local_curriculum\form\cycle_item_form(null, ['context' => $context]);

                if ($id) {
                    $record = $DB->get_record('local_curriculum_cycle_items', ['id' => $id], '*', MUST_EXIST);
                    $form->set_data($record);
                } else {
                    $data = new \stdClass();
                    $data->cycleid = $this->parentid;
                    $form->set_data($data);
                }

                echo $form->render();
                break;
            default:
                $report = system_report_factory::create(
                    cycleitemsreport::class,
                    $context,
                    '',
                    '',
                    0,
                    ['cycleid' => $this->parentid]
                );
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
