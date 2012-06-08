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

include($_ENV["local"].".htauth.local");

class probind_CT_Sql extends CT_Sql {
  var $database_class = "DB_probind";          ## Which database to connect...
  var $database_table = "active_sessions"; ## and find our session data in this table.
}

#class probind_CT_Split_Sql extends CT_Split_Sql {
#  var $database_class = "DB_probind";         ## Which database to connect...
#  var $database_table = "active_sessions_split"; ## and find our session data in this table.
#  var $split_length = 4096                    ## Split rows every 4096 bytes
#}

#class probind_CT_Shm extends CT_Shm {
#  var $max_sessions   = 500;               ## number of maximum sessions
#  var $shm_key        = 0x123754;          ## unique shm identifier
#  var $shm_size       = 64000;             ## size of segment
#}

#class probind_CT_Ldap extends CT_Ldap {
#   var $ldap_host = "localhost";
#   var $ldap_port = 389;
#   var $basedn    = "dc=your-domain, dc=com";
#   var $rootdn    = "cn=root, dc=your-domain, dc=com";
#   var $rootpw    = "secret";
#   var $objclass  = "phplibdata";
#}

#class probind_CT_Dbm extends CT_DBM {
#   var $dbm_file  = "must_exist.dbm";
#}

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

class probind_User extends User {
  var $classname = "probind_User";

  var $magic          = "Abracadabra";     ## ID seed
  var $that_class     = "probind_CT_Sql"; ## data storage container
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

	$username = $_POST["username"];
	$password = $_POST["password"];

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

  function auth_refreshlogin() {
    global $sess;
    global $_PHPLIB;

    include($_PHPLIB["libdir"] . "refreshloginform.ihtml");
  }

  function auth_registerform() {
    global $sess;
    global $_PHPLIB;

    include($_PHPLIB["libdir"] . "registrationform.ihtml");

  }

}

class probind_Auth_Reg extends probind_Auth {
  var $classname = "probind_Auth_Reg";
  var $mode      = "reg";
}


class probind_Default_Auth extends probind_Auth {
  var $classname = "probind_Default_Auth";
  
  var $nobody    = true;
}

class probind_Challenge_Auth extends Auth {
  var $classname      = "probind_Challenge_Auth";

  var $lifetime       =  1;

  var $magic          = "Simsalabim";  ## Challenge seed
  var $database_class = "DB_probind";
  var $database_table = "auth_user";

  function auth_loginform() {
    global $sess;
    global $challenge;
    global $_PHPLIB;
    
    $challenge = md5(uniqid($this->magic));
    $sess->register("challenge");
    
    include($_PHPLIB["libdir"] . "crloginform.ihtml");
  }
  
  function auth_validatelogin() {
    global $username, $password, $challenge, $response;

    if(isset($username)) {
      $this->auth["uname"]=$username;        ## This provides access for "loginform.ihtml"
    }
    $this->db->query(sprintf("select user_id,perms,password ".
                "from %s where username = '%s'",
                          $this->database_table,
                          addslashes($username)));

    while($this->db->next_record()) {
      $uid   = $this->db->f("user_id");
      $perm  = $this->db->f("perms");
      $pass  = $this->db->f("password");
    }
    $exspected_response = md5("$username:$pass:$challenge");

    ## True when JS is disabled
    if ($response == "") {
      if ($password != $pass) {
        return false;
      } else {
        $this->auth["perm"] = $perm;
        return $uid;
      }
    }
    
    ## Response is set, JS is enabled
    if ($exspected_response != $response) {
      return false;
    } else {
      $this->auth["perm"] = $perm;
      return $uid;
    }
  }
}

##
## probind_Challenge_Crypt_Auth: Keep passwords in md5 hashes rather 
##                           than cleartext in database
## Author: Jim Zajkowski <jim@jimz.com>

class probind_Challenge_Crypt_Auth extends Auth {
  var $classname      = "probind_Challenge_Crypt_Auth";

  var $lifetime       =  1;

  var $magic          = "Frobozzica";  ## Challenge seed
  var $database_class = "DB_probind";
  var $database_table = "auth_user_md5";

  function auth_loginform() {
    global $sess;
    global $challenge;
    
    $challenge = md5(uniqid($this->magic));
    $sess->register("challenge");
    
    include("crcloginform.ihtml");
  }
  
  function auth_validatelogin() {
    global $username, $password, $challenge, $response;

    $this->auth["uname"]=$username;        ## This provides access for "loginform.ihtml"
    
    $this->db->query(sprintf("select user_id,perms,password ".
                "from %s where username = '%s'",
                          $this->database_table,
                          addslashes($username)));

    while($this->db->next_record()) {
      $uid   = $this->db->f("user_id");
      $perm  = $this->db->f("perms");
      $pass  = $this->db->f("password");   ## Password is stored as a md5 hash
    }
    $exspected_response = md5("$username:$pass:$challenge");

    ## True when JS is disabled
    if ($response == "") {
      if (md5($password) != $pass) {       ## md5 hash for non-JavaScript browsers
        return false;
      } else {
        $this->auth["perm"] = $perm;
        return $uid;
      }
    }
    
    ## Response is set, JS is enabled
    if ($exspected_response != $response) {
      return false;
    } else {
      $this->auth["perm"] = $perm;
      return $uid;
    }
  }
}

class probind_Perm extends Perm {
  var $classname = "probind_Perm";
  
  }
}




?>
<?php

class annotationsform extends tpl_form {
  var $classname = "annotationsform";

