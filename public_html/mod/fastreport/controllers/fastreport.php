<?php

class fastreport_controller extends controller {

    function __construct($mod_name, $mod_instance_id) {
        parent::__construct($mod_name, $mod_instance_id);
        $this->require_login();
    } // function __construct


    function index() {
        echo "This is fastreport_controller reporting!";        
    } // function index

} // class fastreport_controller 

?>
