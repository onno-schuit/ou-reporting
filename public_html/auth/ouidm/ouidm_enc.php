<?php 

define(MY_KEY, '-S3689c416b736dbe9X@d');

function encrypt($string, $key) {
  $result = '';
  for($i=0; $i<strlen($string); $i++) {
    $char = substr($string, $i, 1);
    $keychar = substr($key, ($i % strlen($key))-1, 1);
    $char = chr(ord($char)+ord($keychar));
    $result.=$char;
  }

  return base64_encode($result);
}

function decrypt($string, $key) {
  $result = '';
  $string = base64_decode($string);

  for($i=0; $i<strlen($string); $i++) {
    $char = substr($string, $i, 1);
    $keychar = substr($key, ($i % strlen($key))-1, 1);
    $char = chr(ord($char)-ord($keychar));
    $result.=$char;
  }

  return $result;
}

/**********************************Database API**********************************/

    /**
     * Connects to the database
     * 
     * @params: $username of the new user 
     * @params: $password the news password
     * 
     * @return: true or false if the connection didn't succeed. 
     */
	function connect_oracle($username, $password, $host_and_db){ 
	
		//Connect to Oracle database
		$conn = oci_connect($username, $password, $host_and_db);
	
		return $conn;
	}
	 
	

?>