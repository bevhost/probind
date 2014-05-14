<?php
include('phplib/prepend.php');
include('inc/config.php');
page_open(array("sess"=>"probind_Session"));
$widemode="";
$sess->that->db->Record = false;

foreach($sess->pt as $thing=>$val) {  # forget everything stored in our session
        switch($thing) {
                case "cart":    /* except these */
                case "auth":
                        break;
                default:
                        #echo "<br>Unsetting $thing";
                        unset($$thing);
                        unset($sess->pt[$thing]);
        }
}
if (is_object($auth)) $auth->logout();
page_close();
header("Location:index.php");
?>
