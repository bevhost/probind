<?php

/*
Copyright (C) 2008 Scott J. LeCompte - http://www.myphpscripts.net

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

global $client_mac, $client_ip, $auth, $nas_id;  #If using MAC Address blocking these should be provided.

// Specify the absolute path to the script
$inj_path = dirname(__FILE__)."/";
if (!isset($webmaster)) $webmaster = "webmaster@".$_SERVER["HTTP_HOST"];
$alerts = 1;
$file = "exploits.txt";

if (isset($client_mac)) {
	$autoban = 1;
	$orig_client_mac = $client_mac;
} else {
	$orig_client_mac = $client_mac = "";
	if ($nas_id) $autoban = 0;
	else $autoban = 1;
}
if (!isset($nas_id)) $nas_id=0;
if (!isset($client_ip)) $client_ip=false;

$kill = 0;
$testmode = 0;
#if ($dev) $testmode = 1;

// Function to convert plain text to hexadecimal.
function text2hex($string) {
	$hex = '';
	$len =
	strlen($string) ;
	for ($i = 0; $i < $len; $i++) {
		$hex .= '%' . str_pad(dechex(ord($string[$i])), 2, 0, STR_PAD_LEFT);
	}
	return $hex;
}

// Begin query scanner

// Variables
$email = text2hex($webmaster);
$ip_addr = $_SERVER['REMOTE_ADDR'];
$mac = $client_mac;
$url = 'http://' . str_replace("?" . $_SERVER['QUERY_STRING'],"",$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

if (($client_ip) and ($nas_id)) $ip_addr = $client_ip;
$AuthUser = false;
$xtra = "";
if (isset($auth->auth["uname"])) {
	$AuthUser = $auth->auth["uname"];
}
if ($AuthUser) {
	$xtra = " OR UserName='$AuthUser' ";
} else {
	$AuthUser = "NotLoggedIn";
}
if ($mac) $xtra .= " OR mac='$mac'";
if ($nas_id) $xtra = " AND nas_id='$nas_id'$xtra";

$sql = "SELECT * FROM exploits WHERE ip='$ip_addr'$xtra";
$db->query($sql);

// Check if the visitor's IP address is already banned or logged.
if ($db->next_record()) {
	extract($db->Record);
	$your="";
	if ($ip == $ip_addr) {
		$your .= "<b>IP Address</b> [ <font color='#0000F0'>$ip</font> ] ";
	}
	if (($mac) and ($mac == $client_mac)) { 
		if ($your) $your .= ", ";
		$your .= "<b>MAC Address</b> [ <font color='#0000F0'>$mac</font> ] ";
	}
	if (($UserName<>"NotLoggedIn") and ($UserName == $AuthUser)) {
		if ($your) $your .= ", ";
		$your .= "<b>UserName</b> [ <font color='#0000F0'>$AuthUser</font> ] ";
	}

	// If the IP address is banned, exit and display "Banned" message.
	if ($banned == 1) { 
		$logged = 1;
		exit('
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title>Banned</title>
			</head>
			<body>
				<div style="margin:auto;width:780px;border:2px solid #F00000;background-color:#ffdbdb;padding:0px 10px 10px 10px;z-index:100;color:#000000;">
					<h1 style="color:#F00000;margin:0px;padding:0px;">Banned</h1>
					<h3 style="margin:0px 20px 10px 20px;">Your ' . $your . ' has been banned for suspected code injection.</h3>
					<div style="border:1px solid #00000;margin:0px 40px 0px 40px;padding:10px;background-color:#ffefef;">
						' . $your . '<br />
						<b>Query String</b> : ' . $string . '<br />
						<b>Date & Time</b> : ' . $datetime . '
					</div>
					<p style="margin:10px 20px 0px 20px;">If you believe this is an error, please contact the webmaster at <a href="mailto:' . $email . '?Subject=Inquiry:%20Banned%20for%20code%20injection - IP: ' . $ip . ' - Violation: ' . $violation . '">' . str_replace(array("@", "%40", "."), array(" <b><s>[ a t ]</s></b> ", " <b><s>[ a t ]</s></b> ", " <b><s>[ d o t ]</s></b> "), $webmaster) . '</a>. '.$helpdesk_phone.'</p>
				</div>
			</body>
		</html>
		'); 
	}
	// If the IP address is logged, display the "Logged" message.
	else {
		$logged = 1;
		echo '
			<div style="position:relative;top:0px;left:0px;border:2px solid #F00000;background-color:#ffdbdb;padding:5px;z-index:100;color:#000000;">
				<b style="color:#F00000">Logged</b> : Your ' . $your . '  has been logged for suspected code injection.  <br />Please contact the webmaster at <a href="mailto:' . $email . '?Subject=Inquiry:%20Logged%20for%20code%20injection - IP: ' . $ip . ' - Violation: ' . $violation . '">' . str_replace(array("@", "%40", "."), array(" <b><s>[ a t ]</s></b> ", " <b><s>[ a t ]</s></b> ", " <b><s>[ d o t ]</s></b> "), $webmaster) . '</a> '.$helpdesk_phone.'
			</div>
		'; 
	}
}
// Format the query string
$string = htmlspecialchars(str_replace(array("%3C","%3E","%5C"), array("<",">","\\"), $_SERVER['QUERY_STRING']));
$lines = file($inj_path . $file);
if ($testmode == 1) {
	echo '<b style="color:#800000">Checking String</b> : ' . $string . ' ';
}

$your="";
if ($ip_addr) {
	$your .= "<b>IP Address</b> [ <font color='#0000F0'>$ip_addr</font> ] ";
}
if ($client_mac) { 
	if ($your) $your .= ", ";
	$your .= "<b>MAC Address</b> [ <font color='#0000F0'>$client_mac</font> ] ";
}
if (($AuthUser<>"NotLoggedIn") and ($AuthUser)) {
	if ($your) $your .= ", ";
	$your .= "<b>UserName</b> [ <font color='#0000F0'>$AuthUser</font> ] ";
}
// Loop through the exploit list and compare it to the query string
$violation = "";
$logged = 0;
foreach ($lines as $line => $value) {
	$check = htmlspecialchars(str_replace(array(" \n","\n"," ","\r"), "", $value));
	// If an exploit is found in the query string, do the following
	if (stristr($string,$check)) {
		$datetime = date("Y-m-d H:i:s");
		$kill = 1;
		$violation .= $db->quote($check);
		if ($testmode == 1) {
			echo '<b style="color:#F00000">Match Found</b> : ' . $check . ' ';
		}
		// If the IP is not already logged, do the following
		if ($logged != 1) {
			// If Autoban is set, log the data and ban the IP
			if ($autoban == 1) {
				$logged = 1;
				$insert = $db->quote($string);
				$db->query("INSERT INTO exploits (ip, nas_id, mac, UserName, string, banned, violation, target, datetime) VALUES".
						"('$ip_addr', '$nas_id', '$mac', '$AuthUser', $insert, '1', $violation, '$url', NOW()) ");  // Insert the data into MySQL
				// Display the "Banned" message
				echo '
				<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
					<head>
						<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
						<title>Banned</title>
					</head>
					<body>
						<div style="position:relative;top:0px;left:0px;margin:auto;width:780px;border:2px solid #F00000;background-color:#ffdbdb;padding:0px 10px 10px 10px;z-index:100;color:#000000;">
							<h1 style="color:#F00000;margin:0px;padding:0px;">Banned</h1>
							<h3 style="margin:0px 20px 10px 20px;">Your '.$your.' has been banned for suspended code injection.</h3>
							<div style="border:1px solid #00000;margin:0px 40px 0px 40px;padding:10px;background-color:#ffefef;">
								' . str_replace(",","<br />",$your) . '<br />
								<b>Query String</b> : ' . $string . '<br />
								<b>Date & Time</b> : ' . $datetime . '
							</div>
							<p style="margin:10px 20px 0px 20px;">If you believe this is an error, please contact the webmaster at <a href="mailto:' . $email . '?Subject=Inquiry:%20Banned%20for%20code%20injection - IP: ' . $ip_addr . ' - Violation: ' . $violation . '">' . str_replace(array("@", "%40", "."), array(" <b><s>[ a t ]</s></b> ", " <b><s>[ a t ]</s></b> ", " <b><s>[ d o t ]</s></b> "), $webmaster) . '</a> '.$helpdesk_phone.'.</p>
						</div>
					</body>
				</html>
				'; 
			}
			// If Autoban is not set, log the data, but do not ban the IP
			else {
				$logged = 1;
				$insert = $db->quote($string);
				$db->query("INSERT INTO exploits (ip, nas_id, mac, UserName, string, banned, violation, target, datetime) VALUES".
					   "('$ip_addr', '$nas_id', '$mac', '$AuthUser', $insert, '0', $violation, '$url', NOW()) ");
				// Display the "Logged" message
				echo'
				<div style="position:relative;top:0px;left:0px;border:2px solid #F00000;background-color:#ffdbdb;padding:5px;z-index:100;color:#000000;">
					<b style="color:#F00000">Logged</b> : Your IP address <b>[ <font color="#0000F0">' . $ip_addr . '</font> ]</b> has been logged for suspected code injection.  Please contact the webmaster at <a href="mailto:' . $email . '?Subject=Inquiry:%20Logged%20for%20code%20injection - IP: ' . $ip_addr . ' - Violation: ' . $violation . '">' . str_replace(array("@", "%40", "."), array(" <b><s>[ a t ]</s></b> ", " <b><s>[ a t ]</s></b> ", " <b><s>[ d o t ]</s></b> "), $webmaster) . '</a>.
				 '.$helpdesk_phone.'</div>
				'; 			
			}
		}
}
	else {
		if ($testmode == 1) {
	#		echo '<b style="color:#00C000">No Match</b> : ' . $check . ' ';
		}
	}
}

if ($kill == 1) { 
	if ($alerts == 1) {
		$subject = "[ " . $_SERVER['HTTP_HOST'] . " ] Injection Exploit Alert";
		$msg = "An injection exploit attempt was logged on $datetime.\n\n";
		$msg .= "IP Address: $ip_addr\n";
		$msg .= "MAC Address: $client_mac\n";
		$msg .= "UserName: $AuthUser\n";
		$msg .= "URL: $url\n";
		$msg .= "Query String: $string\n";
		$msg .= "Violation: $violation\n";
		$msg .= "_________________________\n";
		$msg .= "proBind Injection Scanner";
		$headers = "From: myPHPscripts Injection Scanner <" . $webmaster . ">\n";
		$headers .= "Reply-To: <" . $webmaster . ">\n";
		$headers .= "Return-Path: <" . $webmaster . ">\n";
		$headers .= "Envelope-from: <" . $webmaster . ">\n";
		$headers .= "Content-Type: text/plain; charset=UTF-8\n";
		$headers .= "MIME-Version: 1.0\n";
		mail($webmaster, $subject, $msg, $headers);
	}
	exit(); 
}

#mysql_close($con);
$client_mac = $orig_client_mac;

?>
