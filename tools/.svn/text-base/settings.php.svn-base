<HTML>
<HEAD>
<TITLE>ProBIND Settings</TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="../images/BG-shadowleft.gif">
<TABLE width="99%">
<TR>
 <TD align="left"><H1>ProBIND Settings</H1></TD>
</TR>
</TABLE>
<HR><P>

<?php
require('../inc/lib.inc');

$preamble = '
NB: When you change these settings, the change is only reflected in
domains which are pushed onto the DNS servers after the update. If
you want to make the change apply to all domains in the database, use
the bulk update function, then push updates.<BR><HR><BR>
';

$settings_list['default_external_dns'] = '<B>DEFAULT EXTERNAL DNS:</B><BR>
The default DNS server to use for the external consistency checks. E.g.
ns1.uplink-isp.net.';
$settings_list['default_origin'] = '<B>Default MNAME:</B><BR>The origin 
of domains managed in this database, as published in the SOA records.
This would usually be the hostname of the master DNS server, e.g.
ns1.mydomain.net.';
$settings_list['default_ptr_domain'] = '<B>DEFAULT PTR DOMAIN:</B><BR>PTR 
records are automatically put in the zone files for each A record in 
the database. This setting controls which domain they belong to, e.g.
mydomain.net. Enter a value of NONE to disable this feature.';
$settings_list['hostmaster'] = '<B>Default RNAME:</B><BR>The mailbox to publish 
in SOA records. If you have a "hostmaster" alias which forwards to the
DNS staff, put "hostmaster" in here. Remember to use a "." in stead
of the "@", e.g. hostmaster.mydomain.net.';

$is_bool['two_step_update'] = 1;
$settings_list['two_step_update'] = '<B>TWO_STEP_UPDATE:</B><BR>Server update consiists of a three
independent operations - <B>generate</B> files, <B>push</B> files, and <B>reconfigure</B> server.
In normal mode, systep propose to make all 3 operation at once; in two step mode (valie 1),
system makes <B>generate</B> and <B>push</B> first, and prompt to continue so operator has
extra option to verify logs and ensure that everything went OK.';

$is_bool['slave_on_slaves'] = 1;
$settings_list['slave_on_slaves'] = '<B>SLAVE_ON_SLAVES:</B><BR>System can allocate secondary zones\
on master servers only (if this variable is 0) or on all servers (if it is 1); in some cases (such as
INTRANET zone) it may be necessary to turn this feature on.';

$is_bool['show_all'] = 1;
$settings_list['show_all'] = '<B>SHOW_ALL:</B><BR>If this variable is 1, system shows all zones
on the initial screen; if 0, you need to <B>search</B> zone first. Turn it off in the very big environment.';

function browse_settings()
{
	$seen = array();
	
	global $settings_list, $preamble, $is_bool;
	$query = "SELECT name, value FROM blackboard ORDER BY name";
	$rid = sql_query($query);
	$result = $preamble;
	$result .= "<FORM action=\"settings.php\" method=\"post\">
<INPUT type=\"hidden\" name=\"action\" value=\"update\">
<TABLE>\n";
	while ($setting = mysql_fetch_array($rid)) {
		$name = $setting['name'];
		$value = $setting['value'];
		$text = $settings_list[$name];
		$bool = $is_bool[$name];
		if ($bool) {
		    if ($value == 1) {
			$s1 = " SELECTED";
			$s0 = "";
		    }
		    else {
			$s0 = " SELECTED";
			$s1 = "";
		    }
		    $field = "<SELECT name=\"$name\"><OPTION value=0 $s0>Off</OPTION><OPTION value=1 $s1>On</OPTION></SELECT>";
		}
		else
		    $field = "<INPUT type=\"text\" name=\"$name\" value=\"$value\" SIZE=40 MAXLENGTH=255>";
		if ($text) {
			$result .= "<TR>
 <TD>$text</TD>
 <TD valign=\"top\">$field</TD>
</TR>\n";
			$seen[$name] = 1;
		}
	}
	reset($settings_list);
	$value = '';
	while ($setting = each($settings_list)) {
		$name = $setting[0];
		$text = $settings_list[$name];
		if (!$seen[$name]) {
		    $bool = $is_bool[$name];
		    if ($bool) {
			if ($value == 1) {
			    $s1 = " SELECTED";
			    $s0 = "";
			}
			else {
			    $s0 = " SELECTED";
			    $s1 = "";
			}
			$field = "<SELECT name=\"$name\"><OPTION value=0 $s0>Off</OPTION><OPTION value=1 $s1>On</OPTION></SELECT>";
		    }
		    else
			$field = "<INPUT type=\"text\" name=\"$name\" value=\"$value\" SIZE=40 MAXLENGTH=255>";
			$result .= "<TR>
 <TD>$text</TD>
 <TD valign=\"top\">$field</TD>
</TR>\n";
	}
	}
	$result .= "<TR><TD></TD><TD><INPUT type=\"submit\" value=\"Update settings\"></TD></TR></TABLE>\n</FORM>\n";
	mysql_free_result($rid);
	if (strlen($locks = list_locks()))
		$result .= $locks;
	return $result;
}

function update_settings($input)
{
	global $settings_list;
	reset($settings_list);
	$warnings = "";
	while ($setting = each($settings_list)) {
		$name = $setting[0];
		$value = strtr($input[$name], "@", ".");
		if ($value != '') {
			$sql[] = "DELETE FROM blackboard WHERE name = '$name'";
			$sql[] = "INSERT INTO blackboard (name, value) VALUES ('$name', '$value')";
		} else 
			$warnings .= "<LI>You must specify a value for <B>$name</B>\n";
	}
	if (strlen($warnings)) 
		return "<UL>$warnings</UL>\n";
	while ($q = each($sql)) {
		sql_query($q[1]);
	}
	return browse_settings();
}

function break_lock($input)
{
	$lock = $input['lockname'];
	$query = "DELETE FROM blackboard WHERE name = 'PUSHLOCK'";
	leave_crit($lock);
	return browse_settings();
}

#
# MAIN
#
get_input();
switch (strtolower($INPUT_VARS['action'])) {
case 'update':
	print update_settings($INPUT_VARS);
	break;
case '':
case 'browse':
	print browse_settings();
	break;
case 'breaklock':
	print break_lock($INPUT_VARS);
	break;
default:
	while ($var = each($INPUT_VARS)) {
		print "$var[0] = $var[1]<BR>\n";
	}
	print "action = '".$INPUT_VARS['action']."' => default<P>\n";
}

?>

</BODY>
</HTML>