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
$DOMAIN_RE = "^(\*|[_a-z0-9]+([_a-z0-9-]+[_a-z0-9]+)*)(\.[_a-z0-9]+([_a-z0-9-]+[_a-z0-9]+)*)*\.?$";
$DOMAIN_RE_NODOT = "^[_a-z0-9]+([_a-z0-9-]+[_a-z0-9]+)*(\.[_a-z0-9]+([_a-z0-9-]+[_a-z0-9]+)*)*$";
$IPNO_RE = "/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/";
// From http://forums.dartware.com/viewtopic.php?t=452
$IPV6_RE = '/^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/';

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
	$query = "LOCK TABLES blackboard WRITE";
	sql_query($query);
	$query = "SELECT name, value FROM blackboard WHERE name = 'LOCK_$lockname'";
	$rid = sql_query($query);
	$row = mysql_fetch_array($rid);
	mysql_free_result($rid);
	if ($row) {
		$query = "UNLOCK TABLES";
		sql_query($query);
		if (strlen($row['value']))
			return $row['value'];
		return "<unknown user>";
	}
	$query = "INSERT INTO blackboard (name, value) VALUES ('LOCK_$lockname', '$user')";
	sql_query($query);
	$query = "UNLOCK TABLES";
	sql_query($query);
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
	$query = "LOCK TABLES blackboard WRITE";
	sql_query($query);
	$query = "DELETE FROM blackboard WHERE name = 'LOCK_$lockname'";
	sql_query($query);
	$query = "UNLOCK TABLES";
	sql_query($query);
}

