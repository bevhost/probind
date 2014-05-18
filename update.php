<?php
require 'inc/lib.inc';
if ($INTERFACE!=="stable") require 'header.php';

$start_frame = '
<HTML>
<HEAD>
<TITLE>Add Zone</TITLE>
</HEAD>
<FRAMESET rows="12,60%,*" frameborder="5" border="2" framespacing="0">
  <FRAME src="topshadow.html" name="topshadow" noresize scrolling=no frameborder ="0" border="0" framespacing="0" marginheight="0" marginwidth="0">
      <FRAME src="update.php?frame=MAIN"  name="MAIN"    scrolling=auto frameborder="5" border="3" framespacing="3" marginheight="0" marginwidth="10">
      <FRAME src="update.php?frame=BLANK" name="VIEW"    scrolling=auto frameborder="3" border="3" framespacing="3" marginheight="3" marginwidth="10">
</FRAMESET>
</HTML>
';

$html_top = '
<HTML><HEAD>
<TITLE>Proventum Push DNS Updates</TITLE>
<META http-equiv="Pragma" content="no-cache">
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD><BODY bgcolor="cccc99" background="images/BG-shadowleft.gif">
<TABLE width="100%">
<TR>
 <TD align=left><H3>Pushing DNS updates to the servers</H3></TD>
 <TH align=right><A HREF="manual.html#push">Help</A></TH>
</TR>
</TABLE>
<HR>
';

$html_top1 = '
<HTML><HEAD>
<TITLE>Update LOGS</TITLE>
<META http-equiv="Pragma" content="no-cache">
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD><BODY bgcolor="dddd99" background="images/BG-shadowtop.gif">
';

$html_bottom = '
</BODY>
</HTML>
';

$html_in_progress = '
<HTML><HEAD>
<TITLE>Push in progress</TITLE>
<META http-equiv="Pragma" content="no-cache">
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD><BODY bgcolor="dddd99" background="images/BG-shadowtop.gif">
<TABLE width="100%">
<TR>
 <TD align=left><H3>Pushing DNS updates to the servers</H3></TD>
</TR>
</TABLE>
<HR>
<CENTER>
<TABLE width="40%" bgcolor=RED border=10>
<TR><TD><IMAGE src="images/u0.gif" width=400 height=8></TD></TR>
<TR>
<TD ALIGN=CENTER><span style="color:#ffffff; font-size: 24px;"><strong>Push in progress.<br />Do not cancel.</strong></span></TD>
</TR>
<TR><TD><IMAGE src="images/u0.gif" width=400 height=8></TD></TR>
</TABLE>
</BODY></HTML>
';

$push_in_progress = "
<UL>
<B><BLINK>
%s is already running a push operation. The database is
locked until that process completes. Please be patient, as
a big update can take many minutes to complete.
</BLINK></B>
</UL>
<P>
If this condition persists, or you are otherwise convinced that
an error has occurred, then you can clear the lock condition
on the settings menu.
";



