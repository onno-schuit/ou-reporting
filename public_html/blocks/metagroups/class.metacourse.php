<?php

class metacourse extends master_course {

    var $master_course;
    var $meta_groups = array();
    var $meta_groupings = array();


    function __construct($id, $master_course) {
        global $CFG;

        parent::__construct($id);
        $this->master_course = $master_course;
        $this->load_all(array(
            'meta_groups' => "SELECT * FROM {$CFG->prefix}metagroups_groups WHERE metacourse_id = $this->id",
            'meta_groupings' => "SELECT * FROM {$CFG->prefix}metagroups_groupings WHERE metacourse_id = $this->id",
        ));
    } // function __construct


    function synchronize() {
        $this->sync_with_master_groups();
        //$this->sync_groupings();
    } // function synchronize



    function sync_with_master_groups() {
        foreach($this->master_course->groups as $master_group) {
            $this->sync_master_group($master_group);
        }
                
    } // function sync_with_master_groups


    function sync_master_group($master_group) {
        if (! $metagroup = $this->find_metagroup_for($master_group['id']) ) {
            $metagroup = $this->copy_master_group($master_group);
        }
        //$this->sync_group_members($master_group, $metagroup);
    } // function sync_master_group



    function find_metagroup_for($mastergroup_id) {
        global $CFG;
        if (! $rs = get_recordset_sql("SELECT * FROM {$CFG->prefix}groups WHERE id IN (
            SELECT group_id FROM {$CFG->prefix}metagroups_groups WHERE parent_group_id = $mastergroup_id)")) return false;
        return $rs->FetchRow();
    } // function find_metagroup_for


    function copy_master_group($master_group) {
        $obj = (object) $master_group;
        unset($obj->id);
        $obj->courseid = $this->id;
        if (! $id = insert_record('groups', $obj)) return false;
        $obj->id = $id;


        // register_new_group_with_block

        return (array) $obj;
    } // function copy_master_group


    function sync_group_members($master_group, $metagroup) {
        // find group members from master_group
        if (! $master_group_members = $this->find_group_members_for($master_group)) return;
        // Simply replace existing set of metagroup members with mastergroup members 
        // (group member id does not seem to be in use as foreign key)
        $this->replace_metamembers_with($master_group_members);

    } // function sync_group_members
} // class metacourse

?>
