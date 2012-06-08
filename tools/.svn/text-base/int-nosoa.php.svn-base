<?php
require '../inc/lib.inc';

$html_top = '
<HTML>
<HEAD>
<TITLE>Detect zones without matching SOA records</TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<BODY bgcolor=#9acdd0>
<H1>Detect zones without matching SOA records</H1>
<TABLE border>
';

$html_bottom = '
</TABLE>
</BODY>
</HTML>
';

print $html_top;
$query = "SELECT zones.id AS zid, zones.domain AS zdom, records.id AS rid, records.domain AS rdom FROM zones, records WHERE zones.id = records.zone AND zones.domain != 'TEMPLATE' AND records.type = 'SOA' AND LENGTH(master) = 0 ORDER BY zone";
$rid = sql_query($query);
while ($row = mysql_fetch_array($rid)) {
	$soas[$row['zdom']] = $row['rid'];
}
mysql_free_result($rid);

$query = "SELECT id, domain FROM zones WHERE domain != 'TEMPLATE' AND LENGTH(master) = 0 ORDER BY domain";
$rid = sql_query($query);
while ($row = mysql_fetch_array($rid)) {
	if (!$soas[$row['domain']]) {
		print "<A HREF=\"../brzones.php?frame=records&domain=";
		print $row['domain']."\">".$row['domain']."</A><BR>\n";
		$count++;
	}
}
if ($count) {
	print "$count zones are not properly associated with SOA records.<P>\n";
} else {
	print "All zones are properly associated with SOA records.<P>\n";
}
mysql_free_result($rid);

print $html_bottom;

?>
