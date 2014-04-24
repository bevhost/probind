<?php

if (php_sapi_name() == 'cli' && isset($_SERVER["argc"])) {
	include(dirname(__FILE__)."/../phplib/prepend.php");  		# Command line
	include("config.php");
} else {
	require_once("checkperm.inc");					# web interface
	require_once("idna_convert.class.php");
}

$db = new DB_probind;
$MYSQL_HOST = $db->Host;
$MYSQL_DB = $db->Database;
$MYSQL_USER = $db->User;
$MYSQL_PASSWD = $db->Password;

$BIN = "$TOP/bin";
$SBIN = "$TOP/sbin";
if (empty($TMP))
	$TMP = "$TOP/tmp";
$DOMAIN_RE = '^(\*|[_a-z0-9]+([_a-z0-9-]+[_a-z0-9]+)*)(\.[_a-z0-9]+([_a-z0-9-]+[_a-z0-9]+)*)*\.?$';
$DOMAIN_RE_JS = '^([_a-z0-9]+\.?)+$';
$DOMAIN_RE_NODOT = "^[_a-z0-9]+([_a-z0-9-]+[_a-z0-9]+)*(\.[_a-z0-9]+([_a-z0-9-]+[_a-z0-9]+)*)*$";
$IPNO_RE = "/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/";
$IPV4_RE_JS = '^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$';
// From http://forums.dartware.com/viewtopic.php?t=452
$IPV6_RE_JS = '^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$';
$IPV6_RE = "/$IPV6_RE_JS/";

$MINSECS = 60;
$HOURSECS = 60*$MINSECS;
$DAYSECS = 24*$HOURSECS;
$WEEKSECS = 7*$DAYSECS;

# Maximum time between user / password requests if no activity take place
$SESSION_TIMEOUT = 60;
# We use this error message if a domain lock cannot be acquired
$domain_lock_msg = "
<B>
%s is already running a domain operation. The database is
locked until that process completes.
</B>
<P>
If this condition persists, or you are otherwise convinced that
an error has occurred, then you can clear the lock condition
on the settings menu.
";

# Just a simple wrapper around mysql_query
function sql_query($query)
{
	($rid = mysql_query($query))
		or die("'$query' failed with error:<p>" . mysql_error()."</p>");
	return $rid;
}

if (!function_exists("inet_pton")) {
  function inet_pton($ip) {
    # ipv4
    if (strpos($ip, '.') !== FALSE) {
        $ip = pack('N',ip2long($ip));
    }
    # ipv6
    elseif (strpos($ip, ':') !== FALSE) {
        $ip = explode(':', $ip);
        $res = str_pad('', (4*(8-count($ip))), '0000', STR_PAD_LEFT);
        foreach ($ip as $seg) {
            $res .= str_pad($seg, 4, '0', STR_PAD_LEFT);
        }
        $ip = pack('H'.strlen($res), $res);
    }
    return $ip;
  }
}

function make_ipv6_ptr($ip) {
	$addr = inet_pton($ip);
	$unpack = unpack('H*hex', $addr);
	$hex = $unpack['hex'];
	return implode('.', array_reverse(str_split($hex))) . '.ip6.arpa';
}

# determine the html for setting the owner of a zone
function owner($o="")
{
        global $perm, $auth;
        $count=0;
        if($perm->have_perm("owner")) {
                $owner = $auth->auth["uname"];
                $html = "<input type=hidden name=owner value='$owner'>$owner\n";
                $count=1;
        } else {
                $html = "<select name=owner>\n";
                foreach ($perm->permissions as $k => $v) {
                        if (($perm->have_perm("admin")) or ($perm->have_perm($k))) {
                                if ($o==$k) $sel="selected"; else $sel="";
                                $html .= "  <option $sel value='$k'>$k\n";
                                $count++;
                                $last = $k;
                        }
                }
                $html .= "</select>\n";
                if ($count==1) $html="<input type=hidden name=owner value='$last'>$last\n";
        }
        return $html;
}

#determine sql query addon to limit zones returned to owner or group
function access($a="")
{
        global $perm, $auth;
        if ($perm->have_perm("admin")) return "";
	$query = "";
        if ($perm->have_perm("owner")) $query .= " AND owner='".$auth->auth["uname"]."'";
        else {
                $conj = "AND (";
                foreach ($perm->permissions as $k => $v) {
                        if (($perm->have_perm($k)) or ($perm->have_perm("admin"))) {
                                if($a=="" or $a==$k) {
                                        $query .= " $conj owner='$k'";
                                        $conj = "OR";
                                }
                        }
                }
                if ($query) $query .= ")";
		else {
			$query=" AND 1=0";  /* user does not belong to any groups */
			echo "<b>Permission Error</b><br>You do not have a valid group.";
		}
        }
        return $query;
}


# Convert a number of seconds (as stored in the database) into
# the Weeks/Days/Hours/Minutes/Seconds notation which is so much
# easier for humans to read, and is supported by BIND8
function seconds_to_ttl($seconds)
{
	$ttl = '';

	global $MINSECS, $HOURSECS, $DAYSECS, $WEEKSECS;

	$t = floor($seconds / $WEEKSECS);
	$seconds -= $t * $WEEKSECS;
	if ($t)
		$ttl = $t."W";
	$t = floor($seconds / $DAYSECS);
	$seconds -= $t * $DAYSECS;
	if ($t)
		$ttl .= $t."D";
	$t = floor($seconds / $HOURSECS);
	$seconds -= $t * $HOURSECS;
	if ($t)
		$ttl .= $t."H";
	$t = floor($seconds / $MINSECS);
	$seconds -= $t * $MINSECS;
	if ($t)
		$ttl .= $t."M";
	if ($seconds)
		$ttl .= $seconds."S";
	return $ttl;
}

