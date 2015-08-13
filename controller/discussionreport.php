<?php
defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');
require_once($CFG->dirroot.'/local/xray/controller/reports.php');

/**
 * Xray integration Reports Controller
 *
 * @author Pablo Pagnone
 * @author German Vitale
 * @package local_xray
 */
class local_xray_controller_discussionreport extends local_xray_controller_reports {

    public function init() {
        parent::init();
        // This report will get data by courseid.
        $this->courseid = required_param('courseid', PARAM_RAW);	
    }

    public function view_action() {

        global $PAGE;
        $title = get_string($this->name, $this->component);
        $PAGE->set_title($title);
        $this->heading->text = $title;	

        // Add title to breadcrumb.
        $PAGE->navbar->add(get_string($this->name, $this->component));
        $output = "";

        try {
            $report = "discussion";
            $response = \local_xray\api\wsapi::course(parent::XRAY_COURSEID, $report);
            if(!$response) {
                // Fail response of webservice.
                throw new Exception(\local_xray\api\xrayws::instance()->geterrormsg());
            } else {
                
                // Show graphs.
                $output .= $this->participation_metrics(); // Its a table, I will get info with new call.
                $output .= $this->discussion_activity_by_week($response->elements[1]); // Table with variable columns - Send data to create columns
                $output .= $this->average_words_weekly_by_post($response->elements[3]);
                $output .= $this->social_structure($response->elements[7]);
                $output .= $this->social_structure_with_words_count($response->elements[8]);
                $output .= $this->social_structure_with_contributions_adjusted($response->elements[9]);
                $output .= $this->social_structure_coefficient_of_critical_thinking($response->elements[10]);
                $output .= $this->main_terms($response->elements[11]);
            }
        } catch(exception $e) {
            print_error('error_xray', 'local_xray','',null, $e->getMessage());
        }
        return $output;
    }
    
    /**
     * Report "A summary table to be added" (table).
     *
     */
    private function participation_metrics() {
        $output = "";
        $output .= $this->output->discussionreport_participation_metrics($this->courseid);
        return $output;
    }   
    
    /**
     * Report "Discussion Activity by Week" (table).
     *
     */
    private function discussion_activity_by_week($element) {
        $output = "";
        $output .= $this->output->discussionreport_discussion_activity_by_week($this->courseid, $element);
        return $output;
    }

    /**
     * Json for provide data to participation_metrics table.
     */
    public function jsonparticipationdiscussion_action() {

        global $PAGE;

        // TODO:: Review , implement search, sortable, pagination.
        $return = "";
        
        try {
            // Pager
            $count = optional_param('iDisplayLength', 10, PARAM_RAW);
            $start  = optional_param('iDisplayStart', 10, PARAM_RAW);
            
            $report = "discussion";
            $element = "discussionMetrics";
            
            $response = \local_xray\api\wsapi::courseelement(parent::XRAY_COURSEID,
                                                             $element, 
                                                             $report, 
                                                             null, 
                                                             '', 
                                                             '', 
                                                             $start, 
                                                             $count);
           
            if(!$response) {
                // TODO:: Fail response of webservice.
                throw new Exception(\local_xray\api\xrayws::instance()->geterrormsg());
            } else {
                
                $data = array();
                if(!empty($response->data)){
                    $discussionreportind = get_string('discussionreportindividual', $this->component);//TODO
                    
                    foreach($response->data as $row) {
                        
                        $r = new stdClass();
                        $r->action = "";
                        if(has_capability('local/xray:discussionreportindividual_view', $PAGE->context)) {
                            // Url for discussionreportindividual.
                            $url = new moodle_url("/local/xray/view.php",
                                    array("controller" => "discussionreportindividual",
                                            "xraycourseid" => $this->courseid,
                                            "xrayuserid" => $row->participantId->value
                                    ));
                            $r->action = html_writer::link($url, '', array("class" => "icon_discussionreportindividual",
                                    "title" => $discussionreportind,
                                    "target" => "_blank"));
                        }
                        $r->firstname = $row->firstname->value;
                        $r->lastname = $row->lastname->value;
                        $r->posts = $row->posts->value;
                        $r->contribution = $row->contrib->value;
                        $r->ctc = $row->ctc->value;
                        $r->regularityofcontributions = '';//TODO No value in this object, notify Shani - $row->regularityContrib->value
                        $r->regularityofctc = '';//TODO No value in this object, notify Shani - $row->regularityCTC->value
                        $data[] = $r;
                    }
                }
                
                // Provide info to table.
                $return["recordsFiltered"] = $response->itemCount;
                $return["data"] = $data;
            }
        } catch(exception $e) {
            // Error, return invalid data, and pluginjs will show error in table.
            $return["data"] = "-";
        }
        echo json_encode($return);
        exit();
    }
    
