<!DOCTYPE html
        PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/1999/xhtml/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title><?php echo isset($htmlTitle) ? $htmlTitle : "ProBind:".substr($_SERVER["PHP_SELF"],1,-4); ?></title>
<LINK rel="stylesheet" href="style.css" type="text/css">
<script type="text/javascript" src="/js/jquery.min.js"></script> 
<style type="text/css">

	.modal {
	position: absolute;
	font-family: Arial, Helvetica, sans-serif;
	top: 0;
	right: 0;
	bottom: -2000px;
	left: 0;
	background: rgba(0,0,0,0.7);
	z-index: 999;
	}
	.modal-header,
	.modal-body,
	.modal-footer {
		background-color: #999966;
		width:220px;
		margin: auto;
	}
	.modal-header {
		margin-top:130px;
		height: 40px;
	}
	.modal-header h3 {
		margin:10px 0px 0px 5px;
	}
	.modal-header .close {
		float:right;
		margin:9px;
	}
.modal-header {
  padding: 9px 15px;
  border-bottom: 1px solid #eee;
  -webkit-border-radius: 6px 6px 0 0;
     -moz-border-radius: 6px 6px 0 0;
          border-radius: 6px 6px 0 0;
}

.modal-body {
  max-height: 400px;
  padding: 15px;
  overflow-y: auto;
}

.modal-footer {
  padding: 14px 15px 15px;
  margin-bottom: 0;
  text-align: right;
  background-color: #999966;
  border-top: 1px solid #ddd;
  -webkit-border-radius: 0 0 6px 6px;
     -moz-border-radius: 0 0 6px 6px;
          border-radius: 0 0 6px 6px;
  *zoom: 1;
  -webkit-box-shadow: inset 0 1px 0 #ffffff;
     -moz-box-shadow: inset 0 1px 0 #ffffff;
          box-shadow: inset 0 1px 0 #ffffff;
}

.modal-footer:before,
.modal-footer:after {
  display: table;
  content: "";
}

.modal-footer:after {
  clear: both;
}


        body {
                margin: 0;
                padding: 0 10px 0 10px;
                height: 100%;
		background-color: #cccc99;
        }

        #content {
                margin: 68px -10px 300px 195px;
                display: block;
                padding: 10px;
                overflow: auto;
		background: url('images/BG-shadowleft.gif') repeat-y;
		height: 100%;
        }

        #header {
                display: block;
                top: 0px;
                left: 0px;
                width: 100%;
                height: 62px;
		padding: 3px;
                position: fixed;
                border: 1px solid #888;
		z-index: 10;
		background-color: #999966;
        }

        #navigation {
                display: block;
		margin: 68px 0px 0px 0px;
		top: 0px;
                left: 0px;
                width: 200px;
                height: 100%;
		padding-left: 3px;
		padding-top: 3px;
                position: fixed;
                border: 1px solid #888;
		overflow: hidden;
		background-color: #999966;
        }
	#navigation:hover {
		overflow: visible;
		z-index: 10;
	}
	#navigation a:before {
		content: ' ';
		clear: right;
		display: block;
		background-color: #999966;
	}

        * html #header {position: absolute;}
        * html #navigation {position: absolute;}

@media print {
        #navigation, #header { display:none; }
	#content { margin: 5px; }
}

</style>

<!--[if lte IE 6]>
   <style type="text/css">
   /*<![CDATA[*/
html {overflow-x: auto; overflow-y: hidden;}
   /*]]>*/
   </style>
<![endif]-->

</head>

<body>

<div id='popup'></div>
<div id="header">

<TABLE border="0" cellpadding="0" cellspacing="0" width="100%">
<TR><TD align=center valign=middle>
  <TABLE border="0" cellpadding="0" cellspacing="0">
   <TR>
    <TD><IMG SRC="images/button-left.gif" width="10" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="zones.php"><B><?php print trans('Browse zones');?></B></A></TD>
    <TD><IMG SRC="images/button-join.gif" width="30" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="records.php"><B><?php print trans('Browse records');?></B></A></TD>
    <TD><IMG SRC="images/button-join.gif" width="30" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="addzone.php?frame=addzone"><B><?php print trans('Add a zone');?></B></A></TD>
    <TD><IMG SRC="images/button-join.gif" width="30" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="delzone.php?frame=delzone"><B><?php print trans('Delete a zone');?></B></A></TD>
    <TD><IMG SRC="images/button-join.gif" width="30" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="stats.php"><B><?php print trans('Misc. tools');?></B></A></TD>
    <TD><IMG SRC="images/button-join.gif" width="30" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="update.php?frame=MAIN"><B><?php print trans('Push updates');?></B></A></TD>
    <TD><IMG SRC="images/button-join.gif" width="30" height="33"></TD>
    <TD background="images/button-middle.gif"><A HREF="logout.php"><B><?php print trans('Logout');?></B></A></TD>
    <TD><IMG SRC="images/button-right.gif" width="10" height="33"></TD>
   </TR>
   </TABLE>