function main_update_menu($input)
{
	global $TWO_STEP_UPDATE;
	global $HOST_URL;

	$db = new DB_probind;
	adjust_serials();
	$db->query("SELECT id FROM zones WHERE updated ");
	$zones = $db->num_rows();

	$db->query("SELECT id,hostname,ipno,type,state FROM servers WHERE pushupdates = 1");
	print "<FORM action=\"update.php\"><INPUT type=\"HIDDEN\" name=\"frame\" value=\"VIEW\">\n";
	$res = "<TABLE BORDER=2 width=\"70%\">\n";
	$res .= "<TR><TH>name</TH><TH>ip address</TH><TH>type</TH><TH>state</TH><TH>Do not apply</TH><TH>View</TH><TH>Test</TH></TR>\n";
	$gen_c  = "";
	$push_c = "";
	$conf_c = "";
	$appl_c = "CHECKED";
	while ($db->next_record()) {
		extract($db->Record);

		$T = $state;
		$B = "";
		$skip_c = "";
		switch ($state) {
			case 'OK':
				$T = "<B>OK</B>";
			 	break;
			case 'OUT':
				$T = "<B>need update</B>";
				$B = " bgcolor=yellow";
				$gen_c="CHECKED";
				$push_c = "CHECKED";
				$conf_c = "CHECKED";
				break;
			case 'CHG':
				$T = "<B>need push</B>";
				$B = " bgcolor=yellow";
				$push_c = "CHECKED";
				$conf_c = "CHECKED";
				break;
			case 'CFG':
				$T = "<B>need reconfig</B>";
				$B = " bgcolor=yellow";
				$conf_c = "CHECKED";
				break;
			case 'ERR':
				$T = "<FONT COLOR=WHITE><BLINK>Update error</BLINK></FONT>";
				$B = " bgcolor=red";
				$skip_c = "CHECKED";
				$gen_c = "CHECKED";
				$push_c = "CHECKED";
				break;
			default:  break;
		}


		$res .= "<TR>\n";
		$res .= "\t<TD>$hostname</TD>\n";
		$res .= "\t<TD>$ipno</TD>\n";
		$res .= "\t<TD align=center>$type</TD>\n";
		$res .= "\t<TD $B>$T</TD>\n";
		$res .= "\t<TD align=center><INPUT TYPE=\"CHECKBOX\" $skip_c name=\"skip_$id\"></TD>\n";
		$res .= "\t<TD align=center>";
		    $res .= "<A href=\"view.php?file=$hostname/named.conf\">named.conf</A>,";
			$res .= "<A href=\"$HOST_URL/$hostname/\">files</A>";
			$res .= "</TD>\n";
		$res .= "\t<TD align=center><A href=\"test.php?id=$id\"><img src=\"images/greenbutton.gif\" border=0 high=16 width=24></A></TD>\n";
		$res .= "</TR>\n";
	}
	$res .= "</TABLE>\n";

	$up_text = "START UPDATE";

	if ($TWO_STEP_UPDATE && ($gen_c || $push_c)) {
	    $conf_c = "";
	}

	if ($TWO_STEP_UPDATE && $conf_c)
		$up_text = "FINISH UPDATE";

	print "<TABLE width=\"70%\"  border=\"1\">\n";
	print "\t<TD>Generate files ($zones): <INPUT type=CHECKBOX name=\"gen\" $gen_c></TD>\n";
	print "\t<TD>Push files: <INPUT type=CHECKBOX name=\"push\" $push_c></TD>\n";
	print "\t<TD>Reconfig server: <INPUT type=CHECKBOX name=\"conf\" $conf_c></TD>\n";
	print "</TR>\n";
	print "<TR><TD colspan=3 align=center>";
	print "<INPUT type=submit value=\"$up_text\" class=\"button\" onmouseover=\"this.className='buttonwarning'\" onmouseout=\"this.className='button'\">\n";
	print "</TD></TR>\n";
	print "</TABLE>\n";
	print $res;
	print "</FORM>\n";

	exit;
}