  function setup_fields () {
    $this->form_data->add_element(array("type"=>"text","name"=>"zone","size"=>"11",
		"valid_regex"=>"^[0-9|\-]+$","valid_e"=>" must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>"class=textField","value"=>""));
    $this->form_data->add_element(array("type"=>"textarea","name"=>"descr","rows"=>"5","cols"=>"50",
		"extrahtml"=>"class=textField","value"=>""));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
    $this->form_data->add_element(array("type"=>"reset","name"=>"reset","value"=>"Reset"));
  }

  function show_image($field,$zone) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from annotations WHERE zone=".$zone.";";
        $fvq->query($sql);
        $fvq->next_record();
        //echo "<img src=\"".$fvq->f($field)."\">";
	echo "<img src=\"/".$fvq->f($field)."\">";
  }
  function show_image_href($field,$zone) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from annotations WHERE zone=".$zone.";";
        $fvq->query($sql);
        $fvq->next_record();
        if (strncmp($fvq->f($field),"image/",6)==0) {
	    echo "<a href=\"/".$fvq->f($field)."\">Click Here for a Larger Picture</a>";
	} else {
	    echo "n/a";
	}
  }
  function getblob($filename) {
	global $HTTP_POST_FILES;
        $gbq = new DB_probind;
        $tmpfname = "image/".$gbq->nextid("image").$HTTP_POST_FILES[$filename]['name'];
        if ( move_uploaded_file($HTTP_POST_FILES[$filename]['tmp_name'],"/var/www/probind/public_html/".$tmpfname) ) {
                return $tmpfname;
        } else {
                return "";
        }

  }
  function save_values() {
	global $cart, $submit, $qty, $euid, $zone,$descr;
	$svq = new DB_probind;
	$descr = addslashes($descr);

	if ( $zone ) { 
	    switch ( $submit ) {
		case "AddToCart":
		    $cart->add_item($zone,$qty,$euid);
		    break;
		case "Edit":
		    $sql = "UPDATE annotations SET ";
		    $sql .= "zone = '$zone',";
		    $sql .= "descr = '$descr'";
		    $sql .= " WHERE zone='$zone'\n";
		    EventLog("SQL - Edit",$sql);
		    $svq->query($sql);
		    break;
		case "Delete":
		    $sql = "DELETE FROM annotations WHERE zone=$zone";
		    EventLog("SQL - Delete",$sql);
		    $svq->query($sql);
		    break;
	    }
	} else {
	  if ( $submit == "Add" ) {
	    $sql = "INSERT INTO annotations (";
	    $sql .= "descr";
	    $sql .= ") VALUES (";
	    $sql .= "'$descr'";
	    $sql .= ")";
	    EventLog("SQL - Add",$sql);
	    $svq->query($sql);
            $svq->query("SELECT LAST_INSERT_ID()");
            $svq->next_record();
            $zone = $svq->f(0);
	  }
	}
	return $zone;
  }
  function find_values($zone) {
        if (! is_object($this->form_data)) {
          $this->setup();
        }
        $fvq = new DB_probind;
        $sql = "SELECT * from annotations WHERE zone='".$zone."';";
        $fvq->query($sql);
        $fvq->next_record();
        for (reset($this->form_data->elements); 
		$elrec = current($this->form_data->elements); 
		next($this->form_data->elements)) {
          $el = $elrec["ob"];
          $vn = $el->name;
          global $$vn;
          $$vn = $el->value;
          if ( $fvq->f($el->name) ) {
            if ( $el->multiple ) {
              $this->form_data->elements[$el->name]["ob"]->value = explode(",",$fvq->f($el->name));
            } else {
              $this->form_data->elements[$el->name]["ob"]->value = $fvq->f($el->name);
            }

          }
	  $$vn = $this->form_data->elements[$el->name]["ob"]->value;
        }
	$descr = stripslashes($descr);

  }
}
class annotationsTable extends Table {
  var $classname = "annotationsTable";

  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm;

        echo "<td class=btable><a href=\"".$sess->url('annotations.php').
                $sess->add_query(array("cmd"=>"View","zone"=>$data["zone"]))."\">view</a>";

  if ($perm) {
    if ($perm->have_perm("admin")) {
        echo " <a href=\"".$sess->url('annotations.php').
                $sess->add_query(array("cmd"=>"Edit","zone"=>$data["zone"]))."\">edit</a>";
        echo " <a href=\"".$sess->url('annotations.php').
                $sess->add_query(array("cmd"=>"Delete","zone"=>$data["zone"]))."\">delete</a>";
    }
  }
        echo "</td>";
  }
}
class annotations_Sql_Query extends Sql_Query {
  var $classname = "annotations_Sql_Query";
  var $primary_key = "zone";
}
?>
<?php

class auth_userform extends tpl_form {
  var $classname = "auth_userform";

  function setup_fields () {
    $this->form_data->add_element(array("type"=>"hidden","name"=>"user_id","size"=>"32"));
    $this->form_data->add_element(array("type"=>"text","name"=>"username","maxlength"=>"32","size"=>"32"
		,"minlength"=>1,"length_e"=>"username must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"password","maxlength"=>"32","size"=>"32"
		,"minlength"=>1,"length_e"=>"password must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"perms","maxlength"=>"255","size"=>"255","extrahtml"=>"class=textField"
		));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
    $this->form_data->add_element(array("type"=>"reset","name"=>"reset","value"=>"Reset"));
  }

