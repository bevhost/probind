<?php 
require 'inc/lib.inc'; 
require 'header.php';

$html_top = '
<HTML>
<HEAD>
<TITLE>Detect domains with few MX records</TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="../images/BG-shadowleft.gif">
<H1>Detect domains with few MX records</H1>
';

$html_bottom = "
</BODY>
</HTML>
";

function check_mx_cnt($count)
{
	$db = new DB_probind;
	$type_query = "
	SELECT zones.id AS zid, zones.domain AS zdom, type, count(records.type) AS rcnt 
	FROM zones, records 
	WHERE zones.id = records.zone 
		AND zones.domain != 'TEMPLATE'
		AND zones.domain NOT LIKE '%.in-addr.arpa'
		AND length(zones.master) = 0
		".access()."
	GROUP BY zones.domain, records.type
	";
	$db->query($type_query);
	$result = "";
	$lastdom = "";
	$lastzid = "";
	$mxcnt = 0;
	while ($db->next_record()) {
		if ($db->Record['zdom'] != $lastdom) {
			if ($lastdom && $mxcnt == $count) {
				$result .= "<B><A HREF=\"../brzones.php?frame=records&zone=$lastzid\">$lastdom</A></B><BR>\n";
			} 
			$mxcnt = 0;
			$lastdom = $db->Record['zdom'];
			$lastzid = $db->Record['zid'];
		}
		if ($db->Record['type'] == 'MX')
			$mxcnt = $db->Record['rcnt'];
	}
	if ($mxcnt == $count)
		$result .= "<B><A HREF=\"../brzones.php?frame=records&zone=$lastzid\">$lastdom</A></B><BR>\n";
	return $result;
}

print $html_top;
if ($list = check_mx_cnt(0)) 
	print "Domains without any MX records at all:<P><UL>
$list
</UL><P>
";

if ($list = check_mx_cnt(1)) 
	print "Domains with only one MX record:<P><UL>
$list
</UL><P>
";

print $html_bottom;

?>
