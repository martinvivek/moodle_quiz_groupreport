<?php
//
// This module is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, version 3 and no other version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this software.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file defines the setting form for the quiz category grades report.
 *
 * @package   quiz_grading
 * @copyright 2015 Ray Morris
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');


/**
 * Quiz grading report settings form.
 *
 * @copyright 2010 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_groupreport_settings_form extends moodleform {
    protected $hidden = array();
    protected $shownames;
    protected $showidnumbers;

    public function __construct($hidden, $shownames = true, $showidnumbers = true) {
        global $CFG;
        $this->hidden = $hidden;
        $this->shownames = $shownames;
        $this->showidnumbers = $showidnumbers;
        parent::__construct($CFG->wwwroot . '/mod/quiz/report.php', null, 'get');
    }

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'options', get_string('options', 'quiz_grading'));

        $mform->addElement('text', 'pagesize', get_string('studentsperpage', 'quiz_groupreport'),
                array('size' => 3));
        $mform->setType('pagesize', PARAM_INT);

        if ($this->shownames) {
            $orderoptions['firstname'] = get_string('bystudentfirstname', 'quiz_grading');
            $orderoptions['lastname']  = get_string('bystudentlastname', 'quiz_grading');
        }
        if ($this->showidnumbers) {
            $orderoptions['idnumber'] = get_string('bystudentidnumber', 'quiz_grading');
        }
        $mform->addElement('select', 'order', get_string('orderattempts', 'quiz_grading'),
                $orderoptions);

        foreach ($this->hidden as $name => $value) {
            $mform->addElement('hidden', $name, $value);
            if ($name == 'mode') {
                $mform->setType($name, PARAM_ALPHA);
            } else {
                $mform->setType($name, PARAM_INT);
            }
        }

        $mform->addElement('submit', 'submitbutton', get_string('changeoptions', 'quiz_grading'));
    }
}
