<?php

include_once("{$CFG->dirroot}/mod/soda/class.controller.php");

class soda {


    function __construct() {
        $this->create_mod_library_functions( get_called_class() );
    } // function __construct


    function display() {            
        $mod_name = get_called_class();
        global ${$mod_name}, $CFG, $cm, $course;
        $redirect_actions = array('update', 'delete', 'create', 'insert', 'save');
        ${$mod_name} = static::get_module_instance();
        static::set_variables($mod_name);

        // TODO: call default module->index() to show 'all instances of module'
        $action = optional_param('action', 'index', PARAM_RAW);
        if ( in_array($action, $redirect_actions) ) {
            $this->dispatch($action);
        } 
        $this->add_layout_and_dispatch($action);
    } // function display


    function dispatch($action) {
        $mod_name = get_called_class();
        global $CFG, ${$mod_name};
        $controller = optional_param('controller', 'module', PARAM_RAW);
        $record_id = optional_param("{$controller}_id", false, PARAM_INT);
        $class = $controller . "_controller";
        include_once("{$CFG->dirroot}/mod/$mod_name/controllers/$controller.php");
        $instance = new $class($mod_name, ${$mod_name}->id);
        $instance->$action($record_id);               
    } // function dispatch


    function add_layout_and_dispatch($action) {
        global $CFG;
        $this->print_header(get_called_class());
        echo "<script src='{$CFG->wwwroot}/mod/soda/lib.js' type='text/javascript'></script>";
        $this->dispatch($action);
        $this->print_footer(get_called_class());
    } // function add_layout_and_dispatch


    function print_header($mod_name) {
        global $cm, $course;
        $str_mod_name_singular = get_string('modulename', $mod_name);
        $navigation = build_navigation( get_string('modulename', $mod_name) );
        print_header_simple(format_string($mod_name), "", $navigation, "", "", true,
                            update_module_button($cm->id, $course->id, $str_mod_name_singular), navmenu($course, $cm));               
    } // function print_header


    function print_footer($mod_name) {
        global $course;
        print_footer($course);
    } // function print_footer


    // Creates globally defined wrapper functions for all static functions in this class.
    // Uses the 'mod_name' to prefix the functions to create unique function names.
    //
    // Example: if your mod is called 'planner', planner_get_instance will be created
    // as a wrapper for soda::get_instance().
    function create_mod_library_functions($mod_name) {
        $reflection = new ReflectionClass( get_called_class() );
        foreach($reflection->getMethods(ReflectionMethod::IS_STATIC) as $method) {
            $this->create_wrapper_function($method, $mod_name);
        }
        // special case
        if (! function_exists($mod_name . '_get_' . $mod_name) ) {
            eval('function ' . $mod_name . '_get_' . $mod_name . '($mod_id) { return ' . $mod_name . '::get_mod_by_id($mod_id); }');
        }
    } // function create_mod_library_functions


    function create_wrapper_function($method, $mod_name) {
        $base_method_name = $method->name;
        $param_names = array();
        $params = $method->getParameters();
        foreach ($params as $param) {
            $param_names[] = '$' . $param->getName();
        }
        $arguments = (count($param_names)) ? join(', ', $param_names) : '';
        $function_name = $mod_name . '_' . $base_method_name;
        if (! function_exists($function_name)) {
            $str = "function $function_name($arguments) { return $mod_name::$base_method_name($arguments); }";
            // Better close your eyes now...
            eval($str);
        }               
    } // function create_wrapper_function


    function initialize() {
                
    } // function initialize


    static function add_instance($mod_instance) {
        return insert_record(get_called_class(), $mod_instance);
    } // function add_instance


    static function update_instance($mod_instance) {
        $mod_instance->timemodified = time();
        $mod_instance->id = $mod_instance->instance;
        return update_record(get_called_class(), $mod_instance);       
    } // function update_instance


    static function delete_instance($id) {
        return delete_records(get_called_class(), "id", "$id");
    } // function delete_instance


    static function user_outline($course, $user, $mod, $mod_instance) { return true; }
    static function user_complete($course, $user, $mod, $planner) { return true; }
    static function print_recent_activity($course, $isteacher, $timestart) { return false; }
    static function cron() { return true; }
    static function grades($mod_id) { return NULL; }
    static function get_participants($mod_id) { return false; }
    static function scale_used($mod_id, $scale_id) { return false; }


    static function get_navigation() {
        global $course;
        if ($course->category) {
            return "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
        }
        return '';
    } // function get_navigation


    static function get_module_instance() { 
        global $course, $cm, $id;
        
        $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
        $a  = optional_param('a', 0, PARAM_INT);  // planner ID

        if ($id) {
            if (! $cm = get_record("course_modules", "id", $id)) { error("Course Module ID was incorrect"); }
            if (! $course = get_record("course", "id", $cm->course)) { error("Course is misconfigured"); }
            if (! $mod_instance = get_record(get_called_class(), "id", $cm->instance)) { error("Course module is incorrect"); }
        } else {
            if (! $mod_instance = get_record(get_called_class(), "id", $a)) { error("Course module is incorrect"); }
            if (! $course = get_record("course", "id", $mod_instance->course)) { error("Course is misconfigured"); }
            if (! $cm = get_coursemodule_from_instance("planner", $mod_instance->id, $course->id)) { error("Course Module ID was incorrect"); }
        }
        return $mod_instance;
    } // function get_module_instance


    static function get_mod_by_id($mod_id) {
        return get_record(get_called_class(), "id", $mod_id);
    }


    static function set_variables($mod_name) {
        global $cm, $id, $course, $context;
        if (! $cm = get_coursemodule_from_id($mod_name, $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        } 
        
        if (!$context = get_context_instance(CONTEXT_MODULE, $cm->id)) {
            print_error('badcontext');
        }  
    } // function set_variables

} // class soda 
?>
