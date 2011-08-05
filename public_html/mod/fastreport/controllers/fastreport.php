<?php

class fastreport_controller extends controller {

    function __construct($mod_name, $mod_instance_id) {
        parent::__construct($mod_name, $mod_instance_id);
        $this->require_login();
    } // function __construct


    function index() {
        $this->redirect_to('index', array('controller' => 'log') );
    } // function index


    function download() {
        echo "Hi there from download!";
        $strftimedatetime = get_string("strftimedatetime");

        $filename = 'logs_'.userdate(time(),get_string('backupnameformat'),99,false);
        $filename .= '.txt';
        $this->send_headers($filename);

        echo get_string('savedat').userdate(time(), $strftimedatetime)."\n";
        echo $text."\n";
    } // function download


    function send_headers($filename) {
        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=$filename");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");               
    } // function send_headers

} // class fastreport_controller 

?>
