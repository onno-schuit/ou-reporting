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
        $this->sync_with_master_groupings();
        $this->remove_deleted_master_groups_from_metacourse();
        //$this->remove_deleted_master_groupings_from_metacourse();
    } // function synchronize


    function sync_with_master_groups() {
        foreach($this->master_course->groups as $master_group_properties) {
            $master_group = new master_group($master_group_properties, $this);
            $master_group->sync();
        }
    } // function sync_with_master_groups


    function sync_with_master_groupings() {
        foreach($this->master_course->groupings as $master_grouping_properties) {
            $master_grouping = new master_grouping($master_grouping_properties, $this);
            $master_grouping->sync();
        }      
    } // function sync_with_master_groupings


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


} // class metacourse

?>
