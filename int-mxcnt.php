<?php 
require 'inc/lib.inc'; 
$htmlTitle = "ProBind:TooFewMX";
require 'header.php';

echo '<H1>Detect domains with few MX records</H1>';


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
				$result .= "<B><A HREF=\"zones.php?zone=$lastzid\">$lastdom</A></B><BR>\n";
			} 
			$mxcnt = 0;
			$lastdom = $db->Record['zdom'];
			$lastzid = $db->Record['zid'];
		}
		if ($db->Record['type'] == 'MX')
			$mxcnt = $db->Record['rcnt'];
	}
	if ($mxcnt == $count)
		$result .= "<B><A HREF=\"zones.php?zone=$lastzid\">$lastdom</A></B><BR>\n";
	return $result;
}

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

require 'footer.php';
?>
