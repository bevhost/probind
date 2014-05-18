<?php
require('inc/lib.inc');
require 'header.php';
?>
<TABLE width="99%">
<TR>
 <TD align="left"><H1>DNS server descriptions</H1>
</TR>
</TABLE>
<HR><P>
<?php
$templ_form="";
$template_list = "";

$ht = opendir($TEMPL_DIR);
if ($ht) {
    while ( $entry = readdir($ht) ) {
        if ( $entry == "CVS" || $entry == "." || $entry == ".." || !is_dir("$TEMPL_DIR/$entry") || !is_file("$TEMPL_DIR/$entry/named.tmpl") )
	    continue;
	$template_list[] = $entry;
    };
    closedir($ht);
    $templ_form = mk_select_a("template", $template_list, $DEFAULT_TMPL);
}
else
    $templ_form = "<FONT COLOR=\"red\">Templates not found in '$TEMPL_DIR'</FONT>\n";


$ht = opendir("$TOP/sbin/");
$script_LIST = "";
while ( $entry = readdir($ht)) {
    if (is_dir($entry) || !is_executable("$TOP/sbin/$entry"))
        continue;
    $script_list[] = $entry;
};
closedir($ht);

$script_form = mk_select_a("script", $script_list, $DEFAULT_PUSH);
    
$preamble = '
NB: When you change these settings, the change is only reflected in
domains which are pushed onto the DNS servers after the update. Some changes (such as
changing primary server to the secondary) need bulk update function to be applied.
It is a good practice to clean all generated files in the HOSTS/server_name directory,
check <B>Update host from template</B> box, and
run a <B>Bulk Update</B> function after the significant changes.
<BR>
';

$add_form = '
<FORM action="servers.php" method="post">
<INPUT type="hidden" name="action" value="Add">
<TABLE>
<TR align=left>
 <TH colspan=2>Name</TH>
 <TH>IP number</TH>
</TR><TR align=left>
 <TD colspan=2><INPUT type="text" name="name" size=24 maxlength=64></TD>
 <TD><INPUT type="text" name="ipno" size=15 maxlength=15></TD>
</TR><TR align=left>
 <TH>Type</TH>
 <TH>Updates</TH>
 <TH>NS records</TH>
</TR><TR align=left>
 <TD><SELECT name="type">
  <OPTION>Master</OPTION>
  <OPTION SELECTED>Slave</OPTION>
  <OPTION>Native (PDNS)</OPTION>
  <OPTION>DnsMasq</OPTION>
  </SELECT></TD>
 <TD><SELECT name="push"><OPTION SELECTED>Skip</OPTION>
  <OPTION>Update</OPTION></SELECT></TD>
 <TD><SELECT name="mkrec"><OPTION SELECTED>Skip</OPTION>
  <OPTION>NS record</OPTION></SELECT></TD>
</TR><TR align=left>
 <TH colspan=3>Directory on the server containing the zone files</TH>
</TR><TR align=left>
 <TD colspan=3><INPUT type="text" name="zonedir" SIZE=70 MAXLENGTH=255 value="'."$DEFAULT_DIR".'"></TD>
</TR><TR align=left>
 <TH colspan=3>Chroot Base Directory: Prepended to the zone directory for update location<br>(Leave blank if you do not use chroot)</TH>
</TR><TR align=left>
 <TD colspan=3><INPUT type="text" name="chrootbase" SIZE=70 MAXLENGTH=255 value=""></TD>
</TR><TR align=left>
 <TH colspan=3>Template directory (in '."$TEMPL_DIR".')</TH>
</TR><TR align=left>
 <TD colspan=3>'."$templ_form".'</TD>
</TR><TR align=left>
 <TH colspan=3>Script used to push data to the server</TH>
</TR><TR align=left>
 <TD colspan=3>'."$script_form".'</TD>
</TR><TR align=left>
</TR><TR align=left>
 <TH>Description</TH>
</TR><TR>
 <TD colspan=3><TEXTAREA name="description" COLS=70 ROWS=12></TEXTAREA></TD>
</TR><TR>
 <TD><INPUT type="reset"></TD>
 <TD></TD>
 <TD><INPUT type="submit" value="Add this DNS server"></TD>
</TR></TABLE>
</FORM>
';


$update_form = '
<FORM action="servers.php" method="post">
<INPUT type="hidden" name="action" value="Update">
<INPUT type="hidden" name="server" value="%d">
<TABLE>
<TR align=left>
 <TH colspan=2>Name</TH>
 <TH>IP number</TH>
</TR><TR align=left>
 <TD colspan=2><INPUT type="hidden" name="name" value="%s">%s</TD>
 <TD><INPUT type="text" name="ipno" value="%s" size=15 maxlength=15></TD>