  function show_image($field,$user_id) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from auth_user WHERE user_id=".$user_id.";";
        $fvq->query($sql);
        $fvq->next_record();
        //echo "<img src=\"".$fvq->f($field)."\">";
	echo "<img src=\"/".$fvq->f($field)."\">";
  }
  function show_image_href($field,$user_id) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from auth_user WHERE user_id=".$user_id.";";
        $fvq->query($sql);
        $fvq->next_record();
        if (strncmp($fvq->f($field),"image/",6)==0) {
	    echo "<a href=\"/".$fvq->f($field)."\">Click Here for a Larger Picture</a>";
	} else {
	    echo "n/a";
	}
  }
  function getblob($filename) {
	global $HTTP_POST_FILES;
        $gbq = new DB_probind;
        $tmpfname = "image/".$gbq->nextid("image").$HTTP_POST_FILES[$filename]['name'];
        if ( move_uploaded_file($HTTP_POST_FILES[$filename]['tmp_name'],"/var/www/probind/public_html/".$tmpfname) ) {
                return $tmpfname;
        } else {
                return "";
        }

  }
  function save_values() {
	global $cart, $submit, $qty, $euid, $user_id,$username,$password,$perms;
	$svq = new DB_probind;
	
	if ( $user_id ) { 
	    switch ( $submit ) {
		case "AddToCart":
		    $cart->add_item($user_id,$qty,$euid);
		    break;
		case "Edit":
		    $sql = "UPDATE auth_user SET ";
		    $sql .= "username = '$username',";
		    $sql .= "password = '$password',";
		    $sql .= "perms = '$perms'";
		    $sql .= " WHERE user_id='$user_id'\n";
		    EventLog("SQL - Edit",$sql);
		    $svq->query($sql);
		    break;
		case "Delete":
		    $sql = "DELETE FROM auth_user WHERE user_id=$user_id";
		    EventLog("SQL - Delete",$sql);
		    $svq->query($sql);
		    break;
	    }
	} else {
	  if ( $submit == "Add" ) {
	    $sql = "INSERT INTO auth_user (";
	    $sql .= "username,password,perms";
	    $sql .= ") VALUES (";
	    $sql .= "'$username','$password','$perms'";
	    $sql .= ")";
	    EventLog("SQL - Add",$sql);
	    $svq->query($sql);
            $svq->query("SELECT LAST_INSERT_ID()");
            $svq->next_record();
            $user_id = $svq->f(0);
	  }
	}
	return $user_id;
  }
  function find_values($user_id) {
        if (! is_object($this->form_data)) {
          $this->setup();
        }
        $fvq = new DB_probind;
        $sql = "SELECT * from auth_user WHERE user_id='".$user_id."';";
        $fvq->query($sql);
        $fvq->next_record();
        for (reset($this->form_data->elements); 
		$elrec = current($this->form_data->elements); 
		next($this->form_data->elements)) {
          $el = $elrec["ob"];
          $vn = $el->name;
          global $$vn;
          $$vn = $el->value;
          if ( $fvq->f($el->name) ) {
            if ( $el->multiple ) {
              $this->form_data->elements[$el->name]["ob"]->value = explode(",",$fvq->f($el->name));
            } else {
              $this->form_data->elements[$el->name]["ob"]->value = $fvq->f($el->name);
            }

          }
	  $$vn = $this->form_data->elements[$el->name]["ob"]->value;
        }
	
  }
}
class auth_userTable extends Table {
  var $classname = "auth_userTable";

  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm;

        echo "<td class=btable><a href=\"".$sess->url('auth_user.php').
                $sess->add_query(array("cmd"=>"View","user_id"=>$data["user_id"]))."\">view</a>";

  if ($perm) {
    if ($perm->have_perm("admin")) {
        echo " <a href=\"".$sess->url('auth_user.php').
                $sess->add_query(array("cmd"=>"Edit","user_id"=>$data["user_id"]))."\">edit</a>";
        echo " <a href=\"".$sess->url('auth_user.php').
                $sess->add_query(array("cmd"=>"Delete","user_id"=>$data["user_id"]))."\">delete</a>";
    }
  }
        echo "</td>";
  }
}
class auth_user_Sql_Query extends Sql_Query {
  var $classname = "auth_user_Sql_Query";
  var $primary_key = "user_id";
}
?>
<?php

class blackboardform extends tpl_form {
  var $classname = "blackboardform";

  function setup_fields () {
    $this->form_data->add_element(array("type"=>"hidden","name"=>"name","size"=>"32"));
    $this->form_data->add_element(array("type"=>"text","name"=>"value","maxlength"=>"255","size"=>"255"
		,"minlength"=>1,"length_e"=>"value must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"ctime","maxlength"=>"","size"=>"","extrahtml"=>"class=textField"
		,"valid_e"=>"Invalid Date","valid_regex"=>"nulltimestamp","value"=>"CURRENT_TIMESTAMP"));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
    $this->form_data->add_element(array("type"=>"reset","name"=>"reset","value"=>"Reset"));
  }

  function show_image($field,$name) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from blackboard WHERE name=".$name.";";
        $fvq->query($sql);
        $fvq->next_record();
        //echo "<img src=\"".$fvq->f($field)."\">";
	echo "<img src=\"/".$fvq->f($field)."\">";
  }
  function show_image_href($field,$name) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from blackboard WHERE name=".$name.";";
        $fvq->query($sql);
        $fvq->next_record();
        if (strncmp($fvq->f($field),"image/",6)==0) {
	    echo "<a href=\"/".$fvq->f($field)."\">Click Here for a Larger Picture</a>";
	} else {
	    echo "n/a";
	}
  }
  function getblob($filename) {
	global $HTTP_POST_FILES;
        $gbq = new DB_probind;
        $tmpfname = "image/".$gbq->nextid("image").$HTTP_POST_FILES[$filename]['name'];
        if ( move_uploaded_file($HTTP_POST_FILES[$filename]['tmp_name'],"/var/www/probind/public_html/".$tmpfname) ) {
                return $tmpfname;
        } else {
                return "";
        }

  }
  function save_values() {
	global $cart, $submit, $qty, $euid, $name,$value,$ctime;
	$svq = new DB_probind;
	
	if ( $name ) { 
	    switch ( $submit ) {
		case "AddToCart":
		    $cart->add_item($name,$qty,$euid);
		    break;
		case "Edit":
		    $sql = "UPDATE blackboard SET ";
		    $sql .= "value = '$value',";
		    $sql .= "ctime = '$ctime'";
		    $sql .= " WHERE name='$name'\n";
		    EventLog("SQL - Edit",$sql);
		    $svq->query($sql);
		    break;
		case "Delete":
		    $sql = "DELETE FROM blackboard WHERE name=$name";
		    EventLog("SQL - Delete",$sql);
		    $svq->query($sql);
		    break;
	    }
	} else {
	  if ( $submit == "Add" ) {
	    $sql = "INSERT INTO blackboard (";
	    $sql .= "value,ctime";
	    $sql .= ") VALUES (";
	    $sql .= "'$value','$ctime'";
	    $sql .= ")";
	    EventLog("SQL - Add",$sql);
	    $svq->query($sql);
            $svq->query("SELECT LAST_INSERT_ID()");
            $svq->next_record();
            $name = $svq->f(0);
	  }
	}
	return $name;
  }
  function find_values($name) {
        if (! is_object($this->form_data)) {
          $this->setup();
        }
        $fvq = new DB_probind;
        $sql = "SELECT * from blackboard WHERE name='".$name."';";
        $fvq->query($sql);
        $fvq->next_record();
        for (reset($this->form_data->elements); 
		$elrec = current($this->form_data->elements); 
		next($this->form_data->elements)) {
          $el = $elrec["ob"];
          $vn = $el->name;
          global $$vn;
          $$vn = $el->value;
          if ( $fvq->f($el->name) ) {
            if ( $el->multiple ) {
              $this->form_data->elements[$el->name]["ob"]->value = explode(",",$fvq->f($el->name));
            } else {
              $this->form_data->elements[$el->name]["ob"]->value = $fvq->f($el->name);
            }

          }
	  $$vn = $this->form_data->elements[$el->name]["ob"]->value;
        }
	
  }
}
class blackboardTable extends Table {
  var $classname = "blackboardTable";

  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm;

