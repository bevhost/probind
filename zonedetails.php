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
	$db = new DB_probind;
	if (strlen($options) > 254) {
		print "<FONT color=RED size=+2>Too much options; maximum options length is 255 symbols</FONT><BR>\n";
		return;
	}
	$db->prepare("SELECT id FROM zones WHERE domain = ?");
	$db->execute($domain);
	if ($db->next_record()) $zone=$db->Record;
		else die("No such domain: $domain<P>\n");
	$id = $zone['id'];
	$db->prepare("DELETE FROM annotations WHERE zone = ?");
	$db->execute($id);
	$db->prepare("INSERT INTO annotations (zone, descr) VALUES (?, ?)");
	$db->execute($id,$descrip);
	$options = strtr($options, "'",'"');
	$db->prepare("UPDATE zones SET options=?, updated=1 WHERE id=?");
	$db->execute($options,$id);
}

function domain_details($domain)
{
	$db = new DB_probind;
	$db->prepare("SELECT id, mtime, ctime, options FROM zones WHERE domain = ?");
	$db->execute($domain);
	if ($db->next_record()) $zone=$db->Record;
		else die("No such domain: $domain<P>\n");
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
	$db->prepare("SELECT descr from annotations WHERE zone = ?");
	$db->execute($id);
	$result .= "
<INPUT type=\"hidden\" name=\"action\" value=\"textupdate\">
<INPUT type=\"hidden\" name=\"domain\" value=\"$domain\">
<TEXTAREA name=\"description\" rows=10 cols=60>\n";
	if ($db->next_record())
		$result .= $db->Record['descr'];
	$result .= "</TEXTAREA>
</TR><TR>
<TD><INPUT type=reset></TD>
<TD><INPUT type=submit name=submit value=Update></TD>
</TR></TABLE>
</FORM>
";
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