# Return a HTML formatted list of currently held locks.
function list_locks()
{
	$result = "";
	$query = "SELECT name, value, ctime FROM blackboard WHERE name LIKE 'LOCK_%'";
	$rid = sql_query($query);
	if (!mysql_num_rows($rid))
		return "";
	while (list($name, $lock_holder, $ctime) = mysql_fetch_row($rid)) {
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
	mysql_free_result($rid);
	return $result;
}

# return true iff the $domain string is the name of a domain in the
# database
function known_domain($domain)
{
	$rid = sql_query("SELECT id FROM zones WHERE domain = '$domain'".access());
	$id = 0;
	if (mysql_num_rows($rid))
		$id = mysql_result($rid, 0);
	mysql_free_result($rid);
	return $id;
}

# Return the known information about a zone ID
function get_zone($id)
{
	$rid = sql_query("SELECT * FROM zones WHERE id = $id".access());
	$info = mysql_fetch_array($rid);
	mysql_free_result($rid);
	return $info;
}

# Return the known information about a zone name
function get_named_zone($name)
{
	$rid = sql_query("SELECT * FROM zones WHERE domain = '$name'".access());
	$info = mysql_fetch_array($rid);
	mysql_free_result($rid);
	return $info;
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
	# Find the template data
	$rid = sql_query("SELECT id FROM zones WHERE domain = 'TEMPLATE'");
	$templateid = mysql_result($rid, 0);
	mysql_free_result($rid);
	$rid = sql_query("SELECT serial, refresh, retry, expire, options FROM zones WHERE id = $templateid");
	(list($serial, $refresh, $retry, $expire, $options) = mysql_fetch_row($rid))
		or die("TEMPLATE zone not found in database<P>\n");
	mysql_free_result($rid);
	if (!$master) {
		$zonefile = "$domain.dns";
		$master = "''";
		if ($eoptions)
			$options = $eoptions; // If options are specified, set up them instead of template options
	} else {
		$zonefile = "SEC/$domain.dns";
		$master = "'$master'";
		$options = ""; // No options from TEMPLATE for the secondary zones
	}
	# Make sure that no one else is working on the domain list while
	# we do this.
	if ($user = patient_enter_crit($REMOTE_USER, 'DOMAIN'))
		die (sprintf($domain_lock_msg, ucfirst($user)));
	# Add the domain to the zones table
	$rid = sql_query("INSERT INTO zones (domain, serial, refresh, retry, expire, master, updated, zonefile, options, owner) VALUES ('$domain', $serial, $refresh, $retry, $expire, $master, 1, '$zonefile', '$options', '$owner')");
	$newzone = mysql_insert_id();
	sql_query("UPDATE zones SET ctime = mtime WHERE id = $newzone");
	# Just in case a domain of the same name was recently deleted...
	sql_query("DELETE FROM deleted_domains WHERE domain = '$domain'");


	if ($master != "''") {
		if ($SLAVE_ON_SLAVES)
		    sql_query("UPDATE servers SET state = 'OUT'");
		else
		    sql_query("UPDATE servers SET state = 'OUT' WHERE type = 'M'");
		leave_crit('DOMAIN');
		return $newzone;
	}
	$rid = sql_query("SELECT domain, ttl, type, pref, data FROM records WHERE zone = $templateid");
	while (list($domain, $ttl, $type, $pref, $data) = mysql_fetch_row($rid)){
		sql_query("INSERT INTO records (domain, zone, ttl, type, pref, data) VALUES ('$domain', $newzone, $ttl, '$type', '$pref', '$data')");
	}
	mysql_free_result($rid);
	sql_query("UPDATE records SET ctime = mtime WHERE zone = $newzone");
	if ($master != "''" && !$SLAVE_ON_SLAVES)
		sql_query("UPDATE servers SET state = 'OUT' WHERE type = 'M'");
	else
	    sql_query("UPDATE servers SET state = 'OUT' WHERE type = 'M' OR type = 'S'");
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
	# Make sure that no one else is working on the domain list while
	# we do this.
	if ($user = patient_enter_crit($REMOTE_USER, 'DOMAIN'))
		die (sprintf($domain_lock_msg, ucfirst($user)));
	# Make sure we update any arpa domains which contain synthetic
	# PTR records for any A records about to vanish.
	$rid = sql_query("SELECT data FROM records WHERE zone = $zoneid AND type = 'A'");
	while (list($data) = mysql_fetch_row($rid)) {
		affect_reverses($data);
	}
	mysql_free_result($rid);
	# Add this domain to deleted_domains
	$rid = sql_query("SELECT domain, zonefile, master FROM zones WHERE id = $zoneid".access());
	list($domain, $zonefile, $master) = mysql_fetch_row($rid);
	mysql_free_result($rid);
	sql_query("DELETE FROM deleted_domains WHERE domain = '$domain'");
	sql_query("INSERT INTO deleted_domains SELECT domain, zonefile, owner FROM zones WHERE id = $zoneid");
	#?bevhost says.. why did we backup the domain name but not the records?
	sql_query("CREATE TABLE IF NOT EXISTS deleted_records");
	sql_query("SELECT * FROM records WHERE zone=$zoneid");
	# Finally, zap records, annotations and the zone itself.
	sql_query("DELETE FROM records WHERE zone = $zoneid");
	$rrcount = mysql_affected_rows();
	sql_query("DELETE FROM annotations WHERE zone = $zoneid");
	sql_query("DELETE FROM zones WHERE id = $zoneid");
	if ($master && !$SLAVE_ON_SLAVES)
		sql_query("UPDATE servers SET state = 'OUT' WHERE type = 'M'");
	else
	    sql_query("UPDATE servers SET state = 'OUT' WHERE type = 'M' OR type = 'S'");
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
	$result = "";
	$query = "SELECT id, domain, master, disabled FROM zones";
	switch ($type) {
	case '*':
		$query .= " WHERE domain LIKE '$lookfor'";
		break;
	case 'M':
		$query .= " WHERE domain LIKE '$lookfor' AND NOT master";
		break;
	case 'S':
		$query .= " WHERE domain LIKE '$lookfor' AND master";
		break;
	case 'A':
		$query .= ", annotations WHERE id = zone AND descr LIKE '$lookfor'";
		break;
	}
	$query .= access();
	$query .= " ORDER BY domain";
	$rid = sql_query($query);
	$typestr = "";
	$result1 = "";
	while (list($id, $domain, $master, $disabled) = mysql_fetch_row($rid)) {
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
	mysql_free_result($rid);
	return $result;
}

# Set the 'updated' tag on a domain, ensuring that the next update will
# push it to the BIND servers. The parameter is the record id for a
# resource record in the domain to be updated.
function tag_zone_updated($recordid)
{
	$query = "SELECT zone FROM records WHERE id = $recordid";
	$rid = sql_query($query);
	$zone = mysql_result($rid, 0);
	mysql_free_result($rid);
	sql_query("UPDATE zones SET updated = 1 WHERE id = $zone");
}

# Set the 'updated' tag on a domain, ensuring that the next update will
# push it to the BIND servers. If zone is master zone, all servers will be setted into OUT state
function tag_zoneid_updated($zone)
{
	$rid = sql_query("UPDATE zones SET updated = 1 WHERE id = $zone");
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
	tag_zone_updated($id);
	$query = "SELECT zone, type, data FROM records WHERE id = $id";
	$rid = sql_query($query);
	$orec = mysql_fetch_array($rid);
	$odata = $orec['data'];
	$otype = $orec['type'];
	mysql_free_result($rid);
	if ($otype == 'A') {
		affect_reverses($odata);
	}
	sql_query("DELETE FROM records WHERE id = $id");
}

# Update the contents of an existing recourse record, and mark the
# appropriate domain(s) as being updated. NB: This function should NOT
# be used for MX or SOA records, use upd_mx_record and upd_soa_record
# for those
function upd_record($id, $domain, $ttlspec, $type, $data, $genptr, $comment = '', $disabled = 0)
{
	$data    = strtr($data,"'",'.');
	$comment = strtr($comment, "'",'.');
	if ($type == "MX" || $type == "SOA")
	    die("Internal error: upd_record($id, $domain, $ttlspec, $type, $data)<P>\n");
	if (! $genptr )
		$genptr = 0;
	if (!$disabled)
		$disabled = 0;

	$query = "SELECT zone FROM records WHERE id = $id";
	$rid = sql_query($query);
	$zone = mysql_result($rid, 0);
	mysql_free_result($rid);
	
	if (!strlen($ttlspec)) {
		$ttl = default_ttl($zone);
	} else { 
		$ttl = ttl_to_seconds($ttlspec);
	}

	$query = "SELECT domain, type, data, genptr FROM records WHERE id = $id";
	$rid = sql_query($query);
	$orec = mysql_fetch_array($rid);
	mysql_free_result($rid);
	$odomain = $orec['domain'];
	$odata = $orec['data'];
	$otype = $orec['type'];
	$ogenptr = $orec['genptr'];
	if ($type == 'A') {
		$tmp = valid_ip($data);
		$data = $tmp;
	}
	if ($otype == 'A' &&  ($data != $odata || $type != $otype || $domain != $odomain || $genptr != $ogenptr )) {
		affect_reverses($odata);
	}
	$query = "UPDATE records SET domain = '$domain', ttl = $ttl, type = '$type', data = '$data', genptr = $genptr, comment='$comment', disabled=$disabled WHERE id = $id";
	$rid = sql_query($query);
	tag_zone_updated($id);
	if ($type == 'A')
		affect_reverses($data);
}


# Update the contents of an existing MX record, and mark the appropriate
# domain as being updated. NB: This function should ONLY be used for
# MX records, use upd_record or upd_soa_record for all other record types.
function upd_mx_record($id, $domain, $ttlspec, $pref, $data, $comment = '', $disabled = 0)
{
	$query = "SELECT zone FROM records WHERE id = $id";
	$rid = sql_query($query);
	$orec = mysql_fetch_array($rid);
	mysql_free_result($rid);
	$zone = $orec['zone'];
	
	if (!strlen($ttlspec)) {
		$ttl = default_ttl($zone);
	} else {
		$ttl = ttl_to_seconds($ttlspec);
	}

	$data = strtr($data,"'",'.');
	$comment = strtr($comment,"'",'.');
	if (!$disabled)
		$disabled = 0;
	$query = "UPDATE records SET domain = '$domain', ttl = $ttl, type = 'MX', pref = $pref, data = '$data', comment='$comment', disabled = $disabled WHERE id = $id";
	$rid = sql_query($query);
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
	$query = "SELECT zone, ttl FROM records WHERE id = $id";
	$rid = sql_query($query);
	list($zoneid, $oldttl) = mysql_fetch_row($rid);
	mysql_free_result($rid);

	if (!strlen($ttlspec)) { 
		$ttl = default_ttl($zoneid);
	} else { 
		$ttl = ttl_to_seconds($ttlspec);
	} 

	$query =  "UPDATE records SET ttl = $ttl WHERE zone = $zoneid AND ttl = $oldttl";
	$rid = sql_query($query);
	$query = "UPDATE records SET ttl = $ttl WHERE id = $id";
	$rid = sql_query($query);
	tag_zone_updated($id);
}

# Update the information about an existing domain
function update_zone($id, $serial, $refresh, $retry, $expire, $master, $zonefile = 0, $disabled = 0)
{
	global $SLAVE_ON_SLAVES;
	if (!$zonefile) {
		$rid = sql_query("SELECT zonefile FROM zones WHERE id=$id");
		list($zonefile) = mysql_fetch_row($rid);
		mysql_free_result($rid);
	}

	if (!strlen($master)) {
		$query = "UPDATE zones SET serial = $serial, refresh = $refresh, retry = $retry, expire = $expire, zonefile = '$zonefile', updated = 1, disabled=$disabled WHERE id = $id";
	} else {
		$query = "UPDATE zones SET master = '$master', zonefile = '$zonefile', updated = 1, disabled = $disabled WHERE id = $id";
	}
	$rid = sql_query($query);
	if ($SLAVE_ON_SLAVES)
	    sql_query("UPDATE servers SET state = 'OUT' WHERE type = 'S' AND state != 'OUT'");
}

# Return a formatted HTML string which describes the problems with
# the resource record data, or an empty string of it is OK.
function validate_record($zone, &$domain, $ttl, &$type, &$pref, &$data)
{
	global $DOMAIN_RE, $IPNO_RE, $IPV6_RE;
	$warnings = "";
	if (!strlen($domain))
		$domain = '@';
	if ($domain != '@'
	&& $domain != '*'
	&& !eregi($DOMAIN_RE, $domain))
		$warnings .= "<LI>The domain '$domain' name is invalid.\n";
	if (!ereg("^[a-zA-Z]+$", $type))
		$warnings .= "<LI>You must specify valid a type.\n";
	if (!strlen($data))
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
	if ($type != 'MX')
		$pref = '';
	elseif ($pref < 0 || $pref > 65535 || !strlen($pref))
		$warnings .= "<LI>You must specify an unsigned 16-bit preference for MX records.\n";
	//
	// This modification makes wrong result in the 'import' so turn it off. We always can use it on the high level if necessary.
	//if (strchr($data, ".") && ($type != "TXT") && !valid_ip($data) && $data[strlen($data)-1] != ".")
	//	$data .= ".";
	//
	$zone = get_zone($zone);
	if ($type == 'CNAME' && (($zone['domain']."." == $domain) || $domain == '@'))
		$warnings .= "<LI>A CNAME may not exist for the entire domain.\n";
	if (preg_match("/\.$/i", $domain)
	&& !preg_match("/".$zone['domain'].'.$/i', $domain))
		$warnings .= "<LI>Domain '$domain' is outside the zone.\n";
	return $warnings;
}

# Return the default TTL for a zone ID, as found in the SOA record.
function default_ttl($zone)
{
	$query = "SELECT ttl FROM records WHERE zone = $zone AND type = 'SOA'";
	$rid = sql_query($query);
	if (!mysql_num_rows($rid)) {
		print "ERROR: No SOA record found for zone#$zone<P>\n";
		return 0;
	}
	$ttl = mysql_result($rid, 0);
	mysql_free_result($rid);
	return $ttl;
}

# Add a resource record to an existing domain. The record data
# are assumed to be correct, i.e. verified by validate_record.
function insert_record($zone, $domain, $ttlspec, $type, $pref, $data, $genptr, $comment = '')
{
	if ( $type != 'A' )
		$genptr = 0;
	if (!strlen($ttlspec))
		$ttl = default_ttl($zone);
	else
		$ttl = ttl_to_seconds($ttlspec);
	if ($type == 'A') {
		$tmp = valid_ip($data);
		$data = $tmp;
	}
	$data    = strtr($data,    "'",'.');
	$comment = strtr($comment, "'",'.');
	$query = "INSERT INTO records (zone, domain, ttl, type, data, genptr, comment";
	if (strlen($pref))
		$query .= ", pref";
	$query .= ") VALUES ($zone, '$domain', '$ttl', '$type', '$data', $genptr, '$comment'";
	if (strlen($pref))
		$query .= ", '$pref'";
	$query .= ")";
	$rid = sql_query($query);
	$id = mysql_insert_id();
	$query = "UPDATE records SET ctime = mtime WHERE id = $id";
	$rid = sql_query($query);
	tag_zone_updated($id);
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

	$query = "SELECT serial, updated, master FROM zones WHERE id = $id";
	$rid = sql_query($query);
	list($serial, $updated, $master) = mysql_fetch_row($rid);
	mysql_free_result($rid);
	if ($updated != 1) return $serial;
	$now = date("Ymd")."01";
	if ($serial >= $now)
		$newserial = $serial + 1;
	else
		$newserial = $now;
	sql_query("UPDATE zones SET updated = 2, serial = $newserial WHERE id = $id");

	sql_query("UPDATE servers SET state = 'OUT' WHERE type = 'M' AND state != 'OUT'");

	return $newserial;
}

#
# adjust all serials (and mark servers if they are OUT OF DATE)
function adjust_serials()
{
	$rid = sql_query("SELECT id FROM zones WHERE updated = 1 ");
	while ( list($id) = mysql_fetch_row($rid)) {
	    get_serial($id);
	}
	mysql_free_result($rid);
}

#
# Confirm all updates - set all updated to 0
# Should be called ONLY when all zones whith 'updated' are regenerated
# Must be in 'DOMAIN' crit section
function updates_completed()
{
 	adjust_serials();
	$rid = sql_query("UPDATE zones SET updated = 0 WHERE updated = 2 ");
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
	$query = "SELECT zones.id AS zid, records.domain AS rdom, zones.domain AS zdom, `data` FROM zones, records WHERE zones.id = records.zone AND type = 'AAAA' /*AND genptr = 1*/ AND (`data` LIKE '$ip%'$extra) ORDER BY `data`";
	$rid = sql_query($query);
	$autoflag = FALSE;
	$result = ";\n; PTR's implied by AAAA records in the database\n;\n";
	while ($row = mysql_fetch_array($rid)) {
		if ($row['rdom'] == '@')
			$hostname = $row['zdom'].".";
		elseif ($row['rdom'] != $row['zdom'].".")
			$hostname = $row['rdom'].".".$row['zdom'].".";
		else
			$hostname = $row['rdom'];
		$ptr = substr(make_ipv6_ptr($row['data']),0,$chop);
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
	$octets = count($bytes);
	$query = "SELECT zones.id AS zid, records.domain AS rdom, zones.domain AS zdom, data FROM zones, records WHERE zones.id = records.zone AND type = 'A' AND genptr = 1 AND data LIKE '$iptop.%' ORDER BY data";
	$rid = sql_query($query);
	$autoflag = FALSE;
	$result = "";
	# Output the PTR's implied by A records in the database
	while ($row = mysql_fetch_array($rid)) {
		if ($row['rdom'] == '@')
			$hostname = $row['zdom'].".";
		elseif ($row['rdom'] != $row['zdom'].".")
			$hostname = $row['rdom'].".".$row['zdom'].".";
		else
			$hostname = $row['rdom'];
		preg_match("/$iptop\.(.*)/", $row['data'], $matches);
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
	$query = "SELECT hostname FROM servers WHERE mknsrec";
	$rid = sql_query($query);
	while ($row = mysql_fetch_row($rid))
		$servers[] = $row[0];
	mysql_free_result($rid);
	return $servers;
}

# Format and return the NS records for the BIND servers in the list
function auto_nsrecs($domain, $ttl, $servers)
{
	$query = "SELECT records.id FROM records JOIN zones ON (records.zone=zones.id) WHERE records.type='NS' AND zones.domain='$domain'";
	$rid = sql_query($query);
	if ($row = mysql_fetch_row($rid)) {
		# zone has it's own NS records so bypass auto NS.	
		mysql_free_result($rid);
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
	if (!strlen($DEFAULT_EXTERNAL_DNS))
		$warn[] = "Default MNAME not set.";
	if (!strlen($HOSTMASTER))
		$warn[] = "Default RNAME not set.";
	if (!strlen($DEFAULT_ORIGIN))
		$warn[] = "Default ORIGIN not set.";
	if (!strlen($DEFAULT_PTR_DOMAIN))
		$warn[] = "Default PTR Domain not set.";
	$rid = sql_query("SELECT * FROM servers");
	if (!mysql_num_rows($rid))
		$warn[] = "You have no nameservers configured.";
	mysql_free_result($rid);
	$rid = sql_query("SELECT * FROM zones WHERE domain = 'TEMPLATE'");
	if (!mysql_num_rows($rid))
		$warn[] = "Template Zone missing. Please create a new domain named \"TEMPLATE\".";
	mysql_free_result($rid);
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
	$rid   = sql_query("SELECT domain, master FROM zones WHERE id = $id");
	list($domain, $master)  = mysql_fetch_row($rid);
	mysql_free_result($rid);
	if (!$domain)
	    die("Can not find domain zones.id=$id");

	if ($master)
		return "";

	$len = strlen($domain);
	#
	# 2. Now run search over all domains and check if subdomains do exists
	#
	$subd = $domain;
	while ( ($subd = strstr($subd, '.')) && ($subd = substr($subd,1)) ) {
		$rid = sql_query("SELECT records.domain , records.id, records.type, records.data  FROM records, zones WHERE records.zone = zones.id AND zones.domain = '$subd' AND zones.id != $id");
	    while ( list($name, $recid, $type, $data) = mysql_fetch_row($rid)) {
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
				if ($print_only)
		            $result .= "<TR><TD>$name</TD><TD>$type</TD><TD>$data</TD><TD>; FOUND in $subd</TD></TR>\n";
				else {
					$rid1 = sql_query("UPDATE records SET domain='$name', zone=$id WHERE id = $recid");
		            $result .= "<TR><TD>$name</TD><TD>$type</TD><TD>$data</TD><TD>; MOVED from $subd</TD></TR>\n";
					$updated = 1;
				}

			}
		}
		if (isset($updated) && $updated == 1) {
			sql_query("UPDATE zones SET updated=1 WHERE zones.domain = '$subd'");
			sql_query("UPDATE zones SET updated=1 WHERE id = $id");
			$updated = 0;
		}
		mysql_free_result($rid);

	}
	if ($result)
	    $result = "<TABLE width=\"80%\">\n".$result."</TABLE>\n";
	return $result;
}

function close_database()
{
	mysql_close();
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
$query = "SELECT name, value FROM blackboard";
$rid = sql_query($query);
while ($row = mysql_fetch_array($rid)) {
	switch($var = strtoupper($row['name'])) {
	case 'DEFAULT_EXTERNAL_DNS': $DEFAULT_EXTERNAL_DNS = $row[1]; break;
	case 'DEFAULT_ORIGIN': $DEFAULT_ORIGIN = $row[1]; break;
	case 'DEFAULT_PTR_DOMAIN': $DEFAULT_PTR_DOMAIN = $row[1]; break;
	case 'HOSTMASTER': $HOSTMASTER = $row[1]; break;
	case 'TWO_STEP_UPDATE': $TWO_STEP_UPDATE = $row[1]; break;
	case 'SLAVE_ON_SLAVES': $SLAVE_ON_SLAVES = $row[1]; break;
	case 'SHOW_ALL':        $SHOW_ALL = $row[1]; break;
	default:
	}
}
mysql_free_result($rid);

?>
