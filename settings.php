<?php
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs this condition or there is error on login page
    $settings->add(new admin_setting_confightmleditor('mod_quiz_report_groupreport/attemptheader',
        'Group Report attempt header', 'Prepended to each attempt report', ''));


    /*
    $settings = new admin_settingpage('mod_quiz_report/categorygrades', 'Category Grades');
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext('local_thisplugin/option',
        'Option', 'Information about this option', 100, PARAM_INT));
   */
}

