<?php

class master_grouping {


    function __construct($properties, $metacourse) {
        foreach($properties as $key => $value) {
            $this->$key = $value;
        }
        $this->metacourse = $metacourse;
        $this->master_course = $metacourse->master_course;

        //echo "id = {$this->id}<br/>";
    } // function __construct


    function sync() {
         if ($metagrouping = $this->find_metagrouping() ) {
            $this->update_metagrouping($metagrouping);
        } else {
            $metagrouping = $this->copy();
        }
        //echo "(after update/copy) id = {$this->id}<br/>";
        $this->sync_grouping_groups($metagrouping);               
    } // function sync


    function sync_grouping_groups($metagrouping) {
        global $CFG;
        execute_sql("DELETE FROM {$CFG->prefix}groupings_groups WHERE groupingid = " . $metagrouping['id'], false);
        execute_sql("INSERT INTO {$CFG->prefix}groupings_groups (groupingid, groupid, timeadded) 
                     SELECT  " . $metagrouping['id'] . ", mg.group_id, UNIX_TIMESTAMP() 
                     FROM {$CFG->prefix}metagroups_groups AS mg
                     WHERE mg.parent_group_id IN (
                         SELECT groupid 
                         FROM {$CFG->prefix}groupings_groups
                         WHERE groupingid = {$this->id}
                     )", false);
    } // function sync_grouping_groups


    function find_metagrouping() {
        global $CFG;
        if (! $rs = get_recordset_sql("SELECT * FROM {$CFG->prefix}groupings WHERE id IN (
                                           SELECT grouping_id 
                                           FROM {$CFG->prefix}metagroups_groupings 
                                           WHERE parent_grouping_id = {$this->id}
                                           AND metacourse_id = {$this->metacourse->id}
                                       )")) return false;
        return $rs->FetchRow();
    } // function find_metagrouping


    function update_metagrouping($metagrouping) {
        if ($metagrouping['name'] == $this->name) return;
        $metagrouping['name'] = $this->name;
        $metagrouping['description'] = $this->description;
        if (isset($this->configdata)) $metagrouping['configdata'] = $this->configdata;
        $metagrouping['timemodified'] = time();
        $obj = (object) $metagrouping;
        return update_record('groupings', $obj);
    } // function update_metagrouping


    function copy() {
        $new_grouping = clone $this;
        $new_grouping->courseid = $this->metacourse->id;
        if (! $new_grouping->id = insert_record('groupings', $new_grouping)) {
            error("Something went wrong while inserting a grouping");
            return false;
        }

        $this->register_new_grouping_with_block($new_grouping);

        return (array) $new_grouping;
    } // function copy


    function register_new_grouping_with_block($new_grouping) {
        $metagroups_grouping = new object();
        $metagroups_grouping->metagroups_block_id = $this->master_course->block_instance_id;
        $metagroups_grouping->grouping_id = $new_grouping->id;
        $metagroups_grouping->parent_grouping_id = $this->id;
        $metagroups_grouping->master_course_id = $this->master_course->id;
        $metagroups_grouping->metacourse_id = $this->metacourse->id;
        $metagroups_grouping->timecreated = $metagroups_grouping->timemodified = time();

        if (! $id = insert_record('metagroups_groupings', $metagroups_grouping)) {
            error("Something went wrong while inserting a record in metagroups_groupings");
        }
        return $id;
    } // function register_new_grouping_with_block   

} // class master_grouping 
