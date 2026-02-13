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
 * Item form
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
 * Item form class
 *
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_form extends moodleform {
    /**
     * Definition of the form
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('item', 'local_curriculum'));

        $mform->addElement('text', 'coursecode', get_string('coursecode', 'local_curriculum'), ['maxlength' => '255']);
        $mform->setType('coursecode', PARAM_RAW); // Allow % for wildcards
        $mform->addRule('coursecode', null, 'required', null, 'client');

        $mform->addElement('text', 'grouptemplate', get_string('grouptemplate', 'local_curriculum'), ['maxlength' => '255']);
        $mform->setType('grouptemplate', PARAM_TEXT);

        $mform->addElement('textarea', 'conditions', get_string('conditions', 'local_curriculum'), ['rows' => 10, 'cols' => 50]);
        $mform->setType('conditions', PARAM_RAW);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'parentid');
        $mform->setType('parentid', PARAM_INT);

        $mform->addElement('hidden', 'ptype', \local_curriculum\local\pages\item::PAGEKEY);
        $mform->setType('ptype', PARAM_ALPHANUMEXT);

        $mform->addElement('hidden', 'action', 'add');
        $mform->setType('action', PARAM_TEXT);

        $this->add_action_buttons();
    }

    /**
     * Validation of the form
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['conditions'])) {
            // Validate JSON
            $json = json_decode($data['conditions']);
            if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
                $errors['conditions'] = get_string('invalidjson', 'local_curriculum');
            }
        }

        return $errors;
    }
}
