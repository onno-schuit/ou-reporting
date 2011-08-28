<?php

class metacourse extends master_course {

    var $master_course;
    var $meta_groups = array();
    var $meta_groupings = array();


    function __construct($id, $master_course) {
        global $CFG;

        parent::__construct($id, $master_course->block_instance_id);
        $this->master_course = $master_course;
        $this->load_all(array(
            'meta_groups' => "SELECT * FROM {$CFG->prefix}metagroups_groups WHERE metacourse_id = $this->id",
            'meta_groupings' => "SELECT * FROM {$CFG->prefix}metagroups_groupings WHERE metacourse_id = $this->id",
        ));
    } // function __construct


    function synchronize() {
        $this->sync_with_master_groups();
        //$this->sync_with_master_groupings();
        $this->remove_deleted_master_groups_from_metacourse();
        $this->remove_deleted_master_groupings_from_metacourse();
        //$this->sync_groupings();
    } // function synchronize


    function sync_with_master_groups() {
        foreach($this->master_course->groups as $master_group) {
            $this->sync_master_group($master_group);
        }
                
    } // function sync_with_master_groups


    function sync_with_master_groupings() {
        foreach($this->master_course->groupings as $master_grouping) {
            $this->sync_master_grouping($master_grouping);
        }      
    } // function sync_with_master_groupings


    function sync_master_group($master_group) {
        if ($metagroup = $this->find_metagroup_for($master_group['id']) ) {
            $this->update_metagroup($metagroup, $master_group);
        } else {
            $metagroup = $this->copy_master_group($master_group);
        }
        $this->sync_group_members($master_group, $metagroup);
    } // function sync_master_group


    function sync_master_grouping($master_grouping) {
        if ($metagrouping = $this->find_metagrouping_for($master_grouping['id']) ) {
            $this->update_metagrouping($metagrouping, $master_grouping);
        } else {
            $metagrouping = $this->copy_master_grouping($master_grouping);
        }
    } // function sync_master_grouping
    



    function remove_deleted_master_groupings_from_metacourse() {
        global $CFG;

        $master_grouping_ids = '0';
        if (count($this->master_course->groupings)) {
            $master_grouping_ids = join(',', array_keys($this->master_course->groupings));
        }

        // delete grouping_groups
        execute_sql("DELETE FROM {$CFG->prefix}groupings_groups WHERE id IN (
                         SELECT grouping_id
                         FROM {$CFG->prefix}metagroups_groupings 
                         WHERE master_course_id = {$this->master_course->id} 
                         AND parent_grouping_id NOT IN ($master_grouping_ids)
                     )", false);

         // delete groupings
        execute_sql("DELETE FROM {$CFG->prefix}groupings WHERE id IN (
                         SELECT grouping_id
                         FROM {$CFG->prefix}metagroups_groupings 
                         WHERE master_course_id = {$this->master_course->id} 
                         AND parent_grouping_id NOT IN ($master_grouping_ids)
                     )", false);       

        // delete registration with block
        execute_sql("DELETE FROM {$CFG->prefix}metagroups_groupings 
                     WHERE master_course_id = {$this->master_course->id} 
                     AND parent_grouping_id NOT IN ($master_grouping_ids)", false);
    } // function remove_deleted_master_groupings_from_metacourse 





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

    
    function find_metagrouping_for($mastergrouping_id) {
        global $CFG;
        if (! $rs = get_recordset_sql("SELECT * FROM {$CFG->prefix}groupings WHERE id IN (
                                           SELECT grouping_id 
                                           FROM {$CFG->prefix}metagroups_groupings 
                                           WHERE parent_grouping_id = $mastergrouping_id
                                           AND metacourse_id = {$this->id}
                                       )")) return false;
        return $rs->FetchRow();
    } // function find_metagrouping_for 


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

} // class metacourse

?>
