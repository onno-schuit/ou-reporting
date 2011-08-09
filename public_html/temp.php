<?php

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
?>
