<HTML><HEAD>
<TITLE>Tool menu</TITLE>
<LINK rel="stylesheet" href="../style.css" type="text/css">
</HEAD>
<?php include("../inc/checkperm.inc"); ?>
<BODY bgcolor="#999966">
<HR noshade width="100%" size="1" color="#000000">
<A HREF="stats.php" target="toolcanvas">Statistics</A><BR>
External consistency<BR>
&nbsp;&nbsp;&nbsp;<A HREF="find-lamers.php" target="toolcanvas">Lame delegations</A><BR>
&nbsp;&nbsp;&nbsp;<A HREF="find-baddels.php" target="toolcanvas">NS inconsistencies</A><BR>
Internal consistency<BR>
&nbsp;&nbsp;&nbsp;<A HREF="int-ptr.php" target="toolcanvas">A-less PTR's</A><BR>
&nbsp;&nbsp;&nbsp;<A HREF="int-multia.php" target="toolcanvas">Multiple A's</A><BR>
&nbsp;&nbsp;&nbsp;<A HREF="int-mxcnt.php" target="toolcanvas">Too few MX's</A><BR>
&nbsp;&nbsp;&nbsp;<A HREF="int-invrec.php" target="toolcanvas">Invalid data</A><BR>
<A HREF="full-report.php" target="toolcanvas">Domain report</A><BR>
<A HREF="ip-ranges.php" target="toolcanvas">IP ranges</A><BR>
<A HREF="bulkupdate.php" target="toolcanvas">Bulk update</A><BR>
<?php if ($perm->have_perm("admin")) { ?>
<A HREF="settings.php?action=browse" target="toolcanvas">Settings</A><BR>
<A HREF="servers.php?action=browse" target="toolcanvas">Servers</A><BR>
<A HREF="usermgr.php" target="toolcanvas">Manage Logins</A><BR>
<?php } ?>
<A HREF="password.php" target="toolcanvas">Change Password</A><BR>
<HR noshade width="100%" size="1" color="#000000">
</BODY>
</HTML>
