<?php
require 'inc/lib.inc';

$html_top = '
<HTML><HEAD>
<TITLE>DNS test</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD><BODY bgcolor="dddd99" background="images/BG-shadowtop.gif">
';

$html_bottom = '
</BODY>
</HTML>
';

$html_failure = '
<CENTER>
<TABLE width="30%" bgcolor=RED border=2>
<TR><TD align=CENTER><FONT size=+2 color=WHITE><B>TEST FAILED</B></FONT></TD></TR>
</TABLE>
</CENTER>
';

#
# Returns test form
#
function ns_test_form($id, $input) {
        if (!ctype_digit("$id")) die('Invalid server id.');

        $db = new DB_probind;

	$name = $input['name'];
	$zone = $input['zone'];
	$rtype = $input['type'];
	if (!$rtype)
		$rtype = "SOA";
	$result = "";
	$db->query("SELECT hostname as host, ipno, type FROM servers WHERE id = $id");
	if (!$db->next_record()) {
		return "<FONT color=RED>NO NS SERVER ID=$id</FONT><BR>\n";
	}
			
	extract($db->Record);
	
	if ($type == "S")
		$query = "SELECT domain FROM zones WHERE domain != 'TEMPLATE' AND master = '' ORDER BY mtime DESC";
	else
		$query = "SELECT domain FROM zones WHERE domain != 'TEMPLATE'  ORDER BY master ASC, mtime DESC";
			
	$db->query($query);
	
	$result .= "<FORM action=\"test.php\"><INPUT type=\"HIDDEN\" name=\"id\" value=\"$id\">\n";
	$result .= "<INPUT type=\"HIDDEN\" name=\"host\" value=\"$host\">\n";
	$result .= "<INPUT type=\"HIDDEN\" name=\"ip\" value=\"$ip\">\n";
	$result .= "<TABLE border=4  frame=\"box\">\n<TR>\n";
    $result .= "<TH>\n";
	$result .= "<INPUT type=\"submit\" name=\"Test\" value=\"Testing\" class=\"button\" onmouseover=\"this.className='buttonhover'\" onmouseout=\"this.className='button'\" >";
	$result .= "<TH>Host / IP</TH><TH>Zone</TH><TH>Type</TH>\n";
	$result .= "</TR><TR><TD>\n";
	$result .= "<B> $host ($ip)</B></TD>\n";
	$result .= "<TD><INPUT type=\"TEXT\" length=24 name=\"name\" value=\"$name\"></TD>\n";
	$result .= "<TD>";
	$array = array();
	$first = 1;
	while ($db->next_record()) {
		$domain = $db->Record['domain'];
		$array[] = $domain;
		if (!$zone)
		    $zone = $domain;
		if ($first) {
			$array[] = "";
			$first = 0;
		}
	}
	$result .= mk_select_a("zone", $array, $zone);
	
	$array = array('ANY', 'SOA', 'A', 'PTR', 'CNAME', 'MX', 'NS', 'TXT', 'HINFO', 'SRV');
	$result .= "</TD><TD>\n";
	$result .= mk_select_a("type", $array, $rtype);

	$result .= "</TR>\n</TABLE>\n";
	      
	return $result;
}


#
# MAIN
#

print $html_top;
get_input();

$id = $INPUT_VARS['id'];
print ns_test_form($id, $INPUT_VARS);
if ( $INPUT_VARS['Test'] ) {
	print "<HR>\n";
	$host = $INPUT_VARS['host'];
	$ip   = $INPUT_VARS['ip'];
	$name = $INPUT_VARS['name'];
	$zone = $INPUT_VARS['zone'];
	$type = $INPUT_VARS['type'];
	if ( preg_match('/(\d+)\.(\d+)\.(\d+)\.(\d+)/', $name, $iprq) ) {
		$rq = $iprq[4].".".$iprq[3].".".$iprq[2].".".$iprq[1].".in-addr.arpa";
		$type = "PTR";
	}
	else {
		$rq = $name;
		if ($name && $zone)
			$rq = "$name.$zone";
		else
			$rq = "$name$zone";
		$rq = strtr($rq,     ";<>|&,:","       ");
		$type = strtr($type, ";<>|&,:","       ");
	}
	
	print "request: <B>$rq {type=$type}</B> server: <B>$host ($ip)</B> results:<BR><PRE>\n"; 
	
	$cmd = "$BIN/testns -h $ip -t $type $rq";
	passthru("$cmd", $ret);
	print "</PRE>\n";
	if ($ret) {
		print $html_failure;
	}	
	print $html_bottom;
	// make_test();
}

print $html_bottom;

?>
