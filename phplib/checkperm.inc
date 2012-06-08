<?php
	include "/var/www/probind/public_html/phplib/prepend.php";
	page_open(array("sess" => "probind_Session", "auth" => "probind_Auth", "perm" => "probind_Perm"));
	global $probind_Session;
	if (!$probind_Session) {
//		exit;
	}
?>
