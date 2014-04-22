<?php
require 'inc/lib.inc';

$start_form = '
<HTML>
<HEAD>
<TITLE>Zone Browser</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD>
<FRAMESET cols="170,*" frameborder="0" border="0" framespacing="0">
  <FRAME src="brzones.php?frame=zones" name="left" noresize scrolling=auto frameborder="0" border="0" framespacing="0" marginheight="0" marginwidth="5">
  <FRAMESET rows="12,*" frameborder="0" border="0" framespacing="0">
  <FRAME src="topshadow.html" name="topshadow" noresize scrolling=no frameborder="0" border="0" framespacing="0" marginheight="0" marginwidth="0">
  <FRAME src="tools/stats.php" name="right" noresize scrolling=auto frameborder="0" border="0" framespacing="0" marginheight="0" marginwidth="10">
  </FRAMESET>
</FRAMESET>
</HTML>
';

$left_frame = '
<HTML><HEAD>
<TITLE>Zone list</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD>
<BODY bgcolor="#999966">
<FORM action="brzones.php" method="post">
<INPUT type=hidden name="formname" value="zonesearch">
<TABLE border="0" cellpadding="0" cellspacing="0">
<TR><TD colspan="2"><IMG SRC="images/space.gif" align=top width="3" height="13">'.trans('Search').'</TD></TR>
<TR><TD colspan="2">%s</TD></TR>
<TR><TD colspan="2"><IMG SRC="images/space.gif" align=top width="3" height="13">'.trans('For').'</TD></TR>
<TR><TD colspan="2"><INPUT type=text name="lookfor" value="%s" SIZE="16"></TD></TR>
<TR><TD><IMG SRC="images/space.gif" width="5" height="10"><BR><INPUT type=submit value="'.trans('Search').'" class=button onmousemoveover="this.className=\'buttonhover\'" onmouseout="this.className=\'button\'"></TD>
 <TD valign=bottom><A HREF="manual.html#zones" target="right">Help</A></TD>
</TR>
</TABLE>
</FORM>
<HR noshade width="100%%" size="1" color="#000000">
';

$html_top = '
<HTML><HEAD>
<TITLE>Zone Details</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD><BODY bgcolor="#cccc99" background="images/BG-shadowleft.gif">
';

$default_right_frame = '
<HTML><HEAD>
<TITLE>Zone Details</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD><BODY bgcolor=#cccc99 background="images/BG-shadowleft.gif">
</BODY></HTML>
';

$nodomain_right_frame = '
<HTML><HEAD>
<TITLE>Zone Details</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD><BODY bgcolor=#cccc99 background="images/BG-shadowleft.gif">
The database contains no such domain: "%s".<P>
</BODY>
</HTML>
';

$master_zone_detail_form = '
<TABLE border="0" width="100%%" cellspacing="0" cellpadding="3" bgcolor="#666633">
<TR><TH><FONT color="#cccc99" size="+1">%s</FONT></TH></TR>
<TR><TD valign=top>
<TABLE border="0" width="100%%" cellspacing="3" cellpadding="3" bgcolor="#cccc99">
<TR><TD>
<TABLE border="0" width="100%%">
<FORM action="brzones.php" method="post">
<INPUT type="hidden" name="formname" value="masterzone">
<INPUT type="hidden" name="id" value="%d">
<TR>
  <TD valign=bottom><INPUT type="submit" value="Options" name="formname" class="button" onmouseover="this.className=\'buttonhover\'" onmouseout="this.className=\'button\'"></TD>
  <TH align=left colspan="5">Zonefile</TH>
  <TH align=left>Serial no.</TH>
  <TH align=left>Updated</TH>
  <TH align=left>Disabled</TH>
</TR>
<TR>
  <TD></TD>
  <TD colspan="5"><INPUT type=text name="zonefile" value="%s" size=32></TD>
  <TD><INPUT type=text value="%d" name="serial" size=10></TD>
  <TD>%s</TD>
  <TD><INPUT type=CHECKBOX name="disabled" value=1 %s></TD>
</TR>

<TR>
  <TD rowspan="2" valign=bottom><INPUT type="submit" value="Update" name="formname" class="button" onmouseover="this.className=\'buttonhover\'" onmouseout="this.className=\'button\'"></TD>
  <TH align=left colspan="2">Refresh</TH>
  <TH align=left colspan="3">Retry</TH>
  <TH align=left colspan="2">Expire</TH>
