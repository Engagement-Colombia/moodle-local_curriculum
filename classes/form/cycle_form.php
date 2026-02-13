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
 * Cycle form
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
 * Form for adding/editing a cycle
 *
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cycle_form extends moodleform {
    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('cycle', 'local_curriculum'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '50']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $editoroptions = \local_curriculum\local\controller::get_editoroptions();
        $mform->addElement('editor', 'description_editor', get_string('description'), null, $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('text', 'duration', get_string('durationdays', 'local_curriculum'), ['size' => '10']);
        $mform->setType('duration', PARAM_INT);
        $mform->setDefault('duration', 0);

        $mform->addElement('text', 'stage', get_string('stage', 'local_curriculum'), ['size' => '10']);
        $mform->setType('stage', PARAM_INT);
        $mform->setDefault('stage', 1);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'versionid');
        $mform->setType('versionid', PARAM_INT);

        $mform->addElement('hidden', 'ptype', \local_curriculum\local\pages\cycle::PAGEKEY);
        $mform->setType('ptype', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'action', 'add');
        $mform->setType('action', PARAM_TEXT);

        $this->add_action_buttons();
    }
}
