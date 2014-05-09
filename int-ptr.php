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

$db1 = new DB_probind;
$db2 = new DB_probind;

$lastzone="";
print $html_top;
$query1 = "SELECT zones.id AS zid, zones.domain AS zdom, records.id AS rid, records.domain AS rdom, records.data AS rdata FROM zones, records 
		WHERE zones.id = records.zone AND zones.domain != 'TEMPLATE' AND records.type = 'PTR' AND data NOT LIKE 'host-%' ORDER BY zone";
$query2 = "SELECT zones.domain AS zdom, zones.id AS zid, records.domain AS rdom, data FROM zones, records WHERE zones.id = records.zone AND zones.domain != 'TEMPLATE' AND type IN ('A','AAAA') AND data = ?";
$db2->prepare($query2);
$db1->query($query1);
print "<UL>";
while ($db1->next_record()) {
	$row = $db1->Record;
	$ip6 = false;
	if (preg_match("/^(.*)\.in-addr\.arpa$/", $row['zdom'], $matches)) {
		$bytes = explode(".", $matches[1]);
		$ip = $bytes[count($bytes)-1];
		for ($i = count($bytes)-2; $i>=0; $i--)
			$ip .= ".".$bytes[$i];
		$bytes = explode(".", $row['rdom']);
		for ($i = count($bytes)-1; $i>=0; $i--)
			$ip .= ".".$bytes[$i];
	} else 
	if (preg_match("/^(.*)\.ip6\.arpa$/", $row['zdom'], $matches)) {
		$bytes = explode(".", $matches[1]);
		$ip = $bytes[count($bytes)-1];
		for ($i = count($bytes)-2; $i>=0; $i--) {
			if ($i%4==3) $ip.=":";
			$ip .= $bytes[$i];
		}
		$bytes = explode(".", $row['rdom']);
		for ($i = count($bytes)-1; $i>=0; $i--) {
			if ($i%4==3) $ip.=":";
			$ip .= $bytes[$i];
		}
		if (filter_var($ip, FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)) { 
			$ip6 = inet_ntop(inet_pton($ip));
		}
	} else echo "unexpected error";
	$db2->execute($ip);
	if ($ip6 && !$db2->num_rows()) $db2->execute($ip6);  #check for condensed IP6.
	$IpAddr = $ip6 ? $ip6 : $ip;
	if (!$db2->next_record()) {
		if ($lastzone != $row['zdom']) {
			print "</UL>\n<A HREF=\"zones.php?zone=".$row['zid']."\">";
			print $row['zdom']."</A><UL>";
		}
		print $row['rdom']." &nbsp; IN PTR &nbsp; ";
		if ($tmp = strchr($row['rdata'], ".")) {
			$dom = substr($tmp, 1, strlen($tmp)-2);
			$doms = explode(".", $row['rdata']);
			print "$doms[0].<A HREF=\"zones.php?domain=$dom\">$dom</A> ($IpAddr)<BR>\n";
		} else {
			print $row['rdata']."<BR>\n";
		}
		$lastzone = $row['zdom'];
	}
}
print "</UL>\n";

print $html_bottom;

?>