</TD>
<TD align=right><IMG SRC="images/ProBIND-logo.gif" width="138" height="50" border="0">&nbsp;</TD>
</TR>
</TABLE>

</div>
<script type='text/javascript'>
<?php 
	get_request_values("domtype");
	if (!preg_match('/[MSA*]/',$domtype)) $domtype='M'; 
	echo "var domtype='$domtype';\n"; 
	if (@$SHOW_ALL) {
?>
function update_list(lookfor) {
  var nav = document.getElementById("navigation");
  var a = nav.getElementsByTagName("a");
  for(i=0;i<a.length;i++){
    if(a[i].innerHTML.toLowerCase().indexOf(lookfor.toLowerCase())>-1){
       a[i].style.display='block';
       a[i].style.visibility='visible';
    } else {
       a[i].style.display='none';
    }
  }
}
<?php	} else { ?>
function update_list(lookfor) {
	ajax('find.php?DomType='+domtype+';SrchZone='+lookfor,'domlist');
}
<?php	} ?>
</script>
<div id="navigation">
<?php
$s = substr(substr($_SERVER['PHP_SELF'],1),0,-4);
echo "<h1>$s</h1>";

switch ($s) {
  case "zones":
  case "records":
  case "addzone":
  case "delzone":
  case "update":  

	get_request_values("domtype");


	echo trans('Search')."<BR />\n";
        if ($SHOW_ALL) echo "<SELECT name='domtype' onchange=\"location='/zones.php?domtype='+this.options[this.selectedIndex].value\">\n";
	else echo "<SELECT name='domtype' onchange=\"domtype=this.options[this.selectedIndex].value\">\n";
        echo sprintf("<OPTION value='*'%s>".trans('All zones')."</OPTION>\n", (($domtype == '*') ? ' selected' : ''));
	echo sprintf("<OPTION value='M'%s>".trans('Master zones')."</OPTION>\n", (($domtype == 'M') ? ' selected' : ''));
	echo sprintf("<OPTION value='S'%s>".trans('Slave zones')."</OPTION>\n", (($domtype == 'S') ? ' selected' : ''));
	echo sprintf("<OPTION value='A'%s>".trans('Annotations')."</OPTION>\n", (($domtype == 'A') ? ' selected' : ''));
	echo "</SELECT><BR />\n";
	echo trans('For')."<BR />\n";
	echo "<INPUT type=text name='lookfor' value='' SIZE='16' onkeyup='update_list(this.value)' autocomplete='off'>\n";

	$lookfor = "%";
	$format = "<a href='/zones.php?domtype=$domtype;zone=%s'>%s%s</A>\n";
	echo "<div id=domlist>";
	if ($SHOW_ALL) echo  domain_list($lookfor, $domtype, $format);
	echo "</div>";
      break;
  default:
?>
<A HREF="stats.php">Statistics</A>
<BR>External consistency
&nbsp;&nbsp;&nbsp;<A HREF="find-lamers.php" style='margin-left:50px'>Lame delegations</A>
&nbsp;&nbsp;&nbsp;<A HREF="find-baddels.php">NS inconsistencies</A>
<BR>Internal consistency
&nbsp;&nbsp;&nbsp;<A HREF="int-ptr.php">A-less PTR's</A>
&nbsp;&nbsp;&nbsp;<A HREF="int-multia.php">Multiple A's</A>
&nbsp;&nbsp;&nbsp;<A HREF="int-nosoa.php">Missing SOA's</A>
&nbsp;&nbsp;&nbsp;<A HREF="int-mxcnt.php">Too few MX's</A>
&nbsp;&nbsp;&nbsp;<A HREF="int-invrec.php">Invalid data</A>
<A HREF="full-report.php">Domain report</A>
<A HREF="ip-ranges.php">IP ranges</A>
<A HREF="bulkupdate.php">Bulk update</A>
<?php if ($perm->have_perm("admin")) { ?>
<?php if (class_exists("DB_powerdns")) { ?>
<A HREF="pdns-import.php">PDNS Import</A>
<?php } ?>
<A HREF="settings.php?action=browse">Settings</A>
<A HREF="servers.php?action=browse">Servers</A>
<A HREF="exploits.php">Unblock banned</A>
<A HREF="usermgr.php">Manage Logins</A>
<?php } ?>
<A HREF="password.php">Change Password</A>
<?php
      break;
}
?>
</div>

<div id="content">