        echo "<td class=btable><a href=\"".$sess->url('blackboard.php').
                $sess->add_query(array("cmd"=>"View","name"=>$data["name"]))."\">view</a>";

  if ($perm) {
    if ($perm->have_perm("admin")) {
        echo " <a href=\"".$sess->url('blackboard.php').
                $sess->add_query(array("cmd"=>"Edit","name"=>$data["name"]))."\">edit</a>";
        echo " <a href=\"".$sess->url('blackboard.php').
                $sess->add_query(array("cmd"=>"Delete","name"=>$data["name"]))."\">delete</a>";
    }
  }
        echo "</td>";
  }
}
class blackboard_Sql_Query extends Sql_Query {
  var $classname = "blackboard_Sql_Query";
  var $primary_key = "name";
}
?>
<?php

class deleted_domainsform extends tpl_form {
  var $classname = "deleted_domainsform";

  function setup_fields () {
    $this->form_data->add_element(array("type"=>"hidden","name"=>"domain","size"=>"100"));
    $this->form_data->add_element(array("type"=>"text","name"=>"zonefile","maxlength"=>"80","size"=>"80"
		,"minlength"=>1,"length_e"=>"zonefile must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"owner","maxlength"=>"32","size"=>"32"
		,"minlength"=>1,"length_e"=>"owner must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
    $this->form_data->add_element(array("type"=>"reset","name"=>"reset","value"=>"Reset"));
  }

  function show_image($field,$domain) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from deleted_domains WHERE domain=".$domain.";";
        $fvq->query($sql);
        $fvq->next_record();
        //echo "<img src=\"".$fvq->f($field)."\">";
	echo "<img src=\"/".$fvq->f($field)."\">";
  }
  function show_image_href($field,$domain) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from deleted_domains WHERE domain=".$domain.";";
        $fvq->query($sql);
        $fvq->next_record();
        if (strncmp($fvq->f($field),"image/",6)==0) {
	    echo "<a href=\"/".$fvq->f($field)."\">Click Here for a Larger Picture</a>";
	} else {
	    echo "n/a";
	}
  }
  function getblob($filename) {
	global $HTTP_POST_FILES;
        $gbq = new DB_probind;
        $tmpfname = "image/".$gbq->nextid("image").$HTTP_POST_FILES[$filename]['name'];
        if ( move_uploaded_file($HTTP_POST_FILES[$filename]['tmp_name'],"/var/www/probind/public_html/".$tmpfname) ) {
                return $tmpfname;
        } else {
                return "";
        }

  }
  function save_values() {
	global $cart, $submit, $qty, $euid, $domain,$zonefile,$owner;
	$svq = new DB_probind;
	
	if ( $domain ) { 
	    switch ( $submit ) {
		case "AddToCart":
		    $cart->add_item($domain,$qty,$euid);
		    break;
		case "Edit":
		    $sql = "UPDATE deleted_domains SET ";
		    $sql .= "zonefile = '$zonefile',";
		    $sql .= "owner = '$owner'";
		    $sql .= " WHERE domain='$domain'\n";
		    EventLog("SQL - Edit",$sql);
		    $svq->query($sql);
		    break;
		case "Delete":
		    $sql = "DELETE FROM deleted_domains WHERE domain=$domain";
		    EventLog("SQL - Delete",$sql);
		    $svq->query($sql);
		    break;
	    }
	} else {
	  if ( $submit == "Add" ) {
	    $sql = "INSERT INTO deleted_domains (";
	    $sql .= "zonefile,owner";
	    $sql .= ") VALUES (";
	    $sql .= "'$zonefile','$owner'";
	    $sql .= ")";
	    EventLog("SQL - Add",$sql);
	    $svq->query($sql);
            $svq->query("SELECT LAST_INSERT_ID()");
            $svq->next_record();
            $domain = $svq->f(0);
	  }
	}
	return $domain;
  }
  function find_values($domain) {
        if (! is_object($this->form_data)) {
          $this->setup();
        }
        $fvq = new DB_probind;
        $sql = "SELECT * from deleted_domains WHERE domain='".$domain."';";
        $fvq->query($sql);
        $fvq->next_record();
        for (reset($this->form_data->elements); 
		$elrec = current($this->form_data->elements); 
		next($this->form_data->elements)) {
          $el = $elrec["ob"];
          $vn = $el->name;
          global $$vn;
          $$vn = $el->value;
          if ( $fvq->f($el->name) ) {
            if ( $el->multiple ) {
              $this->form_data->elements[$el->name]["ob"]->value = explode(",",$fvq->f($el->name));
            } else {
              $this->form_data->elements[$el->name]["ob"]->value = $fvq->f($el->name);
            }

          }
	  $$vn = $this->form_data->elements[$el->name]["ob"]->value;
        }
	
  }
}
class deleted_domainsTable extends Table {
  var $classname = "deleted_domainsTable";

  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm;

        echo "<td class=btable><a href=\"".$sess->url('deleted_domains.php').
                $sess->add_query(array("cmd"=>"View","domain"=>$data["domain"]))."\">view</a>";