</TR><TR align=left>
 <TH>Type</TH>
 <TH>Update</TH>
 <TH>NS record</TH>
</TR><TR align=left>
 <TD>%s</TD>
 <TD>%s</TD>
 <TD>%s</TD>
</TR><TR align=left>
 <TH colspan=3>Directory on the server containing the zone files</TH>
</TR><TR align=left>
 <TD colspan=3><INPUT type="text" name="zonedir" value="%s" SIZE=70 MAXLENGTH=255></TD>
</TR><TR align=left>
 <TH colspan=3>Chroot Base Directory: Prepended to the zone directory for update location<br>(Leave blank if you do not use chroot)</TH>
</TR><TR align=left>
 <TD colspan=3><INPUT type="text" name="chrootbase" SIZE=70 MAXLENGTH=255 value="%s"></TD>
</TR><TR align=left>
 <TH colspan=1>Template directory</TH>
 <TD colspan=1>%s</TD>
 <TD colspan=1>Update host from template? <INPUT type="checkbox" name="updatet" value=1></TD>
</TR><TR align=left>
 <TH colspan=1>Script used to push data to the server</TH>
 <TD colspan=1>%s</TD> 
</TR><TR align=left>
 <TH colspan=3>Description</TH>
 
</TR><TR>
 <TD colspan=3><TEXTAREA name="description" COLS=80 ROWS=4>%s</TEXTAREA></TD>
</TR><TR>
<TH>Options (<b>No syntax check here!</b>)</TH>
</TR><TR>
 <TD colspan=3><TEXTAREA name="options" COLS=80 ROWS=7>%s</TEXTAREA></TD>
</TR><TR>
 <TD><INPUT type="reset"></TD>
 <TD><INPUT type="submit" name="subaction" value="Delete"></TD>
 <TD><INPUT type="submit" name="subaction" value="Update"></TD>
</TR></TABLE>
</FORM>
';

$confirm_delete_form = '
<FORM action="servers.php" method="post">
<INPUT type="hidden" name="server" value="%d">
<INPUT type="hidden" name="name" value="%s">
<INPUT type="hidden" name="action" value="delete">
<INPUT type="hidden" name="subaction" value="realdelete">
Do you really want to delete this DNS server from the database?<P>
<B>%s</B><P>
<INPUT type="submit" value="Really Delete">
</FORM>
';


function valid_path($path)
{
	$bare = rtrim(ltrim($path));
	return strlen($bare) && ereg("^/", $bare);
}

function valid_server($name, $ipno, $type, $push, $zonedir, $chrootbase, $template, $script)
{
	global $TOP;
	global $TEMPL_DIR;
	$name = trim($name);
	$warns = "";
	if (!strlen($name))
		$warns .= "You must specify a hostname<BR>\n";
	if (!preg_match("/^[-\w._]+$/", $name) || preg_match('/\.\./',$name))
		$warns = "Invalid server name '$name'";
	if (!valid_ip($ipno))
		$warns .= "You must specify a valid IP number<BR>\n";
	if ($type != 'M' && $type != 'S' && $type != 'N' && $type != 'D')
		$warns .= "The server type must be 'M','S','N' or 'D'<BR\n";
	if (strlen($zonedir) && !valid_path($zonedir))
		$warns .= "The zone directory is not a valid path<BR>\n";
	if (strlen($chrootbase) && !valid_path($chrootbase))
		$warns .= "The chroot base directory is not a valid path<BR>\n";
		if (strlen($template) && !is_dir("$TEMPL_DIR/$template"))
		$warns .= "'$TEMPL_DIR/$template' does not exist<BR>\n";
	if (strlen($script) && !preg_match("/^[-\w._]+$/", $script))
		$warns .= "The script is not a valid filename<BR>\n";
	if (strlen($script) && !file_exists("$TOP/sbin/$script"))
		$warns .= "'$TOP/sbin/$script' does not exist<BR>\n";
	if ($push && (!valid_path($zonedir) || !$template || !$script))
		$warns .= "You must specify a zone directory, a template file and a 'push' script for an updateable server<BR>\n";
	return $warns;
}

