<?php 
require '../inc/lib.inc'; 

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
	$query = "
	SELECT zones.id AS zid, zones.domain AS zdom, records.domain AS rdom, pref, data
	FROM zones, records 
	WHERE zones.id = records.zone 
		AND zones.domain != 'TEMPLATE'
		AND length(zones.master) = 0
		AND type = 'MX'
		AND (pref IS NULL OR length(pref) = 0)
	ORDER BY zones.domain, records.domain
	";
	$result = "";

	$rid = sql_query($query);
	if (mysql_num_rows($rid)) while ($row = mysql_fetch_array($rid)) {
		$result .= $row['rdom'].".".$row['zdom'];
		$result .= " IN MX ".$row['data']."<BR>\n";
	} else
		$result = "All MX records are valid.<P>\n";
	mysql_free_result($rid);
	return $result;
}

function verify_a()
{
	$query = "
	SELECT zones.id AS zid, zones.domain AS zdom, records.domain AS rdom, data
	FROM zones, records 
	WHERE zones.id = records.zone 
		AND zones.domain != 'TEMPLATE'
		AND type = 'A'
	ORDER BY zones.domain, records.domain
	";
	$result = "";

	$rid = sql_query($query);
	while ($row = mysql_fetch_array($rid)) {
		if (!strlen($row['data']) || !valid_ip($row['data'])) {
			$result .= $row['rdom'].".".$row['zdom'];
			$result .= " IN A ".$row['data']."<BR>\n";
		}
	} 
	mysql_free_result($rid);
	if (!strlen($result))
		$result = "All A records point to valid IP addresses<P>\n";
	return $result;
}


print $html_top;
print verify_mx();
print verify_a();
print $html_bottom;

?>