  if ($perm) {
    if ($perm->have_perm("admin")) {
        echo " <a href=\"".$sess->url('deleted_domains.php').
                $sess->add_query(array("cmd"=>"Edit","domain"=>$data["domain"]))."\">edit</a>";
        echo " <a href=\"".$sess->url('deleted_domains.php').
                $sess->add_query(array("cmd"=>"Delete","domain"=>$data["domain"]))."\">delete</a>";
    }
  }
        echo "</td>";
  }
}
class deleted_domains_Sql_Query extends Sql_Query {
  var $classname = "deleted_domains_Sql_Query";
  var $primary_key = "domain";
}
?>
<?php

class recordsform extends tpl_form {
  var $classname = "recordsform";

  function setup_fields () {
    $this->form_data->add_element(array("type"=>"hidden","name"=>"id","size"=>"11"));
    $this->form_data->add_element(array("type"=>"text","name"=>"zone","size"=>"11",
		"valid_regex"=>"^[0-9|\-]+$","valid_e"=>"zone must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>"class=textField","value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"domain","maxlength"=>"100","size"=>"100"
		,"minlength"=>1,"length_e"=>"domain must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"ttl","maxlength"=>"15","size"=>"15","extrahtml"=>"class=textField"
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"type","maxlength"=>"10","size"=>"10"
		,"minlength"=>1,"length_e"=>"type must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"pref","maxlength"=>"5","size"=>"5","extrahtml"=>"class=textField"
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"data","maxlength"=>"255","size"=>"255"
		,"minlength"=>1,"length_e"=>"data must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"mtime","maxlength"=>"","size"=>"","extrahtml"=>"class=textField"
		,"valid_e"=>"Invalid Date","valid_regex"=>"nulltimestamp","value"=>"CURRENT_TIMESTAMP"));
    $this->form_data->add_element(array("type"=>"text","name"=>"ctime","maxlength"=>"","size"=>"","extrahtml"=>"class=textField"
		,"valid_e"=>"Invalid Date","valid_regex"=>"nulltimestamp","value"=>"0000-00-00 00:00:00"));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
    $this->form_data->add_element(array("type"=>"reset","name"=>"reset","value"=>"Reset"));
  }

  function show_image($field,$id) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from records WHERE id=".$id.";";
        $fvq->query($sql);
        $fvq->next_record();
        //echo "<img src=\"".$fvq->f($field)."\">";
	echo "<img src=\"/".$fvq->f($field)."\">";
  }
  function show_image_href($field,$id) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from records WHERE id=".$id.";";
        $fvq->query($sql);
        $fvq->next_record();
        if (strncmp($fvq->f($field),"image/",6)==0) {
	    echo "<a href=\"/".$fvq->f($field)."\">Click Here for a Larger Picture</a>";
	} else {
	    echo "n/a";
	}
  }
  function getblob($filename) {
	global $HTTP_POST_FILES;
        $gbq = new DB_probind;
        $tmpfname = "image/".$gbq->nextid("image").$HTTP_POST_FILES[$filename]['name'];
        if ( move_uploaded_file($HTTP_POST_FILES[$filename]['tmp_name'],"/var/www/probind/public_html/".$tmpfname) ) {
                return $tmpfname;
        } else {
                return "";
        }

  }
  function save_values() {
	global $cart, $submit, $qty, $euid, $id,$zone,$domain,$ttl,$type,$pref,$data,$mtime,$ctime;
	$svq = new DB_probind;
	
	if ( $id ) { 
	    switch ( $submit ) {
		case "AddToCart":
		    $cart->add_item($id,$qty,$euid);
		    break;
		case "Edit":
		    $sql = "UPDATE records SET ";
		    $sql .= "zone = '$zone',";
		    $sql .= "domain = '$domain',";
		    $sql .= "ttl = '$ttl',";
		    $sql .= "type = '$type',";
		    $sql .= "pref = '$pref',";
		    $sql .= "data = '$data',";
		    $sql .= "mtime = '$mtime',";
		    $sql .= "ctime = '$ctime'";
		    $sql .= " WHERE id='$id'\n";
		    EventLog("SQL - Edit",$sql);
		    $svq->query($sql);
		    break;
		case "Delete":
		    $sql = "DELETE FROM records WHERE id=$id";
		    EventLog("SQL - Delete",$sql);
		    $svq->query($sql);
		    break;
	    }
	} else {
	  if ( $submit == "Add" ) {
	    $sql = "INSERT INTO records (";
	    $sql .= "zone,domain,ttl,type,pref,data,mtime,ctime";
	    $sql .= ") VALUES (";
	    $sql .= "'$zone','$domain','$ttl','$type','$pref','$data','$mtime','$ctime'";
	    $sql .= ")";
	    EventLog("SQL - Add",$sql);
	    $svq->query($sql);
            $svq->query("SELECT LAST_INSERT_ID()");
            $svq->next_record();
            $id = $svq->f(0);
	  }
	}
	return $id;
  }
  function find_values($id) {
        if (! is_object($this->form_data)) {
          $this->setup();
        }
        $fvq = new DB_probind;
        $sql = "SELECT * from records WHERE id='".$id."';";
        $fvq->query($sql);
        $fvq->next_record();
        for (reset($this->form_data->elements); 
		$elrec = current($this->form_data->elements); 
		next($this->form_data->elements)) {
          $el = $elrec["ob"];
          $vn = $el->name;
          global $$vn;
          $$vn = $el->value;
          if ( $fvq->f($el->name) ) {
            if ( $el->multiple ) {
              $this->form_data->elements[$el->name]["ob"]->value = explode(",",$fvq->f($el->name));
            } else {
              $this->form_data->elements[$el->name]["ob"]->value = $fvq->f($el->name);
            }

          }
	  $$vn = $this->form_data->elements[$el->name]["ob"]->value;
        }
	
  }
}
class recordsTable extends Table {
  var $classname = "recordsTable";

  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm;

        echo "<td class=btable><a href=\"".$sess->url('records.php').
                $sess->add_query(array("cmd"=>"View","id"=>$data["id"]))."\">view</a>";

