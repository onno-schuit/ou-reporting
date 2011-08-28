<?php 


/**
 */
class block_metagroups extends block_base {
    function init() {
        $this->title = get_string('blockname','block_metagroups');
        $this->version = 2007101510;
    }

    function has_config() {return true;}

    function get_content() {
        global $USER, $CFG, $COURSE, $context;
        
        if (! has_capability('block/metagroups:useblock', $context, $USER->id)) {
            return ""; 
        }

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        
        
        // TEST
        /*
        $metagroups->load_courses("6,8");
        if (! $source = $metagroups->extract_course(6)) error("boink!");
        if (! $target = $metagroups->extract_course(8)) error("boink!");
        $target->synchronize_with($source);
        */
        // END TEST 
        $courses = $this->get_courses();
        $source_id = $this->get_template_course_id();
        $this->content->text .= "
          <div>
            " . get_string("copy_label", "block_metagroups") . "
            <div>
              <script type='text/javascript'>
                function metagroups_validation() {
                  var s = document.getElementById(\"source_course_id\");
                  if (s.options[s.selectedIndex].value == 0) {
                    alert('" . get_string("invalid_submit", "block_metagroups") . "');
                    return false;
                  }
                  return true;
                }
              </script>
              <form onsubmit='return metagroups_validation();' action='$CFG->wwwroot/blocks/metagroups/test.php' name='template_course' method='post'>
                <input type='hidden' name='master_course_id' value='{$COURSE->id}'/>
                <input type='hidden' name='block_instance_id' value='{$this->instance->id}'/>
                <div style='text-align:center'>
                  <input type='submit' value='OK'/>
                </div>
              </form>
            </div>
          </div>
        ";
  
                        
        //$this->content->text .= "<a href='$CFG->wwwroot/blocks/metagroups/copy_course.php'>Copy template course (6) users and groups to current course ($COURSE->id)";

        return $this->content;
    } // function get_content
    
    
    function get_courses() {
        global $CFG;
        return get_records_sql(
            "SELECT id, shortname 
             FROM {$CFG->prefix}course
             ORDER BY shortname");
    } // function get_courses
    
    
    function get_template_course_id() {
        if (! $record = get_record("metagroups", "block_instance_id", $this->instance->id)) {
            return false;
        }
        return $record->template_course_id;
    } // function get_template_course_id

     /**
     * Delete everything related to this instance. Overrides parent method (which is just an interface).
     * @return boolean
     */
    function instance_delete() {
        delete_records_select("metagroupss", " block_instance_id = {$this->instance->id} ");
        return true;
    }
    
    
    public static function store_template_course_id($course_id, $template_course_id, $block_instance_id) {
        global $CFG;
        if (! $record = get_record("metagroupss", "block_instance_id", $block_instance_id)) {
            return execute_sql("INSERT INTO {$CFG->prefix}metagroupss (course_id, template_course_id, block_instance_id) VALUES ($course_id, $template_course_id, $block_instance_id)", false);
        }
        if ($record->template_course_id != $template_course_id) {
            $record->template_course_id = $template_course_id;          
            return update_record("metagroupss", $record);
        }
    } // public static function store_template_course_id
    
} // class block_metagroups

?>
