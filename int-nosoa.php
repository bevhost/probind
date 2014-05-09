<?php
require 'inc/lib.inc';
require 'header.php';

echo "<H1>Detect zones without matching SOA records</H1>\n";

$db = new DB_probind;

function fix($id) {
	global $db, $template;
	if (!ctype_digit($id)) die ("Invalid zone id");
        $db->query("INSERT INTO records (domain, zone, ttl, type, pref, data, port, weight, comment, genptr, ctime, mtime) ".
                   "SELECT domain, $id, ttl, type, pref, data, port, weight, '', 1, NOW(), NOW() ".
		   "FROM records WHERE zone=$template AND type='SOA'");
}

if ($cmd) {
    $db->query("SELECT id FROM zones WHERE domain = 'TEMPLATE'");
    if ($template = $db->fetchColumn()) {
	switch($cmd) {
	    case "Fix":
		fix($id);
		break;
	    case "Fix All":
		foreach($_REQUEST["ids"] as $id) fix($id);
		break;
	}
    } else echo "TEMPLATE domain not found";
}

$sql = "SELECT z.id, z.domain FROM zones z
	LEFT OUTER JOIN records r ON (r.zone=z.id AND r.type='SOA')
	WHERE LENGTH(z.master)=0 AND r.id IS NULL";

$db->query($sql);	
	
if ($count = $db->num_rows()) {
	print "$count zones are not properly associated with SOA records.<P>\n";

	echo "<form method=post>\n";
	while ($db->next_record()) {
		extract($db->Record);
		print "$domain <A HREF='int-nosoa.php?cmd=Fix;id=$id'>fix</A><BR>\n";
		print "<input type='hidden' name='ids[]' value='$id'>\n";
	}
	echo "<input type='submit' name='cmd' value='Fix All'>";
} else {
	print "All zones are properly associated with SOA records.<P>\n";
}

require 'footer.php';
?>
