<?php
require_once("{$CFG->dirroot}/config.php");
require_once("{$CFG->dirroot}/blocks/metagroups/class.master_course.php");
require_once("{$CFG->dirroot}/blocks/metagroups/class.metacourse.php");

/**
 */
class metagroups {
  
    var $courses;
    var $metacourses = array();
    
    
    function __construct() {
      global $temp_table_counter;
      $temp_table_counter = 0;
    } // function __construct


    function synchronize_metacourses_for($master_course_id) {
        $master_course = new master_course($master_course_id);
        $master_course->synchronize_all();

        //print_object($master_course);
                
    } // function synchronize_metacourses_for



    // copies all users and groups from source course to target course
    function copy_course($source_course_id, $target_course_id) {
        if (! $this->load_and_build_courses("$source_course_id, $target_course_id")) {
            error("Could not load courses");
        }
        if (! $source = $this->extract_course($source_course_id)) return false;
        if (! $target = $this->extract_course($target_course_id)) return false;
        return $target->synchronize_with($source); 
    } // function copy_course
    
    
    function synchronize_all() {
        global $CFG;

    } // function synchronize_all
    
    
    function extract_course($id) {
        foreach($this->courses as $course) {
            if ($course->id == $id) {
                return $course;
            }
        }
        return false;
    } // function extract_course
    

    function load_and_build_courses($string_ids) {
        global $CFG;        
        
        if (!$contexts = get_records_sql(
            "SELECT id, instanceid 
             FROM {$CFG->prefix}context 
             WHERE contextlevel = 50
             AND instanceid IN ($string_ids)")
        ) { return false; }
        
        
        
        $array_ids = explode(',', $string_ids);
        foreach($array_ids as $course_id) {
            $this->courses[$course_id] = new synchronizer_course($contexts, $course_id);
        }
        return true;
    } // function load_and_build_courses
        
}  // class metagroups





class synchronizer_course {
    var $context_id = false;
    var $id;
    
