<?php

include_once("{$CFG->dirroot}/mod/fastreport/controllers/fastreport.php");

class log_controller extends fastreport_controller {

    function index() {
        $this->get_view();
    } // function index


    function download() {
        $this->send_headers('logs_' . userdate(time(), get_string('backupnameformat'), 99, false) . '.csv');
        $this->process_logs( function($row) {
            echo implode(',', $row) . "\n";
        });
    } // function download


    // Sample code
    function save_to_file() {
        if (!$file = fopen("/home/onno/php/ou/public_html/logs_" . userdate(time(), get_string('backupnameformat'), 99, false) . '.csv', 'a')) exit("Could not open or create file");
        $this->process_logs( function($row) use (&$file) {
            fwrite($file, implode(',', $row) ."\n");
        });
    } // function save_to_file


    function on_screen() {
        $recordset = $this->get_logs();
        $this->get_view(array('recordset' => $recordset));
    } // function on_screen


    function process_logs($function) {
        $recordset = $this->get_logs();

        $ldcache = array();
        while ($row = $recordset->FetchRow()) {
            $ld = $this->get_log_display_info($ldcache, $row);
            if ($ld && !empty($row['info'])) {
                // ugly hack to make sure fullname is shown correctly
                if (($ld->mtable == 'user') and ($ld->field ==  sql_concat('firstname', "' '" , 'lastname'))) {
                    $row['info'] = fullname(get_record($ld->mtable, 'id', $row['info']), true);
                } else {
                    $row['info'] = get_field($ld->mtable, $ld->field, 'id', $row['info']);
                }
            }

            //Filter log->info
            $row['info'] = format_string($row['info']);
            $row['info'] = strip_tags(urldecode($row['info']));    // Some XSS protection

            $function($row);
        }               
    } // function process_logs


    function get_log_display_info($ldcache, $row) {
        if (isset($ldcache[$row['module']][$row['action']])) return $ldcache[$row['module']][$row['action']];
        // TODO: replace with faster retrieval method
        return $ldcache[$row['module']][$row['action']] = get_record('log_display', 'module', $row['module'], 'action', $row['action']);
    } // function get_log_display_info


    function get_logs() {
        global $CFG;
        $start_date  = $this->get_timestamp('start_date'); 
        $end_date  = $this->get_timestamp('end_date'); 

        $condition = ((!$start_date) || (!$end_date)) ? "" : " WHERE time BETWEEN $start_date AND $end_date ";
        return get_recordset_sql("SELECT course.shortname,
                                      FROM_UNIXTIME(log.time),
                                      log.ip,
                                      CONCAT(user.firstname, ' ', user.lastname),
                                      CONCAT(log.module, ' ', log.action),
                                      log.info,
                                      log.module AS module,
                                      log.action AS action
                                  FROM {$CFG->prefix}log AS log 
                                  LEFT JOIN {$CFG->prefix}course AS course ON course.id = log.course
                                  LEFT JOIN {$CFG->prefix}user AS user ON user.id = log.userid
                                  $condition
                                  ORDER BY time DESC",
                                  $limitfrom=null, $limitnum=null);               
    } // function get_logs


    function get_timestamp($date) {
        if (! $parsed_date = ($s = optional_param($date, false, PARAM_RAW)) ? strptime($s, '%d-%m-%Y %H:%M') : $s ) return false;
        return mktime($parsed_date['tm_hour'], $parsed_date['tm_min'], $parsed_date['tm_sec'], $parsed_date['tm_mon'] + 1, $parsed_date['tm_mday'], $parsed_date['tm_year'] + 1900);
    } // function get_timestamp


    function send_headers($filename) {
        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=$filename");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");               
    } // function send_headers

} // class log_controller
