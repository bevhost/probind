<?php include("inc/checkperm.inc"); ?>
<HTML>
<HEAD>
<TITLE>ProBIND Menu</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD>
<BODY bgcolor="#999966">
<TABLE border="0" cellpadding="0" cellspacing="0" width="100%">
<TR><TD align=center valign=middle>
  <TABLE border="0" cellpadding="0" cellspacing="0">
   <TR>
    <TD><IMG SRC="images/button-left.gif" width="10" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="brzones.php" target="canvas"><B><?php print trans('Browse zones');?></B></A></TD>
    <TD><IMG SRC="images/button-join.gif" width="30" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="brrec.php" target="canvas"><B><?php print trans('Browse records');?></B></A></TD>
    <TD><IMG SRC="images/button-join.gif" width="30" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="addzone.php" target="canvas"><B><?php print trans('Add a zone');?></B></A></TD>
    <TD><IMG SRC="images/button-join.gif" width="30" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="delzone.php" target="canvas"><B><?php print trans('Delete a zone');?></B></A></TD>
    <TD><IMG SRC="images/button-join.gif" width="30" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="tools/" target="canvas"><B><?php print trans('Misc. tools');?></B></A></TD>
    <TD><IMG SRC="images/button-join.gif" width="30" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="update.php" target="canvas"><B><?php print trans('Push updates');?></B></A></TD>
    <TD><IMG SRC="images/button-join.gif" width="30" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="logout.php" target="_top"><B><?php print trans('Logout');?></B></A></TD>
    <TD><IMG SRC="images/button-right.gif" width="10" height="33"></TD>
   </TR>
   </TABLE>
</TD>
<TD align=right><IMG SRC="images/ProBIND-logo.gif" width="138" height="50" border="0">&nbsp;</TD>
</TR>
</TABLE>
</BODY>
</HTML>
