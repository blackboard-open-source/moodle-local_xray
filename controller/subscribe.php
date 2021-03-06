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
 * Xray integration Reports Controller
 *
 * @package   local_xray
 * @author    Pablo Pagnone
 * @author    German Vitale
 * @copyright Copyright (c) 2016 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class local_xray_controller_subscribe
 */
class local_xray_controller_subscribe extends mr_controller {

    public function view_action() {
        global $CFG, $USER, $DB, $PAGE, $OUTPUT;

        $courseid = required_param('courseid', PARAM_INT);
        $saved = optional_param('saved', 0, PARAM_INT);

        require_once($CFG->dirroot.'/local/xray/locallib.php');
        require_once($CFG->dirroot.'/local/xray/subscribeform.php');

        if (local_xray_email_enable() && $courseid != SITEID) {
            // Add the heading text.
            $this->heading->text = get_string("subscriptiontitle", $this->component);
            // Add title.
            $PAGE->set_title(get_string("subscriptiontitle", $this->component));
            // Add params in URL.
            $params = array('controller' => 'subscribe');
            $this->url->params($params);

            // Check if the setting is enabled.
            $disabled = false;
            if ($globalsub = $DB->get_record('local_xray_globalsub', array('userid' => $USER->id), 'type', IGNORE_MULTIPLE)) {
                if ($globalsub->type == XRAYSUBSCRIBEON || $globalsub->type == XRAYSUBSCRIBEOFF) {
                    $disabled = true;
                }
            }

            if (local_xray_single_activity_course($courseid)) {
                $returnurl = new moodle_url('/course/index.php', array('section' => 'course'));
                redirect(
                    $returnurl,
                    get_string('email_singleactivity', 'local_xray'),
                    null,
                    \core\output\notification::NOTIFY_WARNING
                );
            }

            $mform = new subscribe_form($this->url, array('disabled' => $disabled));

            // Create navbar.
            $PAGE->navbar->add(get_string("navigation_xray", $this->component));
            $PAGE->navbar->add(get_string("subscriptiontitle", $this->component), $this->url);

            // Check if the user is subscribed.
            $exists = local_xray_is_subscribed($USER->id, $courseid);

            // Process data.
            if ($fromform = $mform->get_data()) {
                // Subscribe in one course form.
                if ($fromform->subscribe && !$exists) {
                    $subscribed = new stdClass();
                    $subscribed->courseid = $courseid;
                    $subscribed->userid = $USER->id;
                    $DB->insert_record('local_xray_subscribe', $subscribed);
                } else if (!$fromform->subscribe && $exists) {
                    $DB->delete_records('local_xray_subscribe', array('courseid' => $courseid, 'userid' => $USER->id));
                }
                $this->url->param('saved', 1);
                redirect($this->url);
            }

            // Set the current value.
            if ($exists) {
                $toform = new stdClass();
                $toform->subscribe = 1;
                $mform->set_data($toform);
            }
            // Display form.
            $this->print_header();
            if ($saved) {
                echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
            }
            $mform->display();
            $frequency = get_config('local_xray', 'emailfrequency');
            if ($disabled) {
                echo $OUTPUT->notification(get_string("subscriptiondisabled", $this->component), 'info');
            } else if ($frequency == XRAYNEVER) {
                echo $OUTPUT->notification(get_string("emailsdisabled", $this->component), 'info');
            }
            $this->print_footer();
        }
    }

    /**
     * Require capabilities.
     */
    public function require_capability() {
        require_capability("{$this->plugin}:subscription_view", $this->get_context());
    }
}