# The reverse of the above function
function ttl_to_seconds($ttl)
{
	global $MINSECS, $HOURSECS, $DAYSECS, $WEEKSECS;
        if (eregi("[wdhms]", $ttl)) {
                eregi("([0-9]+w)?([0-9]+d)?([0-9]+h)?([0-9]+m)?([0-9]+s)?", $ttl, $fields);
                return $fields[1]*$WEEKSECS + $fields[2]*$DAYSECS + $fields[3]*$HOURSECS + $fields[4]*$MINSECS + $fields[5];
        }
	if (!ctype_digit("$ttl")) $ttl = "14400";
        return $ttl;
}

#
# This function, and leave_crit() below, implements a form of
# cooperative locking. Hard table locks are held only for a brief
# moment, while the lock notices are placed on the blackboard.
# Critical sections exist during these operations : update,
# add domain and delete domain.

# Acquire a lock on the critical section $lockname
# Return 0 for success, or a string containing the name of
# the user who currently holds the lock
#
function enter_crit($user, $lockname)
{
	global $auth;
	if (empty($user)) $user = $auth->auth["uname"];
	$db = new DB_probind;
	$db->query("LOCK TABLES blackboard WRITE");
	$db->prepare("SELECT name, value FROM blackboard WHERE name = ?");
	$db->execute("LOCK_$lockname");
	if ($db->next_record()) {
		$value = $db->Record["value"];
		$db->query("UNLOCK TABLES");
		if ($value)
			return $value;
		return "<unknown user>";
	}
	$db->prepare("INSERT INTO blackboard (name, value) VALUES (?, ?)");
	$data = array("LOCK_$lockname",$user); 
	$db->execute($data);
	$db->query("UNLOCK TABLES");
	return 0;
}

# Try to get a lock, for up to 5 seconds before giving up. Return a
# string with the username holding the lock.
function patient_enter_crit($user, $lockname)
{
	$max_tries = 6;
	for ($i=0; $i<$max_tries; $i++) {
		$user = enter_crit($user, $lockname);
		if (!$user)
			return 0;
		if ($i == ($max_tries-1))
			return($user);
		sleep(1);
	}
}

# Release read/write locks acquired above
#
function leave_crit($lockname)
{
	$db = new DB_probind;
	$db->query("LOCK TABLES blackboard WRITE");
	$db->prepare("DELETE FROM blackboard WHERE name = ?");
	$db->execute("LOCK_$lockname");
	$db->query("UNLOCK TABLES");
}

# Return a HTML formatted list of currently held locks.
function list_locks()
{
	$db = new DB_probind;
	$db->query("SELECT name, value as lock_holder, ctime FROM blackboard WHERE name LIKE 'LOCK_%'");
	$result = "";
	while ($db->next_record()) {
		extract($db->Record);
		$lockname = substr($name, 1+strpos($name, "_"), strlen($name));
		$result .= "<P><HR><P>
<TABLE>
<TR><TD width=\"50%\">
The '<B>$lockname</B>' lock was acquired by <B>$lock_holder</B> at $ctime and
is still held. If you are very sure about it, you can break this lock by
clicking on this button:<P>
</TD><TD valign=\"top\">
<FORM action=\"settings.php\" method=\"post\">
<INPUT type=\"hidden\" name=\"action\" value=\"breaklock\">
<INPUT type=\"hidden\" name=\"lockname\" value=\"$lockname\">
<INPUT type=\"submit\" value=\"Break\">
</TD></TR>
</TABLE>
</FORM>
";
	}
	return $result;
}

# return true iff the $domain string is the name of a domain in the
# database
function known_domain($domain)
{
	$db = new DB_probind;
	$db->prepare("SELECT id FROM zones WHERE domain = ? ".access());
	$db->execute($domain);
	$id = 0;
	if ($db->next_record())
		$id = $db->Record["id"];
	return $id;
}

# Return the known information about a zone ID
function get_zone($id)
{
	$db = new DB_probind;
	$db->prepare("SELECT * FROM zones WHERE id = ? ".access());
	$db->execute($id);
	$db->next_record(PDO::FETCH_ASSOC);
	return $db->Record;
}

# Return the known information about a zone name
function get_named_zone($name)
{
	$db = new DB_probind;
	$db->prepare("SELECT * FROM zones WHERE domain = ? ".access());
	$db->execute($name);
	$db->next_record(PDO::FETCH_ASSOC);
	return $db->Record;
}

# return true if the $domain string is at least syntactically
# correct as a domain name, and does not already appear in the database
function valid_domain($domain)
{
	global $DOMAIN_RE_NODOT;
	if (known_domain($domain))
		return 0;
	return ereg($DOMAIN_RE_NODOT, $domain);
}

# return true iff the $ip string is an IP number
function valid_ip($ip)
{
	$bytes = explode('.', $ip);
	if (count($bytes) != 4)
		return 0;
	for ($i=0; $i<4; $i++) {
		if (strspn($bytes[$i], "0123456789") != strlen($bytes[$i]))
			return 0;
		if ($bytes[$i] > 255)
			return 0;
	}
	return sprintf("%d.%d.%d.%d",
		$bytes[0], $bytes[1], $bytes[2], $bytes[3]);
}

