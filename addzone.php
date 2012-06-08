<?php
require 'inc/lib.inc';

$html_top = '
<HTML>
<HEAD>
<TITLE>Add a zone</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="images/BG-shadowleft.gif">
<TABLE width="100%">
<TR>
 <TD align=left><H1>Adding a zone</H1></TD>
</TR>
</TABLE>
<HR><P>
';

$html_bottom = '
</BODY>
</HTML>
';

$start_frame = '
<HTML>
<HEAD>
<TITLE>Add Zone</TITLE>
</HEAD>
<FRAMESET rows="12,*" frameborder="0" border="0" framespacing="0">
  <FRAME src="topshadow.html" name="topshadow" noresize scrolling=no frameborder
="0" border="0" framespacing="0" marginheight="0" marginwidth="0">
  <FRAME src="addzone.php?frame=addzone" name="main" noresize scrolling=auto fr
ameborder="0" border="0" framespacing="0" marginheight="0" marginwidth="10">
</FRAMESET>
</HTML>
';

$start_form = "
<FORM method=\"post\" action=\"addzone.php\">
<INPUT type=hidden name=\"type\" value=\"master\">
<TABLE width=\"100%\">
<TR><TD>Domain name</TD>
    <TD><TEXTAREA name=\"newdomain\" rows=8 cols=44></TEXTAREA></TD>
    <TD>Enter one or more names of domains to add to the database, each on
    a separate line</TD></TR>
<TR><TD colspan=2 align=center><INPUT type=submit value=\"Add Master Domain(s)\"></TD>
</TABLE>
</FORM>
<P><HR><P>

<FORM method=\"post\" action=\"addzone.php\">
<INPUT type=hidden name=\"type\" value=\"slave\">
<TABLE width=\"100%\">
<TR><TD>Domain name</TD>
    <TD><INPUT name=\"newdomain\" size=32></TD>
<TR><TD>Master server</TD>
    <TD><INPUT name=\"newmaster\" size=32></TD>
<TR><TD colspan=2 align=center><INPUT type=submit value=\"Add Slave Domain\"></TD>
</TABLE>
</FORM>
";

function validate_domain($domain)
{
	$warnings = "";
	if (!strlen($domain))
		$warnings .= "<LI>You didn't specify a new domain name.\n";
	if (!valid_domain($domain))
		$warnings .= "<LI>'$domain' is not a valid domain name, or the domain already exists in the database.\n";
	return $warnings;
}

function validate_master($master)
{
	$warnings = "";
	if (!$master)
		$warnings .= "<LI>You must specify a master for this domain.\n";
	if (valid_domain($master)) {
		if (!($tmp = gethostbyname($master)))
			$warnings .= "<LI>'$master' is an unknown hostname.\n";
		else
			$master = $tmp;
	} elseif (strlen($master) && !valid_ip($master))
		$warnings .= "<LI>'$master' is neither a valid IP number, nor an existing domain name.\n";
	return $warnings;
}

function add_master_domain($input)
{
	$domains = explode("\n", $input['newdomain']);
	while(list($d, $line) = each($domains)) {
	    $domain = trim(ltrim($line));
	    $warnings = validate_domain($domain);
	    if (strlen($warnings)) {
		    $result .= "The '$domain' domain was not created, for the following reasons:<P><UL>\n$warnings</UL>\n";
	    } else {
		    $id = add_domain($domain, '');
		    $res1   .= fill_in_domain($id, 1);
		    $result .= "<HR><P>Domain '<A HREF=\"brzones.php?frame=records&zone=$id\">$domain</A>' successfully added.<P>\n";
			if ($res1) {
				$result .= "<HR>\n<H3>Records found in other domains which should be moved into the new domain</H3>\n";
			    $result .= "<br><FORM action=\"addzone.php\"><INPUT type=\"HIDDEN\" name=\"id\" value=\"$id\"><INPUT type=\"HIDDEN\" name=\"type\" value=\"fill\">\n";
				$result .= "<INPUT type=\"submit\" value=\"Move records into the new zone\"> <INPUT type=\"submit\" value=\"Cancel\" name=\"Cancel\"></FORM>\n";
				$result .= $res1."<HR>\n";
			}
	    }
    }
    return $result;
}

function add_slave_domain($input)
{
	$domain = $input['newdomain'];
	$master = $input['newmaster'];
	$warnings = validate_domain($domain);
	$warnings .= validate_master($master);
	# Enough validation, lets do it.
	if (strlen($warnings)) {
		$result .= "The domain was not created, for the following reasons:<P><UL>\n$warnings</UL>\n";
	} else {
		$id = add_domain($domain, $master);
		$result .= "<HR><P>Domain '<A HREF=\"brzones.php?frame=records&zone=$id\">$domain</A>' successfully added.<P>\n";
	}
	$result .= "<hr><p>\n";
	return $result;
}

function fill_master_domain($input)
{
	$id = $input['id'];
	return fill_in_domain($id, 0);
}

#
# MAIN
#

get_input();

$inputtype = '';
if(isset($INPUT_VARS['type']))
{
	$inputtype = $INPUT_VARS['type'];
}

switch ($inputtype) {
case 'master':
	print $html_top.add_master_domain($INPUT_VARS).$html_bottom;
	break;
case 'slave':
	print $html_top.add_slave_domain($INPUT_VARS).$html_bottom;
	break;
case 'fill':
    if ( !$INPUT_VARS['Cancel'])
    {
		print $html_top.fill_master_domain($INPUT_VARS).$html_bottom;
		break;
	}
default:
	if (isset($INPUT_VARS['frame']) && $INPUT_VARS['frame'] == 'addzone') {
		print $html_top.$start_form.$html_bottom;
	} else {
		print $start_frame;
	}
}

?>