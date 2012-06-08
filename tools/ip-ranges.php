<HTML>
<HEAD>
<TITLE></TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="../images/BG-shadowleft.gif">
<TABLE width="99%">
<TR>
 <TD align="left"><H1>Display IP usage</H1>
</TR>
</TABLE>
<HR><P>

<?php

require '../inc/lib.inc'; 

function subnet_menu()
{
	$query = "SELECT domain FROM zones WHERE domain LIKE '%.in-addr.arpa' ORDER BY domain";
	$rid = sql_query($query);
	$result = "<SELECT name=\"subnet\">\n";
	while ($row = mysql_fetch_row($rid)) {
		$result .= "<OPTION>$row[0]</OPTION>\n";
	}
	mysql_free_result($rid);
	$result .= "</SELECT>\n";
	return $result;
}

function subnet_view($subnet)
{
	$doms = explode(".", $subnet);
	$prefix = $doms[0];
	for ($i=1; $doms[$i] != "in-addr"; $i++)
		$prefix = "$doms[$i].$prefix";
	$query = "SELECT zones.domain AS zdom, zones.id AS zid, records.domain AS rdom, records.data AS rdata FROM zones, records WHERE zones.id = records.zone AND records.data LIKE '$prefix.%'";
	$rid = sql_query($query);
	while ($row = mysql_fetch_array($rid)) {
		if ($row['rdom'] == $row['zdom'].".")
			$descr = sprintf("<A HREF=\"../brzones.php?frame=records&zone=%d\">%s</A>",
				$row['zid'], $row['zdom']);
		else
			$descr = sprintf("%s.<A HREF=\"../brzones.php?frame=records&zone=%d\">%s</A>",
				$row['rdom'], $row['zid'], $row['zdom']);
		$bytes = explode(".", $row['rdata']);
		$hosts[$bytes[3]][] = $descr;
	}
	mysql_free_result($rid);
	$query = "SELECT zones.domain AS zdom, zones.id AS zid, records.data AS rdata, CONCAT('$prefix.', records.domain) AS rdom FROM zones, records WHERE zones.id = records.zone AND records.type = 'PTR' AND zones.domain = '$subnet'";
	$rid = sql_query($query);
	while ($row = mysql_fetch_array($rid)) {
		$descr = sprintf("%s<BR>&nbsp;(explicit PTR in <A HREF=\"../brzones.php?frame=records&zone=%d\">%s</A>)",
			$row['rdata'], $row['zid'], $row['zdom']);
		$bytes = explode(".", $row['rdom']);
		$hosts[$bytes[3]][] = $descr;
	}
	mysql_free_result($rid);
	$firstfree = 256;
	$result = "<TABLE><TR align=left><TH>Host</TH><TH>Hostname</TH></TR>\n";
	for ($i=1; $i<255; $i++) {
		if ($hosts[$i]) {
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
get_input();
if ($subnet = $INPUT_VARS['subnet']) {

	$query = "SELECT id FROM zones WHERE domain = '$subnet'";
	$rid = sql_query($query);
	$row = mysql_fetch_row($rid);
	mysql_free_result($rid);
	$zid = $row[0];
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