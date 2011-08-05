<?php

class controller {

    var $mod_name;
    var $model_name;
    var $view;
    var $redirect = false;

    function __construct($mod_name, $mod_instance_id) {
        $this->mod_name = $mod_name;
        $this->model_name = substr(get_called_class(), 0, strrpos(get_called_class(), "_")); 
        $this->base_url = $this->create_base_url();
        $instance_id_name = "{$this->mod_name}_id";
        $this->{$instance_id_name} = $mod_instance_id;
    } // function __construct

    
    function index() {
        echo "Please overwrite the Soda controller method 'index' with your own method.";
    } // function index


    function check_capability($capability_short = 'edit', $user = false) {
        global $context, $USER;
        if (!$user) $user = $USER;
        return has_capability("mod/{$this->mod_name}:$capability_short{$this->model_name}", $context, $user->id); 
    } // function check_capability


    function require_login() {
        require_login();
    } // function require_login


    function redirect_to($action, $parameters = false) {
        if ($parameters) $parameters = $this->remove_collections_from_parameters($parameters);
        $query_string = ($parameters) ? http_build_query($parameters) . '&' : '';
        $this->redirect = true;
        redirect( $this->get_url("{$query_string}action=$action" , '') );
    } // function redirect_to


    function remove_collections_from_parameters($parameters = array()) {
        if (! count($parameters)) return $parameters;
        $filtered_array = array();
        foreach($parameters as $key => $value) {
            if (! ((is_array($value)) || (is_object($value))) ) $filtered_array[$key] = $value;
        }
        return $filtered_array;
    } // function remove_collections_from_parameters 


    function get_view($data_array = array(), $view = false) {
        global $CFG, $id;

        foreach($data_array as $variable_name => $value) {
            $$variable_name = $value;
        }
        //include correct view
        $trace = debug_backtrace();
        $this->view = ($view) ? $view : $trace[1]['function'];
        ob_start(); // Start output buffering
        include_once("{$CFG->dirroot}/mod/{$this->mod_name}/views/{$this->model_name}/{$this->view}.html");
        $content = ob_get_contents(); // Store buffer in variable
        ob_end_clean(); // End buffering and clean up

        return $content;
    } // function get_view


    function get_url($parameter_string = '') {
        global $id;
        $postfix =  (strpos($parameter_string, 'controller') === false) ? "&controller={$this->model_name}" : "";
        $parameter_string = ($parameter_string == '') ? '' : "&$parameter_string";
        return $this->base_url . "?id=$id{$parameter_string}{$postfix}";
    } // function get_url


    function create_base_url() {
        global $CFG;
        return "{$CFG->wwwroot}/mod/{$this->mod_name}/index.php";
    } // function create_base_url 


    function form_parameters($parameters = array() ) {
        global $id;
        if (! array_key_exists('controller', $parameters) ) $parameters['controller'] = $this->model_name;
        if (! array_key_exists('id', $parameters) ) $parameters['id'] = $id;
        if (! array_key_exists('action', $parameters) ) {
            error("Please provide an action parameter for your form parameters in {$this->model_name}/{view $this->view}");
        }
        $inputs = array();
        foreach($parameters as $key => $value) {
            $inputs[] = "<input type='hidden' value='$value' name='$key' id='$key'/>";
        }
        return join("\n", $inputs);
    } // function form_parameters


    function post_to_url_js($parameters, $action = 'delete') {
        global $id;
        $quoted_parameters = array();
        foreach($parameters as $key => $value) {
            $quoted_parameters[] = "'$key': '$value'";
        }
        $parameters_string = (count($quoted_parameters)) ? join(',', $quoted_parameters) . ',' : '';
        return "post_to_url('$this->base_url',
                { $parameters_string 'controller': '$this->model_name', 'id': '$id', 'action': '$action' })"; 
    } // function post_to_url


    function base_url() {
        return $this->base_url;        
    } // function base_url

} // class controller

?>
