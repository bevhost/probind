<?php
require 'inc/lib.inc'; 
require 'header.php';
?>
<TABLE width="99%">
<TR>
 <TD align="left"><H1>Display IP usage</H1>
</TR>
</TABLE>
<HR><P>


<?php
function subnet_menu()
{
	$db = new DB_probind;
	$db->query("SELECT domain FROM zones WHERE domain LIKE '%.in-addr.arpa' ORDER BY domain");
	$domains = $db->fetchAll();
	$result = "<SELECT name=\"subnet\">\n";
	foreach ($domains as $domain) {
		$result .= "<OPTION>$domain</OPTION>\n";
	}
	$result .= "</SELECT>\n";
	return $result;
}

function subnet_view($subnet)
{
	$db = new DB_probind;
	$doms = explode(".", $subnet);
	$prefix = $doms[0];
	for ($i=1; $doms[$i] != "in-addr"; $i++)
		$prefix = "$doms[$i].$prefix";
	$db->prepare("SELECT zones.domain AS zdom, zones.id AS zid, records.domain AS rdom, records.data AS rdata 
			FROM zones, records WHERE zones.id = records.zone AND records.data LIKE ? ".access());
	$db->execute($prefix.".%");
	while ($db->next_record()) {
		if ($db->Record['rdom'] == $db->Record['zdom'].".")
			$descr = sprintf("<A HREF=\"../brzones.php?frame=records&zone=%d\">%s</A>",
				$db->Record['zid'], $db->Record['zdom']);
		else
			$descr = sprintf("%s.<A HREF=\"../brzones.php?frame=records&zone=%d\">%s</A>",
				$db->Record['rdom'], $db->Record['zid'], $db->Record['zdom']);
		$bytes = explode(".", $db->Record['rdata']);
		$hosts[$bytes[3]][] = $descr;
	}
	$db->prepare("SELECT zones.domain AS zdom, zones.id AS zid, records.data AS rdata, 
			CONCAT(?, records.domain) AS rdom 
			FROM zones, records 
			WHERE zones.id = records.zone AND records.type = 'PTR' AND zones.domain = ?");
	$db->execute($prefix.".",$subnet);
	while ($db->next_record()) {
		$descr = sprintf("%s<BR>&nbsp;(explicit PTR in <A HREF=\"../brzones.php?frame=records&zone=%d\">%s</A>)",
			$db->Record['rdata'], $db->Record['zid'], $db->Record['zdom']);
		$bytes = explode(".", $db->Record['rdom']);
		$hosts[$bytes[3]][] = $descr;
	}
	$firstfree = 256;
	$result = "<TABLE><TR align=left><TH>Host</TH><TH>Hostname</TH></TR>\n";
	for ($i=1; $i<255; $i++) {
		if (@$hosts[$i]) {
			if ($firstfree < $i) {
				$lastfree = $i - 1;
				if (($lastfree - $firstfree) >= 1)
					$result .= "<TR><TD bgcolor=\"#669966\">$firstfree .. $lastfree</TD><TD background=\"../images/BG-shade-green.gif\">Not used</TD></TR>\n";
				else
					$result .= "<TR><TD bgcolor=\"#669966\">$firstfree</TD><TD background=\"../images/BG-shade-green.gif\">Not used</TD></TR>\n";
				$firstfree = 256;
			}
			$result .= "<TR><TD bgcolor=\"#ff9933\">$i</TD><TD background=\"../images/BG-shade-orng.gif\">"
				.join("<BR>\n\t",$hosts[$i])."</TD></TR>\n";
		} else {
			if ($firstfree > $i)
				$firstfree = $i;
		}
	}
	if ($firstfree < $i) {
		$lastfree = $i - 1;
		if (($lastfree - $firstfree) > 1)
			$result .= "<TR><TD bgcolor=\"#669966\">$firstfree .. $lastfree</TD><TD background=\"../images/BG-shade-green.gif\">Not used</TD></TR>\n";
		else
			$result .= "<TR><TD bgcolor=\"#ff9933\">$firstfree</TD><TD background=\"../images/BG-shade-orng.gif\">Not used</TD></TR>\n";
	}
	$result .= "</TABLE>\n";
	return $result;
}

#
# MAIN
#

$db = new DB_probind;

get_input();

$zid = false;
if ($subnet = @$INPUT_VARS['subnet']) {

	$db->prepare("SELECT id FROM zones WHERE domain = ?");
	$db->execute($subnet);
	if ($db->next_record()) $zid = $db->Record[0];
	else echo "$subnet not found.";
}
if ($zid) {
	print "IP number usage in the <A HREF=\"../brzones.php?frame=records&zone=$zid\">$subnet</A> subnet<P>\n<UL>\n";
	print subnet_view($subnet);
	print "</UL>\n";
} else {
	print "Select a subnet to view<P>\n";
	print "<FORM action=\"ip-ranges.php\" method=\"post\">\n";
	print subnet_menu();
	print "<INPUT type=\"submit\" value=\"View\">\n";
	print "</FORM>\n";
}

?>

</BODY>
</HTML>
