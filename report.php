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
 * This file defines the quiz category grades report class.
 *
 * @package   quiz_groupreport
 * @copyright 2015 Ray Morris <Ray.Morris@teex.tamu.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/groupreport/groupreportsettings_form.php');

/**
 * Quiz report to display grades per-category, using categories from the question bank.
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_groupreport_report extends quiz_default_report {
    const DEFAULT_PAGE_SIZE = 5;
    const DEFAULT_ORDER = 'lastname';

    protected $viewoptions = array();
    protected $cm;
    protected $quiz;
    protected $context;

    public function display($quiz, $cm, $course) {
        global $CFG, $PAGE, $OUTPUT;
        global $DB;

        $PAGE->set_pagelayout('print');
        $PAGE->requires->js('/mod/quiz/report/groupreport/groupreport.js');

        $this->quiz = $quiz;
        $this->cm = $cm;
        $this->course = $course;

        // Get the URL options.
        $group = optional_param('group', null, PARAM_INT);
        $pagesize = optional_param('pagesize', self::DEFAULT_PAGE_SIZE, PARAM_INT);
        $page = optional_param('page', 0, PARAM_INT);
        $order = optional_param('order', self::DEFAULT_ORDER, PARAM_ALPHA);
        $qubaid = optional_param('qubaid', null, PARAM_INT);

        // Assemble the options required to reload this page.
        $optparams = array('page');
        foreach ($optparams as $param) {
            if ($$param) {
                $this->viewoptions[$param] = $$param;
            }
        }
        if ($pagesize != self::DEFAULT_PAGE_SIZE) {
            $this->viewoptions['pagesize'] = $pagesize;
        }
        if ($order != self::DEFAULT_ORDER) {
            $this->viewoptions['order'] = $order;
        }

        // Check permissions.
        $this->context = context_module::instance($cm->id);
        require_capability('mod/quiz:grade', $this->context);
        $shownames = has_capability('quiz/grading:viewstudentnames', $this->context);
        $showidnumbers = has_capability('quiz/grading:viewidnumber', $this->context);

        // Validate order.
        if (!in_array($order, array('random', 'date', 'firstname', 'lastname', 'idnumber'))) {
            $order = self::DEFAULT_ORDER;
        } else if (!$shownames && ($order == 'firstname' || $order == 'lastname')) {
            $order = self::DEFAULT_ORDER;
        } else if (!$showidnumbers && $order == 'idnumber') {
            $order = self::DEFAULT_ORDER;
        }
        if ($order == 'random') {
            $page = 0;
        }

        // Get the group, and the list of significant users.
        $this->currentgroup = $this->get_current_group($cm, $course, $this->context);
        if ($this->currentgroup == self::NO_GROUPS_ALLOWED) {
            $this->users = array();
        } else {
            $this->users = get_users_by_capability($this->context,
                    array('mod/quiz:reviewmyattempts', 'mod/quiz:attempt'), '', '', '', '',
                    $this->currentgroup, '', false);
        }
      
        $hasquestions = quiz_has_questions($quiz->id);

        // Start output.
        $this->print_header_and_tabs($cm, $course, $quiz, 'groupreport');
        echo html_writer::tag('p', '<br/><br/><br/><br/>', array ('class' => 'header_space'));    #add space to top of page to allow for header to show up properly
        echo $OUTPUT->heading(get_string('groupreport', 'quiz_groupreport'), 2, 'cg_heading');

        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css\">\n";
        echo '<link rel="stylesheet" type="text/css" href="report/groupreport/styles.css">';

        // What sort of page to display?
        if (!$hasquestions) {
            echo quiz_no_questions_message($quiz, $cm, $this->context);
        } else if (!isset($group)) {
            $group = $this->currentgroup;
        }
        if ($groupmode = groups_get_activity_groupmode($this->cm)) {
            // Groups is being used.
            groups_print_activity_menu($this->cm, $PAGE->url, false, true);
        }
        $this->display_attempts($pagesize, $page, $shownames, $showidnumbers, $order, $group, $qubaid);
        return true;
    }

    protected function get_quiz_attempts_by_group($group = 0, $orderby = 'lastname, attempt', $qubaid = 0) {
        global $DB;

        # preview = 0 will leave out admin previews
        $where = "quiz = :mangrquizid AND
                state = :statefinished";
        $params = array('mangrquizid' => $this->cm->instance, 'statefinished' => quiz_attempt::FINISHED);

        if ($qubaid) {
            $where .= ' AND qa.id=:qubaid ';
            $params['qubaid'] = $qubaid;
        }

        if ($group) {
            $users = get_users_by_capability($this->context,
                    array('mod/quiz:reviewmyattempts', 'mod/quiz:attempt'), 'u.id, u.id', '', '', '',
                    $group, '', false);
            if (empty($users)) {
                $where .= ' AND userid = 0';
            } else {
                list($usql, $uparam) = $DB->get_in_or_equal(array_keys($users),
                        SQL_PARAMS_NAMED, 'mangru');
                $where .= ' AND userid ' . $usql;
                $params += $uparam;
            }
        }

        $sql = "SELECT qa.*, u.firstname, u.lastname, u.idnumber FROM {quiz_attempts} AS qa JOIN {user} AS u ON qa.userid=u.id
                WHERE $where
                ORDER BY $orderby";
        return $DB->get_records_sql($sql, $params);
    }


    /**
     * Get the URL of the front page of the report that lists all the questions.
     * @return string the URL.
     */
    protected function base_url() {
        return new moodle_url('/mod/quiz/report.php',
                array('id' => $this->cm->id, 'mode' => 'groupreport'));
    }


    protected function choose_group() {
        global $OUTPUT;
        global $PAGE;

        // $pageoptions = array();
        // list ($pageoptions['fromtime_int'], $pageoptions['totime_int']) = get_timerestrictions();

        if ($groupmode = groups_get_activity_groupmode($this->cm)) {
            // Groups is being used.
            groups_print_activity_menu($this->cm, $PAGE->url, false, true);
        }
    }

    protected function display_single_attempt_heading($quizattempt, $table) {
        global $OUTPUT;

        $userinfo = $quizattempt->lastname . ', ' . $quizattempt->firstname;
        $score = round($quizattempt->sumgrades / $this->quiz->sumgrades * 100);
        if ($score >= 70) {
            $result = 'PASS';
            $color = "color:black;";
        } else {
            $result = 'FAIL';
            $color = "color:red;";
        }

        //and add a table row
        $table->data[] = 
            array
            (
                html_writer::tag('div', $userinfo, array ('class' => 'cg_userinfo')),
                html_writer::tag('div', $quizattempt->idnumber),
                html_writer::tag('div', userdate($quizattempt->timefinish), array('class' => 'cg_time')),
                html_writer::tag('div', round($quizattempt->sumgrades / $this->quiz->sumgrades * 100, 0) . '%', array('style' => $color)),
                html_writer::tag('div', $result, array('style' => $color))
            );
    }

    protected function display_single_attempt($quizattempt, $table, $first = false) {
        echo html_writer::start_tag('div', array ('class' => 'cg_attempt'));
        if ($first) { echo get_config('mod_quiz_report_groupreport', 'attemptheader'); }
        echo $this->display_single_attempt_heading($quizattempt, $table);
        echo html_writer::end_tag('div');
    }

    protected function getscores($quizattempt) {
        global $DB;
        $sql = "SELECT category, count(*) AS numquestions, SUM(ISNULL(fraction, 0)) AS sumgrade, ROUND(AVG(ISNULL(fraction, 0)) * 100, 0) AS grade
                FROM mdl_question_attempt_steps qas
                JOIN mdl_question_attempts qa ON qas.questionattemptid=qa.id
                JOIN mdl_question q ON q.id=qa.questionid

                JOIN mdl_question_usages qu ON qu.id=qa.questionusageid
                JOIN mdl_context ON mdl_context.id=qu.contextid
                JOIN mdl_question_categories qc ON qc.id=q.category
                JOIN mdl_quiz_attempts quiza ON quiza.uniqueid=questionusageid
                WHERE qas.state IN ('gradedright', 'gradedwrong', 'gradedpartial', 'gaveup')
                    AND quiza.id=?
                GROUP BY category";

        $leafscores = $DB->get_records_sql($sql, array($quizattempt->id));
        $topcat = $this->lowest_common_ancestor(array_keys($leafscores));
        list ($where, $params) = $DB->get_in_or_equal(array_keys($leafscores));
        $categories = $DB->get_records_select('question_categories', 'id ' . $where, $params);
        $allcats = $this->getsubcats($topcat);
        return $this->buildtree($allcats, $leafscores);
    }

    protected function get_timerestrictions() {
        if ( (!empty($_POST['fromtime']['enabled'])) || (!empty($_POST['totime']['enabled'])) ) {
            $fromtimearray = optional_param_array('fromtime', array('year'=>1970, 'month'=>1, 'day'=>1, 'enabled'=>0), PARAM_INT);
            $this->fromtime = make_timestamp($fromtimearray['year'], $fromtimearray['month'], $fromtimearray['day'], 0, 0, 0, 99);
            $totimearray = optional_param_array('totime', array('year'=>2032, 'month'=>1, 'day'=>1, 'enabled'=>0), PARAM_INT);
            $this->totime = make_timestamp($totimearray['year'], $totimearray['month'], $totimearray['day'], 23, 59, 59, 99);
        }
        if ( empty($fromtimearray['enabled']) || (! $fromtimearray['enabled'] )) {
            $this->fromtime = optional_param('fromtime_int', 0, PARAM_INT);
        }
        if ( empty($totimearray['enabled']) || (!$totimearray['enabled']) ) {
            $this->totime = optional_param('totime_int', 0xFFFFFFFF, PARAM_INT);
        }
        return array ($this->fromtime, $this->totime);
    }

    protected function display_attempts($pagesize, $page, $shownames, $showidnumbers, $order, $group, $qubaid = 0) {
        global $OUTPUT;
        global $PAGE;

        if($qubaid) {
            $attempts = $this->get_quiz_attempts_by_group($group, $order, $qubaid);
            $this->display_single_attempt($attempts[$qubaid]);
            return;
        }

        $attempts = $this->get_quiz_attempts_by_group($group, $order);
        $count = count($attempts);
        if ($pagesize * $page >= $count) {
            $page = 0;
        }

        // Prepare the form.
        $hidden = array(
            'id' => $this->cm->id,
            'mode' => 'groupreport',
            'page' => $page,
        );

        $mform = new quiz_groupreport_settings_form($hidden, $shownames, $showidnumbers);

        // Tell the form the current settings.
        $settings = new stdClass();
        $settings->pagesize = $pagesize;
        $settings->order = $order;
        $mform->set_data($settings);
        $mform->display();
 
        if ($count > $pagesize) {
            $url = new moodle_url($PAGE->url, array('pagesize' => $pagesize, 'group' => $group));
            echo $OUTPUT->paging_bar($count, $page, $pagesize, $url);
        }

        $table = new html_table();
        $table->class = 'generaltable';
        $table->id = 'groupreport';
        $table->width = '97%';

        //add headers to the table
        $table->head[] = get_string('name', 'quiz_groupreport');
        $table->head[] = get_string('idnum', 'quiz_groupreport');
        $table->head[] = get_string('date', 'quiz_groupreport');
//        $table->head[] = get_string('attempt', 'quiz_groupreport');
        $table->head[] = get_string('score', 'quiz_groupreport');
        $table->head[] = get_string('result', 'quiz_groupreport');

        //center all elements
        for($i = 0; $i < count($table->head); ++$i)
            $table->align[] = 'center';

        //start off with an empty table
        $table->data = array();

        echo html_writer::tag('h2', $this->quiz->name);

        $first = true;
        foreach (array_slice($attempts, $page * $pagesize, $pagesize, true) AS $attempt) {
            $this->display_single_attempt($attempt, $table, $first);
            $first = false;
        }

        echo html_writer::table($table);
    }


    /**
     * Initialise some parts of $PAGE and start output.
     *
     * @param object $cm the course_module information.
     * @param object $coures the course settings.
     * @param object $quiz the quiz settings.
     * @param string $reportmode the report name.
     */
    public function print_header_and_tabs($cm, $course, $quiz, $reportmode = 'overview') {
        global $PAGE, $OUTPUT;

        // Print the page header.
        $PAGE->set_title($quiz->name);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        $context = context_module::instance($cm->id);
        // echo $OUTPUT->heading(format_string($quiz->name, true, array('context' => $context)));
    }
}
