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
        if ($metagroup = $this->find_metagroup_for($this->id) ) {
            $this->update_metagroup($metagroup);
        } else {
            $metagroup = $this->copy();
        }
        $this->sync_group_members($metagroup);
    } // function sync_master_group


    function remove_deleted_master_groups_from_metacourse() {
        global $CFG;

        $master_group_ids = '0';
        if (count($this->master_course->groups)) {
            $master_group_ids = join(',', array_keys($this->master_course->groups));
        }
        // delete groupmembers
        execute_sql("DELETE FROM {$CFG->prefix}groups_members WHERE groupid IN (
                         SELECT group_id
                         FROM {$CFG->prefix}metagroups_groups 
                         WHERE master_course_id = {$this->master_course->id} 
                         AND parent_group_id NOT IN ($master_group_ids)
                     )", false);

        // delete groups
        execute_sql("DELETE FROM {$CFG->prefix}groups WHERE id IN (
                         SELECT group_id
                         FROM {$CFG->prefix}metagroups_groups 
                         WHERE master_course_id = {$this->master_course->id} 
                         AND parent_group_id NOT IN ($master_group_ids)
                     )", false);

        // delete registration with block
        execute_sql("DELETE FROM {$CFG->prefix}metagroups_groups 
                     WHERE master_course_id = {$this->master_course->id} 
                     AND parent_group_id NOT IN ($master_group_ids)", false);
    } // function remove_deleted_master_groups_from_metacourse 


    function update_metagroup($metagroup, $master_group) {
        if ($metagroup['name'] == $master_group['name']) return;
        $metagroup['name'] = $master_group['name'];
        $metagroup['timemodified'] = time();
        $obj = (object) $metagroup;
        return update_record('groups', $obj);
    } // function update_metagroup


    function find_metagroup_for($mastergroup_id) {
        global $CFG;
        if (! $rs = get_recordset_sql("SELECT * FROM {$CFG->prefix}groups WHERE id IN (
                                           SELECT group_id 
                                           FROM {$CFG->prefix}metagroups_groups 
                                           WHERE parent_group_id = $mastergroup_id
                                           AND metacourse_id = {$this->id}
                                       )")) return false;
        return $rs->FetchRow();
    } // function find_metagroup_for


    function copy_master_group($master_group) {
        $new_group = (object) $master_group;
        unset($new_group->id);
        $new_group->courseid = $this->id;
        if (! $id = insert_record('groups', $new_group)) return false;
        $new_group->id = $id;

        $this->register_new_group_with_block($new_group, (object) $master_group);

        return (array) $new_group;
    } // function copy_master_group


    function register_new_group_with_block($new_group, $master_group) {
        $metagroups_group = new object();
        $metagroups_group->metagroups_block_id = $this->master_course->block_instance_id;
        $metagroups_group->group_id = $new_group->id;
            $metagroups_group->parent_group_id = $master_group->id;
        $metagroups_group->master_course_id = $this->master_course->id;
        $metagroups_group->metacourse_id = $this->id;
        $metagroups_group->timecreate = $metagroups_group->timecreate = time();

        return insert_record('metagroups_groups', $metagroups_group);
    } // function register_new_group_with_block


    function sync_group_members($master_group, $metagroup) {
        global $CFG;
        execute_sql("DELETE FROM {$CFG->prefix}groups_members WHERE groupid = " . $metagroup['id'], false);
        execute_sql("INSERT INTO {$CFG->prefix}groups_members (groupid, userid, timeadded) 
                     SELECT  " . $metagroup['id'] . ", userid, timeadded 
                     FROM {$CFG->prefix}groups_members 
                     WHERE groupid = " . $master_group['id'], false);
    } // function sync_group_members


} // class master_group 
