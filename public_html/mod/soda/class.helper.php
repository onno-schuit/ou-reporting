<?php

class helper {


    function __call($method, $args) {
        if (!method_exists($this->controller, $method)) {
            throw new Exception("Unknown method [$method]");
        }

        return call_user_func_array(
            array($this->controller, $method),
            $args
        );
    }


    function __get($property) {
        if (!property_exists($this->controller, $property)) {
            throw new Exception("Unknown property [$property]");
        }
        return $this->controller->$property;
                
    } // function __get

} // class helper 

?>
