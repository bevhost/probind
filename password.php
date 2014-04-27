<HTML><HEAD>
<TITLE>Bulk update</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD><BODY bgcolor="#cccc99" background="images/BG-shadowleft.gif">
<?php
include("phplib/prepend.php");
include("inc/config.php");
page_open(array("sess" => $_ENV["SessionClass"], "auth" => $_ENV["AuthClass"], "perm" => $_ENV["PermClass"]));
include("header.php");

$db = new $_ENV["DatabaseClass"];
$db->query("select * from auth_user where username='".$auth->auth["uname"]."'");
if ($db->next_record()) { extract($db->Record); } else { die("Username not found"); }

$error = "";
if ($op = @$_POST["old_pass"]) {
	if (hash_auth($auth->auth["uname"],$op) == $password) {
		$p1 = $_POST["Password"];
		$p2 = $_POST["Password2"];
		$uid = $_POST["user_id"];
		$l1 = strlen($p1);
		if(!(mb_ereg("[A-Z]",$p1) &&
         	    mb_ereg("[a-z]",$p1) &&
         	    mb_ereg("[0-9]",$p1))) $error = "New Password To Weak";
		else if ($l1<5) $error = "New Password Too Short";
		else if ($uid<>$user_id) $error = "UserID Mismatch";
		else if ($l1>32) $error = "New Password Too Long";
		else if ($p1<>$p2) $error = "New Passwords Don't Match";
		else {
			$op = addslashes(hash_auth($auth->auth["uname"],$op));
			$p1 = addslashes(hash_auth($auth->auth["uname"],$p1));
			$db->query("select username from auth_user where password='$op' and user_id='$uid'");
			if ($db->next_record()) {
				$db->query("update auth_user set password='$p1' where user_id='$uid'");
				echo "<p>Successfully updated password for $username.</p>";
				page_close();
				exit;
			} else {
				echo "<p>An error happened when trying to update the password for $username. Please contact your system's administrator.</p>";
			}
		}
	} else {
		$error = "Incorrect Old Password";
	}
}
if ($error) echo "<br><p class=error>$error</p>";

?>
<form name=PasswordChangeForm method=post onsubmit="return PCFvalidator()">
<input type=hidden name=user_id value='<?php print "$user_id"; ?>'>
<fieldset style="width:300px;margin:100px;";>
<legend>Changing password for <?=$username?> </legend>
<table>
<tr><td>Old Password </td><td><input name=old_pass value='' type=password></td></tr>
<tr><td>New Password </td><td><input name=Password value='' type=password></td></tr>
<tr><td>New Password </td><td><input name=Password2 value='' type=password></td></tr>
<tr><td> </td><td><input type=submit name=submit value=Change></td></tr>
</table>
</fieldset>
<p>New passwords should be 6 to 32 characters in length and contain at least:-
<ul>
<li>One A-Z</li>
<li>One a-z</li>
<li>One 0-9</li>
</ul>
</p>

</form>
<script>
function PCFvalidator(f) {
    if (f.elements["Password"].value != f.elements["Password2"].value) {
        alert("Passwords don't match");
        f.elements["Password2"].focus();
        return false;
    }
    return true;
}
</script>
<?php

page_close();
?>