# Return a string of error messages if the input is unsuitable to
# go into the database, otherwise insert it and return 0.
function add_servers($input)
{
	$db = new DB_probind;
	global $HOST_DIR;
	global $TEMPL_DIR;
	$res = '';
	$name = chop(ltrim($input['name']));
	$ipno = $input['ipno'];
	$type = substr($input['type'],0,1); # == 'Master' ? 'M' : 'S');
	$push = ($input['push'] == 'Skip' ? 0 : 1);
	$mkrec = ($input['mkrec'] == 'Skip' ? 0 : 1);
	$zonedir = $input['zonedir'];
	$chrootbase = $input['chrootbase'];
	$template = $input['template'];
	$script = $input['script'];
	$descr = $input['description'];
	$warnings = valid_server($name, $ipno, $type, $push, $zonedir, $chrootbase, $template, $script);
	if (strlen($warnings)) 
		return $warnings;
	$db->prepare("SELECT * FROM servers WHERE hostname = ?");
	$db->execute($name);
	$count = $db->num_rows();
	if ($count)
		return "$name already exists in the database.<BR>\n";
	$query = "INSERT INTO servers SET "; 		$param = array();
	$query .= "hostname = :hostname, ";		$param[":hostname"]=$name;
	$query .= "ipno = :ipno, ";			$param[":ipno"]=$ipno;
	$query .= "type = :type, ";			$param[":type"]=$type;
	$query .= "pushupdates = :pushupdates, ";	$param[":pushupdates"]=$push;
	$query .= "mknsrec = :mknsrec, ";		$param[":mknsrec"]=$mkrec;
	$query .= "zonedir = :zonedir, ";		$param[":zonedir"]=$zonedir;
	$query .= "chrootbase = :chrootbase, ";		$param[":chrootbase"]=$chrootbase;
	$query .= "template = :template, ";		$param[":template"]=$template;
	$query .= "script = :script, ";			$param[":script"]=$script;
	$query .= "descr = :descr, ";			$param[":descr"]=$descr;
	$query .= "state = 'OUT'";
	$db->prepare($query);
	$db->execute($param);

	if (is_file("$TEMPL_DIR/$template/named.tmpl")) {
	    // Create directory for the host (use script if it exists)
	    passthru("mkdir -p $HOST_DIR/$name/SEC 2>& 1",$res);
	    if (!$res)
	        passthru("cp $TEMPL_DIR/$template/*.* $HOST_DIR/$name/. 2>& 1", $res);
		
	    if ($res)
	        return "<br>Error when creating directory $HOST_DIR/$name/.<br>";

	    if ( is_executable("$HOST_DIR/add_script"))
	        passthru("$HOST_DIR/add_script $name 2>& 1",  $res);

	    if ($res)
	        return "<br>Error running script $HOST_DIR/add_script<br>";

	    $db->query("UPDATE zones SET updated = 1 WHERE NOT master AND domain != 'TEMPLATE'");
	    $db->query("UPDATE servers SET state = 'OUT' WHERE type = 'M' OR type = 'S'");

	return 0;
	}
}

# Return a HTML form with the current makeup of the server
function mk_update_form($server)
{
	if (!ctype_digit("$server")) die('Invalid server id.');

	global $update_form, $template_list, $script_list;
	$db = new DB_probind;
	$query = "SELECT id, hostname as name, ipno, type, pushupdates as push, mknsrec as mkrec, 
		zonedir, chrootbase, template, script, descr, options FROM servers WHERE id = $server";
	$db->query($query);
	if ($db->next_record()) {
		extract($db->Record);
		$type = (int)($type == 'S');
		$result = sprintf($update_form,
			$id, $name, $name, $ipno, 
			mk_select("type", array("Master", "Slave"), $type), 
			mk_select("push", array("Skip", "Update"), $push), 
			mk_select("mkrec", array("Skip", "NS record"), $mkrec),
			$zonedir, $chrootbase,
			mk_select_a("template", $template_list, $template),
			mk_select_a("script", $script_list, $script),
			$descr, $options);
	} else {
		$result = "No such server in the database: $server.<P>\n";
	}
	return $result;
}

function browse_servers()
{
	global $SERVER_TYPES;
	$db = new DB_probind;
	$query = "SELECT id, hostname, ipno, type, pushupdates, mknsrec FROM servers ORDER BY hostname";
	$db->query($query);
	$result = "<FORM action=\"servers.php\" method=\"post\">
<INPUT type=\"hidden\" name=\"action\" value=\"addform\">
<TABLE><TR align=left>
 <TH>Name</TH>
 <TH>IP number</TH>
 <TH>Type</TH>
 <TH>Update</TH>
 <TH>NS record</TH>
</TR>\n";
	while ($db->next_record()) {
		$server = $db->Record;
		$id = $server['id'];
		$name = $server['hostname'];
		$ipno = $server['ipno'];
		$type = $SERVER_TYPES[$server['type']];
		$push = ($server['pushupdates'] ? "Yes" : "No");
		$mkrec = ($server['mknsrec'] ? "Yes" : "No");
		$result .= "<TR>
 <TD><A HREF=\"servers.php?action=detailedview&server=$id\">$name</A></TD>
 <TD>$ipno</TD>
 <TD>$type</TD>
 <TD>$push</TD>
 <TD>$mkrec</TD>
</TR>\n";
	}
	$result .= "<TR><TD><INPUT type=\"submit\" value=\"Add another server\"></TD></TR>\n";
	$result .= "</TABLE>\n</FORM>\n";
	return $result;
}

