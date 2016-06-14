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
 * Capability definitions for the quiz manual grading report.
 *
 * @package   quiz_grading
 * @copyright 2010 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    /*
    // Is the user allowed to see the student's real names while grading?
    'quiz/grading:viewstudentnames' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        ),
        'clonepermissionsfrom' =>  'mod/quiz:viewreports'
    ),

    // Is the user allowed to see the student's idnumber while grading?
    'quiz/grading:viewidnumber' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        ),
        'clonepermissionsfrom' =>  'mod/quiz:viewreports'
    )
    */
);
