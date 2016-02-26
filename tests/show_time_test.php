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
 * show_time_hours_minutes tests
 *
 * @package   local_xray
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xray\tests;

defined('MOODLE_INTERNAL') || die();

/**
 * Test show_time_hours_minutes method
 *
 * @author    German Vitale
 * @package   local_xray
 * @group local_xray
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_xray_show_time_testcase extends \advanced_testcase {

    /**
     * Method show_time_hours_minutes
     * Test positive values lower than 24 hours.
     */
    public function test_positive_lower_values() {
        $this->resetAfterTest(true);
        global $CFG;
        require_once($CFG->dirroot.'/local/xray/controller/reports.php');
        // Create a higher value than 24 hours.
        $highervalue = 60 * 12;

        $result = \local_xray_controller_reports::show_time_hours_minutes($highervalue);
        $time = explode(":", $result);
        $hours = $time[0];

        $this->assertEquals(12, $hours);
    }

    /**
     * Method show_time_hours_minutes
     * Test values highers than 24 hours.
     */
    public function test_high_values() {
        $this->resetAfterTest(true);
        global $CFG;
        require_once($CFG->dirroot.'/local/xray/controller/reports.php');
        // Create a higher value than 24 hours.
        $highervalue = 60 * 25;

        $result = \local_xray_controller_reports::show_time_hours_minutes($highervalue);
        $time = explode(":", $result);
        $hours = $time[0];

        $this->assertEquals(25, $hours);
    }

    /**
     * Method show_time_hours_minutes
     * Test negative values.
     */
    public function test_negative_values() {
        $this->resetAfterTest(true);
        global $CFG;
        require_once($CFG->dirroot.'/local/xray/controller/reports.php');
        // Create a negative value.
        $negativevalue = -1;

        $result = \local_xray_controller_reports::show_time_hours_minutes($negativevalue);

        $this->assertSame('-', $result);
    }

    /**
     * Method show_time_hours_minutes
     * Test non numeric values.
     */
    public function test_non_numeric_values() {
        $this->resetAfterTest(true);
        global $CFG;
        require_once($CFG->dirroot.'/local/xray/controller/reports.php');
        // Create a not number value.
        $nonnumeric = 'break';

        $result = \local_xray_controller_reports::show_time_hours_minutes($nonnumeric);

        $this->assertSame('-', $result);
    }
}