function update_servers($input)
{
	global $confirm_delete_form;
	global $HOST_DIR;
	global $TEMPL_DIR;

	$db = new DB_probind;

	if (isset($input['server'])) {$id = $input['server'];}
	if (isset($input['name'])) {$name = $input['name'];}
	if (isset($input['ipno'])) {$ipno = $input['ipno'];}
	if (isset($input['type'])) {$type = ($input['type'] == 'Master' ? 'M' : 'S');}
	if (isset($input['push'])) {$push = ($input['push'] == 'Skip' ? 0 : 1);}
	if (isset($input['mkrec'])) {$mkrec = ($input['mkrec'] == 'Skip' ? 0 : 1);}
	if (isset($input['zonedir'])) {$zonedir = $input['zonedir'];}
	if (isset($input['chrootbase'])) {$chrootbase = $input['chrootbase'];}
	if (isset($input['template'])) {$template = $input['template'];}
	if (isset($input['script'])) {$script = $input['script'];}
	if (isset($input['description'])) {$descr = $input['description'];}
	if (isset($input['updatet'])) { $updatet=$input['updatet']; }
	if (isset($input['options'])) {$options = strtr( $input['options'], "'", '"');}
	
	if (!isset($input['subaction'])) {return "INTERNAL ERROR<P>\n";}

	switch (strtolower($input['subaction'])) {
	case 'delete':
		if (!ctype_digit("$id")) die('Invalid server id.');
		return sprintf($confirm_delete_form, $id, $name, $name);
		break;
	case 'realdelete':
		if (!ctype_digit("$id")) die('Invalid server id.');
		$query = "DELETE FROM servers WHERE id = $id";
		$db->query($query);
		if (is_file("$HOST_DIR/$name/named.tmpl")) {
		    if(is_executable("$HOST_DIR/delete_script"))
		        passthru("$HOST_DIR/delete_script $name 2>& 1");
		    passthru("rm -rf $HOST_DIR/$name 2>& 1");
		}
		
		return "Deleted the '$name' server.<P>\n";
	case 'update':
		$warns = valid_server($name, $ipno, $type, $push, $zonedir, $chrootbase, $template, $script);
		if (strlen($warns)) return $warns;
		$query = "UPDATE servers SET "; 		$param = array();
		$query .= "hostname = :hostname, ";		$param[":hostname"]=$name;
		$query .= "ipno = :ipno, ";			$param[":ipno"]=$ipno;
		$query .= "type = :type, ";			$param[":type"]=$type;
		$query .= "pushupdates = :pushupdates, ";	$param[":pushupdates"]=$push;
		$query .= "mknsrec = :mknsrec, ";		$param[":mknsrec"]=$mkrec;
		$query .= "zonedir = :zonedir, ";		$param[":zonedir"]=$zonedir;
		$query .= "chrootbase = :chrootbase, ";		$param[":chrootbase"]=$chrootbase;
		$query .= "template = :template, ";		$param[":template"]=$template;
		$query .= "script = :script, ";			$param[":script"]=$script;
		$query .= "descr = :descr, ";			$param[":descr"]=$descr;
		$query .= "options = :options, ";		$param[":options"]=$options;
		$query .= "state = 'OUT' WHERE id = :id";	$param[":id"]=$id;
		$db->prepare($query);
		$db->execute($param);
		$count = $db->affected_rows();
		if (isset($updatet) && is_file("$TEMPL_DIR/$template/named.tmpl")) {
		    passthru("mkdir -p $HOST_DIR/$name/SEC");
		    passthru("mv -f $HOST_DIR/$name/named.tmpl $HOST_DIR/$name/named.tmpl-old 2>& 1");
		    passthru("cp $TEMPL_DIR/$template/*.* $HOST_DIR/$name/. 2>& 1");
		    print "<H3><FONT color=RED>Directory has been updated from new template</FONT></H3>";
		}
		break;
	default:
		return "INTERNAL ERROR<P>\n";
	}
	return mk_update_form($input['server']);
}

#
# MAIN
#
get_input();
switch (strtolower($INPUT_VARS['action'])) {
case 'browse':
	print browse_servers();
	print "<HR>$preamble<HR>\n";
	break;
case 'detailedview':
	print mk_update_form($INPUT_VARS['server']);
	break;
case 'add':
	if ($warns = add_servers($INPUT_VARS)) 
		print $warns;
	else
		print $add_form;
	break;
case 'addform':
	print $add_form;
	break;
case 'update':
case 'delete':
	print update_servers($INPUT_VARS);
	break;
default:
	while ($var = each($INPUT_VARS)) {
		print "$var[0] = $var[1]<BR>\n";
	}
	print "action = '".$INPUT_VARS['action']."' => default<P>\n";
}

?>

</BODY>
</HTML>
