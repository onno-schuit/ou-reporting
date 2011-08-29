<?php

class master_group {


    function __construct($properties, $metacourse) {
        foreach($properties as $key => $value) {
            $this->$key = $value;
        }
        $this->metacourse = $metacourse;
        $this->master_course = $metacourse->master_course;
    } // function __construct


    function sync() {
         if ($metagroup = $this->find_metagroup() ) {
            $this->update_metagroup($metagroup);
        } else {
            $metagroup = $this->copy();
        }
        $this->sync_group_members($metagroup);               
    } // function sync


    function sync_group_members($metagroup) {
        global $CFG;
        execute_sql("DELETE FROM {$CFG->prefix}groups_members WHERE groupid = " . $metagroup['id'], false);
        execute_sql("INSERT INTO {$CFG->prefix}groups_members (groupid, userid, timeadded) 
                     SELECT  " . $metagroup['id'] . ", userid, timeadded 
                     FROM {$CFG->prefix}groups_members 
                     WHERE groupid = " . $this->id, false);
    } // function sync_group_members


    function find_metagroup() {
        global $CFG;
        if (! $rs = get_recordset_sql("SELECT * FROM {$CFG->prefix}groups WHERE id IN (
                                           SELECT group_id 
                                           FROM {$CFG->prefix}metagroups_groups 
                                           WHERE parent_group_id = {$this->id}
                                           AND metacourse_id = {$this->metacourse->id}
                                       )")) return false;
        return $rs->FetchRow();
    } // function find_metagroup


    function update_metagroup($metagroup) {
        if ($metagroup['name'] == $this->name) return;
        $metagroup['name'] = $this->name;
        $metagroup['timemodified'] = time();
        $obj = (object) $metagroup;
        return update_record('groups', $obj);
    } // function update_metagroup


    function copy() {
        $new_group = clone $this;
        $new_group->courseid = $this->metacourse->id;
        if (! $new_group->id = insert_record('groups', $new_group)) return false;

        $this->register_new_group_with_block($new_group);

        return (array) $new_group;
    } // function copy


    function register_new_group_with_block($new_group) {
        $metagroups_group = new object();
        $metagroups_group->metagroups_block_id = $this->master_course->block_instance_id;
        $metagroups_group->group_id = $new_group->id;
        $metagroups_group->parent_group_id = $this->id;
        $metagroups_group->master_course_id = $this->master_course->id;
        $metagroups_group->metacourse_id = $this->metacourse->id;
        $metagroups_group->timecreated = $metagroups_group->timemodified = time();

        return insert_record('metagroups_groups', $metagroups_group);
    } // function register_new_group_with_block   

} // class master_group 
