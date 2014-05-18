<?php 
require_once('inc/lib.inc');
include('header.php');


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

$db->query("SELECT domain, id, zonefile FROM zones WHERE updated AND domain != 'TEMPLATE'".access()." ORDER BY zonefile");

$count = $db->num_rows();

if ($count) {
	print "<P>The database contains changes to $count domains.<P><UL>";
	while ($db->next_record()) {
		extract($db->Record);
		print "<LI><A HREF=\"zones.php?zone=$id\">$domain</A>\n";
	}
	print "</UL>\n";
	$update = TRUE;
}

$db->query("SELECT domain FROM deleted_domains WHERE 1 ".access());
$count = $db->num_rows();
if ($count) {
	print "<P>The following domains have been deleted from the database.<P><UL>\n";
	while ($db->next_record()) {
		extract($db->Record);
		print "<LI>$domain</LI>\n";
	}
	print "</UL><HR>\n";
	$update = TRUE;
}
if ($update)
	print "These changes have not been pushed out to the actual DNS servers. Click the 'Update' button above to execute the changes to these zones:<P><HR><P>\n";

# Bragging ...
print "<TABLE width=\"100%\"><TR><TD>\n";
print "<TABLE border cellpadding=4><TR><TH>Statistic</TH><TH>Count</TH></TR>\n";
$db->query("SELECT COUNT(id) FROM zones WHERE (master IS NULL OR NOT master) AND domain != 'TEMPLATE'");
if ($db->next_record()) $count=$db->Record[0]; else $count='unknown';
print "<TR><TD>Authoritative domains</TD><TD align=right>$count</TD></TR>\n";

$db->query("SELECT COUNT(id) FROM zones WHERE master");
if ($db->next_record()) $count=$db->Record[0]; else $count='unknown';
print "<TR><TD>Slave domains</TD><TD align=right>$count</TD></TR>\n";

$db->query("SELECT COUNT(records.id) FROM records, zones WHERE zones.domain != 'TEMPLATE' AND records.zone = zones.id ");
if ($db->next_record()) $count=$db->Record[0]; else $count='unknown';
print "<TR><TD>Resource records</TD><TD align=right>$count</TD></TR>\n";
print "</TABLE></TD><TD>\n";

$db->query("SELECT id, hostname, ipno, type, pushupdates, mknsrec, state FROM servers ORDER BY hostname");
print "<TABLE border cellpadding=4><TR><TH colspan=7>Managed DNS Servers</TH></TR>\n";
print "<TR><TH>Server</TH><TH>Ip number</TH><TH>Type</TH><TH>Update?</TH><TH>NS record?</TH><TH>state</TH><TH>test</TH></TR>\n";
while ($db->next_record()) {
	$row = $db->Record;
	$id = $row[0];
	$type = $SERVER_TYPES[$row[3]];
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
	print "<TR><TD><A HREF=\"servers.php?action=detailedview&server=$row[0]\">$row[1]</A></TD><TD>$row[2]</TD><TD align=center>$type</TD><TD align=center>$push</TD><TD align=center>$mkrec</TD><TD align=CENTER $B>$T</TD><TD align=center><A href=\"test.php?id=$id\"><img src=\"images/greenbutton.gif\" border=0 high=16 width=24></A></TD></TR>\n";
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
