<?php
require 'inc/lib.inc'; 
require 'header.php';
?>

<TABLE width="99%">
<TR>
 <TD align="left"><H1>Annotated list of all known zones</H1>
</TR>
</TABLE>
<HR><P>


<?php
#
# MAIN
#
get_input();

$count = $db->prepare("SELECT COUNT(*) FROM records WHERE zone = ?");
$annotations = $db->prepare("SELECT descr FROM annotations WHERE zone = ?");

$db->query("SELECT domain, id, master FROM zones WHERE domain != 'TEMPLATE'".access()." ORDER BY domain");
while ($db->next_record()) {
	extract($db->Record);
	if ($master)
		$zonedetails = "[Slave zone, master = $master]";
	else {
		$count->execute(array($id));  # php execute (not phplib), so must be an array.
		$rrs = $count->fetchColumn();
		$zonedetails = "[Authoritative zone, contains $rrs Resource Records]";
	}
	$annotations->execute(array($id));
	$descr = $annotations->fetchColumn();
	$html = join("<BR>\n", explode("\n", $descr));
	print "<B>$domain</B> $zonedetails<P>\n<UL>$html</UL>\n<P>\n";
}

?>

</BODY>
</HTML>
