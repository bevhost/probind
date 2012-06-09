<?php

// Root of the ProBIND Application
$TOP = '/usr/local/bind-web/probind';

//default ownership groups, don't remove admin or owner
//admin has access to everything
//owner has access to zones owned by their username
//you can add multiple permissions to a user in auth_user table
$PERMS = "admin,owner,group1,group2,group3,etc";

// Database connection information
class DB_probind extends DB_Sql {
  var $Host     = "localhost";
  var $Database = "probind";
  var $User     = "probinduser";
  var $Password = "CHANGEME";
}

$idn_version = 2008;  // internationalisation version for punycode converter

// Optional settings are below. These are safe defaults, but you can adjust
// them if you need to.

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 'On');
date_default_timezone_set("Australia/Sydney");

// Where do we put temporary files?
$TMP='/tmp';

// Directories - templates, HOSTS and LOGS
$TEMPL_DIR = "$TOP/templates";
$HOST_DIR =  "$TOP/HOSTS";
$LOG_DIR  =  "$TOP/LOGS";

// Access to the HOSTS and LOGS directories from the web
$HOST_URL = "HOSTS/";
$LOG_URL  = "LOGS/";

// Some program defaults. This should move into the db at some point
$DEFAULT_PUSH = "push.remote";
$DEFAULT_DIR  = "/var/named9";
$DEFAULT_TMPL = "v9-master";
?>
