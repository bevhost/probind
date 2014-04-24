<HTML>
<HEAD>
<TITLE>Domain details</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="images/BG-shadowleft.gif">

<?php

require('inc/lib.inc');

function update_description($domain, $descrip, $options)
{
	if (strlen($options) > 254) {
		print "<FONT color=RED size=+2>Too much options; maximum options length is 255 symbols</FONT><BR>\n";
		return;
	}
	$query = "SELECT id FROM zones WHERE domain = '$domain'";
	$rid = sql_query($query);
	($zone = mysql_fetch_array($rid))
		or die("No such domain: $domain<P>\n");
	mysql_free_result($rid);
	$id = $zone['id'];
	$query = "DELETE FROM annotations WHERE zone = $id";
	$rid = sql_query($query);
	$query = "INSERT INTO annotations (zone, descr) VALUES ($id, '$descrip')";
	$rid = sql_query($query);
	$options = strtr($options, "'",'"');
	$rid = sql_query("UPDATE zones SET options='$options', updated=1 WHERE id=$id");
}

function domain_details($domain)
{
	$query = "SELECT id, mtime, ctime, options FROM zones WHERE domain = '$domain'";
	$rid = sql_query($query);
	($zone = mysql_fetch_array($rid))
		or die("No such domain: $domain<P>\n");
	mysql_free_result($rid);
	$mtime = $zone['mtime'];
	$ctime = $zone['ctime'];
	$id = $zone['id'];
	$options = htmlspecialchars($zone['options']);
	$result = "<H1>$domain</H1>\n";
	$result .= "<FORM action=\"zonedetails.php\" method=\"post\">\n";
	$result .= "<TABLE width=\"100%\" border><TR align=left><TH>Zone created in database</TH><TH>Last update in database</TH></TR>
<TR><TD>$ctime</TD><TD>$mtime</TD></TR>
<TR><TD colspan=2 align=CENTER>Zone options (<b>no syntax check here!</b>):<HR> <TEXTAREA rows=10 cols=60 name=\"options\">$options</TEXTAREA></TD></TR>
<TR><TD colspan=2 align=CENTER>When you create or modify a domain, please add a note to the domain
description. The note should contain the date, your initials and a few
words about what was done (and perhaps why). Please add new entries
at the top.
<HR>
";
	$query = "SELECT descr from annotations WHERE zone = $id";
	$rid = sql_query($query);
	$result .= "
<INPUT type=\"hidden\" name=\"action\" value=\"textupdate\">
<INPUT type=\"hidden\" name=\"domain\" value=\"$domain\">
<TEXTAREA name=\"description\" rows=10 cols=60>\n";
	if ($annotation = mysql_fetch_array($rid)) {
		$result .= $annotation['descr'];
	}	
	$result .= "</TEXTAREA>
</TR><TR>
<TD><INPUT type=reset></TD>
<TD><INPUT type=submit name=submit value=Update></TD>
</TR></TABLE>
</FORM>
";
	mysql_free_result($rid);
	return $result;
}

#
# MAIN
#
get_input();

if ($domain = $INPUT_VARS['domain']) {
	if (isset($INPUT_VARS['action']) && ($INPUT_VARS['action'] == "textupdate")) {
		update_description($INPUT_VARS['domain'],
			htmlspecialchars($INPUT_VARS['description']), $INPUT_VARS['options']);
	}
	print domain_details($domain);
} else {
	print "No domain specified.<P>$QUERY_STRING<P>\n";
}

?>

</BODY>
</HTML>
