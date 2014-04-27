<?php 
require 'inc/lib.inc'; 
require 'header.php';

$html_top = '
<HTML>
<HEAD>
<TITLE>Detect PTR records without matching A records</TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="../images/BG-shadowleft.gif">
<H1>Detect PTR records without matching A records</H1>
';

$html_bottom = "
</BODY>
</HTML>
";

$lastzone="";
print $html_top;
$query = "SELECT zones.id AS zid, zones.domain AS zdom, records.id AS rid, records.domain AS rdom, records.data AS rdata FROM zones, records WHERE zones.id = records.zone AND zones.domain != 'TEMPLATE' AND records.type = 'PTR' AND data NOT LIKE 'host-%' ORDER BY zone";
$rid = sql_query($query);
print "<UL>";
while ($row = mysql_fetch_array($rid)) {
	eregi("^(.*)\.in-addr\.arpa$", $row['zdom'], $matches);
	$bytes = explode(".", $matches[1]);
	$ip = $bytes[count($bytes)-1];
	for ($i = count($bytes)-2; $i>=0; $i--)
		$ip .= ".".$bytes[$i];
	$bytes = explode(".", $row['rdom']);
	for ($i = count($bytes)-1; $i>=0; $i--)
		$ip .= ".".$bytes[$i];
	$query2 = "SELECT zones.domain AS zdom, zones.id AS zid, records.domain AS rdom, data FROM zones, records WHERE zones.id = records.zone AND zones.domain != 'TEMPLATE' AND type = 'A' AND data = '$ip'";
	$rid2 = sql_query($query2);
	if (!mysql_num_rows($rid2)) {
		if ($lastzone != $row['zdom']) {
			print "</UL>\n<A HREF=\"../brzones.php?frame=records&zone=".$row['zid']."\">";
			print $row['zdom']."</A><UL>";
		}
		print $row['rdom']." &nbsp; IN PTR &nbsp; ";
		if ($tmp = strchr($row['rdata'], ".")) {
			$dom = substr($tmp, 1, strlen($tmp)-2);
			$doms = explode(".", $row['rdata']);
			print "$doms[0].<A HREF=\"../brzones.php?frame=records&domain=$dom\">$dom</A><BR>\n";
		} else {
			print $row['rdata']."<BR>\n";
		}
		$lastzone = $row['zdom'];
	}
	mysql_free_result($rid2);
}
mysql_free_result($rid);
print "</UL>\n";

print $html_bottom;

?>
