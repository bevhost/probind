<?php 
require 'inc/lib.inc'; 
require 'header.php';

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

$count=0;
function list_record($record)
{
	$GLOBALS["count"]++;
	if ($record['rdom']<>'@' and substr($record['rdom'],-1)<>'.') print $record['rdom']."." ;
	print "<A HREF=\"../brzones.php?frame=records&zone=".$record['zid'];
	print "\">".$record['zdom']."</A><BR>\n";
}

$ul=0;
print $html_top;
$query="
SELECT records.data, records.domain as rdom, zones.domain as zdom, zones.id as zid
FROM zones 
JOIN records ON (zones.id=records.zone) 
WHERE zones.disabled=0
and records.disabled=0
AND data IN (
	SELECT data 
	FROM records 
	WHERE type IN ('A','AAAA') 
	AND domain<>'TEMPLATE'
	AND disabled=0
	GROUP BY data
	HAVING count(*)>1
		)
".access()." 
ORDER BY records.type, records.data, zones.domain, records.domain";

$last = "";
$db = new DB_probind;
$db->query($query);
while ($db->next_record()) {
	$data = $db->Record["data"];
	if ($last<>$data) {
		if ($last) echo "</UL><br>";
		echo "<B>$data</B><UL>\n";
	}
	list_record($db->Record);
	$last = $data;
}
if ($last) echo "</UL>";
print "<HR><P>".$db->num_rows()." ($count) records.<BR>\n";
print $html_bottom;

?>
