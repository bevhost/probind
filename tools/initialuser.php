#!/usr/bin/php

<?php

// $debug=1;

/* Initial user creation for ProBIND
 * 
 * This script is capable of creating a new user in the database.
 * It does NOT require a previous user not autentication, so, after you created
 * one user, DELETE this script.
 * This assumes you did all the previous steps as defined by INSTALL.
 * 
 */

include dirname(__FILE__) . "/../phplib/prepend.php";
require_once(dirname(__FILE__) . "/../inc/config.php");

$username = "root";
$password = "changeme";
$perms = "admin,owner";
$hash_secret = "Jabberwocky..."; # change this, but change it both here and in usermgr.php

// ## Get a database connection
$db = new DB_probind;

// Create a uid and insert the user...
$u_id=md5(uniqid($hash_secret));
$query = "insert into auth_user values('$u_id','$username','$password','$perms')";
$db->query($query);
if ($db->affected_rows() == 0) {
	print "Error creating user.\n";
	break;
}
print "User $username created.\n";

?>
