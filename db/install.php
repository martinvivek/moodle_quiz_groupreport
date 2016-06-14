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
 * Post-install script for the quiz manual grading report.
 * @package   quiz_grading
 * @copyright 2013 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Post-install script
 */
function xmldb_quiz_groupreport_install() {
    global $DB;

    $record = new stdClass();
    $record->name         = 'groupreport';
    $record->displayorder = '7000';
    $record->capability   = 'mod/quiz:grade';

    $DB->insert_record('quiz_reports', $record);
}
