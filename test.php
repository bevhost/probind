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
	if (isset($input['name'])) { $name = $input['name']; } else { $name = ""; }
	if (isset($input['zone'])) $zone = $input['zone'];
	if (isset($input['type'])) $rtype = $input['type'];
	if (!isset($rtype))
		$rtype = "SOA";
	$result = "";
	$query = "SELECT hostname, ipno, type FROM servers WHERE id = $id";
	$rid = sql_query($query);
	if (!mysql_num_rows($rid)) {
		return "<FONT color=RED>NO NS SERVER ID=$id</FONT><BR>\n";
	}
			
	list($host, $ip, $type) = mysql_fetch_row($rid);
	mysql_free_result($rid);
	
	if ($type == "S")
		$query = "SELECT domain FROM zones WHERE domain != 'TEMPLATE' AND master = '' ORDER BY mtime DESC";
	else
		$query = "SELECT domain FROM zones WHERE domain != 'TEMPLATE'  ORDER BY master ASC, mtime DESC";
			
	$rid = sql_query($query);
	
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
	while (list($domain) = mysql_fetch_row($rid)) {
		$array[] = $domain;
		if (!isset($zone))
		    $zone = $domain;
		if ($first) {
			$array[] = "";
			$first = 0;
		}
	}
	mysql_free_result($rid);
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
if ( isset($INPUT_VARS['Test']) ) {
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
