<?php


require_once("{$CFG->dirroot}/blocks/metagroups/class.master_course.php");
require_once("{$CFG->dirroot}/blocks/metagroups/class.metacourse.php");

class synchronizer {

    var $courses;
    var $metacourses = array();
    var $block_instance_id;
    

    function __construct($block_instance_id) {
      global $temp_table_counter;

      $this->block_instance_id = $block_instance_id;
      $temp_table_counter = 0;
    } // function __construct


    function synchronize_metacourses_for($master_course_id) {
        $master_course = new master_course($master_course_id, $this->block_instance_id);
        $master_course->synchronize_all();
    } // function synchronize_metacourses_for

} // class synchronizer 