    function __construct($contexts, $course_id) {
        $this->id = $course_id;
        $this->set_context_id($contexts);       
    } // function __construct
    
    
    function set_context_id($contexts) {
        foreach ($contexts as $context) {
            if ($this->id == $context->instanceid)  {
                $this->context_id = $context->id;
            }
        }      
    } // function set_context_id
    
    
    function synchronize_with($source) {
        if ( (!$this->context_id) || (!$this->id) ) return false; 
        $this->remove_existing_course_memberships();
        $this->delete_group_members_not_in($source);
        $this->copy_users_from($source);
        $this->update_groups($source);
        return true;
    } // function synchronize_with
    
    
    function remove_existing_course_memberships() {
        return $this->delete_role_assignments($this->id, 50);
    } // function remove_existing_course_memberships
    
    
    function delete_group_members_not_in($source) {       
        global $CFG, $temp_table_counter;
        $temp_table_counter++;
        // DML does not support executing two consecutive SQL statements in one call of execute_sql...
        execute_sql("CREATE TEMPORARY TABLE tmptable_groups_members_{$temp_table_counter}
                       SELECT gm.userid, g.name
                       FROM {$CFG->prefix}groups_members AS gm, {$CFG->prefix}groups AS g
                       WHERE g.courseid = {$source->id}
                       AND gm.groupid = g.id", false);        
        execute_sql(" CREATE INDEX idx_userid ON tmptable_groups_members_{$temp_table_counter} (userid); ", false);  
        execute_sql(" CREATE INDEX idx_name ON tmptable_groups_members_{$temp_table_counter} (name); ", false);  
        /*
        return execute_sql(
            "DELETE gm1 FROM {$CFG->prefix}groups_members AS gm1 INNER JOIN {$CFG->prefix}groups AS g1 ON gm1.groupid = g1.id
             WHERE g1.courseid = {$this->id} 
             AND gm1.userid NOT IN (
               SELECT userid
               FROM tmptable_groups_members_{$temp_table_counter}
               WHERE name LIKE g1.name)", false);        
         */
        execute_sql(
            "DELETE gm1 FROM {$CFG->prefix}groups_members AS gm1 INNER JOIN {$CFG->prefix}groups AS g1 ON gm1.groupid = g1.id
             WHERE g1.courseid = {$this->id} 
             AND NOT EXISTS (
               SELECT *
               FROM tmptable_groups_members_{$temp_table_counter}
               WHERE name LIKE g1.name
               AND userid = gm1.userid)", false);  

        return execute_sql("DROP TEMPORARY TABLE IF EXISTS tmptable_groups_members_{$temp_table_counter}", false);
    } // function delete_group_members_not_in     
    
    
    function copy_users_from($source) {
        global $CFG;
        // -- Insert multiple records --
        // Moodle does not support inserting multiple records at once (at all...). So, here's our own multiple insert
        execute_sql("INSERT INTO {$CFG->prefix}role_assignments 
                     (roleid, contextid, userid, hidden, timestart, timeend, timemodified, modifierid, enrol, sortorder)
                     SELECT ra2.roleid, $this->context_id, ra2.userid, ra2.hidden, ra2.timestart, ra2.timeend, ra2.timemodified, ra2.modifierid, ra2.enrol, ra2.sortorder
                     FROM {$CFG->prefix}role_assignments AS ra2, {$CFG->prefix}context AS c
                     WHERE ra2.contextid = c.id
                     AND c.contextlevel = 50
                     AND c.instanceid = $source->id", false);  
    } // function copy_users_from
    
    
    function update_groups($source) {
        $this->delete_groups_not_in($source);
        $this->add_groups_from($source);
        $this->add_group_members_from($source);
    } // function update_groups
    
    
    function delete_groups_not_in($source) {
        global $CFG, $temp_table_counter;     
        $temp_table_counter++;
        // DML does not support executing two consecutive SQL statements in one call of execute_sql...
        execute_sql("CREATE TEMPORARY TABLE tmptable_groups_{$temp_table_counter}
                            SELECT name FROM {$CFG->prefix}groups WHERE courseid = {$source->id}", false);
        return execute_sql("DELETE g1 FROM {$CFG->prefix}groups AS g1 WHERE g1.courseid = {$this->id} AND NOT EXISTS 
                              (SELECT g2.name FROM tmptable_groups_{$temp_table_counter} AS g2
                               WHERE g2.name = g1.name)", false);
    } // function delete_groups_not_in
    
    
    function add_groups_from($source) {
        global $CFG;
        return execute_sql("INSERT INTO {$CFG->prefix}groups 
                            (courseid, name, description)
                            SELECT $this->id, g2.name, g2.description
                            FROM {$CFG->prefix}groups AS g2
                            WHERE g2.courseid = $source->id
                            AND g2.name NOT IN (
                              SELECT g3.name 
                              FROM {$CFG->prefix}groups AS g3
                              WHERE g3.courseid = $this->id)", false);      
    } // function add_groups_from
    
    
    function add_group_members_from($source) {
        global $CFG;
        return execute_sql("INSERT INTO {$CFG->prefix}groups_members 
                            (groupid, userid)
                            SELECT target_g.id, source_gm.userid
                            FROM {$CFG->prefix}groups AS target_g,
                                 {$CFG->prefix}groups_members AS source_gm,
                                 (SELECT * FROM mdl_groups WHERE courseid = {$source->id}) AS source_g
                            WHERE target_g.courseid = {$this->id}  
                            AND source_g.name LIKE target_g.name
                            AND source_gm.groupid = source_g.id
                            AND NOT EXISTS (
                              SELECT *
                              FROM mdl_groups_members
                              WHERE userid = source_gm.userid
                              AND groupid = target_g.id
                            )", false); 
/*
        return execute_sql("INSERT INTO {$CFG->prefix}groups_members 
                            (groupid, userid)
                            SELECT target_g.id, source_gm.userid
                            FROM {$CFG->prefix}groups AS target_g, {$CFG->prefix}groups_members AS source_gm
                            WHERE target_g.courseid = {$this->id}  
                            AND target_g.name IN (
                              SELECT source_g.name
                              FROM {$CFG->prefix}groups AS source_g
                              WHERE source_g.id = source_gm.groupid
                              AND source_g.courseid = {$source->id}
                              AND NOT EXISTS (
                                SELECT userid 
                                FROM {$CFG->prefix}groups_members AS gm JOIN {$CFG->prefix}groups AS g ON gm.groupid = g.id  
                                WHERE gm.userid = source_gm.userid
                                AND gm.groupid = target_g.id
                                AND g.courseid = {$source->id}
                              )
                            )
                            AND NOT EXISTS (
                              SELECT * 
                              FROM {$CFG->prefix}groups_members AS gm2
                              WHERE gm2.groupid = target_g.id
                              AND gm2.userid = source_gm.userid)", false);      
 */
    } // function add_group_members_from    

    
    function delete_role_assignments($target_instance_ids, $contextlevel) {
        global $CFG;
        return delete_records_select("role_assignments",
                                     "contextid IN (
                                        SELECT id 
                                        FROM {$CFG->prefix}context 
                                        WHERE contextlevel = $contextlevel 
                                        AND instanceid IN ($target_instance_ids))"
                                     );      
    } // function delete_role_assignments
    
} // class synchronizer_course


?>