# Add a new domain to the database, and initialize it with the contents
# of the TEMPLATE domain. Return the ID of the new domain.
# NB: The 'master' parameter contains the IP number of the DNS master
# server for the new (slave) domain, i.e., it is the empty string if
# the domain is a master domain.
function add_domain($domain, $master, $eoptions = "", $owner)
{
	global $domain_lock_msg, $REMOTE_USER, $SLAVE_ON_SLAVES;
	$db = new DB_probind;
	# Find the template data
	$db->query("SELECT id as templateid, serial, refresh, retry, expire, options FROM zones WHERE domain = 'TEMPLATE'");
	if ($db->next_record(PDO::FETCH_ASSOC)) extract($db->Record);
		else die("TEMPLATE zone not found in database<P>\n");
	if (!$master) {
		$zonefile = "$domain.dns";
		if ($eoptions)
			$options = $eoptions; // If options are specified, set up them instead of template options
	} else {
		$zonefile = "SEC/$domain.dns";
		$options = ""; // No options from TEMPLATE for the secondary zones
	}
	# Make sure that no one else is working on the domain list while
	# we do this.
	if ($user = patient_enter_crit($REMOTE_USER, 'DOMAIN'))
		die (sprintf($domain_lock_msg, ucfirst($user)));
	# Add the domain to the zones table
	$db->prepare("INSERT INTO zones SET domain=:domain, serial=:serial, refresh=:refresh, retry=:retry, expire=:expire, 
			   master=:master, updated=1, zonefile=:zonefile, options=:options, owner=:owner, ctime=NOW(), mtime=NOW()");
	$db->execute(array(":domain"=>$domain,":serial"=>$serial,":refresh"=>$refresh,":retry"=>$retry,":expire"=>$expire,
			   ":master"=>$master,":zonefile"=>$zonefile,":options"=>$options,":owner"=>$owner));
	$newzone = $db->lastInsertId();
	# Just in case a domain of the same name was recently deleted...
	$db->prepare("DELETE FROM deleted_domains WHERE domain = ?");
	$db->execute($domain);


	if ($master) {
		if ($SLAVE_ON_SLAVES)
		    $db->query("UPDATE servers SET state = 'OUT'");
		else
		    $db->query("UPDATE servers SET state = 'OUT' WHERE type = 'M'");
		leave_crit('DOMAIN');
		return $newzone;
	}

/*
	$rid = sql_query("SELECT domain, ttl, type, pref, data FROM records WHERE zone = $templateid");
	while (list($domain, $ttl, $type, $pref, $data) = mysql_fetch_row($rid)){
		sql_query("INSERT INTO records (domain, zone, ttl, type, pref, data) VALUES ('$domain', $newzone, $ttl, '$type', '$pref', '$data')");
	}
	sql_query("UPDATE records SET ctime = mtime WHERE zone = $newzone");
*/
	$db->query("INSERT INTO records (domain, zone, ttl, type, pref, data, port, weight, comment, genptr, ctime, mtime) ".
		   "SELECT domain, $newzone, ttl, type, pref, data, port, weight, '', 1, NOW(), NOW() FROM records WHERE zone=$templateid");

	if ($master && !$SLAVE_ON_SLAVES)
		$db->query("UPDATE servers SET state = 'OUT' WHERE type = 'M'");
	else
	    $db->query("UPDATE servers SET state = 'OUT' WHERE type = 'M' OR type = 'S'");
	leave_crit('DOMAIN');
	return $newzone;
}

# Clean up after a domain, and delete it. NB The table deleted_domains
# contains a list of domains which have been deleted from the database,
# in order to make it possible for the update process to remove them
# from the BIND servers. Return the number of resource records deleted
# along with the domain.
function del_zone($zoneid)
{
	global $REMOTE_USER, $domain_lock_msg;
	global $SLAVE_ON_SLAVES;

	# Make sure $zoneid is only numbers.
	if (!ctype_digit("$zoneid")) die('Invalid zoneid.'); 

	$db = new DB_probind;
	# Make sure that no one else is working on the domain list while
	# we do this.
	if ($user = patient_enter_crit($REMOTE_USER, 'DOMAIN'))
		die (sprintf($domain_lock_msg, ucfirst($user)));
	# Make sure we update any arpa domains which contain synthetic
	# PTR records for any A records about to vanish.
	$db->query("SELECT data FROM records WHERE zone = $zoneid AND type = 'A'");
	while ($db->next_record()) {
		affect_reverses($db->Record["data"]);
	}
	# Add this domain to deleted_domains
	$db->query("SELECT domain, zonefile, master FROM zones WHERE id = $zoneid ".access());
	if (!$db->next_record()) {
		leave_crit('DOMAIN');
		echo "Domain Not Found";
		return false;
	}
	extract($db->Record); 
	$db->prepare("DELETE FROM deleted_domains WHERE domain = ?");
	$db->execute($domain);
	$db->query("INSERT INTO deleted_domains SELECT domain, zonefile, owner FROM zones WHERE id = $zoneid");
	#?bevhost says.. why did we backup the domain name but not the records?
	$db->query("SHOW TABLES LIKE 'deleted_records'");
	if ($db->next_record()) {
		$db->query("INSERT INTO deleted_records SELECT * FROM records WHERE zone=$zoneid");
	} else {
		$db->query("CREATE TABLE IF NOT EXISTS deleted_records");
		$db->query("SELECT * FROM records WHERE zone=$zoneid");
	}
	# Finally, zap records, annotations and the zone itself.
	$db->query("DELETE FROM records WHERE zone = $zoneid");
	$rrcount = $db->num_rows();
	$db->query("DELETE FROM annotations WHERE zone = $zoneid");
	$db->query("DELETE FROM zones WHERE id = $zoneid");
	if ($master && !$SLAVE_ON_SLAVES)
		$db->query("UPDATE servers SET state = 'OUT' WHERE type = 'M'");
	else
	    $db->query("UPDATE servers SET state = 'OUT' WHERE type = 'M' OR type = 'S'");
	leave_crit('DOMAIN');
	return $rrcount;
}

# Return a formatted list of domains which match the $lookfor string
# $type further restricts the search to all domains (*), master domains (M),
# Slave domains (S) or domains where the annotations contains the $lookfor
# string (A). The $format string should be of the form: "..%d...%s..%s..",
# where the %d will be filled with the numerical zone id (suitable for
# a HREF), the first %s with the domain name, and the last %s with either
# "(slave)" or an empty string.
function domain_list($lookfor, $type, $format)
{
	$db = new DB_probind;
	$result = "";
	$query = "SELECT id, domain, master, disabled FROM zones";
	switch ($type) {
	case '*':
		$query .= " WHERE domain LIKE ?";
		break;
	case 'M':
		$query .= " WHERE domain LIKE ? AND NOT master";
		break;
	case 'S':
		$query .= " WHERE domain LIKE ? AND master";
		break;
	case 'A':
		$query .= ", annotations WHERE id = zone AND descr LIKE ?";
		break;
	}
	$query .= access();
	$query .= " ORDER BY domain";
	$db->prepare($query);
	$db->execute($lookfor);
	$typestr = "";
	$result1 = "";
	while ($db->next_record()) {
		extract($db->Record);
		if ($disabled)
			$d = "<IMAGE border=0 width=\"8\" height=\"8\" SRC=\"images/noway.gif\">";
		else
		        $d = "";
		if ($type == "*")
			$typestr = ($master ? " (slave)" : "");
		if ($int_dom = display_if_international($domain)) $domain = $int_dom;
		if (preg_match("/\.in-addr\.arpa(\.)?$/", $domain))
			$result1 .= sprintf($format, $id, $d.$domain , $typestr);
		else
			$result .= sprintf($format, $id, $d.$domain, $typestr);
	}
	$result .= $result1;
	return $result;
}

# Set the 'updated' tag on a domain, ensuring that the next update will
# push it to the BIND servers. The parameter is the record id for a
# resource record in the domain to be updated.
function tag_zone_updated($recordid)
{
#	$query = "SELECT zone FROM records WHERE id = $recordid";
#	$rid = sql_query($query);
#	$zone = mysql_result($rid, 0);
#	mysql_free_result($rid);
#	sql_query("UPDATE zones SET updated = 1 WHERE id = $zone");
	if (!ctype_digit("$recordid")) die('Invalid recordid.'); 
	$db = new DB_probind;
	$db->query("UPDATE zones JOIN records ON (zones.id=records.zone) SET zones.updated=1 WHERE records.id=$recordid");
}

# Set the 'updated' tag on a domain, ensuring that the next update will
# push it to the BIND servers. If zone is master zone, all servers will be setted into OUT state
function tag_zoneid_updated($zone)
{
	if (!ctype_digit("$zone")) die('Invalid zoneid.'); 
	$db = new DB_probind;
	$db->query("UPDATE zones SET updated = 1 WHERE id = $zone");
}

# Find the arpa domain (if any) which correspond to an IP number, and
# mark it updated.
function affect_reverses($ip)
{
	$bytes = explode('.', $ip);
	if ($zone = known_domain("$bytes[0].in-addr.arpa"))
		tag_zoneid_updated($zone);
	if ($zone = known_domain("$bytes[1].$bytes[0].in-addr.arpa"))
		tag_zoneid_updated($zone);
	if ($zone = known_domain("$bytes[2].$bytes[1].$bytes[0].in-addr.arpa"))
		tag_zoneid_updated($zone);
}

# Zap a singe resource record, duly marking the appropriate domain(s)
# for update.
function del_record($id)
{
	if (!ctype_digit("$id")) die('Invalid record id.'); 
	$db = new DB_probind;
	tag_zone_updated($id);
	$db->query("SELECT zone, type, data FROM records WHERE id = $id");
	if ($db->next_record()) {
		if ($db->Record["type"] == 'A') {
			affect_reverses($db->Record["data"]);
		}
		$db->query("DELETE FROM records WHERE id = $id");
	} else echo "Record $id not found";
}

# Update the contents of an existing recourse record, and mark the
# appropriate domain(s) as being updated. NB: This function should NOT
# be used for MX or SOA records, use upd_mx_record and upd_soa_record
# for those
function upd_record($id, $domain, $ttlspec, $type, $data, $genptr, $comment = '', $disabled = 0)
{
	if (!ctype_digit("$id")) die('Invalid record id.'); 
	$db = new DB_probind;
	$data    = strtr($data,"'",'.');
	$comment = strtr($comment, "'",'.');
	if ($type == "MX" || $type == "SOA")
	    die("Internal error: upd_record($id, $domain, $ttlspec, $type, $data)<P>\n");
	if (! $genptr )
		$genptr = 0;
	if (!$disabled)
		$disabled = 0;
	$db->query("SELECT zone, domain, type, data, genptr FROM records WHERE id = $id");
	if (!$db->next_record()) die("Record $id not found");
	extract($db->Record,EXTR_PREFIX_ALL,'old');
	if (!isset($ttlspec) || !strlen($ttlspec)) {
		$ttl = default_ttl($old_zone);
	} else {
		$ttl = ttl_to_seconds($ttlspec);
	}
	if ($type == 'A') {
		$tmp = valid_ip($data);
		$data = $tmp;
	}
	if ($old_type == 'A' &&  ($data != $old_data || $type != $old_type || $domain != $old_domain || $genptr != $old_genptr )) {
		affect_reverses($old_data);
	}
	$db->prepare("UPDATE records SET domain=:domain, ttl=:ttl, type=:type, data=:data, genptr=:genptr, comment=:comment, disabled=:disabled WHERE id = $id");
	$db->execute(array(":domain"=>$domain,":ttl"=>$ttl,":type"=>$type,":data"=>$data,":genptr"=>$genptr,":comment"=>$comment,":disabled"=>$disabled));
	tag_zone_updated($id);
	if ($type == 'A')
		affect_reverses($data);
}


# Update the contents of an existing MX record, and mark the appropriate
# domain as being updated. NB: This function should ONLY be used for
# MX records, use upd_record or upd_soa_record for all other record types.
function upd_mx_record($id, $domain, $ttlspec, $pref, $data, $comment = '', $disabled = 0)
{
	if (!ctype_digit("$id")) die('Invalid record id.'); 
	$db = new DB_probind;
	$db->query("SELECT zone FROM records WHERE id = $id");
	if (!$db->next_record()) die("Record $id not found");
	extract($db->Record,EXTR_PREFIX_ALL,'old');
	if (!isset($ttlspec) || !strlen($ttlspec)) {
		$ttl = default_ttl($old_zone);
	} else {
		$ttl = ttl_to_seconds($ttlspec);
	}
	$data = strtr($data,"'",'.');
	$comment = strtr($comment,"'",'.');
	if (!$disabled)
		$disabled = 0;
	$db->prepare("UPDATE records SET domain = :domain, ttl = :ttl, type = 'MX', pref = :pref, data = :data, comment=:comment, disabled = :disabled WHERE id = $id");
	$db->execute(array(":domain"=>$domain, ":ttl"=>$ttl, ":pref"=>$pref, ":data"=>$data, ":comment"=>$comment, ":disabled"=>$disabled));
	tag_zone_updated($id);
}

# Update the contents of an existing MX record, and mark the appropriate
# domain as being updated. This function also updated the TTL info for all
# resource records in the same domain, except those which have been
# assigned a different TTL.
# NB: This function should ONLY be used for SOA records, use upd_record
# or upd_mx_record for all other record types.
function upd_soa_record($id, $ttlspec)
{
	if (!ctype_digit("$id")) die('Invalid record id.'); 
	$db = new DB_probind;

	# update all other records in the zone that have the same ttl
	$db->query("SELECT zone, ttl as old_ttl FROM records WHERE id = $id");
	if ($db->next_record()) extract($db->Record); else die("SOA Record $id not found");

	if (isset($ttlspec)) {
		$ttl = ttl_to_seconds($ttlspec);
	} else {
		$ttl = $old_ttl;
	}

	if ($ttl != $old_ttl) {
		$db->prepare("UPDATE records SET ttl = :ttl WHERE zone = :zone AND ttl = :old_ttl");
		$db->execute(array(":ttl"=>$ttl, ":zone"=>$zone, ":old_ttl"=>$old_ttl));

		# now update soa ttl to match
		$db->prepare("UPDATE records SET ttl = ? WHERE id = $id");
		$db->execute($ttl);
	}

	// Maybe we shouldn't even tag the zone as updated, if the ttl didn't change
	tag_zone_updated($id);
}

# Update the information about an existing domain
function update_zone($id, $serial, $refresh, $retry, $expire, $master, $zonefile = 0, $disabled = 0)
{
	global $SLAVE_ON_SLAVES;
	if (!ctype_digit("$id")) die("Invalid zone id: $id."); 
	$db = new DB_probind;
	if (!$zonefile) {
		$db->query("SELECT zonefile FROM zones WHERE id=$id");
		if ($db->next_record()) extract($db->Record); else die("Zone $id not found for update");
	}

	if (!isset($master) || !strlen($master)) {
		$db->prepare("UPDATE zones SET serial = :serial, refresh = :refresh, retry = :retry, expire = :expire, zonefile = :zonefile, updated = 1, disabled=:disabled WHERE id = $id");
		$db->execute(array(":serial"=>$serial,":refresh"=>$refresh,":retry"=>$retry,":expire"=>$expire,":zonefile"=>$zonefile,":disabled"=>$disabled));
	} else {
		$db->prepare("UPDATE zones SET master = :master, zonefile = :zonefile, updated = 1, disabled = :disabled WHERE id = $id");
		$db->execute(array(":master"=>$master, ":zonefile"=>$zonefile, ":disabled"=>$disabled));
	}
	if ($SLAVE_ON_SLAVES)
	    $db->query("UPDATE servers SET state = 'OUT' WHERE type = 'S' AND state != 'OUT'");
}

# Return a formatted HTML string which describes the problems with
# the resource record data, or an empty string of it is OK.
function validate_record($zone, &$domain, $ttl, &$type, &$pref, &$data)
{
	global $DOMAIN_RE, $IPNO_RE, $IPV6_RE;
	$warnings = "";
	if (!isset($domain) || !strlen($domain))
		$domain = '@';
	if ($domain != '@'
	&& $domain != '*'
	&& !eregi($DOMAIN_RE, $domain))
		$warnings .= "<LI>The domain '$domain' name is invalid.\n";
	if (!ereg("^[a-zA-Z]+$", $type))
		$warnings .= "<LI>You must specify valid a type.\n";
	if (!isset($data) || !strlen($data))
		$warnings .= "<LI>You must specify data.\n";
	if ($type == 'A') {
		$rc = preg_match($IPNO_RE, $data, $bytes);
		if (!$rc || $bytes[0]<0 || $bytes[0]>255
		 || $bytes[1]<0 || $bytes[1]>255
		 || $bytes[2]<0 || $bytes[2]>255
		 || $bytes[3]<0 || $bytes[3]>255)
		 	$warnings .= "<LI>'$data' is not a correct IP number.\n";
	}
	if ($type =='AAAA') {
		if (!preg_match($IPV6_RE, $data))
			$warnings .= "<li>'$data' is not a valid IPv6 address.\n";
	}
	$type = strtoupper($type);
	if ($type != 'MX') {
		$pref = '';
	} else {
		if (!isset($pref) || $pref < 0 || $pref > 65535 || !strlen($pref)) {
			$warnings .= "<LI>You must specify an unsigned 16-bit preference for MX records.\n";
		}
		if ( preg_match("/^\d+\.\d+\.\d+\.\d+$/" , $data) ) {
			$warnings .= "<li>You can not insert IPs on MX records!\n";
		}
	}
	//
	// This modification makes wrong result in the 'import' so turn it off. We always can use it on the high level if necessary.
	//if (strchr($data, ".") && ($type != "TXT") && !valid_ip($data) && $data[strlen($data)-1] != ".")
	//	$data .= ".";
	//
	$zone = get_zone($zone);
	if ($type == 'CNAME' && (($zone['domain']."." == $domain) || $domain == '@'))
		$warnings .= "<LI>A CNAME may not exist for the entire domain.\n";
	if (preg_match("/\.$/i", $domain)) {
		if (!preg_match("/".$zone['domain'].'.$/i', $domain))
			$warnings .= "<LI>Domain '$domain' is outside the zone.\n";
	} else {
		if (preg_match("/".$zone['domain'].'$/i', $domain))
			$warnings .= "<LI>Domain '$domain' invalid: check if you didn't loose a dot or something...\n";
	}
	return $warnings;   

}

# Return the default TTL for a zone ID, as found in the SOA record.
function default_ttl($zone)
{
	if (!ctype_digit("$zone")) die('Invalid zone id.'); 
	$db = new DB_probind;
	$db->query("SELECT ttl FROM records WHERE zone = $zone AND type = 'SOA'");
	if (!$db->next_record()) {
		print "ERROR: No SOA record found for zone#$zone<P>\n";
		return 0;
	}
	return $db->Record["ttl"];
}

# Add a resource record to an existing domain. The record data
# are assumed to be correct, i.e. verified by validate_record.
function insert_record($zone, $domain, $ttlspec, $type, $pref, $data, $genptr, $comment = '')
{
	if ( $type != 'A' )
		$genptr = 0;
	if (!isset($ttlspec) || !strlen($ttlspec))
		$ttl = default_ttl($zone);
	else
		$ttl = ttl_to_seconds($ttlspec);
	if ($type == 'A') {
		$tmp = valid_ip($data);
		$data = $tmp;
	}
	$data    = strtr($data,    "'",'.');
	$comment = strtr($comment, "'",'.');
	$param = array();
	$query = "INSERT INTO records SET ctime=NOW(), mtime=NOW(), ";
	$query .= "zone=:zone, "; 	$param[":zone"] = $zone;
	$query .= "domain=:domain, ";	$param[":domain"] = $domain;
	$query .= "ttl=:ttl, ";		$param[":ttl"] = $ttl;
	$query .= "type=:type, ";	$param[":type"] = $type;
	$query .= "data=:data, ";	$param[":data"] = $data;
	$query .= "genptr=:genptr, ";	$param[":genptr"] = $genptr;
	$query .= "comment=:comment";	$param[":comment"] = $comment;
	if (isset($pref) && strlen($pref)) {
	   $query .= ", pref=:pref";	$param[":pref"] = $pref;
	}
	if (isset($port) && strlen($port)) {
	   $query .= ", port=:port";	$param[":port"] = $port;
	}
	if (isset($weight) && strlen($weight)) {
	  $query .= ", weight=:weight"; $param[":weight"] = $weight;
	}
	$db = new DB_probind;
	$db->prepare($query);
	$db->execute($param);
	tag_zone_updated($db->lastInsertId());
	if ($type == 'A')
		affect_reverses($data);
}

# Move the input data to $INPUT_VARS, as we don't care which method
# was used to invoke this page
function get_input()
{
	global $INPUT_VARS, $db, $idn_version;
	$IDN = new idna_convert(array('idn_version' => $idn_version));
	foreach($_REQUEST as $k=>$v) {
		$utf8 = to_utf8($v);
		$punycode = $IDN->encode($utf8);
		$INPUT_VARS[$k] = $db->escape_string($punycode);
	}
}

function decode($str) {
	global $idn_version;
	if (strtolower(substr($str,0,4))=="xn--") {
        	$IDN = new idna_convert(array('idn_version' => $idn_version));
		$str .= " (".$IDN->decode(strtolower($str)).")";
	}
	return $str;
}

function display_if_international($str) {
	global $idn_version;
	if (strtolower(substr($str,0,4))=="xn--") {
        	$IDN = new idna_convert(array('idn_version' => $idn_version));
		return $IDN->decode(strtolower($str));
	} else {
		return "";
	}
}

# Return the serial number to use for the domain ID. Automatically
# updates the database as needed.
function get_serial($id)
{
	if (!ctype_digit("$id")) die('Invalid record id.'); 
	$db = new DB_probind;
	$db->query("SELECT serial, updated, master FROM zones WHERE id = $id");
	if ($db->next_record()) extract($db->Record); else die("zone $id not found");
	if ($updated != 1) return $serial;
	$now = date("Ymd")."01";
	if ($serial >= $now)
		$newserial = $serial + 1;
	else
		$newserial = $now;
	$db->query("UPDATE zones SET updated = 2, serial = $newserial WHERE id = $id");

	$db->query("UPDATE servers SET state = 'OUT' WHERE type = 'M' AND state != 'OUT'");

	return $newserial;
}

#
# adjust all serials (and mark servers if they are OUT OF DATE)
function adjust_serials()
{
	$db = new DB_probind;
	$db->query("SELECT id FROM zones WHERE updated = 1 ");
	while ($db->next_record()) {
	    get_serial($db->Record["id"]);
	}
}

#
# Confirm all updates - set all updated to 0
# Should be called ONLY when all zones whith 'updated' are regenerated
# Must be in 'DOMAIN' crit section
function updates_completed()
{
	$db = new DB_probind;
 	adjust_serials();
	$db->query("UPDATE zones SET updated = 0 WHERE updated = 2 ");
}

function auto_ip6_ptrs($domain, $ttl, $reversehosts)
{
	$chop = (strlen($domain)+1) * -1;
	$mainptr = substr($domain, 0, strlen($domain)-9);
	$pieces = array_reverse(explode(".",$mainptr));  
    	$hex = implode("",$pieces);
	$ip = "";
	while ($hex) {
		$ip .= substr($hex,0,4) . ":";	
		$hex = substr($hex,4);
	}
	$shortip = $ip;
	while (strpos($shortip,":0")) $shortip = str_replace(":0",":",$shortip);
	while (strpos($shortip,":::")) $shortip = str_replace(":::","::",$shortip);
	$octets = count($pieces);
	if ($ip==$shortip) $extra=""; else $extra = " OR `data` LIKE '$shortip%'";
	$query = "
	SELECT zones.id AS zid, records.domain AS rdom, zones.domain AS zdom, `data` 
	FROM zones, records 
	WHERE zones.id = records.zone AND type = 'AAAA' /*AND genptr = 1*/ 
	  AND (`data` LIKE '$ip%'$extra) 
	ORDER BY `data`";
	$db = new DB_probind;
	$db->query($query);
	$autoflag = FALSE;
	$result = ";\n; PTR's implied by AAAA records in the database\n;\n";
	while ($db->next_record()) {
		if ($db->Record['rdom'] == '@')
			$hostname = $db->Record['zdom'].".";
		elseif ($db->Record['rdom'] != $db->Record['zdom'].".")
			$hostname = $db->Record['rdom'].".".$db->Record['zdom'].".";
		else
			$hostname = $db->Record['rdom'];
		$ptr = substr(make_ipv6_ptr($db->Record['data']),0,$chop);
		$result .= "$ptr\t$ttl IN\tPTR\t$hostname\n";
	}
	return $result;
}
#
#
function auto_ptrs($domain, $ttl, $reversehosts)
{
	global $DEFAULT_PTR_DOMAIN;
	preg_match("/(.*)\.in-addr\.arpa(\.)?/", $domain, $matches);
	$bytes = explode(".", $matches[1]);
	$iptop = join('.', array_reverse($bytes));
	$ips = array();
	$octets = count($bytes);
	$query = "	
	SELECT zones.id AS zid, records.domain AS rdom, zones.domain AS zdom, data 
	FROM zones, records 
	WHERE zones.id = records.zone 
	  AND type = 'A' AND genptr = 1 AND data LIKE '$iptop.%' 
	ORDER BY data";
	$db = new DB_probind;
	$db->query($query);
	$autoflag = FALSE;
	$result = "";
	# Output the PTR's implied by A records in the database
	while ($db->next_record()) {
		if ($db->Record['rdom'] == '@')
			$hostname = $db->Record['zdom'].".";
		elseif ($db->Record['rdom'] != $db->Record['zdom'].".")
			$hostname = $db->Record['rdom'].".".$db->Record['zdom'].".";
		else
			$hostname = $db->Record['rdom'];
		preg_match("/$iptop\.(.*)/", $db->Record['data'], $matches);
		$host = $matches[1];
		$host0 = "";
		$host1 = "";
		if ( preg_match("/(\d+)\.(\d+)\.(\d+)/", $host, $matches) ) {
			$host = "$matches[3].$matches[2].$matches[1]";
			$host0 = "$matches[2].$matches[1]";
			$host1 = "$matches[1]";
		}
		else if ( preg_match("/(\d+)\.(\d+)/", $host, $matches) ) {
			$host = "$matches[2].$matches[1]";
			$host0 = "$matches[1]";
		}
		if (@$reversehosts[$host] || ($host0 && @$reversehosts[$host0]) || ($host1 && @$reversehosts[$host1]) )
			continue;
		if (!$autoflag) {
			$autoflag = TRUE;
			$result .= ";\n; PTR's implied by A records in the database\n;\n";
		}
		$pos = strpos($hostname, "*");
		$fixedhost = preg_replace("/\*/", "wildcard", $hostname);
		$result .= "$host\t$ttl IN\tPTR\t$fixedhost\n";
		$ips[$host] = 1;
	}
	mysql_free_result($rid);
	# Output PTR's with bullshit data for all IP numbers not covered
	# by explicit PTR records in the database or implicit ones from A's
	if ($reversehosts) {
		reset($reversehosts);
		while ($host = each($reversehosts)) {
			if (!strchr($host[0], "."))
				$ips[$host[0]] = 1;
		}
	}
	if (strtoupper($DEFAULT_PTR_DOMAIN) == "NONE")
		return $result;
	if ($octets==1) {
		$result .= ";\n; Cowardly refusing to generate so many records as to run out of RAM\n;\n";
		return $result;
	}

	$result .= ";\n; Default PTR records for unused IP numbers\n;\n";

	$reversed = array_reverse($bytes);
	for ($i=0; $i<=3; $i++) {
		if ($octets>$i) {
			# if octet specified, use it for octet begin and end of loop
			$ob[$i] = $oe[$i] = $reversed[$i];
		} else {
			# octet not specified, so loop through entire range
			$ob[$i] = 0; $oe[$i] = 255;
		}
	}
	$subzone = "";
	for ($i=$ob[0]; $i<=$oe[0]; $i++) { 
	 for ($j=$ob[1]; $j<=$oe[1]; $j++) {
	  for ($k=$ob[2]; $k<=$oe[2]; $k++) {
	    if ($octets<3) { 
		$result .= ";\n; $i.$j.$k PTRs\n;\n";
		if ($octets==1) $subzone = "$j.$k.";
		if ($octets==2) $subzone = "$k.";
	    }
	    for ($l=$ob[3]; $l<=$oe[3]; $l++) {
		if (@$ips["$subzone$l"])
			continue;
		$hostname = sprintf("host-%s-%s-%s-%s.$DEFAULT_PTR_DOMAIN.",$i,$j,$k,$l);
		$result .= "$subzone$l\t$ttl IN\tPTR\t$hostname\n";
	    }
	  }
	 }
	}
	return $result;
}

# Return a list of BIND servers which should appear as NS records
function published_servers()
{
	$servers = Array();
	$db = new DB_probind;
	$db->query("SELECT hostname FROM servers WHERE mknsrec");
	#return array_keys($db->sth->fetchAll(PDO::FETCH_GROUP));  #we could write it like this, but it's too complicated.
	while ($db->next_record()) 
		$servers[] = $db->Record[0];
	return $servers;
}

# Format and return the NS records for the BIND servers in the list
function auto_nsrecs($domain, $ttl, $servers)
{
	$db = new DB_probind;
	$db->prepare("SELECT records.id FROM records JOIN zones ON (records.zone=zones.id) WHERE records.type='NS' AND zones.domain=?");
	$db->execute($domain);
	if ($db->next_record()) {
		# zone has it's own NS records so bypass auto NS.	
		return "";
	} # otherwise...
	$result = ";\n; NS's implied by server records in the database\n;\n";
	if ( count($servers) ) {
	while ($server = each($servers))
		$result .= $domain.".\t$ttl IN\tNS\t$server[1].\n";
	}
	return $result;
}

# Return a string describing what is wrong with the database
function database_state()
{
	$warn = array();
	global $DEFAULT_EXTERNAL_DNS, $HOSTMASTER;
	global $DEFAULT_ORIGIN, $DEFAULT_PTR_DOMAIN;
	if (!isset($DEFAULT_EXTERNAL_DNS) || !strlen($DEFAULT_EXTERNAL_DNS))
		$warn[] = "Default MNAME not set.";
	if (!isset($HOSTMASTER) || !strlen($HOSTMASTER))
		$warn[] = "Default RNAME not set.";
	if (!isset($DEFAULT_ORIGIN) || !strlen($DEFAULT_ORIGIN))
		$warn[] = "Default ORIGIN not set.";
	if (!isset($DEFAULT_PTR_DOMAIN) || !strlen($DEFAULT_PTR_DOMAIN))
		$warn[] = "Default PTR Domain not set.";
	$db = new DB_probind;
	$db->query("SELECT * FROM servers");
	if (!$db->num_rows())
		$warn[] = "You have no nameservers configured.";
	$db->query("SELECT * FROM zones WHERE domain = 'TEMPLATE'");
	if (!$db->num_rows())
		$warn[] = "Template Zone missing. Please create a new domain named \"TEMPLATE\".";
	return $warn;
}

function mk_select($name, $array, $presel)
{
	$result = "<SELECT name=\"$name\">\n";
	for ($i=0; $i<count($array); $i++) {
		$result .= "<OPTION";
		if ($i == $presel) {
			$result .= " SELECTED";
			$selected = 1;
		}
		$result .= ">$array[$i]</OPTION>\n";
	}

	$result .= "</SELECT>\n";
	return $result;
}

function mk_select_a($name, $array, $presel)
{
	$result = "<SELECT name=\"$name\">\n";
	$selected = 0;
	for ($i=0; $i<count($array); $i++) {
		$result .= "<OPTION";
		if ($array[$i] == $presel) {
			$result .= " SELECTED";
			$selected = 1;
		}
		$result .= ">$array[$i]</OPTION>\n";
	}

	if (!$selected) {
		$result .= "<OPTION SELECTED value=\"$presel\">$presel</OPTION>";
	}
	$result .= "</SELECT>\n";
	return $result;
}

function abort($txt)
{
    print "$txt\n";
    exit(1);
}

# Check new domain, and if it is subdomain of the existing one, and it contains
# records which fit into the new domain, move this records into the new one
function fill_in_domain($id, $print_only)
{
	global $domain_lock_msg, $REMOTE_USER;
	$result = "";
	#
	# 1. Find our $domain name
	#
	if (!ctype_digit("$id")) die('Invalid record id.'); 
	$db = new DB_probind;
	$db->query("SELECT domain, master FROM zones WHERE id = $id");
	if ($db->next_record()) extract($db->Record);
	if (!$domain)
	    die("Can not find domain zones.id=$id");

	if ($master)
		return "";

	$len = strlen($domain);
	#
	# 2. Now run search over all domains and check if subdomains do exists
	#

	# Prepare (and store) this statement for later use...
	$update_records = $db->prepare("UPDATE records SET domain=:domain, zone=:zone WHERE id=:recid");

	$subd = $domain;
	while ( ($subd = strstr($subd, '.')) && ($subd = substr($subd,1)) ) {
	    	$db->query("
	SELECT records.domain as name, records.id as recid, records.type, records.data  
	FROM records, zones 
	WHERE records.zone = zones.id AND zones.domain = '$subd' AND zones.id != $id");
	    
	    	while ( $db->next_record() ) {
			extract($db->Record);
			if ($type == 'SOA' || !$name || $name == '@')
			    continue;
			if (substr($name, strlen($name)-1) != '.')
			    $name = "$name.$subd";
			else
			    $name = substr($name,0,strlen($name)-1);
			$nlen = strlen($name);
			// Now verify
			if ( $nlen > $len && strcasecmp(".".$domain, substr($name, $nlen - $len - 1)) == 0) {
				$name = substr($name, 0,$nlen - $len - 1);
				if ($print_only) {
			            	$result .= "<TR><TD>$name</TD><TD>$type</TD><TD>$data</TD><TD>; FOUND in $subd</TD></TR>\n";
				} else {
					$update_records->execute(array(":domain"=>$name, ":zone"=>$id, ":recid"=>$recid));
					$result .= "<TR><TD>$name</TD><TD>$type</TD><TD>$data</TD><TD>; MOVED from $subd</TD></TR>\n";
					$updated = 1;
				}

			}
		}
		if (isset($updated) && $updated == 1) {
			$db->query("UPDATE zones SET updated=1 WHERE zones.domain = '$subd'");
			$db->query("UPDATE zones SET updated=1 WHERE id = $id");
			$updated = 0;
		}

	}
	if ($result)
	    $result = "<TABLE width=\"80%\">\n".$result."</TABLE>\n";
	return $result;
}

function close_database()
{
#	mysql_close();
}

#
# MAIN
#

# Global initializations
($DBLINK = mysql_pconnect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWD))
	or die("Unable to connect to the database on $MYSQL_HOST as $MYSQ_USER<P>\n");

mysql_select_db($MYSQL_DB, $DBLINK)
	or die("Database $MYSQL_DB not found<P>\n");

# Get the constants from the blackboard
$db = new DB_probind;
$db->query("SELECT name, value FROM blackboard");
while ($db->next_record()) {
    extract($db->Record);
    switch(strtoupper($name)) {
	case 'DEFAULT_EXTERNAL_DNS':	$DEFAULT_EXTERNAL_DNS = $value; break;
	case 'DEFAULT_ORIGIN': 		$DEFAULT_ORIGIN = $value; break;
	case 'DEFAULT_PTR_DOMAIN': 	$DEFAULT_PTR_DOMAIN = $value; break;
	case 'HOSTMASTER': 		$HOSTMASTER = $value; break;
	case 'TWO_STEP_UPDATE': 	$TWO_STEP_UPDATE = $value; break;
	case 'SLAVE_ON_SLAVES': 	$SLAVE_ON_SLAVES = $value; break;
	case 'SHOW_ALL':        	$SHOW_ALL = $value; break;
	default:
    }
}

?>
