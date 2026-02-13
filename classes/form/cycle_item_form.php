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
 * Cycle item form
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_curriculum\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for adding/editing a cycle item
 *
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cycle_item_form extends moodleform {
    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('item', 'local_curriculum'));

        $mform->addElement('text', 'coursecode', get_string('coursecode', 'local_curriculum'), ['size' => '50']);
        $mform->setType('coursecode', PARAM_TEXT);
        $mform->addRule('coursecode', null, 'required', null, 'client');

        $mform->addElement('text', 'grouptemplate', get_string('grouptemplate', 'local_curriculum'), ['size' => '50']);
        $mform->setType('grouptemplate', PARAM_TEXT);

        $mform->addElement('textarea', 'conditions', get_string('conditions', 'local_curriculum'), ['cols' => '50', 'rows' => '8']);
        $mform->setType('conditions', PARAM_RAW);

        $mform->addElement('text', 'validity', get_string('validitydays', 'local_curriculum'), ['size' => '10']);
        $mform->setType('validity', PARAM_INT);
        $mform->setDefault('validity', 0);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'cycleid');
        $mform->setType('cycleid', PARAM_INT);

        $mform->addElement('hidden', 'ptype', \local_curriculum\local\pages\cycle_item::PAGEKEY);
        $mform->setType('ptype', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'action', 'add');
        $mform->setType('action', PARAM_TEXT);

        $this->add_action_buttons();
    }
}
