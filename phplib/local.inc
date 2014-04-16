<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998,1999 SH Online Dienst GmbH
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id: local.inc,v 1.23 1999/08/25 11:40:48 kk Exp $
 *
 */ 

$_ENV["BaseName"] = "probind";
$_ENV["DatabaseClass"] = "DB_".$_ENV["BaseName"];
$_ENV["SessionClass"] = $_ENV["BaseName"]."_Session";
$_ENV["AuthClass"] = $_ENV["BaseName"]."_Auth";
$_ENV["PermClass"] = $_ENV["BaseName"]."_Perm";
$_ENV["RegisterMode"] = "Approve";  /* Auto, Approve or Email, see register.php */
$_ENV["DocRoot"] = $_SERVER["DOCUMENT_ROOT"];
$_ENV["SiteRoot"] = $_ENV["DocRoot"];
if (isset($_ENV["SubFolder"])) $_ENV["SiteRoot"] .="/".$_ENV["SubFolder"];
$_ENV["MyForeignKeys"]="LinkedTables";
$_ENV["MyForeignKeysDB"]=$_ENV["DatabaseClass"];
$_ENV["LogSqlTo"] = "z_audit_log";
$_ENV["LogViewsTo"] = "z_audit_log";



class probind_CT_Sql extends CT_Sql {
  var $database_class = "DB_probind";          ## Which database to connect...
  var $database_table = "active_sessions"; ## and find our session data in this table.
}

class probind_Session extends Session {
  var $classname = "probind_Session";

  var $cookiename     = "";                ## defaults to classname
  var $magic          = "Hocuspocus";      ## ID seed
  var $mode           = "cookie";          ## We propagate session IDs with cookies
  var $fallback_mode  = "get";
  var $lifetime       = 0;                 ## 0 = do session cookies, else minutes
  var $that_class     = "probind_CT_Sql"; ## name of data storage container
  var $gc_probability = 5;  
  var $auto_init      = "setup.inc";
}

class probind_Auth extends Auth {
  var $classname      = "probind_Auth";

  var $lifetime       =  0;

  var $database_class = "DB_probind";
  var $database_table = "auth_user";
  
  function auth_loginform() {
    global $sess;
    global $_PHPLIB;

    include($_PHPLIB["local"] . "loginform.ihtml");
  }
  
  function auth_validatelogin() {

    $username = trim(@$_POST["username"]);
    $password = hash_auth($username,@$_POST['password']);

    if(isset($username)) {
      $this->auth["uname"]=$username;        ## This provides access for "loginform.ihtml"
    }
    
    $uid = false;
    
    $this->db->query(sprintf("select user_id, perms ".
                             "        from %s ".
                             "       where username = '%s' ".
                             "         and password = '%s'",
                          $this->database_table,
                          $this->db->quote($username),
                          $this->db->quote($password)));

    while($this->db->next_record()) {
      $uid = $this->db->f("user_id");
      $this->auth["perm"] = $this->db->f("perms");
    }
    return $uid;
  }

  
}


class probind_Perm extends Perm {
  var $classname = "probind_Perm";

  var $permissions = array(
                                "admin" => 1,
                                "owner" => 2,
                          );

}

function hash_auth($u,$p) {
        # it is recommended you come up with your own method here
        # make sure it's database safe, as there is no additional escaping in password functions
        return addslashes($p);         // store passwords in clear text - not recommended.
        #return md5($p);                // better but still not great.
        #return sha1($p.$u);            // username used as a salt, and better hash
        # # WARNING: DO NOT USE base64_encode'd methods, those WON'T be database safe
        #return base64_encode(hash("sha256",$p.md5($u),true));  // now this could be tricky to crack
        #return base64_encode(hash("sha512",sha1($p).md5($u),true));  // now this could be tricky to crack
}

?>
