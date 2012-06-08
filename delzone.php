<?php
require 'inc/lib.inc';

$html_top = '
<HTML>
<HEAD>
<TITLE>Deleting a zone</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD>
<BODY bgcolor="#cccc99" background="images/BG-shadowleft.gif">
<TABLE width="100%">
<TR>
 <TD align=left><H1>Deleting a zone</H1></TD>
</TR>
</TABLE>
<HR><P>
';

$start_frame = '
<HTML>
<HEAD>
<TITLE>Deleting a Zone</TITLE>
</HEAD>
<FRAMESET rows="12,*" frameborder="0" border="0" framespacing="0">
  <FRAME src="topshadow.html" name="topshadow" noresize scrolling=no frameborder ="0" border="0" framespacing="0" marginheight="0" marginwidth="0">
  <FRAME src="delzone.php?frame=delzone%s" name="main" noresize scrolling=auto frameborder="0" border="0" framespacing="0" marginheight="0" marginwidth="10">
</FRAMESET>
</HTML>
';

$start_form = "
<FORM method=\"post\" action=\"delzone.php\">
<TABLE width=\"100%%\">
<TR><TD>Domain name</TD><TD><INPUT name=\"trashdomain\" size=32 value=\"%s\"></TD>
<TR><TD colspan=2 align=center><INPUT type=submit value=\"Delete zone from database\"></TD>
</TABLE>
</FORM>
";

#
# MAIN
#
get_input();
if (($domain = $INPUT_VARS['domain']) || !$INPUT_VARS['trashdomain']) {
	if ($INPUT_VARS['frame'] == "delzone")
		print $html_top.sprintf($start_form, $domain);
	else {
		if ($domain)
			$extra = "&domain=$domain";
		print sprintf($start_frame, $extra);
	}
} else {
	print $html_top;
	$trashdomain = $INPUT_VARS['trashdomain'];

	#
	# Input validation (long ...)
	$warnings = '';
	if (!strlen($trashdomain))
		$warnings .= "<LI>You didn't specify a zone.\n";
	if (!($domid = known_domain($trashdomain)))
		$warnings .= "<LI>'$trashdomain' does not exist in the database.\n";
	if (strtoupper(ltrim(rtrim($trashdomain))) == "TEMPLATE")
		$warnings .= "<LI>You may not delete the TEMPLATE zone.\n";
	# Enough validation, lets do it.
	if (strlen($warnings)) {
		print "The domain was not deleted, for the following reasons:<P><UL>\n$warnings</UL>\n";
	} else {
		if (!$INPUT_VARS['iamserious']) {
			print "
			<B>WARNING</B>: You are about to delete domain <A href=\"brzones.php?frame=records&mode=view&zone=$domid\">$trashdomain</A> permanently.

			<P>Zone can be restored manually only (it will be placed into 'DELETED' folder)
			<P><CENTER>
			<A HREF=\"delzone.php?trashdomain=$trashdomain&iamserious=true\">
			<IMG SRC=\"images/wasp-warning.gif\" alt=\"Go ahead - do it!\">
			</A>
			";

		}
		else {
			$rrcount = del_zone($domid);
			print "<HR><P>Domain '$trashdomain' (id = $domid) and $rrcount resource records successfully removed.<P>\n";
		}
	}
	print "<hr><p>\n";
}

?>

</BODY>
</HTML>