</TR>
<TR>
  <TD colspan="2"><INPUT type=text value="%s" name="refresh" size="5"></TD>
  <TD colspan="3"><INPUT type=text value="%s" name="retry" size="5"></TD>
  <TD colspan="2"><INPUT type=text value="%s" name="expire" size="7"></TD>
</TR>
<TR>
  <TD colspan="9"><HR noshade width="100%%" size="1" color="#000000"></TD>
</TR>
';

$slave_zone_detail_form = '
<FORM action="brzones.php" method="post">
<INPUT type="hidden" name="formname" value="slavezone">
<INPUT type="hidden" name="id" value="%d">
<TABLE border=0 width=60%%><TR><TH align=left>Domain</TH><TH align=left>Master</TH></TR>
<TR>
	<TD>%s</TD>
	<TD><INPUT type=text value="%s" name="master" size=15></TD></TR>
<TR><TH align=left>Zonefile</TH><TH align=left>Updated</TH><TH>Disabled</TH></TR>
<TR>
	<TD><INPUT type=text value="%s" name="zonefile" size=50></TD>
	<TD>%s</TD>
	<TD><INPUT type=CHECKBOX name="disabled" value=1 %s></TD></TR>
</TABLE>
<TABLE border width=60%%><TR>
	<TD align=center><INPUT type="submit" value="Options" name="formname"></TD>
	<TD align=center><INPUT type="submit" value="Update" name="formname"></TD>
	<TD align=center><INPUT type="submit" value="Delete Zone?" name="formname" class="button" onmouseover="this.className=\'buttonhover\'" onmouseout="this.className=\'button\'"></TD>
</TR></TABLE>
';

$rr_form_top = '
<TR>
	<TD valign=bottom><INPUT type="submit" value="Add RR" name="formname" class="button" onmouseover="this.className=\'buttonhover\'" onmouseout="this.className=\'button\'"></TD>
	<TH align=left>'.trans('Domain').'</TH>
	<TH align=left>'.trans('TTL').'</TH>
	<TH align=left>'.trans('Type').'</TH>
	<TH align=left>'.trans('Pref').'</TH>
	<TH align=left>'.trans('Ptr').'?</TH>
	<TH align=left colspan=2>'.trans('Data').'</TH>
	<TH align=left>'.trans('Comment').'</TH>
	<TH>
	</TH>
</TR>
</FORM>
<FORM action="brzones.php" method="post">
<INPUT type="hidden" name="formname" value="rrform">
<INPUT type="hidden" name="zone" value="%d">
';

$rr_form_hr = '
<TR><TD colspan="9"><HR noshadow></TD></TR>
';

$rr_form_bot = '
</FORM></TABLE>
</TD></TR></TABLE>
</TD></TR></TABLE>
';

$add_form = "
$html_top
<H2>Add a record</H2>
Adding a record to the %s domain.<P>
<FORM action=\"brzones.php\" method=\"post\">
<INPUT type=\"hidden\" name=\"zone\" value=\"%s\">
<INPUT type=\"hidden\" name=\"formname\" value=\"addrrform\">
<TABLE>
<TR><TH align=left>Domain</TH>
	<TD><INPUT type=\"text\" name=\"domain\" size=20></TD></TR>
<TR><TH align=left>TTL</TH>
	<TD><INPUT type=\"text\" name=\"ttl\" size=5></TD></TR>
<TR><TH align=left>Type</TH>
	<TD>%s</TD></TR>
<TR><TH align=left>Pref</TH>
	<TD><INPUT type=\"text\" name=\"pref\" size=3></TD></TR>
<TR><TH align=left>Data</TH>
	<TD><INPUT type=\"text\" name=\"data\" size=25></TD></TR>
<TR><TH align=left>Generate PTR?</TH>
	<TD><SELECT name=\"genptr\"><OPTION value=\"1\" selected>yes</OPTION><OPTION value=\"0\">no</OPTION></SELECT> (for <B>A</B> rr only)</TD></TR>
<TR><TH align=left>Comment</TH>
	<TD><INPUT type=\"text\" name=\"comment\" size=25></TD></TR>
