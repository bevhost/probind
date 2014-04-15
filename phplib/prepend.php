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
if (php_sapi_name() === 'cli') $_ENV["local"] = getcwd()."/phplib/";
$_ENV["libdir"] = "/usr/share/phplib/";

$QUERY_STRING="";

require($_ENV["libdir"] . "db_mysqli.inc");  /* Change this to match your database. */
require($_ENV["libdir"] . "ct_sql.inc");    /* Change this to match your data storage container */
require($_ENV["libdir"] . "session.inc");   /* Required for everything below.      */
require($_ENV["libdir"] . "auth.inc");      /* Disable this, if you are not using authentication. */
require($_ENV["libdir"] . "perm.inc");      /* Disable this, if you are not using permission checks. */
require($_ENV["local"] . "local.inc");     /* Required, contains your local configuration. */
require($_ENV["libdir"] . "page.inc");      /* Required, contains the page management functions. */


function EventLog($Description,$ExtraInfo="",$Level="Info") {
	global $PHP_SELF, $argv, $REMOTE_ADDR, $auth;
	$db = new DB_probind;
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

function get_request_values($varlist) {
        $vars = explode(",",$varlist);
        foreach($vars as $v) {
                if (isset($_REQUEST[$v])) {
                        if (is_array($_REQUEST[$v])) $GLOBALS[$v]=$_REQUEST[$v];
                        else $GLOBALS[$v]=to_utf8($_REQUEST[$v]);
                        if ($v=="submit") {
                                $ok = @$_ENV["AllowPostWithoutReferer"];        /* Pages with this set will always pass */
                                if (array_key_exists("HTTP_REFERER",$_SERVER)) {
                                        $proto = array_key_exists("HTTPS",$_SERVER) ? "https://" : "http://" ;
                                        $ref = strtolower($proto.$_SERVER["HTTP_HOST"]);
                                        if (substr(strtolower($_SERVER["HTTP_REFERER"]),0,strlen($ref))==$ref) { $ok=true; }
                                }
                                if (!$ok) {
                                #       die("Suspected CSRF Attack");
                                }
                        }
                } else {
                        if (!isset($GLOBALS[$v])) $GLOBALS[$v]=false;
                }
        }
        if (!is_array($GLOBALS[$v])) $GLOBALS["q_".$v]="'".addslashes($GLOBALS[$v])."'";  // can't use database specific yet, not defined.
}

if (array_key_exists("widemode",$_REQUEST)) $GLOBALS["widemode"]=$_REQUEST["widemode"];

function to_utf8( $string ) {
// From http://w3.org/International/questions/qa-forms-utf-8.html
    if ( preg_match('%^(?:
      [\x09\x0A\x0D\x20-\x7E]            # ASCII
    | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
    | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
    | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
    | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
    | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
    | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
    | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
)*$%xs', $string) ) {
        return $string;
    } else {
        return iconv( 'CP1252', 'UTF-8', $string);
    }
}



?>
