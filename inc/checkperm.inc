<?php
	include dirname(__FILE__) . "/../phplib/prepend.php";

//Configuration file sits in same directory as this file.
//Using dirname() is a valid way to get the right file no matter
//what our caller was.
if(file_exists(dirname(__FILE__) . "/config.php"))
        require_once(dirname(__FILE__) . "/config.php");
else
        die('Could not load configuration file. Please check this ProBIND installation.');

page_open(array("sess" => "probind_Session", "auth" => "probind_Auth", "perm" => "probind_Perm"));

$PermMaskBitValue = 1;
foreach(explode(",",$PERMS) as $p) {
	$perm->permissions[$p] = $PermMaskBitValue;
	$PermMaskBitValue *= 2;
}
if ($PermMaskBitValue<2) die('Please set permissions in configuration file');

?>
