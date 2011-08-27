<?php


class master_course {


    var $groups = array();
    var $group_memberships = array();
    var $groupings = array();
    var $associated_metacourse_ids = array();
    var $id;
    var $block_instance_id;

    function __construct($id, $block_instance_id) {
        global $CFG;

        $this->id = $id;
        $this->block_instance_id = $block_instance_id;
        $this->load_all(array(
            'groupings' => "SELECT * FROM {$CFG->prefix}groupings WHERE courseid = $this->id",
            'associated_metacourse_ids' => "SELECT parent_course AS id 
                                            FROM {$CFG->prefix}course_meta 
                                            WHERE child_course = $this->id",
            'groups' => "SELECT * FROM {$CFG->prefix}groups WHERE courseid = $this->id"
        ));

    } // function __construct


    function load_all($setters) {
        foreach($setters as $setter => $sql) {
            $this->load($setter, $sql);
        }               
    } // function load_all


    function load($setter, $sql) {
        if (! $rs = get_recordset_sql($sql) ) return;
        while ($row = $rs->FetchRow()) {
            $this->{$setter}[$row['id']] = $row;
        }    
    } // function load_groups


    function synchronize_all() {
        foreach($this->associated_metacourse_ids as $metacourse_row) {
            $metacourse = new metacourse($metacourse_row['id'], $this);
            $metacourse->synchronize();
        }        
    } // function synchronize


                
} // class master_course 
