<html>
<head>
	<title>OU-IDM SSO Login Fail</title>
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
<?php  require_once('../config.php');?>
   

<body>
<div class="mydiv" >
        <?php
        	echo '<h3>'.$_GET['debug'].'</h3>';    
        ?>
</div>
</body>
</html>