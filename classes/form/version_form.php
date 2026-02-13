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
 * Version form
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_curriculum\form;

use core\plugininfo\local;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Version form class
 *
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class version_form extends moodleform {
    /**
     * Definition of the form
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('version', 'local_curriculum'));

        $mform->addElement('text', 'name', get_string('name'), ['maxlength' => '255']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('date_time_selector', 'startdate', get_string('startdate', 'local_curriculum'));
        $mform->addRule('startdate', null, 'required', null, 'client');

        $mform->addElement('date_time_selector', 'enddate', get_string('enddate', 'local_curriculum'), ['optional' => true]);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'programid');
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('hidden', 'ptype', \local_curriculum\local\pages\version::PAGEKEY);
        $mform->setType('ptype', PARAM_ALPHANUMEXT);

        $mform->addElement('hidden', 'action', 'add');
        $mform->setType('action', PARAM_TEXT);

        $this->add_action_buttons();
    }
}
