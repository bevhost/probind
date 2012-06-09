<?php
require 'inc/lib.inc';

$ROWS_PER_PAGE = 50;

$html_top = "<html><body>";


$start_frame = '
<HTML>
<HEAD>
<TITLE>Browse Records</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD>
<FRAMESET rows="12,*" frameborder="0" border="0" framespacing="0">
  <FRAME src="topshadow.html" name="topshadow" noresize scrolling=no frameborder ="0" border="0" framespacing="0" marginheight="0" marginwidth="0">
  <FRAME src="brrec.php?frame=brrec" name="main" noresize scrolling=auto frameborder="0" border="0" framespacing="0" marginheight="0" marginwidth="10">
</FRAMESET>
</HTML>
';

$query_form = '
<HTML><HEAD>
<TITLE>Resource Record Query Form</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD><BODY bgcolor="cccc99" background="images/BG-shadowleft.gif">
<FORM action="brrec.php" method="post">
<INPUT type=hidden name="select" value="result">
<INPUT type=hidden name="pointer" value="%d">
<TABLE width="100%%">
<TR><TH>Zone</TH><TH>Domain</TH><TH>Type</TH><TH>Pref</TH><TH>Data</TH></TR>
<TR>
<TD><INPUT TYPE=text name="zdomain" value="%s" SIZE=15 MAXLENGTH=100 onfocus=this.select()></TD>
<TD><INPUT TYPE=text name="rdomain" value="%s" SIZE=15 MAXLENGTH=100 onfocus=this.select()></TD>
%s
<TD><INPUT type=text name="pref" value="%s" SIZE=2 MAXLENGTH=4 onfocus=this.select()></TD>
<TD><INPUT type=text name="data" value="%s" SIZE=25></TD>
<TD><INPUT type=submit name="submit" value="Search"></TD>
<TD align=right><A HREF="manual.html#records">Help</A></TD>
</TR></TABLE>
</FORM>
Use the %% wildcard character to specify a substring search.
<HR>
';

$query_type = '
<TD><SELECT name="type">
	<OPTION>*</OPTION>
	<OPTION>A</OPTION>
	<OPTION>CNAME</OPTION>
	<OPTION>MX</OPTION>
	<OPTION>NS</OPTION>
	<OPTION>PTR</OPTION>
	<OPTION>TXT</OPTION>
	<OPTION>HINFO</OPTION>
	<OPTION>SRV</OPTION>
</SELECT></TD>
';

$empty_result = '
<HR><BR>
<H1>Resource Record Query Results</H1>
<BR><HR><BR>
';

$button_form = '
<FORM action="brrec.php" method="post">
<INPUT type=hidden name="select" value="result">
<INPUT type=hidden name="zdomain" value="%s">
<INPUT type=hidden name="rdomain" value="%s">
<INPUT type=hidden name="type" value="%s">
<INPUT type=hidden name="pref" value="%s">
<INPUT type=hidden name="data" value="%s">
<INPUT type=hidden name="pointer" value="%s">
<INPUT type=submit name="%s" value="%s">
</FORM>
';

$html_close = "</BODY></HTML>\n";

function query_type($type)
{
	$result = "<TD><SELECT name=\"type\">\n";
	$result .= sprintf("<OPTION%s>*</OPTION>\n",
		((!$type || ($type == '*')) ? ' selected' : ''));
	$types = array('A', 'AAAA', 'CNAME', 'MX', 'NS', 'PTR', 'TXT', 'HINFO', 'SRV');
	while ($tp = each($types)) {
		$result .= sprintf("<OPTION%s>%s</OPTION>\n",
			($type == $tp[1] ? ' selected' : ''),
			$tp[1]);
	}
	$result .= "</SELECT></TD>\n";
	return $result;
}

function query_form($INPUT_VARS)
{
	global $query_form;

	$zdomain = '';
	$rdomain = '';
	$type = '';
	$pref = '';
	$data = '';
	$pointer = '';

	//Check if the form has been submitted before
	if(isset($INPUT_VARS['zdomain']))
	{
		$zdomain = ltrim(rtrim($INPUT_VARS['zdomain']));
		$rdomain = ltrim(rtrim($INPUT_VARS['rdomain']));
		$type = ltrim(rtrim($INPUT_VARS['type']));
		$pref = ltrim(rtrim($INPUT_VARS['pref']));
		$data = ltrim(rtrim($INPUT_VARS['data']));
		$pointer = ltrim(rtrim($INPUT_VARS['pointer']));
	}
	return sprintf($query_form,
		$pointer, $zdomain, $rdomain, query_type($type), $pref, $data);
}

