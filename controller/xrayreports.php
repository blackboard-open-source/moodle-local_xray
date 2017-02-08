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
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/* @var stdClass $CFG */
require_once($CFG->dirroot . '/local/xray/controller/reports.php');
use local_xray\event\get_report_failed;
/**
 * Xray integration Reports Controller
 *
 * @package   local_xray
 * @author    Pablo Pagnone
 * @author    German Vitale
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_xray_controller_xrayreports extends local_xray_controller_reports {

    /**
     * Report name.
     * @var String
     */
    private $reportname;

    /**
     * Old report name.
     * @var String
     */
    private $oldreportname;

    /**
     * Init
     */
    public function init() {
        $this->reportname = required_param("name", PARAM_ALPHANUMEXT);
        $this->showid = 0;
        if ($this->reportname == 'activityindividual' || $this->reportname == 'discussionindividual' ||
                $this->reportname == 'discussionindividualforum') {
            $this->showid = required_param("showid", PARAM_INT);
        }
        $this->oldreportname = local_xray_name_conversion($this->reportname, true);
    }

    /**
     * Require capability.
     */
    public function require_capability() {
        if (!local_xray_is_course_enable()) {
            $ctx = $this->get_context();
            throw new required_capability_exception($ctx, "{$this->plugin}:{$this->oldreportname}_view", 'nopermissions', '');
        }
        require_capability("{$this->plugin}:{$this->oldreportname}_view", $this->get_context());
    }

    public function view_action() {

        global $CFG, $COURSE, $PAGE, $SESSION, $USER, $OUTPUT;

        // Add title.
        $PAGE->set_title(get_string($this->oldreportname, $this->component));
        // Add params.
        $jouleparams = array('name' => $this->reportname);
        // Report name. Fix reportname for X-Ray params.
        $xrayparams = array('name' => $this->reportname);

        // Forum Activity Report, Student Activity Report or Student Discussion Report.
        if ($this->showid) {
            $jouleparams["showid"] = $this->showid;
            $xrayparams["showid"] = $this->showid;
        }

        $this->url->params($xrayparams);

        $PAGE->navbar->add(get_string("navigation_xray", $this->component));
        $PAGE->navbar->add(get_string($this->oldreportname, $this->component), $this->url);

        // The title will be displayed in the X-ray page.
        $this->heading->text = '';

        if (!local_xray_reports()) {
            return $OUTPUT->notification(get_string("noaccessxrayreports", $this->component), 'error');
        }

        $output = '';
        try {
            // Menu (Always show menu).
            $output .= $this->print_top();
            // Get the URL.
            $xrayreportsurl  = get_config('local_xray', 'xrayreportsurl');
            if (($xrayreportsurl === false) || ($xrayreportsurl === '')) {
                print_error("error_xrayreports_nourl", $this->component);
            }
            // Tokens.
            // TODO this should be disabled for INT-10445. It will be added later.
            /*$tokenparams = \local_xray\local\api\jwthelper::get_token_params();
            if (!$tokenparams) {
                // Error to get token for shiny server.
                print_error("error_xrayreports_gettoken", $this->component);
            }
            $xrayparams = array_merge($xrayparams,$tokenparams);*/

            /*
             * Check if exist cookie for xray to use Safari browser.If not exist,we redirect to xray side with param
             * url for create cookie there and from xray side will redirect to Joule again.
             */
            if ((strpos($_SERVER['HTTP_USER_AGENT'], 'Safari')) && !isset($SESSION->xray_cookie_xrayreports)) {
                $xrayparams["url"] = $this->url->out(false); // Add page to redirect from xray server.
                $url = new moodle_url($xrayreportsurl."/auth", $xrayparams);
                $SESSION->xray_cookie_xrayreports = true;
                redirect($url);
            }
            // The param jouleurl is required in shiny server to add link to each report of joule side.
            $xrayparams["jouleurl"] = $CFG->wwwroot;
            // Course id.
            $xrayparams["courseid"] = $COURSE->id;
            // User id: Instructor/Admin who is seeing the report.
            $xrayparams["uid"] = $USER->id;

            $url = new moodle_url($xrayreportsurl, $xrayparams);
            $output .= $this->output->print_iframe_systemreport($url);
        } catch (Exception $e) {
            get_report_failed::create_from_exception($e, $this->get_context(), $this->name)->trigger();
            $output = $this->print_error('error_xray', $e->getMessage());
        }

        return $output;
    }
}