<TR><TD></TD><TD><INPUT type=\"submit\" value=\"Add this record\"></TD></TR>
</TABLE></FORM>
<P>
Tip: You can use '@' instead of typing the fully-qualified domain
name (in the Domain field). This can be very useful when adding
MX records, or when fleshing out the TEMPLATE pseudo-domain.
<P>
</BODY>
</HTML>
";

$static_head = '
<TR><TD colspan="9"><HR noshade width="100%" size=1 color="#000000"></TD></TR>
<TR><TD colspan="6"><PRE>
';

$static_bottom = '
</PRE></TD>
<TD valign=top colspan="2">
</FORM>
<FORM action="brzones.php" method="post">
<INPUT type="hidden" name="id" value="%d">
<INPUT type="submit" value="Delete Zone?" name="formname" class="button" onmouseover="this.className=\'buttonwarning\'" onmouseout="this.className=\'button\'">
</TD></TR>
';

$html_close = "</BODY></HTML>\n";

$update_in_progress = "
<UL>
<B><BLINK>
%s is already running an update operation. The database is
locked until that process completes. Please try again in
a moment.
</BLINK></B>
</UL>
<P>
If this condition persists, or you are otherwise convinced that
an error has occurred, then you can clear the 'DOMAIN' lock condition
on the settings menu.
";

# dynamically generate a dropdown menu of available RR types. If the
# select argument is set, preselect that one. Returns finished HTML
$TYPELIST = array();	# Cache from DB lookup
function type_menu($tag, $select, $on="")
{
	global $TYPELIST;
	if (!$TYPELIST) {
		$rid = sql_query("SELECT type FROM typesort WHERE type != 'SOA' ORDER BY type");
		while ($record = mysql_fetch_array($rid)) {
			$TYPELIST[] = $record[0];
		}
		mysql_free_result($rid);
	}
	$result = "<SELECT name=\"$tag\" $on>\n";
	for ($i=0; $i<count($TYPELIST); $i++) {
		$type = $TYPELIST[$i];
		if ($type == $select)
			$result .= "<OPTION SELECTED>$type</OPTION>\n";
		else
			$result .= "<OPTION>$type</OPTION>\n";
	}
	$result .= "</SELECT>\n";
	return $result;
}

function domain_search_form($input)
{
	global $left_frame;
	$type = '';
	if (isset($INPUT_VARS['domtype']))
	{
		$type = $input['domtype'];
	}

	if (!$type)
		$type = "M";

	$srchstr = '';
	if (isset($INPUT_VARS['lookfor']))
	{
		$srchstr = $input['lookfor'];
	}

	$typebox = "<SELECT name=\"domtype\">\n";
	$typebox .= sprintf("<OPTION value=\"*\"%s>".trans('All zones')."</OPTION>\n",
		(($type == '*') ? ' selected' : ''));
	$typebox .= sprintf("<OPTION value=\"M\"%s>".trans('Master zones')."</OPTION>\n",
		(($type == 'M') ? ' selected' : ''));
	$typebox .= sprintf("<OPTION value=\"S\"%s>".trans('Slave zones')."</OPTION>\n",
		(($type == 'S') ? ' selected' : ''));
	$typebox .= sprintf("<OPTION value=\"A\"%s>".trans('Annotations')."</OPTION>\n",
		(($type == 'A') ? ' selected' : ''));
	$typebox .= "</SELECT>\n";
	return sprintf($left_frame, $typebox, $srchstr);
}

