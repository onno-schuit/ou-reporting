<?php

class model {

    function __construct($properties) {
        foreach($properties as $key => $value) {
            $this->$key = $value;
        }               
    } // function __construct
} // class model 

?>
