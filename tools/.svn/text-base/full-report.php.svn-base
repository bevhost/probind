<HTML>
<HEAD>
<TITLE></TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="../images/BG-shadowleft.gif">
<TABLE width="99%">
<TR>
 <TD align="left"><H1>Annotated list of all known zones</H1>
</TR>
</TABLE>
<HR><P>

<?php

require '../inc/lib.inc'; 

#
# MAIN
#
get_input();

$query = "SELECT domain, id, master FROM zones WHERE domain != 'TEMPLATE' ORDER BY domain";
$rid = sql_query($query);
while (list($dom, $id, $master) = mysql_fetch_row($rid)) {
	if ($master)
		$zonedetails = "[Slave zone, master = $master]";
	else {
		$query = "SELECT COUNT(*) FROM records WHERE zone = $id";
		$rid2 = sql_query($query);
		list($rrs) = mysql_fetch_row($rid2);
		mysql_free_result($rid2);
		$zonedetails = "[Authoritative zone, contains $rrs Resource Records]";
	}
	$query = "SELECT descr FROM annotations WHERE zone = $id";
	$rid2 = sql_query($query);
	list($descr) = mysql_fetch_row($rid2);
	mysql_free_result($rid2);
	$html = join("<BR>\n", explode("\n", $descr));
	print "<B>$dom</B> $zonedetails<P>\n<UL>$html</UL>\n<P>\n";
}
mysql_free_result($rid);

?>

</BODY>
</HTML>