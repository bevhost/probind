<?php
require '../inc/lib.inc';

$html_top = '
<HTML>
<HEAD>
<TITLE>Detect lame delegations</TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="../images/BG-shadowleft.gif">
<H1>Detect lame delegations</H1>
';

$html_bottom = '
</BODY>
</HTML>
';

$entrance_prologue = '
This tool detects lame delegations on our own DNS servers. 
In order to do so, we need the hostname (or IP address) of
a DNS server <em>outside</em> of your network. The default
will usually be a sensible choice. Specifying a server
controlled through this database will accomplish exactly
nothing.<P>
<B>NB:</B> This will take a long time. Have patience.<P>
';

$entrance_body = '
<FORM action="find-lamers.php" method="post">
<INPUT type=text size=32 name=nameserver value="%s">
&nbsp;
<INPUT type=submit value="Start">
</FORM>
';

$search_prologue = '
We manage these domains, but they do not appear to be 
delegated to any of our DNS servers. Hence, some sort 
of intervention is probably appropriate. <P>
';

$search_epilogue = '
<P><HR><P>Completed.<P>
Found %s lame delegations.<P>
';

function end_domain($begin, $domainform, $serverform, $end, $noservers, &$counter)
{
	global $current_domain, $name_servers, $servers;
	$matches = 0;
	$row = "";
	while ($name_servers && ($ns = each($name_servers))) {
		$row .= sprintf($serverform, $ns[1]);
		if ($servers[$ns[1]])
			$matches++;
	}
	if (!$matches) {
		$counter++;
		$dom = sprintf($domainform, $current_domain);
		if (count($name_servers))
			$result =  $begin.$dom.$row.$end;
		else 
			$result =  $begin.$dom.$noservers.$end;
	}
	$current_domain = "";
	$name_servers = array();
	return $result;
}

function begin_domain($domain)
{
	global $current_domain, $name_servers;
	if ($current_domain) 
		end_domain();
	$current_domain = $domain;
}

function domain_name_server($nameserver)
{
	global $name_servers;
	$name_servers[] = strtolower(ltrim(trim($nameserver)));
}

function entrance_page($text = "")
{
	global $html_top, $entrance_prologue, $entrance_body, $html_bottom;
	$query = "SELECT value FROM blackboard WHERE name = 'default_external_dns'";
	$rid = sql_query($query);
	list($default_resolver) = mysql_fetch_row($rid);
	mysql_free_result($rid);
	print $html_top;
	print $entrance_prologue;
	print $text;
	print sprintf($entrance_body, $default_resolver);
	print $html_bottom;
	exit;
}

function initialize_servers()
{
	global $servers;
	$rid = sql_query("SELECT hostname FROM servers");
	while ($row = mysql_fetch_row($rid)) {
		$servers[$row[0]] = 1;
	}
	mysql_free_result($rid);
}

#
# MAIN
#
get_input();

if (!$INPUT_VARS['nameserver']) {
	entrance_page();
} else {
	$tmp = strtolower(ltrim(rtrim($INPUT_VARS['nameserver'])));
	if (ereg($DOMAIN_RE, $tmp) 
	&& ((gethostbyname($tmp) != $tmp) || valid_ip($tmp)))
		$nameserver = $tmp;
	else 
		entrance_page("Invalid hostname.<P>\n");
}

print sprintf($html_top, "#dcdcdc");
initialize_servers();
$rid = sql_query("SELECT domain FROM zones WHERE (master IS NULL OR NOT master) AND domain != 'TEMPLATE' AND domain != '0.0.127.in-addr.arpa'".access()." ORDER BY domain");
$listfile = fopen("$TMP/domains", "w");
while ($row = mysql_fetch_row($rid)) {
	$domain = sprintf("%s\n", $row[0]);
	fwrite($listfile, $domain);
}
fclose($listfile);
mysql_free_result($rid);
print $search_prologue;
$pipe = popen("$BIN/nsrecs -h $nameserver < $TMP/domains", "r");
$lamecounter = 0;
while (!feof($pipe)) {
	$result = fgets($pipe, 1000);
	$hostnames = explode(" ", $result);
	if (strlen($hostnames[0])) {
		$zone = get_named_zone($hostnames[0]);
		$domstr = "<B><A HREF=\"../brzones.php?frame=records&zone=".$zone['id']."\">%s</A></B> &nbsp; ";
		begin_domain($hostnames[0]);
		for ($i=1; $i<count($hostnames); $i++) {
			domain_name_server($hostnames[$i]);
		}
		$lamecounter++;
		print end_domain("", "$domstr is delegated to these servers:<UL>", "<LI>%s", "</UL><BR>\n", "<P>No NS records found", $lamecounter);
	}
}
pclose($pipe);
print sprintf($search_epilogue, $lamecounter);
print $html_bottom;


?>