  if ($perm) {
    if ($perm->have_perm("admin")) {
        echo " <a href=\"".$sess->url('records.php').
                $sess->add_query(array("cmd"=>"Edit","id"=>$data["id"]))."\">edit</a>";
        echo " <a href=\"".$sess->url('records.php').
                $sess->add_query(array("cmd"=>"Delete","id"=>$data["id"]))."\">delete</a>";
    }
  }
        echo "</td>";
  }
}
class records_Sql_Query extends Sql_Query {
  var $classname = "records_Sql_Query";
  var $primary_key = "id";
}
?>
<?php

class serversform extends tpl_form {
  var $classname = "serversform";

  function setup_fields () {
    $this->form_data->add_element(array("type"=>"hidden","name"=>"id","size"=>"11"));
    $this->form_data->add_element(array("type"=>"text","name"=>"hostname","maxlength"=>"200","size"=>"200"
		,"minlength"=>1,"length_e"=>"hostname must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"ipno","maxlength"=>"15","size"=>"15"
		,"minlength"=>1,"length_e"=>"ipno must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"type","maxlength"=>"1","size"=>"1"
		,"minlength"=>1,"length_e"=>"type must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"pushupdates","size"=>"1",
		"valid_regex"=>"^[0-9|\-]+$","valid_e"=>"pushupdates must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>"class=textField","value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"mknsrec","size"=>"1",
		"valid_regex"=>"^[0-9|\-]+$","valid_e"=>"mknsrec must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>"class=textField","value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"zonedir","maxlength"=>"255","size"=>"255"
		,"minlength"=>1,"length_e"=>"zonedir must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"template","maxlength"=>"255","size"=>"255"
		,"minlength"=>1,"length_e"=>"template must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"script","maxlength"=>"255","size"=>"255"
		,"minlength"=>1,"length_e"=>"script must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"textarea","name"=>"descr","rows"=>"5","cols"=>"50",
		"extrahtml"=>"class=textField"));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
    $this->form_data->add_element(array("type"=>"reset","name"=>"reset","value"=>"Reset"));
  }

  function show_image($field,$id) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from servers WHERE id=".$id.";";
        $fvq->query($sql);
        $fvq->next_record();
        //echo "<img src=\"".$fvq->f($field)."\">";
	echo "<img src=\"/".$fvq->f($field)."\">";
  }
  function show_image_href($field,$id) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from servers WHERE id=".$id.";";
        $fvq->query($sql);
        $fvq->next_record();
        if (strncmp($fvq->f($field),"image/",6)==0) {
	    echo "<a href=\"/".$fvq->f($field)."\">Click Here for a Larger Picture</a>";
	} else {
	    echo "n/a";
	}
  }
  function getblob($filename) {
	global $HTTP_POST_FILES;
        $gbq = new DB_probind;
        $tmpfname = "image/".$gbq->nextid("image").$HTTP_POST_FILES[$filename]['name'];
        if ( move_uploaded_file($HTTP_POST_FILES[$filename]['tmp_name'],"/var/www/probind/public_html/".$tmpfname) ) {
                return $tmpfname;
        } else {
                return "";
        }

  }
  function save_values() {
	global $cart, $submit, $qty, $euid, $id,$hostname,$ipno,$type,$pushupdates,$mknsrec,$zonedir,$template,$script,$descr;
	$svq = new DB_probind;
	$descr = addslashes($descr);

	if ( $id ) { 
	    switch ( $submit ) {
		case "AddToCart":
		    $cart->add_item($id,$qty,$euid);
		    break;
		case "Edit":
		    $sql = "UPDATE servers SET ";
		    $sql .= "hostname = '$hostname',";
		    $sql .= "ipno = '$ipno',";
		    $sql .= "type = '$type',";
		    $sql .= "pushupdates = '$pushupdates',";
		    $sql .= "mknsrec = '$mknsrec',";
		    $sql .= "zonedir = '$zonedir',";
		    $sql .= "template = '$template',";
		    $sql .= "script = '$script',";
		    $sql .= "descr = '$descr'";
		    $sql .= " WHERE id='$id'\n";
		    EventLog("SQL - Edit",$sql);
		    $svq->query($sql);
		    break;
		case "Delete":
		    $sql = "DELETE FROM servers WHERE id=$id";
		    EventLog("SQL - Delete",$sql);
		    $svq->query($sql);
		    break;
	    }
	} else {
	  if ( $submit == "Add" ) {
	    $sql = "INSERT INTO servers (";
	    $sql .= "hostname,ipno,type,pushupdates,mknsrec,zonedir,template,script,descr";
	    $sql .= ") VALUES (";
	    $sql .= "'$hostname','$ipno','$type','$pushupdates','$mknsrec','$zonedir','$template','$script','$descr'";
	    $sql .= ")";
	    EventLog("SQL - Add",$sql);
	    $svq->query($sql);
            $svq->query("SELECT LAST_INSERT_ID()");
            $svq->next_record();
            $id = $svq->f(0);
	  }
	}
	return $id;
  }
  function find_values($id) {
        if (! is_object($this->form_data)) {
          $this->setup();
        }
        $fvq = new DB_probind;
        $sql = "SELECT * from servers WHERE id='".$id."';";
        $fvq->query($sql);
        $fvq->next_record();
        for (reset($this->form_data->elements); 
		$elrec = current($this->form_data->elements); 
		next($this->form_data->elements)) {
          $el = $elrec["ob"];
          $vn = $el->name;
          global $$vn;
          $$vn = $el->value;
          if ( $fvq->f($el->name) ) {
            if ( $el->multiple ) {
              $this->form_data->elements[$el->name]["ob"]->value = explode(",",$fvq->f($el->name));
            } else {
              $this->form_data->elements[$el->name]["ob"]->value = $fvq->f($el->name);
            }

          }
	  $$vn = $this->form_data->elements[$el->name]["ob"]->value;
        }
	$descr = stripslashes($descr);

  }
}
class serversTable extends Table {
  var $classname = "serversTable";

  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm;

        echo "<td class=btable><a href=\"".$sess->url('servers.php').
                $sess->add_query(array("cmd"=>"View","id"=>$data["id"]))."\">view</a>";

