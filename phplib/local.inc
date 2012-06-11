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
$_ENV["SiteRoot"] = $_ENV["DocRoot"]."/".$_ENV["SubFolder"];


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
    global $username, $password;

    $username = trim(@$_POST["username"]);
    $password = trim(@$_POST["password"]);

    if(isset($username)) {
      $this->auth["uname"]=$username;        ## This provides access for "loginform.ihtml"
    }
    
    $uid = false;
    
    $this->db->query(sprintf("select user_id, perms ".
                             "        from %s ".
                             "       where username = '%s' ".
                             "         and password = '%s'",
                          $this->database_table,
                          addslashes($username),
                          addslashes($password)));

    while($this->db->next_record()) {
      $uid = $this->db->f("user_id");
      $this->auth["perm"] = $this->db->f("perms");
    }
    return $uid;
  }

  
}


class probind_Perm extends Perm {
  var $classname = "probind_Perm";
}


?>
