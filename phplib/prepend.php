<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998,1999 SH Online Dienst GmbH
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id: prepend.php3,v 1.9 1999/10/24 10:21:24 kk Exp $
 *
 */ 

//setup php for working with Unicode data
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');
ob_start('mb_output_handler');
ini_set('default_charset', 'UTF-8');

ini_set('display_errors', 'On');

date_default_timezone_set("Australia/Sydney");

if (array_key_exists("parameters",$_GET)) {
  $explode = explode("/", $_GET["parameters"]);
  for($i=0; $i < count($explode); $i++){
    $params[$explode[$i]] = $explode[$i+1];
    $varname = $explode[$i];
    $$varname = $explode[$i+1];
    $i++;
  }
}

$_ENV["local"] = $_SERVER["DOCUMENT_ROOT"]."/phplib/"; 
$_ENV["libdir"] = "/usr/share/phplib/";

$QUERY_STRING="";

require($_ENV["libdir"] . "db_mysql.inc");  /* Change this to match your database. */
require($_ENV["libdir"] . "ct_sql.inc");    /* Change this to match your data storage container */
require($_ENV["libdir"] . "session.inc");   /* Required for everything below.      */
require($_ENV["libdir"] . "auth.inc");      /* Disable this, if you are not using authentication. */
require($_ENV["libdir"] . "perm.inc");      /* Disable this, if you are not using permission checks. */
require($_ENV["local"] . "local.inc");     /* Required, contains your local configuration. */
require($_ENV["libdir"] . "page.inc");      /* Required, contains the page management functions. */


function EventLog($Description,$ExtraInfo="",$Level="Info") {
	global $PHP_SELF, $argv, $REMOTE_ADDR, $auth;
	$db = new DB_hotspot;
	if ($PHP_SELF) $Program=$PHP_SELF; else $Program = $argv[0];
	if ($auth) $UserName = $auth->auth["uname"]; else $UserName="NotLoggedIn";
	$sql = "INSERT INTO EventLog SET ";
	$sql .= "Program = '$Program',";
	$sql .= "IPAddress = '$REMOTE_ADDR',";
	$sql .= "UserName = '$UserName',";
	$sql .= "Description = '".addslashes($Description)."',";
	$sql .= "Level = '$Level',";
	$sql .= "ExtraInfo = '".addslashes($ExtraInfo)."'";
	$db->query($sql);
}


?>
