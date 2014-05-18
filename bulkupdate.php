<?php
require 'inc/lib.inc'; 
require 'header.php';

$html_top = '
<HTML><HEAD>
<TITLE>Bulk update</TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD><BODY bgcolor="#cccc99" background="../images/BG-shadowleft.gif">
<TABLE width="99%">
<TR>
 <TD align="left"><H1>Marking all domains as updated</H1>
</TR>
</TABLE>
<HR><P>
';

$stern_warning = "
<B>WARNING:</B> You are about to mark all domains in the database as
having been updated. This means that the next time you push database
updates to the DNS servers, it will take a very long time.
<P>
This operation is only appropriate in a situation where one or more
of the DNS servers is known to be out of synchronization with this
database, or if there has been a change to the set of DNS servers 
which should appear in NS records.
<P>
<CENTER>
<A HREF=\"bulkupdate.php?iamserious=true\">
<IMG SRC=\"../images/wasp-warning.gif\" alt=\"Go ahead - do it!\">
</A>
</CENTER>
<P><HR><P>
</BODY></HTML>
";

get_input();
print $html_top;

if (empty($INPUT_VARS['iamserious']) || $INPUT_VARS['iamserious'] != 'true') {
	print $stern_warning;
	exit();
}

$db = new DB_probind;
$db->query("UPDATE zones SET updated = 1 WHERE NOT master AND domain != 'TEMPLATE'".access());
$db->query("UPDATE servers SET state = 'OUT'");

print "<P>Done. Now you need to push the updates.<P><HR>\n";
print "</BODY></HTML>\n";
?>