  if ($perm) {
    if ($perm->have_perm("admin")) {
        echo " <a href=\"".$sess->url('servers.php').
                $sess->add_query(array("cmd"=>"Edit","id"=>$data["id"]))."\">edit</a>";
        echo " <a href=\"".$sess->url('servers.php').
                $sess->add_query(array("cmd"=>"Delete","id"=>$data["id"]))."\">delete</a>";
    }
  }
        echo "</td>";
  }
}
class servers_Sql_Query extends Sql_Query {
  var $classname = "servers_Sql_Query";
  var $primary_key = "id";
}
?>
<?php

class typesortform extends tpl_form {
  var $classname = "typesortform";

  function setup_fields () {
    $this->form_data->add_element(array("type"=>"text","name"=>"type","maxlength"=>"10","size"=>"10"
		,"minlength"=>1,"length_e"=>"type must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"ord","size"=>"2",
		"valid_regex"=>"^[0-9|\-]+$","valid_e"=>"ord must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>"class=textField","value"=>""));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
    $this->form_data->add_element(array("type"=>"reset","name"=>"reset","value"=>"Reset"));
  }

  function show_image($field,$id) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from typesort WHERE id=".$id.";";
        $fvq->query($sql);
        $fvq->next_record();
        //echo "<img src=\"".$fvq->f($field)."\">";
	echo "<img src=\"/".$fvq->f($field)."\">";
  }
  function show_image_href($field,$id) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from typesort WHERE id=".$id.";";
        $fvq->query($sql);
        $fvq->next_record();
        if (strncmp($fvq->f($field),"image/",6)==0) {
	    echo "<a href=\"/".$fvq->f($field)."\">Click Here for a Larger Picture</a>";
	} else {
	    echo "n/a";
	}
  }
  function getblob($filename) {
	global $HTTP_POST_FILES;
        $gbq = new DB_probind;
        $tmpfname = "image/".$gbq->nextid("image").$HTTP_POST_FILES[$filename]['name'];
        if ( move_uploaded_file($HTTP_POST_FILES[$filename]['tmp_name'],"/var/www/probind/public_html/".$tmpfname) ) {
                return $tmpfname;
        } else {
                return "";
        }

  }
  function save_values() {
	global $cart, $submit, $qty, $euid, $type,$ord;
	$svq = new DB_probind;
	
	if ( $id ) { 
	    switch ( $submit ) {
		case "AddToCart":
		    $cart->add_item($id,$qty,$euid);
		    break;
		case "Edit":
		    $sql = "UPDATE typesort SET ";
		    $sql .= "type = '$type',";
		    $sql .= "ord = '$ord'";
		    $sql .= " WHERE id='$id'\n";
		    EventLog("SQL - Edit",$sql);
		    $svq->query($sql);
		    break;
		case "Delete":
		    $sql = "DELETE FROM typesort WHERE id=$id";
		    EventLog("SQL - Delete",$sql);
		    $svq->query($sql);
		    break;
	    }
	} else {
	  if ( $submit == "Add" ) {
	    $sql = "INSERT INTO typesort (";
	    $sql .= "ord";
	    $sql .= ") VALUES (";
	    $sql .= "'$type''$ord'";
	    $sql .= ")";
	    EventLog("SQL - Add",$sql);
	    $svq->query($sql);
            $svq->query("SELECT LAST_INSERT_ID()");
            $svq->next_record();
            $id = $svq->f(0);
	  }
	}
	return $id;
  }
  function find_values($id) {
        if (! is_object($this->form_data)) {
          $this->setup();
        }
        $fvq = new DB_probind;
        $sql = "SELECT * from typesort WHERE id='".$id."';";
        $fvq->query($sql);
        $fvq->next_record();
        for (reset($this->form_data->elements); 
		$elrec = current($this->form_data->elements); 
		next($this->form_data->elements)) {
          $el = $elrec["ob"];
          $vn = $el->name;
          global $$vn;
          $$vn = $el->value;
          if ( $fvq->f($el->name) ) {
            if ( $el->multiple ) {
              $this->form_data->elements[$el->name]["ob"]->value = explode(",",$fvq->f($el->name));
            } else {
              $this->form_data->elements[$el->name]["ob"]->value = $fvq->f($el->name);
            }

          }
	  $$vn = $this->form_data->elements[$el->name]["ob"]->value;
        }
	
  }
}
class typesortTable extends Table {
  var $classname = "typesortTable";

  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm;

        echo "<td class=btable><a href=\"".$sess->url('typesort.php').
                $sess->add_query(array("cmd"=>"View","id"=>$data["id"]))."\">view</a>";

  if ($perm) {
    if ($perm->have_perm("admin")) {
        echo " <a href=\"".$sess->url('typesort.php').
                $sess->add_query(array("cmd"=>"Edit","id"=>$data["id"]))."\">edit</a>";
        echo " <a href=\"".$sess->url('typesort.php').
                $sess->add_query(array("cmd"=>"Delete","id"=>$data["id"]))."\">delete</a>";
    }
  }
        echo "</td>";
  }
}
class typesort_Sql_Query extends Sql_Query {
  var $classname = "typesort_Sql_Query";
  var $primary_key = "id";
}
?>
<?php

class zonesform extends tpl_form {
  var $classname = "zonesform";

