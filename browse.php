<?php
include('inc/lib.inc');
$_ENV["MyForeignKeys"]="LinkedTables";
$_ENV["MyForeignKeysDB"]="DB_probind";
error_reporting(E_ALL^(E_STRICT|E_NOTICE));
#$EditMode='on';


if ($export_results) {
        page_open(array("sess"=>$_ENV["SessionClass"],"auth"=>$_ENV["AuthClass"],"perm"=>$_ENV["PermClass"],"silent"=>"silent"));
} else {
        page_open(array("sess"=>$_ENV["SessionClass"],"auth"=>$_ENV["AuthClass"],"perm"=>$_ENV["PermClass"]));
	#if ($Field) include("pophead.ihtml"); else include("head.ihtml");
	if ($records_group_by) $by = " by $records_group_by"; else $by="";
	if (empty($Field)) include("header.php");
}
check_view_perms();

$db = new DB_probind;

echo "<span class='big'>Probind Records$by</span>";

