<?php 
require 'inc/lib.inc'; 
require 'header.php';

$html_top = '
<HTML>
<HEAD>
<TITLE>Find records with invalid data</TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="../images/BG-shadowleft.gif">
<H1>Find records with invalid data</H1>
';

$html_bottom = "
</BODY>
</HTML>
";

function verify_mx()
{
	$db = new DB_probind;
	$query = "
	SELECT zones.id AS zid, zones.domain AS zdom, records.domain AS rdom, pref, data
	FROM zones, records 
	WHERE zones.id = records.zone 
		AND zones.domain != 'TEMPLATE'
		AND length(zones.master) = 0
		AND type = 'MX'
		AND (pref IS NULL OR length(pref) = 0)
		".access()."
	ORDER BY zones.domain, records.domain
	";

	$result = "";
	$db->query($query);
	while ($db->next_record()) {
		$result .= $db->Record['rdom'].".".$db->Record['zdom'];
		$result .= " IN MX ".$db->Record['data']."<BR>\n";
	} 
	if (!$result) $result = "All MX records are valid.<P>\n";
	return $result;
}

function verify_a()
{
	$db = new DB_probind;
	$query = "
	SELECT zones.id AS zid, zones.domain AS zdom, records.domain AS rdom, data
	FROM zones, records 
	WHERE zones.id = records.zone 
		AND zones.domain != 'TEMPLATE'
		AND type = 'A'
		".access()."
	ORDER BY zones.domain, records.domain
	";
	$result = "";

	$db->query($query);
	while ($db->next_record()) {
		if (!strlen($db->Record['data']) || !valid_ip($db->Record['data'])) {
			$result .= $db->Record['rdom'].".".$db->Record['zdom'];
			$result .= " IN A ".$db->Record['data']."<BR>\n";
		}
	} 
	if (!$result) $result = "All A records point to valid IP addresses<P>\n";
	return $result;
}


print $html_top;
print verify_mx();
print verify_a();
print $html_bottom;

?>
