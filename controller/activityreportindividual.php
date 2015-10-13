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
 * Report controller.
 *
 * @package   local_xray
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/* @var stdClass $CFG */
require_once($CFG->dirroot . '/local/xray/controller/reports.php');

/**
 * Activity Report Individual
 *
 * @package   local_xray
 * @author    Pablo Pagnone
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_xray_controller_activityreportindividual extends local_xray_controller_reports {

    public function init() {
        parent::init();
        $this->userid = required_param('userid', PARAM_INT);
    }

    public function view_action() {
        global $PAGE, $DB;

        // Add nav to return to activityreport.
        $PAGE->navbar->add(get_string("activityreport", $this->component),
            new moodle_url('/local/xray/view.php',
                array("controller" => "activityreport",
                    "courseid" => $this->courseid)));
        $PAGE->navbar->add($PAGE->title);
        $output = '';
        try {
            $report = "activity";
            $response = \local_xray\local\api\wsapi::course($this->courseid, $report, $this->userid);
            if (!$response) {
                // Fail response of webservice.
                \local_xray\local\api\xrayws::instance()->print_error();

            } else {
                $output .= $this->print_top();
                $output .= $this->output->inforeport($response->reportdate,
                                                    $DB->get_record('user', array('id' => $this->userid)));
                $output .= $this->activity_by_date($response->elements->activityLevelTimeline);
                $output .= $this->activity_last_two_weeks($response->elements->barplotOfActivityWholeWeek);
                $output .= $this->activity_last_two_weeks_byweekday($response->elements->barplotOfActivityByWeekday);

            }
        } catch (Exception $e) {
            $output = $this->print_error('error_xray', $e->getMessage());
        }

        return $output;
    }

    /**
     * Report Students activity (table).
     *
     * @param stdClass $element
     * @return string
     */
    private function activity_by_date($element) {

        $output = "";
        $output .= $this->output->activityreportindividual_activity_by_date($element);
        return $output;
    }

    /**
     * Report Activity of course by day.
     *
     * @param stdClass $element
     * @return string
     */
    private function activity_last_two_weeks($element) {

        $output = "";
        $output .= $this->output->activityreportindividual_activity_last_two_weeks($element);
        return $output;
    }

    /**
     * Report Activity by time of day.
     *
     * @param stdClass $element
     * @return string
     */
    private function activity_last_two_weeks_byweekday($element) {

        $output = "";
        $output .= $this->output->activityreportindividual_activity_last_two_weeks_byday($element);
        return $output;
    }
}
