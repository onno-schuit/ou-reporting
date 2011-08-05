<?php

include_once("{$CFG->dirroot}/mod/fastreport/controllers/fastreport.php");

class log_controller extends fastreport_controller {

    function index() {
        $this->get_view();
    } // function index

} // class log_controller
