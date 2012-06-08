<?php 
require '../inc/lib.inc'; 

$html_top = '
<HTML>
<HEAD>
<TITLE>Detect multiple A records for the same IP address</TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="../images/BG-shadowleft.gif">
<H1>Detect multiple A records for the same IP address</H1>
';

$html_bottom = "
</BODY>
</HTML>
";

function list_record($record)
{
	print $record['rdom'].".";
	print "<A HREF=\"../brzones.php?frame=records&zone=".$record['zid'];
	print "\">".$record['zdom']."</A><BR>\n";
}

print $html_top;
# List all A records, sorted by IP number
$query = "SELECT zones.id AS zid, zones.domain AS zdom, records.id AS rid, records.domain AS rdom, records.data AS rdata FROM zones, records WHERE zones.id = records.zone AND records.type = 'A' AND zones.domain != 'TEMPLATE' ORDER BY records.data";
$rid = sql_query($query);
$lastrow['rdata'] = "";
$first = 1;
while ($row = mysql_fetch_array($rid)) {
	if ($row['rdata'] == $lastrow['rdata']) {
		if ($first) {
			print "<B>".$lastrow['rdata']."</B><UL>\n";
			list_record($lastrow);
			$first = 0;
			$ul++;
		}
		list_record($row);
	} else if ($ul) {
		print "</UL>\n";
		$first = 1;
		$ul--;
	}
	$lastrow = $row;
}
if ($ul)
	print "</UL>\n";
print "<HR><P>".mysql_num_rows($rid)." A records examined.<BR>\n";
mysql_free_result($rid);

print $html_bottom;

?>