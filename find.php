<?php // Search command for checking against current usernames/email addresses - used in new account creation
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache");
header("Cache-Control: post-check=0, pre-check=0");
header("Pragma: no-cache");

include("inc/lib.inc");

$db = new DB_probind;

get_request_values("ZoneExists,ZoneNotExists,SetZone,SrchZone,DomType");

if ($ZoneExists) {
    if (!preg_match("/$DOMAIN_RE_NODOT/", $ZoneExists)) {
        echo "<img class='icon' src='/images/icons/cross.png' /> ";
        echo trans("Invalid");
    } else { 
        $db->prepare("SELECT id FROM zones WHERE domain = ?");
	$db->execute($ZoneExists);
        if ($db->next_record()) {
                echo "<img class='icon' src='/images/icons/tick.png' /> ";
                echo trans("OK");
        } else {
                echo "<img class='icon' src='/images/icons/cross.png' /> ";
                echo trans("Not Found");
        }
   }
}
if ($ZoneNotExists) {
    if (!preg_match("/$DOMAIN_RE_NODOT/", $ZoneNotExists)) {
        echo "<img class='icon' src='/images/icons/cross.png' /> ";
        echo trans("Invalid");
    } else { 
        $db->prepare("SELECT id FROM zones WHERE domain = ?");
	$db->execute($ZoneNotExists);
        if ($db->next_record()) {
                echo "<img class='icon' src='/images/icons/cross.png' /> ";
                echo trans("Already Exists");
        } else {
                echo "<img class='icon' src='/images/icons/tick.png' /> ";
                echo trans("OK");
        }
   }
}


if ($SetZone) {
        $count=0;
        $db->prepare("SELECT id, domain FROM zones WHERE domain LIKE ? ORDER BY domain LIMIT 20");
	$db->execute("%$SetZone%");
        echo "<a href=javascript:hidepopup(); id=close title='close'>x</a>\n";
        echo "<ul>\n";
        while ($db->next_record()) {
                $count++;
		extract($db->Record);
		echo " <li><a onclick='set(\"$domain\")'>$domain</a></li>";
        }
        echo "</ul>\n";
}

if ($SrchZone!==false) {
        $format = "<a href='/zones.php?domtype=$DomType;zone=%s'>%s%s</A>\n";
        $IDN = new idna_convert(array('idn_version' => $idn_version));
        $utf8 = to_utf8($SrchZone);
        $lookfor = $IDN->encode($utf8);
	echo domain_list("%$lookfor%", $DomType, $format);
}