function result_form($INPUT_VARS)
{
	global $empty_result, $button_form, $ROWS_PER_PAGE;
	$result = "";
	$zdomain = ltrim(rtrim($INPUT_VARS['zdomain']));
	$rdomain = ltrim(rtrim($INPUT_VARS['rdomain']));
	$type = ltrim(rtrim($INPUT_VARS['type']));
	$pref = ltrim(rtrim($INPUT_VARS['pref']));
	$data = ltrim(rtrim($INPUT_VARS['data']));
	$pointer = ltrim(rtrim($INPUT_VARS['pointer']));
	if (!strlen($zdomain.$rdomain.$pref.$data) && $type == '*')
		return $empty_result;
	$queryhead = "SELECT zones.id AS zid, zones.domain AS zdom, records.domain AS rdom, records.id AS rrid, type, pref, data FROM zones, records ";
	$counthead = "SELECT count(*) FROM zones, records ";
	$querycond = "WHERE zones.id = records.zone AND zones.domain != 'TEMPLATE' AND type != 'SOA'".access();
	if (strlen($zdomain)) {
		if (strchr($zdomain, "%"))
			$querycond .= " AND zones.domain like '$zdomain'";
		else
			$querycond .= " AND zones.domain = '$zdomain'";
	}
	if (strlen($rdomain)) {
		if (strchr($rdomain, "%"))
			$querycond .= " AND records.domain like '$rdomain'";
		else
			$querycond .= " AND records.domain = '$rdomain'";
	}
	if ($type != '*')
		$querycond .= " AND type = '$type'";
	if (strlen($pref))
		$querycond .= " AND pref = $pref";
	if (strlen($data)) {
		if (strchr($data, "%"))
			$querycond .= " AND records.data like '$data'";
		else
			$querycond .= " AND records.data = '$data'";
	}
	$querytail = " ORDER BY zones.domain, records.domain";
	$querytail .= " LIMIT $pointer, ".($pointer+$ROWS_PER_PAGE);
	$rid = sql_query($counthead.$querycond);
	$row = mysql_fetch_row($rid);
	$count = $row[0];
	mysql_free_result($rid);
	$displ = ( (($pointer + $ROWS_PER_PAGE) <= $count) ?
		$ROWS_PER_PAGE : ($count % $ROWS_PER_PAGE) );
	$result .= "Displaying $displ of $count records, starting at $pointer<BR>\n";
	$rid = sql_query($queryhead.$querycond.$querytail);
#	$result .= "Found ".($count - $pointer)." matching records.<BR>\n";
	$result .= "<TABLE><TR>\n";
	$result .= "<TD>";
	$result .= sprintf($button_form,
		$zdomain, $rdomain, $type, $pref, $data,
		0, "start",
		"<<<< First");
	$result .= "</TD>";
	if ($pointer) {
		$result .= "<TD>";
		$result .= sprintf($button_form,
			$zdomain, $rdomain, $type, $pref, $data,
			$pointer-$ROWS_PER_PAGE, "prev",
			"<< Prev $ROWS_PER_PAGE");
		$result .= "</TD>";
	}
	if (($count >= 50) && (($pointer + $ROWS_PER_PAGE) <= $count)) {
		$result .= "<TD>";
		$result .= sprintf($button_form,
			$zdomain, $rdomain, $type, $pref, $data,
			$pointer+$ROWS_PER_PAGE, "next",
			"Next $ROWS_PER_PAGE >>");
		$result .= "</TD>";
	}
	$result .= "<TD>";
	$result .= sprintf($button_form,
		$zdomain, $rdomain, $type, $pref, $data,
		($count - ($count % $ROWS_PER_PAGE)), "last",
		"Last >>>>");
	$result .= "</TD>";
	$result .= "</TR></TABLE>\n";
	$result .= "<TABLE><TR><TH align=left>Host</TH><TH align=left>Domain</TH><TH align=left>Type</TH><TH align=left>Pref</TH><TH align=left>Data</TH></TR>\n";
	while ($row = mysql_fetch_array($rid)) {
		$result .= "<TR><TD>".$row['rdom']."</TD>";
		$result .= "<TD><A HREF=\"brzones.php?frame=records&zone=";
		$result .= $row['zid']."&rrid=".$row['rrid']."\">".$row['zdom']."</A></TD>";
		$result .= "<TD>".$row['type']."</TD>";
		$result .= "<TD>".$row['pref']."</TD>";
		$result .= "<TD>".$row['data']."</TD></TR>\n";
	}
	$result .= "</TABLE>\n";
	mysql_free_result($rid);
	return $result;
}

#
# MAIN
#

get_input();

$selectval = isset($INPUT_VARS['select'])? $INPUT_VARS['select'] : ''; 
if (!$selectval) {
	$selectval='result';
	$INPUT_VARS['type']='*';
	$INPUT_VARS['zdomain']='%';
	$INPUT_VARS['rdomain']='%';
	$INPUT_VARS['pref']='';
	$INPUT_VARS['data']='';
	$INPUT_VARS['pointer']='0';
}

switch($selectval) {
case 'result':
	print query_form($INPUT_VARS);
	print result_form($INPUT_VARS);
	break;
case 'query':
	print query_form($INPUT_VARS);
	break;
default:
	if (isset($INPUT_VARS['frame']) && $INPUT_VARS['frame'] == 'brrec')
		print $html_top . query_form(array());
	else
		print $start_frame;
}
print $html_close;
?>
