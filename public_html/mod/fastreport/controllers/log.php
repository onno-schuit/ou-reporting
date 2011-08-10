<?php

include_once("{$CFG->dirroot}/mod/fastreport/controllers/fastreport.php");

class log_controller extends fastreport_controller {

    var $columns = array('shortname', 'time', 'ip', 'fullname', 'mod_action', 'info');
    var $log_display_cache = array();
    var $fullname_cache = array();
    var $info_cache = array();

    static $separation = ';';

    function index() {
        $this->get_view();
    } // function index


    function download() {
        $this->send_headers('logs_' . userdate(time(), get_string('backupnameformat'), 99, false) . '.csv');
        echo '"' . implode('"' . log_controller::$separation . '"', $this->columns) . "\"\n";
        $this->process_logs( function($row, $columns) {
            $counter = 0;
            foreach($columns as $column) {
                $counter++;
                $value = (isset($row[$column])) ?  $row[$column] : '';
                echo ($counter == count($columns)) ? "\"$value\"\n" : "\"$value\"" . log_controller::$separation;
            }
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
        $this->get_view(array('recordset' => $recordset, 'columns' => $this->columns));
    } // function on_screen


    function process_logs($function) {
        $recordset = $this->get_logs();
        while ($row = $recordset->FetchRow()) {
            $log_display_info = $this->get_log_display_info($row);
            //$row['info'] = strip_tags(urldecode(format_string($this->set_info_row($log_display_info, $row))));
            $row['info'] = strip_tags(urldecode($this->set_info_row($log_display_info, $row)));
            $function($row, $this->columns);
        }               
    } // function process_logs


    function set_info_row($log_display_info, $row) {
        if (! ($log_display_info && !empty($row['info'])) ) return $row['info'];
        if (($log_display_info->mtable == 'user') and ($log_display_info->field ==  sql_concat('firstname', "' '" , 'lastname'))) {
            if (isset($this->fullname_cache[ $row['info'] ])) return $this->fullname_cache[ $row['info'] ];
            return $this->fullname_cache[ $row['info'] ] = fullname(get_record($log_display_info->mtable, 'id', $row['info']), true);
        } 
        $key = $log_display_info->mtable . $log_display_info->field . $row['info'];
        if (isset($this->info_cache[$key])) return $this->info_cache[$key];
        return $this->info_cache[$key] = get_field($log_display_info->mtable, $log_display_info->field, 'id', $row['info']);
    } // function set_info_row


    function get_log_display_info($row) {
        $key = $row['module'] . $row['action'];
        if (isset($this->log_display_cache[ $key ])) return $this->log_display_cache[ $key ];
        return $this->log_display_cache[ $key ] = get_record('log_display', 'module', $row['module'], 'action', $row['action']);
    } // function get_log_display_info


    function get_logs() {
        global $CFG;
        $start_date  = $this->get_timestamp('start_date'); 
        $end_date  = $this->get_timestamp('end_date'); 
        $condition = ((!$start_date) || (!$end_date)) ? "" : " WHERE time BETWEEN $start_date AND $end_date ";
        return get_recordset_sql("SELECT course.shortname,
                                      FROM_UNIXTIME(log.time) AS time,
                                      log.ip,
                                      CONCAT(user.firstname, ' ', user.lastname) AS fullname,
                                      CONCAT(log.module, ' ', log.action) AS mod_action,
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