function generate_files($input)
{
	global $UPDATE_LOG;
	global $A_LOG;
	global $A_LOGE;
	global $HOST_DIR;
	global $HOST_URL;
	global $TOP;
	global $BIN;
	global $SERVER_TYPES;
	global $HOSTMASTER;

	$skipped = 0;
	$ret = '';
	$error = 0;
	$err = '';

	$db = new DB_probind;

	$db->prepare("SELECT id, domain, master, zonefile FROM zones WHERE updated AND domain != 'TEMPLATE' ORDER BY domain");
	$db->execute();
	$zones = $db->fetchAll();

	$db->prepare("SELECT domain, zonefile FROM deleted_domains");
	$db->execute();
	$deleted_domains = $db->fetchAll();

	$db->query("SELECT id as servid, hostname as server, type, zonedir FROM servers ".
			"WHERE (state = 'OUT' OR state = 'ERR') AND pushupdates");

	while ($db->next_record()) {
		extract($db->Record);
		if (!empty($input["skip_$servid"])) {
			print "<FONT COLOR=BROWN>Skipping $server</FONT><BR>\n";
			$skipped = 1;
			continue;
		}

		print "<H4>Updating $server (as ".$SERVER_TYPES[$type].")</H4>\n";

		# This server need real file update
		if (!file_exists("$HOST_DIR/$server")) mkdir("$HOST_DIR/$server");
		chdir("$HOST_DIR/$server") || die("$!: $HOST_DIR/$server<P>\n");
		$cmd = "TOP=$TOP $BIN/mknamed.conf $server named.conf ";
		print $cmd;
		passthru("$cmd >> $UPDATE_LOG 2>& 1", $ret);
		if ($ret != 0) {
		    print "<A href=\"view.php?file=$server/named.conf\"><FONT color=RED>mknamed.conf</FONT></A> failure, see $A_LOGE<BR>\n";
		    $error = 1;
		}
		else
		    print "<A href=\"view.php?file=$server/named.conf\"><FONT color=GREEN>named.conf</FONT></A> updated, see $A_LOG<BR>\n";

		if ($type == 'N') {
			foreach($zones as $zone) {
				update_powerdns_zone($zone["id"],$server,$HOSTMASTER);
			}
		}

		if ($type == 'M') {
			print "<UL>\n";

			foreach($deleted_domains as $trash) {
			    	$zonefile = $trash['zonefile'];
				$domain = $trash['domain'];

				$cmd = "mkdir -p DELETED;rm -f DELETED/'$zonefile'; mv '$zonefile' DELETED/. && echo '$zonefile' >> deleted_files";
				exec($cmd);
				print "<LI><A href=\"view.php?file=$server/DELETED/$zonefile\">$domain deleted</A>\n";
			}

			$out= "";
			$list = "";
			foreach($zones as $zone) {
				 $domain = $zone['domain'];
				 if (!$zone['master'])
					$domains[] = $zone['domain'];

				 $zonefile = $zone['zonefile'];

				 if (count($domains) >= 64) {
					$cmd =  "TOP=$TOP $BIN/mkzonefile -d $HOST_DIR/$server -u ".join(" ", $domains);
					passthru("$cmd 2>&1 >>$UPDATE_LOG", $ret);
					$domains = array();
					if ($ret != 99) {
				    	    $error = 1;
				    		print "</UL><H3><FONT color=RED>Error in mkzonefile, see $A_LOG</FONT></H3><UL>\n";
			    	    	//print $err;
							}
					else
						print $out;
					$out = "";
					$err = "";

				 }
				 $out .=  "<LI><A href=\"view.php?file=$server/$zonefile\">$domain</A> updated\n";
				 $err .= "<LI><A href=\"view.php?file=$server/$zonefile\">$domain</A> <FONT color=RED>error</FONT>\n";
			    if (count($domains)) {
				 $cmd =  "TOP=$TOP $BIN/mkzonefile -d $HOST_DIR/$server -u ".join(" ", $domains);
				 // print "<I>$cmd</I><BR>\n";
				 passthru("$cmd 2>&1 >>$UPDATE_LOG", $ret);
				 $domains = array();
				 if ($ret != 99) {
				    $error = 1;
				    print "</UL><H3><FONT color=RED>Error in mkzonefile, see $A_LOG</FONT></H3><UL>\n";
				    print $err;
				 }
				 else
					print $out;
			    }
			}
			print "</UL>\n";


			//----------------------------------------------------//


		}

		if ($error)
			$text = "<FONT color=RED>ERROR:</FONT>";
		else
			$text = "<FONT color=RED>UPDATED:</FONT>";

		print "$text<A href=\"$HOST_URL/$server/\">$server</A><HR>\n";
		if ($error) {
			$db->prepare("UPDATE servers SET state='ERR' WHERE id = ?");
			$db->execute($servid);
			break;
		}
		else {
			$db->prepare("UPDATE servers SET state='CHG' WHERE id = ?");
			$db->execute($servid);
		}
	};


	if (!$error) {
		patient_enter_crit('INTERNAL1', 'DOMAIN');
		if (!$skipped)
			updates_completed();
		$db->query("DELETE FROM deleted_domains");
		leave_crit('DOMAIN');
	};

    if  ($error)
	    $err_text="&error=1";
	else {
	    $err_text="";
	}
    return ($error? "file update failure" : "");
}