    /**
     * Json for provide data to discussion_activity_by_week table.
     */
    public function jsonweekdiscussion_action() {
    
        global $PAGE;
    
        // TODO:: Review , implement search, sortable, pagination.
        $return = array();
        try {
            // Pager
            $count = optional_param('iDisplayLength', 10, PARAM_RAW);
            $start  = optional_param('iDisplayStart', 10, PARAM_RAW);

            $report = "discussion";
            $element = "discussionActivityByWeek";

            $response = \local_xray\api\wsapi::courseelement(parent::XRAY_COURSEID, // TODO:: Hardcoded.
                    $element,
                    $report,
                    null,
                    '',
                    '',
                    $start,
                    $count);
            
            if(!$response) {
                // TODO:: Fail response of webservice.
                throw new Exception(\local_xray\api\xrayws::instance()->geterrormsg());
            } else {
                $data = array();

                //$posts = array('weeks' => get_string('posts', 'local_xray'));
                $avglag = array('weeks' => get_string('averageresponselag', 'local_xray'));
                $avgwordcount = array('weeks' => get_string('averagenoofwords', 'local_xray'));

                if(!empty($response->data)){
                    foreach($response->data as $col) {
                        //$posts[$col->week->value] = $col->posts->value;
                        $avglag[$col->week->value] = $col->avgLag->value;
                        $avgwordcount[$col->week->value] = $col->avgWordCount->value;
                    }
                    //$data[] = $posts;
                    $data[] = $avglag;
                    $data[] = $avgwordcount;
                }
                
                // Provide info to table.
                $return["recordsFiltered"] = $response->itemCount;
                $return["data"] = $data;
            }
        } catch(exception $e) {
            // Error, return invalid data, and pluginjs will show error in table.
            $return["data"] = "-";
        }
        echo json_encode($return);
        exit();
    }
    
    /**
     * Report Average Words Weekly by Post.
     *
     */
    private function average_words_weekly_by_post($element) {
        $output = "";
        $output .= $this->output->discussionreport_average_words_weekly_by_post($element);
        return $output; 
    }
    
    /**
     * Report Social Structure.
     *
     */    
    private function social_structure($element) {
        $output = "";
        $output .= $this->output->discussionreport_social_structure($element);
        return $output; 
    }
    
    /**
     * Report Social Structure With Words Count.
     * @param unknown $element
     */
    private function social_structure_with_words_count($element) {

        $output = "";
        $output .= $this->output->discussionreport_social_structure_with_words_count($element);
        return $output;
    }
    
    /**
     * Report Social Structure With Contributions Adjusted
     */
    private function social_structure_with_contributions_adjusted($element) {
    
        $output = "";
        $output .= $this->output->discussionreport_social_structure_with_contributions_adjusted($element);
        return $output;
    }   
    
    /**
     * Report Social Structure Coefficient of Critical Thinking
     */
    private function social_structure_coefficient_of_critical_thinking($element) {
    
        $output = "";
        $output .= $this->output->discussionreport_social_structure_coefficient_of_critical_thinking($element);
        return $output;
    }
    
    /**
     * Report Main Terms 
     */
    private function main_terms($element) {
    
        $output = "";
        $output .= $this->output->discussionreport_main_terms($element);
        return $output;
    }
}