function record_form($record)
{
	$id = $record['id'];
	$on = "onchange=\"update_$id.className='buttonwarning';op_$id.value='Upd'\"";
	if ($record['type'] == 'SOA') {
		$result = sprintf("<INPUT type=\"hidden\" name=\"type_%d\" value=\"SOA\">\n", $record['id']);
		$result .= sprintf("<TR><TD><INPUT type=\"submit\" name=\"update_%s\" value=\"Upd\" class=\"button\"></TD>\n", $record['id']);
		$result .= sprintf("<TD><SELECT name=\"mode\"><OPTION value=\"view\">view</OPTION><OPTION value=\"edit\" SELECTED>edit</OPTION></SELECT></TD>\n");
		$result .= sprintf("<TD><INPUT type=\"text\" value=\"%s\" name=\"ttl_%d\" size=5 $on></TD>", seconds_to_ttl($record['ttl']), $record['id']);
		$result .= "<TD>SOA</TD>\n<TD></TD>\n<TD colspan=4></TD></TR>\n";
	} else {
		if ( $record['disabled'] ) {
			$ena_s = "";
			$dis_s = " SELECTED";
		}
		else {
			$ena_s = " SELECTED";
			$dis_s = "";
		}
		$result = sprintf("<TR><TD><INPUT type=\"submit\" name=\"update_%d\" value=\"Upd\" class=\"button\">\n", $id);
		$result .= "<INPUT type=\"hidden\" name=\"op_$id\" value=\"\">";
		$result .= sprintf("<SELECT type=\"submit\" name=\"status_%d\" $on><OPTION value=\"On\" $ena_s>on</OPTION><OPTION value=\"off\" $dis_s>off</OPTION><OPTION value=\"del\">del</OPTION></SELECT></TD>\n", $id);
		$result .= sprintf("<TD><INPUT type=\"text\" value=\"%s\" name=\"domain_%d\" size=20 $on></TD>\n", $record['domain'], $id);
		$result .= sprintf("<TD><INPUT type=\"text\" value=\"%s\" name=\"ttl_%d\" size=5 $on></TD>\n", seconds_to_ttl($record['ttl']), $id);
		$result .= sprintf("<TD colspan=1>%s</TD>", type_menu(sprintf("type_%d", $id), $record['type'], $on));
		$result .= sprintf("<TD><INPUT type=\"text\" value=\"%s\" name=\"pref_%d\" size=3 $on></TD>\n", $record['pref'], $id);
		if ( $record['type'] == 'A' ) {
			if ($record['genptr'] == 1)
				$result .= sprintf("<TD><INPUT type=\"checkbox\" name=\"genptr_%d\" $on value=\"1\" checked></TD>\n",$id);
			else
				$result .= sprintf("<TD><INPUT type=\"checkbox\" name=\"genptr_%d\" $on value=\"1\"></TD>\n",$id);
		} else {
			$result .= "<TD></TD>\n";
		}
		$result .= sprintf("<TD colspan=2><INPUT type=\"text\" value=\"%s\" name=\"data_%d\" size=25 $on></TD>\n", $record['data'], $id);
		$result .= sprintf("<TD><INPUT type=\"text\" value=\"%s\" name=\"comment_%d\" size=16 $on></TD></TR>\n", strip_tags($record['comment']), $id);
	}
	return $result;
}

function record_view($record)
{
	$id = $record['id'];
	$result = "";
	if ($record['type'] == 'SOA') {
		$result .= sprintf("<INPUT type=\"hidden\" name=\"rrid\">");
		$result .= sprintf("<TR>\n\t<TD></TD>\n");
		$result .= sprintf("<TD><INPUT type=\"submit\" name=\"mode\" value=\"edit zone (total)\" class=\"button\" onmouseover=\"this.className='buttonhover'\" onmouseout=\"this.className='button'\"></TD>\n");
		$result .= sprintf("\t<TD>%s</TD>\n", seconds_to_ttl($record['ttl']));
		$result .= "\t<TD>SOA</TD>\n\t<TD></TD>\n\t<TD colspan=2></TD>\n\t<TD colspan=2></TD>\n</TR>\n";
	} else {
		if ( !$record['disabled'] )
			$stat_html = "<IMAGE width=\"15\" height=\"15\" SRC=\"images/greenbutton.gif\">";
		else
			$stat_html = "<IMAGE width=\"15\" height=\"15\" SRC=\"images/noway.gif\">";
		$result .= sprintf("<TR><TD align=CENTER><INPUT type=\"submit\" name=\"edit\" value=\"edit\" onclick=\"rrid.value='$id'\" class=\"button\" onmouseover=\"this.className='buttonhover'\" onmouseout=\"this.className='button'\"> $stat_html</TD>\n");
		$result .= sprintf("\t<TD>%s</TD>\n", decode($record['domain']));
		$result .= sprintf("\t<TD>%s</TD>\n", seconds_to_ttl($record['ttl']));
		$result .= sprintf("\t<TD>%s</TD>\n", $record['type']);
		$result .= sprintf("\t<TD>%s</TD>\n", $record['pref']);
		if ( $record['type'] == 'A' ) {
			if ($record['genptr'] == 1)
				$result .= sprintf("\t<TD>yes</TD>\n");
			else
				$result .= sprintf("\t<TD>no</TD>\n");
		} else {
			$result .= "\t<TD></TD>\n";
		}
		$result .= sprintf("\t<TD colspan=2>%s</TD>\n", $record['data']);
		$result .= sprintf("\t<TD>; %s</TD>\n</TR>\n", strip_tags($record['comment']));
	}
	return $result;
}