  function setup_fields () {
    $this->form_data->add_element(array("type"=>"hidden","name"=>"id","size"=>"11"));
    $this->form_data->add_element(array("type"=>"text","name"=>"domain","maxlength"=>"100","size"=>"100"
		,"minlength"=>1,"length_e"=>"domain must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"serial","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$|^$","valid_e"=>"serial must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>"class=textField"));
    $this->form_data->add_element(array("type"=>"text","name"=>"refresh","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$|^$","valid_e"=>"refresh must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>"class=textField"));
    $this->form_data->add_element(array("type"=>"text","name"=>"retry","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$|^$","valid_e"=>"retry must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>"class=textField"));
    $this->form_data->add_element(array("type"=>"text","name"=>"expire","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$|^$","valid_e"=>"expire must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>"class=textField"));
    $this->form_data->add_element(array("type"=>"text","name"=>"master","maxlength"=>"15","size"=>"15"
		,"minlength"=>1,"length_e"=>"master must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"zonefile","maxlength"=>"80","size"=>"80"
		,"minlength"=>1,"length_e"=>"zonefile must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"mtime","maxlength"=>"","size"=>"","extrahtml"=>"class=textField"
		,"valid_e"=>"Invalid Date","valid_regex"=>"nulltimestamp","value"=>"CURRENT_TIMESTAMP"));
    $this->form_data->add_element(array("type"=>"text","name"=>"ctime","maxlength"=>"","size"=>"","extrahtml"=>"class=textField"
		,"valid_e"=>"Invalid Date","valid_regex"=>"nulltimestamp","value"=>"0000-00-00 00:00:00"));
    $this->form_data->add_element(array("type"=>"text","name"=>"updated","size"=>"11",
		"valid_regex"=>"^[0-9|\-]+$|^$","valid_e"=>"updated must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>"class=textField","value"=>"0"));
    $this->form_data->add_element(array("type"=>"text","name"=>"owner","maxlength"=>"32","size"=>"32"
		,"minlength"=>1,"length_e"=>"owner must not be blank.  Required field.","extrahtml"=>"class=textField"
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
    $this->form_data->add_element(array("type"=>"reset","name"=>"reset","value"=>"Reset"));
  }

  function show_image($field,$id) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from zones WHERE id=".$id.";";
        $fvq->query($sql);
        $fvq->next_record();
        //echo "<img src=\"".$fvq->f($field)."\">";
	echo "<img src=\"/".$fvq->f($field)."\">";
  }
  function show_image_href($field,$id) {
        $fvq = new DB_probind;
        $sql = "SELECT ".$field." from zones WHERE id=".$id.";";
        $fvq->query($sql);
        $fvq->next_record();
        if (strncmp($fvq->f($field),"image/",6)==0) {
	    echo "<a href=\"/".$fvq->f($field)."\">Click Here for a Larger Picture</a>";
	} else {
	    echo "n/a";
	}
  }
  function getblob($filename) {
	global $HTTP_POST_FILES;
        $gbq = new DB_probind;
        $tmpfname = "image/".$gbq->nextid("image").$HTTP_POST_FILES[$filename]['name'];
        if ( move_uploaded_file($HTTP_POST_FILES[$filename]['tmp_name'],"/var/www/probind/public_html/".$tmpfname) ) {
                return $tmpfname;
        } else {
                return "";
        }

  }
  function save_values() {
	global $cart, $submit, $qty, $euid, $id,$domain,$serial,$refresh,$retry,$expire,$master,$zonefile,$mtime,$ctime,$updated,$owner;
	$svq = new DB_probind;
	
	if ( $id ) { 
	    switch ( $submit ) {
		case "AddToCart":
		    $cart->add_item($id,$qty,$euid);
		    break;
		case "Edit":
		    $sql = "UPDATE zones SET ";
		    $sql .= "domain = '$domain',";
		    $sql .= "serial = '$serial',";
		    $sql .= "refresh = '$refresh',";
		    $sql .= "retry = '$retry',";
		    $sql .= "expire = '$expire',";
		    $sql .= "master = '$master',";
		    $sql .= "zonefile = '$zonefile',";
		    $sql .= "mtime = '$mtime',";
		    $sql .= "ctime = '$ctime',";
		    $sql .= "updated = '$updated',";
		    $sql .= "owner = '$owner'";
		    $sql .= " WHERE id='$id'\n";
		    EventLog("SQL - Edit",$sql);
		    $svq->query($sql);
		    break;
		case "Delete":
		    $sql = "DELETE FROM zones WHERE id=$id";
		    EventLog("SQL - Delete",$sql);
		    $svq->query($sql);
		    break;
	    }
	} else {
	  if ( $submit == "Add" ) {
	    $sql = "INSERT INTO zones (";
	    $sql .= "domain,serial,refresh,retry,expire,master,zonefile,mtime,ctime,updated,owner";
	    $sql .= ") VALUES (";
	    $sql .= "'$domain','$serial','$refresh','$retry','$expire','$master','$zonefile','$mtime','$ctime','$updated','$owner'";
	    $sql .= ")";
	    EventLog("SQL - Add",$sql);
	    $svq->query($sql);
            $svq->query("SELECT LAST_INSERT_ID()");
            $svq->next_record();
            $id = $svq->f(0);
	  }
	}
	return $id;
  }
  function find_values($id) {
        if (! is_object($this->form_data)) {
          $this->setup();
        }
        $fvq = new DB_probind;
        $sql = "SELECT * from zones WHERE id='".$id."';";
        $fvq->query($sql);
        $fvq->next_record();
        for (reset($this->form_data->elements); 
		$elrec = current($this->form_data->elements); 
		next($this->form_data->elements)) {
          $el = $elrec["ob"];
          $vn = $el->name;
          global $$vn;
          $$vn = $el->value;
          if ( $fvq->f($el->name) ) {
            if ( $el->multiple ) {
              $this->form_data->elements[$el->name]["ob"]->value = explode(",",$fvq->f($el->name));
            } else {
              $this->form_data->elements[$el->name]["ob"]->value = $fvq->f($el->name);
            }

          }
	  $$vn = $this->form_data->elements[$el->name]["ob"]->value;
        }
	
  }
}
class zonesTable extends Table {
  var $classname = "zonesTable";

  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm;

        echo "<td class=btable><a href=\"".$sess->url('zones.php').
                $sess->add_query(array("cmd"=>"View","id"=>$data["id"]))."\">view</a>";

  if ($perm) {
    if ($perm->have_perm("admin")) {
        echo " <a href=\"".$sess->url('zones.php').
                $sess->add_query(array("cmd"=>"Edit","id"=>$data["id"]))."\">edit</a>";
        echo " <a href=\"".$sess->url('zones.php').
                $sess->add_query(array("cmd"=>"Delete","id"=>$data["id"]))."\">delete</a>";
    }
  }
        echo "</td>";
  }
}
class zones_Sql_Query extends Sql_Query {
  var $classname = "zones_Sql_Query";
  var $primary_key = "id";
}
?>
