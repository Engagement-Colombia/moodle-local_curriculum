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
 * Cycle form class
 *
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cycle_form extends moodleform {
    /**
     * Definition of the form
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('cycle', 'local_curriculum'));

        $mform->addElement('text', 'name', get_string('name'), ['maxlength' => '255']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $editoroptions = \local_curriculum\local\controller::get_editoroptions();
        $mform->addElement('editor', 'description_editor', get_string('description'), null, $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('text', 'durationdays', get_string('durationdays', 'local_curriculum'));
        $mform->setType('durationdays', PARAM_INT);
        $mform->setDefault('durationdays', 0);
        $mform->addRule('durationdays', null, 'numeric', null, 'client');

        $mform->addElement('text', 'stage', get_string('stage', 'local_curriculum'));
        $mform->setType('stage', PARAM_INT);
        $mform->setDefault('stage', 1);
        $mform->addRule('stage', null, 'numeric', null, 'client');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'parentid');
        $mform->setType('parentid', PARAM_INT);

        $mform->addElement('hidden', 'ptype', \local_curriculum\local\pages\cycle::PAGEKEY);
        $mform->setType('ptype', PARAM_ALPHANUMEXT);

        $mform->addElement('hidden', 'action', 'add');
        $mform->setType('action', PARAM_TEXT);

        $this->add_action_buttons();
    }
}