function right_frame($vars)
{
	global $html_top, $default_right_frame, $master_zone_detail_form;
	global $slave_zone_detail_form, $nodomain_right_frame;
	global $rr_form_top, $rr_form_bot, $static_head, $static_bottom, $rr_form_hr;
	$result = $html_top;

	$zone = '';
	$search = '';

	if (isset($vars['domain']))
	{
		if (($dom = $vars['domain']) && !$vars['zone']) {
			$rid = sql_query("SELECT * FROM zones WHERE domain = '$dom'");
			if ($record = mysql_fetch_array($rid)) {
				$zone = $record['id'];
				mysql_free_result($rid);
			} else {
				mysql_free_result($rid);
				return sprintf($nodomain_right_frame, $dom);
			}
		}
	}

	if ($zone != '' || isset($vars['zone']))
	{
		if ($zone || $zone = $vars['zone']) {
			$explicit_ptrs = Array();
			$rid = sql_query("SELECT * FROM zones WHERE id = $zone");
			if (!mysql_num_rows($rid)) {
				mysql_free_result($rid);
				return sprintf($nodomain_right_frame, "zone#$zone");
			}
			$record = mysql_fetch_array($rid);
			mysql_free_result($rid);
			$domain = $record['domain'];
			$domstr = strtoupper($domain);
			if ($domain == 'TEMPLATE')
				$updtext = trans("N/A");
			elseif ($record['updated'])
				$updtext = trans("Yes");
			else
				$updtext = trans("No");

			if ($record['disabled'])
				$distext = " CHECKED";
			else
				$distext = "";

			if ($record['master'])
				$result .= sprintf($slave_zone_detail_form,
					$record['id'], decode($domain),
					$record['master'], $record['zonefile'],
					$updtext, $distext);
			else
				$result .= sprintf($master_zone_detail_form,
					decode($domstr), $record['id'],
					$record['zonefile'], $record['serial'],
					$updtext, $distext,
					seconds_to_ttl($record['refresh']),
					seconds_to_ttl($record['retry']),
					seconds_to_ttl($record['expire']));
			if ($record['master'])
				return $result;
			if (isset($vars['rrid']))
				$rrid = $vars['rrid'];
			$rid = sql_query("SELECT id, domain, ttl, records.type AS type, pref, data, genptr, comment, lpad(pref, 5, '0') AS sortpref, records.disabled AS disabled FROM records, typesort WHERE zone = $zone AND records.type = typesort.type ORDER BY typesort.ord, domain, sortpref");
			$result .= sprintf($rr_form_top, $zone);
			$result1 = "";
			while ($record = mysql_fetch_array($rid)) {
			if (isset($vars['mode']) && ($vars['mode'] == 'edit' || $vars['mode'] == 'edit zone (total)')) {
				$result .= record_form($record);
			} else {
				$result1 .= record_view($record);
				if (isset($rrid) && $record['id'] == $rrid) {
				$result .= record_form($record);
					$result .= $rr_form_hr;
				}
			}
				if (($record['type'] == 'PTR' || $record['type'] == 'NS') && !$record['disabled'] && isset($record['domain'])) {
					if (!isset($explicit_ptrs[$record['domain']]) {$explicit_ptrs[$record['domain']] = 0;}
					$explicit_ptrs[$record['domain']]++;
				} elseif ($record['type'] == 'SOA') {
					$soa_ttl = $record['ttl'];
				}
			}
			mysql_free_result($rid);
		$result .= $result1;
			$result .= $static_head;
			$servers = published_servers();
			$ttl = default_ttl($zone);
			/* This variable never gets set, so comment it out for now
			if (is_string($default_ttl))
				$result .= $ttl;
			*/
			$result .= auto_nsrecs($domain, seconds_to_ttl($ttl), $servers);
			if (preg_match("/\.ip6\.arpa(\.)?$/", $domain))
				$result .= auto_ip6_ptrs($domain, seconds_to_ttl($soa_ttl), $explicit_ptrs);
			if (preg_match("/\.in-addr\.arpa(\.)?$/", $domain))
				$result .= auto_ptrs($domain, seconds_to_ttl($soa_ttl), $explicit_ptrs);
			$result .= sprintf($static_bottom, $zone);
			$result .= $rr_form_bot;
			$result .= "</BODY></HTML>\n";
		} else {
			return $default_right_frame;
		}
	}
	return $result;
}

function perform_rr_action($input)
{
	global $REMOTE_USER, $update_in_progress;
	
	if (!isset($done)) $done = array();
	if (isset($id)) $done[$id] = 0;
	$result = "";
	
	while ($var = each($input)) {
		if ($var['value'] == 'Upd') {
			$action = $var['value'];
			list($d, $id) = split("_", $var['key']);
		if ($d != "op" && $d != "update")
			continue;
		if (isset($done[$id]) && $done[$id] == 1)
			continue;
		$done[$id] = 1;
		if (isset($input["status_$id"])) {
			$status = $input["status_$id"];
		} else {
			$status = '';
		}
		if ( $status == 'del' ) {
		del_record($id);
			   continue;
			}
			if ( $status == 'off')
			    $disabled = 1;
			else
			    $disabled = 0;
		switch ($input["type_$id"]) {
		case 'SOA':
			upd_soa_record($id, $input["ttl_$id"]);
			break;
		case 'MX':
			if ($user = patient_enter_crit($REMOTE_USER,'DOMAIN')){
				$result = sprintf($update_in_progress, ucfirst($user));
				break;
			}
			$warn = validate_record($input['zone'],
				$input["domain_$id"], $input["ttl_$id"],
				$input["type_$id"], $input["pref_$id"],
				$input["data_$id"]);
			if ($warn) {
				leave_crit('DOMAIN');
				$result = "<HR><P><UL>$warn</UL>\n";
				break;
			}
			upd_mx_record($id,
				$input["domain_$id"], $input["ttl_$id"],
				$input["pref_$id"], $input["data_$id"], $input["comment_$id"], $disabled);
			leave_crit('DOMAIN');
			break;
		default:
			if ($user = patient_enter_crit($REMOTE_USER,'DOMAIN')){
				$result = sprintf($update_in_progress, ucfirst($user));
				break;
			}
			$warn = validate_record($input['zone'],
				$input["domain_$id"], $input["ttl_$id"],
				$input["type_$id"], $input["pref_$id"],
				$input["data_$id"]);
			if ($warn) {
				leave_crit('DOMAIN');
				$result = "<HR><P><UL>$warn</UL>\n";
				break;
			}

			if (!isset($input["domain_$id"])) $input["domain_$id"] = "";
			if (!isset($input["ttl_$id"])) $input["ttl_$id"] = "";
			if (!isset($input["type_$id"])) $input["type_$id"] = "";
			if (!isset($input["data_$id"])) $input["data_$id"] = "";
			if (!isset($input["genptr_$id"])) $input["genptr_$id"] = "";
			if (!isset($input["comment_$id"])) $input["comment_$id"] = "";

			upd_record($id, $input["domain_$id"],
				$input["ttl_$id"], $input["type_$id"],
					$input["data_$id"], $input["genptr_$id"], $input["comment_$id"], $disabled);
			leave_crit('DOMAIN');
		}

		}
	}
	return $result;
}

function perform_zone_update($INPUT_VARS)
{
	$id = $INPUT_VARS['id'];
	$serial = $INPUT_VARS['serial'];
	$refresh = ttl_to_seconds($INPUT_VARS['refresh']);
	$retry = ttl_to_seconds($INPUT_VARS['retry']);
	$expire = ttl_to_seconds($INPUT_VARS['expire']);
	$master = $INPUT_VARS['master'];
	$zonefile = $INPUT_VARS['zonefile'];
	$disabled = $INPUT_VARS['disabled'];
	update_zone($id, $serial, $refresh, $retry, $expire, $master, $zonefile, $disabled ? 1 : 0);
}

function add_record($INPUT_VARS)
{
	global $REMOTE_USER, $update_in_progress;
	$zone = ltrim(rtrim($INPUT_VARS['zone']));
	$domain = ltrim(rtrim($INPUT_VARS['domain']));
	$ttl = ltrim(rtrim($INPUT_VARS['ttl']));
	$type = ltrim(rtrim($INPUT_VARS['type']));
	$pref = ltrim(rtrim($INPUT_VARS['pref']));
	$data = ltrim(rtrim($INPUT_VARS['data']));
	$genptr = ltrim(rtrim($INPUT_VARS['genptr']));
	$comment = ltrim(rtrim($INPUT_VARS['comment']));
	if ($user = patient_enter_crit($REMOTE_USER,'DOMAIN')){
		print sprintf($update_in_progress, ucfirst($user));
		exit();
	}
	$warnings = validate_record($zone, $domain, $ttl, $type, $pref, $data);
	if (strlen($warnings)) {
		leave_crit('DOMAIN');
		return "$html_top\n<UL>\n$warnings</UL>\n";
	}
	$zone = ltrim(rtrim($INPUT_VARS['zone']));
	insert_record($zone, $domain, $ttl, $type, $pref, $data, $genptr, $comment);
	leave_crit('DOMAIN');
	return "";
}

#
# MAIN
#
get_input();

if (isset($INPUT_VARS['frame']))
{
switch ($INPUT_VARS['frame']) {
case 'zones':
	if (!isset($SHOW_ALL)) {
			print domain_search_form($INPUT_VARS);
			exit();
	}
	$INPUT_VARS['formname'] = 'zonesearch';
	break;
case 'records':
if ($warnings = database_state())
		{
			echo '<div class="warning">';
			
			echo '<strong>Warning: The database is not in an operational state. The following problems exist:</strong>';
			
			echo '<ul>';
			
			foreach ($warnings as $warningmsg)
				echo '<li>', $warningmsg, '</li>';
		
			echo '</ul>';
		
			echo '</div>';
		}

		print right_frame($INPUT_VARS);
		exit();
	}
}

if (isset($INPUT_VARS['formname']))
{
switch($INPUT_VARS['formname']) {
case 'masterzone':
case 'slavezone':
case 'Update':
			perform_zone_update($INPUT_VARS);
			$INPUT_VARS['zone'] = $INPUT_VARS['id'];
			print right_frame($INPUT_VARS);
			break;
case 'addrrbutton':
case 'Add RR':
			$info = get_zone($INPUT_VARS['id']);
			print sprintf($add_form, $info['domain'], $INPUT_VARS['id'], type_menu("type", ''));
			break;
case 'addrrform':
			print add_record($INPUT_VARS);
			$info = get_zone($INPUT_VARS['zone']);
			print sprintf($add_form, $info['domain'], $INPUT_VARS['zone'], type_menu("type", ''));
	if (!isset($INPUT_VARS['mode']))
		$INPUT_VARS['mode'] = 'view';
	print right_frame($INPUT_VARS);
			break;
case 'rrform':
			if ($res = perform_rr_action($INPUT_VARS)) {
				print "$html_top.$res\n";
				exit();
			}

			print right_frame($INPUT_VARS);
			break;
case 'Delete Zone?':
			$info = get_zone($INPUT_VARS['id']);
			header("Location: delzone.php?frame=delzone&domain=".$info['domain']);
			break;
case 'Details':
case 'Options':
			$info = get_zone($INPUT_VARS['id']);
			header("Location: zonedetails.php?domain=".$info['domain']);
			break;
case 'zonesearch':
	print domain_search_form($INPUT_VARS);
	$str = isset($INPUT_VARS['lookfor'])? $INPUT_VARS['lookfor']: '';
	$domtype = isset($INPUT_VARS['domtype'])? $INPUT_VARS['domtype']: '';
	print domain_list("%$str%", $domtype, "<A HREF=\"brzones.php?frame=records&zone=%s&mode=view\" target=\"right\">%s%s</A><BR>\n");
	print $html_close;
	break;
default:
	print $start_form;
	break;
	}
}
else
{
	print $start_form;
}
?>
