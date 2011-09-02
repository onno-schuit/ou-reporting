<?php

require_once('../config.php');
require ('scripts/ouidm_enc.php');
			
if (isset($_SERVER['HTTP_OUUSER'])){
  $username = $_SERVER['HTTP_OUUSER'];
  $ipadrs = $_SERVER['SERVER_ADDR'];
}

/**
 * 
 * Get the username from the protected HTTP HEADER
 * 
 * */

global $CFG;


if (!empty($_POST)) { 
    header("Location: $CFG->wwwroot/login/index.php?ouidmdata=".serialize($_POST));
	exit();
}

?>

<html>
<head>
	<title>Open Universiteit Nederland (Moodle-IDM)</title>
	<script type="text/javascript" src="scripts/prototype.js"></script>	
	<style type="text/css">
	.mydiv {
		background: url(images/idm_ou.jpg) no-repeat top left;
		text-align: center;
		border: .1em solid #ccc; 
		height: 300px;
		width:920px;
		padding-top: 120px;
		margin-left:auto;
		margin-right:auto;
	}	
	</style>	
</head>

<body onLoad='$("newdata").submit()'>
<div class="mydiv" >
    <form id="newdata" name="newdata" method="post">
        <?php
        echo "<h3>Please wait a moment, you will be forwarded to the right URL!</h3>";    
        echo '<input type="hidden" id="username" name="username" value="'. encrypt($username, MY_KEY) .'">'
            .'<input type="hidden" id="ipadrs" name="ipadrs" value="'. encrypt($ipadrs,MY_KEY) .'">';
        ?>
    </form>
    <p>
    	<img src="images/ajax-loader.gif">
    </p>    
</div>

</body>
</html>