function run_scripts($input, $push, $conf)
{

	global $UPDATE_LOG, $UPDATE_LOG_NAME;
	global $A_LOG;
	global $A_LOGE;
	global $HOST_DIR;
	global $TOP;
	global $SBIN;
	
	$ret = '';
	$error = 0;
	
	if (!$push && !$conf)
		return;

	$db = new DB_probind;

	$db->query("
	SELECT id as servid, hostname as server, ipno, type, zonedir, chrootbase, script, state 
	FROM servers 
	WHERE pushupdates != 0 and type<>'N'");

	while ($db->next_record()) {
		extract($db->Record);

		$cmd = "";
		if ( ($state == 'CHG' || $state == 'ERR') && $push ) {
			$cmd = " -PUSH";
			if ($conf) {
				$cmd .= " -CONF";
				$new = 'OK';
			}
			else
				$new = 'CFG';
		}
		else if ( ($state == 'CFG' || $state == 'ERR') && $conf ) {
			$cmd = "-CONF";
			$new = 'OK';
		}

		if (!empty($input["skip_$servid"]) || $cmd == "" ) {
			continue;
		}

		$cmd = "TOP=$TOP $SBIN/$script $cmd $ipno $chrootbase$zonedir";

		print "<BR>$server:\n";

		# This server need real file update
		chdir("$HOST_DIR/$server") || die("$!: $HOST_DIR/$server<P>\n");

		passthru("$cmd >> $UPDATE_LOG 2>& 1", $ret);

		if ($ret != 0) {
		    print "<FONT color=RED><B>script failure, see $A_LOGE.</B></FONT>Command: <FONT size=-2>$cmd</FONT><BR>\n";
		    $error = 1;

		}
		else {
		    print "script completed, see $A_LOG. Command: <FONT size=-2>$cmd</FONT><BR>\n";
		}

		if ($error) {
			$db->prepare("UPDATE servers SET state='ERR' WHERE id = ?");
			$db->execute($servid);
			break;
		}
		else {
			$db->prepare("UPDATE servers SET state=? WHERE id = ?");
			$db->execute($new,$servid);
		}
	};


    if  ($error)
	    $err_text="&error=1";
	else
	    $err_text="";

	// print "<H4>Log file: <A href=\"view.php?base=LOGS&file=$UPDATE_LOG_NAME$err_text\">$UPDATE_LOG</A></H4>\n";
	return ($error? "script failure" : "");
}

#
# MAIN
#

get_input();
$err = '';

#
# 1. Update serial numbers if necessary. Decision about generating zone will be done
# by comparing server serial id and zone zerial id
# gen=1 - generate zones; sync = 1 - syncronyze3 zones, conf = 1 - configure zones,
# id_%d = 1 - server id for the operation (id_ALL=1 means ALL servers)
#
# If no operation is specified, serials are updated and overall view is presented
#

$gen   = empty($INPUT_VARS['gen']) ? '' : $INPUT_VARS['gen'];
$push  = empty($INPUT_VARS['push']) ? '' : $INPUT_VARS['push'];
$conf  = empty($INPUT_VARS['conf']) ? '' : $INPUT_VARS['conf'];
$frame = empty($INPUT_VARS['frame']) ? '' : $INPUT_VARS['frame'];


#
# Set up frame structure
#
if (!$frame) {
	$frame = 'MAIN';
}


if ($frame == 'MAIN' ) {
	print $html_top;
	print main_update_menu($INPUT_VARS);
	exit;
}

if ($frame == 'PROGRESS' ) {
	print $html_in_progress;
	exit;
}

if ($frame == 'BLANK') {
    print $html_top1;
    print $html_bottom;
	exit;
}

print $html_top1;

if ( !$LOG_DIR || !opendir($LOG_DIR) ) {
	die("<H3><FONT color=\"red\">Can not open log directory: $LOG_DIR</FONT></H3>\n");
};
closedir();
$UPDATE_LOG_NAME= date("YmdHis").".log";
$UPDATE_LOG = "$LOG_DIR/$UPDATE_LOG_NAME";
$A_LOG="<A href=\"view.php?base=LOGS&file=$UPDATE_LOG_NAME\">LOG</A>";
$A_LOGE="<A href=\"view.php?base=LOGS&file=$UPDATE_LOG_NAME&error=1\">LOG</A>";

#print "<SCRIPT>open('update.php?frame=PROGRESS','MAIN');</SCRIPT><HR>\n";
#for($i = 0; $i < 512; $i++)
#    print "<B></B>\n";

if ($user = patient_enter_crit($auth->auth["uname"], 'PUSH')) {
	print sprintf($push_in_progress, ucfirst($user));
	exit();
}

if ($gen) {
	$err = generate_files($INPUT_VARS);
	if ($err) {
		print "<H3><FONT color=RED>Update interrupted due to the error</FONT></H3>\n";
		print "$err <br>\n";
	}
}

if ( !$err && ($push || $conf)) {
	$err = run_scripts($INPUT_VARS, $push, $conf);
	if ($err) {
		print "<H3><FONT color=RED>Update interrupted due to the error</FONT></H3>\n";
		print "$err <br>\n";
	}
}


if ($err) {
	print "<H3><FONT color=RED>Errors";
	if (file_exists($UPDATE_LOG)) {
		print ", see logs ";
		print "<A href=\"view.php?base=LOGS&file=$UPDATE_LOG_NAME\">here</A></FONT></H3>\n";
	}
}
else {
	print "<H3><FONT color=GREEN>Completed";
	if (file_exists($UPDATE_LOG)) {
		print ", see logs ";
		print "<A href=\"view.php?base=LOGS&file=$UPDATE_LOG_NAME\">here</A></FONT></H3>\n";
	}
};
leave_crit('PUSH');

close_database();
#print "<SCRIPT>open('update.php?frame=MAIN','MAIN');</SCRIPT><HR>\n";
print $html_bottom;
?>


