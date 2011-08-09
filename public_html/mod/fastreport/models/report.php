<?php

include_once("{$CFG->dirroot}/mod/soda/class.model.php");

class report extends model {


    function validate() {
        /*
        validator::is_valid('start_date', array('type' => 'integer',
                                                'required' => true,
                                                'max' => 100,
                                                'min' => 2));
        */
        return validator::is_valid('start_date', function($start_date) { return ($start_date != ''); });
    } // function validate


} // class education_profession 
