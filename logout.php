<?php
include('phplib/prepend.php');
page_open(array("sess"=>"probind_Session"));
$widemode="";
$auth->logout();
page_close();
header("Location:index.php");
?>
