<?php 
require 'inc/lib.inc'; 
require 'header.php';

echo '<H1>View/Import Domains From PowerDNS</H1>';
echo "\n<script type='text/javascript' src='/js/scripts.js'></script>\n";

$db = new DB_powerdns;

if ($WithSelected) {
        check_edit_perms();
        if (!ctype_digit(implode("",$id))) die("Invalid id(s)");
        switch ($WithSelected) {
		case "Import":
			echo "Importing ".count($id)." zones.<br>\n";
			foreach ($id as $i) import_pdns_zone($i);
			break;
	}
        echo "&nbsp<a href=\"".$sess->self_url();
        echo "\">Back.</a><br>\n";
        page_close();
        exit;

}


switch ($cmd) {
    case "View":
	$db->query("SELECT * FROM records WHERE domain_id=$id");
	$t = new Table;
	$t->heading="on";
	$t->fields = array('name','ttl','type','prio','content');
	$t->show_result($db);
	break;
    case "Import":
	import_pdns_zone($id);
	break;
    default:

$query="
SELECT d.id, d.name, count(*) as total,
	SUM(IF(r.type='A',1,0)) as A,
	SUM(IF(r.type='AAAA',1,0)) as AAAA,
	SUM(IF(r.type='PTR',1,0)) as PTR,
	SUM(IF(r.type='MX',1,0)) as MX,
	SUM(IF(r.type='NS',1,0)) as NS
FROM domains d
JOIN records r ON d.id=r.domain_id 
WHERE d.type='NATIVE'
GROUP BY name
ORDER BY name
";

$db->query($query);

if ($db->num_rows()) {
	$t = new Table;
	$t->heading="on";
	$t->fields = array('name','total','A','AAAA','PTR','MX','NS');
        $t->add_extra = Array('View','Import');
        $t->checkbox_menu = Array('View','Import');
        $t->check = 'id';
	$t->show_result($db);
} else {
	echo "No zones found.";
}

}
require 'footer.php';
?>
