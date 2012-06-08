<HTML>
<HEAD>
<TITLE>Statistics</TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<BODY bgcolor="#9999cc" background="../images/BG-shadowleft.gif">
<TABLE width="99%">
<TR>
 <TD align="left"><H1>Statistics</H1></TD>
</TR>
</TABLE>
<HR><P>

<?php
require_once('../inc/lib.inc');

if ($warnings = database_state())
{
	echo '<div class="warning">';
	
	echo '<strong>Warning: The database is not in an operational state. The following problems exist:</strong>';
	
	echo '<ul>';
	
	foreach ($warnings as $warningmsg)
		echo '<li>', $warningmsg, '</li>';

	echo '</ul>';

	echo '</div>';
	
	
}

adjust_serials();

// Begin check for changes.
$update = FALSE;

$rid = sql_query("SELECT domain, id, zonefile FROM zones WHERE updated AND domain != 'TEMPLATE' ORDER BY zonefile");

$count = mysql_num_rows($rid);

if ($count) {
	print "<P>The database contains changes to $count domains.<P><UL>";
	while ($row = mysql_fetch_row($rid)) {
		print "<LI><A HREF=\"../brzones.php?frame=records&zone=$row[1]\">$row[0]</A>\n";
	}
	print "</UL>\n";
	$update = TRUE;
}

mysql_free_result($rid);
$rid = sql_query("SELECT domain FROM deleted_domains");
$count = mysql_num_rows($rid);
if ($count) {
	print "<P>The following domains have been deleted from the database.<P><UL>\n";
	while ($row = mysql_fetch_row($rid)) {
		print "<LI>$row[0]\n";
	}
	print "</UL><HR>\n";
	$update = TRUE;
}
mysql_free_result($rid);
if ($update)
	print "These changes have not been pushed out to the actual DNS servers. Click the 'Update' button above to execute the changes to these zones:<P><HR><P>\n";

# Bragging ...
print "<TABLE width=\"100%\"><TR><TD>\n";
print "<TABLE border cellpadding=4><TR><TH>Statistic</TH><TH>Count</TH></TR>\n";
$rid = sql_query("SELECT COUNT(id) FROM zones WHERE (master IS NULL OR NOT master) AND domain != 'TEMPLATE'");
$count = mysql_result($rid, 0);
mysql_free_result($rid);
print "<TR><TD>Authoritative domains</TD><TD align=right>$count</TD></TR>\n";
$rid = sql_query("SELECT COUNT(id) FROM zones WHERE master");
$count = mysql_result($rid, 0);
mysql_free_result($rid);
print "<TR><TD>Slave domains</TD><TD align=right>$count</TD></TR>\n";
$rid = sql_query("SELECT COUNT(records.id) FROM records, zones WHERE zones.domain != 'TEMPLATE' AND records.zone = zones.id ");
$count = mysql_result($rid, 0);
mysql_free_result($rid);
print "<TR><TD>Resource records</TD><TD align=right>$count</TD></TR>\n";
print "</TABLE></TD><TD>\n";

$rid = sql_query("SELECT id, hostname, ipno, type, pushupdates, mknsrec, state FROM servers ORDER BY hostname");
print "<TABLE border cellpadding=4><TR><TH colspan=7>Managed DNS Servers</TH></TR>\n";
print "<TR><TH>Server</TH><TH>Ip number</TH><TH>Type</TH><TH>Update?</TH><TH>NS record?</TH><TH>state</TH><TH>test</TH></TR>\n";
while ($row = mysql_fetch_row($rid)) {
	$id = $row[0];
	$type = ($row[3] == 'M' ? 'Master' : 'Slave');
	$push = ($row[4] ? 'Yes' : 'No');
	$mkrec = ($row[5] ? 'Yes' : 'No');
	$state = $row[6];
	$B = "";
	if ($push == 'No')
	    $state = 'OK';
	switch ($state) {
			case 'OK':  
				$T = "<B>OK</B>";
				$B = " bgcolor=lightgreen";
			 	break;
			case 'OUT': 
				$T = "<B>need update</B>"; 
				$B = " bgcolor=yellow";
				break;
			case 'CHG': 
				$T = "<B>need push</B>";   
				$B = " bgcolor=yellow";
				break;
			case 'CFG': 
				$T = "<B>need reconfig</B>"; 
				$B = " bgcolor=yellow";
				break;
			case 'ERR': 
				$T = "<FONT COLOR=WHITE><BLINK>Update error</BLINK></FONT>";
				$B = " bgcolor=red";
				break;
			default:  $T = $state;break;
	}
	print "<TR><TD><A HREF=\"servers.php?action=detailedview&server=$row[0]\">$row[1]</A></TD><TD>$row[2]</TD><TD align=center>$type</TD><TD align=center>$push</TD><TD align=center>$mkrec</TD><TD align=CENTER $B>$T</TD><TD align=center><A href=\"../test.php?id=$id\"><img src=\"../images/greenbutton.gif\" border=0 high=16 width=24></A></TD></TR>\n";
}
?>
</TABLE>
</TD>
</TR>
</TABLE>
<HR>
<P>
</BODY>
</HTML>