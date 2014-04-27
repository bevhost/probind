<?php

include("inc/checkperm.inc");
get_request_values("id,cmd,submit,rowcount,sortorder,sortdesc,startingwith,start,prev,next,last,cond,EditMode,WithSelected,widemode,Field,_http_referer,export_results");
$orig_cmd=$cmd;


$f = new EventLogform;

$db = new DB_probind;
$db->ConnectFlag = MYSQL_CLIENT_INTERACTIVE;

class MyEventLogTable extends EventLogTable {
	var $classname="MyEventLogTable";

  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm;
                    
        echo "<td class=btable><a href=\"".$sess->url('EventLog.php').
                $sess->add_query(array("cmd"=>"View","id"=>$data["id"]))."\">view</a>";
                    
	if (isset($data["ExtraInfo"])) {
	    if (strlen($data["ExtraInfo"])<300) {
		echo " <a href=\"javascript:alert('";
		echo htmlspecialchars(str_replace("\n","\\n",str_replace("'","`",$data["ExtraInfo"])),ENT_QUOTES,"UTF-8");
		echo "');\">extra</a>";
	    }
	}
        echo "</td>";
  }
}

if ($submit) {
  switch ($submit) {

   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if (isset($auth)) {
     check_edit_perms();
     if (!$f->validate($result)) {
        $cmd = $submit;
        echo "<font class=bigTextBold>$cmd Event Log</font>\n";
        $f->display();
        page_close();
        exit;
     }
     else
     {
        echo "Saving....";
        $f->save_values();
        echo "<b>Done!</b><br>\n";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to EventLog.</a><br>\n";
        page_close();
        exit;
     }
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
   case "View":
   case "Back":
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to EventLog.</a><br>\n";
        page_close();
        exit;

   case "Delete":
    if (isset($auth)) {
	check_edit_perms();
        echo "Deleting....";
        $f->save_values();
        echo "<b>Done!</b><br>\n";
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to EventLog.</a><br>\n";
        page_close();
        exit;
  }
} else {
    if ($id) {
	$f->find_values($id);
    }
}
switch ($cmd) {
    case "View":
    case "Delete":
	$f->freeze();
    case "Add":

    case "Edit":
	echo "<font class=bigTextBold>$cmd Event Log</font>\n";
	$f->display();
	break;
    default:
	$cmd="Query";
	$t = new MyEventLogTable;
	$t->heading = 'on';
	$t->trust_the_data = true;
	$t->add_extra = 'on';
	$db = new DB_probind;

	echo "<font class=bigTextBold>$cmd Event Log</font>\n";

	get_request_values("SrchLevel,SrchProgram,SrchWebUser,SrchDesc,SrchDesc2,SrchExtra,EventTime,SrchIPAddr,hidehello,hidepoll");
	if (!is_array($SrchLevel)) $SrchLevel=array("Info","Warning","Error");
	#$sess->register("SrchLevel,SrchProgram,SrchWebUser,SrchDesc,hidepoll");

?>
<form name=EventLogform method=POST><br>
<input type=checkbox <?php if (in_array("Info",$SrchLevel)) echo "checked"; ?> name=SrchLevel[] value=Info>Info
<input type=checkbox <?php if (in_array("Warning",$SrchLevel)) echo "checked"; ?> name=SrchLevel[] value=Warning>Warning
<input type=checkbox <?php if (in_array("Error",$SrchLevel)) echo "checked"; ?> name=SrchLevel[] value=Error>Error
<input type=checkbox <?php if (in_array("Debug",$SrchLevel)) echo "checked"; ?> name=SrchLevel[] value=Debug>Debug
<br>
<!--
Description <select name=SrchDesc>
<option value=''>Any
<option <?php if ($SrchDesc=='SQL') echo 'selected'; ?> value='SQL'>SQL Adds/Edits/Deletes
<option <?php if ($SrchDesc=='IPN') echo 'selected'; ?> value='IPN'>PayPal IPNs
<option <?php if ($SrchDesc=='logged in') echo 'selected'; ?> value='logged in'>Logins
<option <?php if ($SrchDesc=='Exceeded Usage') echo 'selected'; ?> value='Exceeded Usage'>Exceeded Limits
<option <?php if ($SrchDesc=='Client Purchase') echo 'selected'; ?> value='Client Purchase'>Client Purchase
<option <?php if ($SrchDesc=='Subscriber Renewal') echo 'selected'; ?> value='Subscriber Renewal'>Subscriber Renewal
<option <?php if ($SrchDesc=='Boot User') echo 'selected'; ?> value='Boot User'>Boot User
</select>-->
 <input name=SrchDesc2 value='<?=$SrchDesc2?>' size=30>
<input type=submit value=Search>
<br>
IP Address<input name=SrchIPAddr value='<?=$SrchIPAddr?>'><br>
Program <select name=SrchProgram>
<option value=''>Any
<?php 
$db->query("SELECT Program, count(*) AS cnt FROM EventLog GROUP BY Program ORDER BY cnt DESC");
while ($db->next_record()) {
	$p=$db->f(0);
	echo "<option ";
	if ($SrchProgram==$p) echo 'selected';
	echo " value='$p'>$p</option>\n";
}
?>
</select><br />
Username<input name='SrchWebUser' value='<?=$SrchWebUser?>'>
<br>    Event Time
                <input name=EventTime id=EventTime value='<?=$EventTime?>' size=40>
                <a href="javascript:show_calendar('document.EventLogform.EventTime', document.EventLogform.EventTime.value);">
                <img src=/images/cal.gif width=16 height=16 border=0 alt="Click here to pick a date from the calendar"></a>
                <a href="javascript:show_help('helpdate.php');" alt="Click here to find out about acceptable date formats">Help</a>
<?php if (substr($sortorder,-5,5)==" desc") { $sortdesc=1; $sortorder=substr($sortorder,0,-5); } ?>
<br>Sorted by 
<select name=sortorder>
<option value='id desc'>Most Recent First
<option <?php if ($sortorder=='EventTime') echo 'selected'; ?> value='EventTime'>Event Time
<option <?php if ($sortorder=='Program') echo 'selected'; ?> value='Program'>Program
<option <?php if ($sortorder=='IPAddress') echo 'selected'; ?> value='IPAddress'>IP Address
<option <?php if ($sortorder=='UserName') echo 'selected'; ?> value='UserName'>WebUser
<option <?php if ($sortorder=='Description') echo 'selected'; ?> value='Description'>Description
<option <?php if ($sortorder=='ExtraInfo') echo 'selected'; ?> value='ExtraInfo'>Extra Info
<option <?php if ($sortorder=='Level') echo 'selected'; ?> value='Level'>Level
</select>
<input type=checkbox name=sortdesc <?php if ($sortdesc) { echo "checked"; $sortorder .= ' desc'; } ?> value='desc'> reversed
<br />Display 
<?php
    if ($rowcount) {
	if ($start) $startingwith = 0;
	if ($prev) $startingwith -= $rowcount;
	if ($next)  $startingwith += $rowcount;
	if ($last) $startingwith = $total-$rowcount;
    } else $rowcount=500;
    if (!$startingwith) $startingwith='0';
?>
<input name=rowcount value='<?=$rowcount?>' size=5>
rows, starting from row 
<input name=startingwith value='<?=$startingwith?>' size=5>
<input name=start type=submit value='&lt;&lt;' width=30>
<input name=prev type=submit value='&lt;' width=30>
<input name=next type=submit value='&gt;' width=30>
<input name=last type=submit value='&gt;&gt;' width=30>
<br />
<?php

        $sql = "";
	$match=Array();
	if ($SrchDesc2) $match = explode(" ",$SrchDesc2);
	if ($SrchDesc) $match[] = $SrchDesc;
	if ($SrchWebUser) $match[] = $SrchWebUser;
	if ($SrchIPAddr) $match[] = $SrchIPAddr;
	if (!empty($match)) {
		$sql .= "MATCH (Description,ExtraInfo) AGAINST ('".implode(" +",$match)."' IN BOOLEAN MODE)";
	}
        if ($SrchProgram) {
                if ($sql) $sql .= " and ";
		$sql .= "Program='$SrchProgram'";
	}
        if ($SrchWebUser) {
                if ($sql) $sql .= " and ";
                $sql .= " UserName = '$SrchWebUser' ";
        }
	if ($hidepoll) {
                if ($sql) $sql .= " and ";
                $sql .= "( Program not like '%poll%' )";
	}
	if ($hidehello) {
                if ($sql) $sql .= " and ";
                $sql .= "( Program not like '%hello%' )";
	}
        if ($EventTime) {
                if ($sql) $sql .= " and ";
                $sql .= "( EventTime like '$EventTime%' )";
        }
        if ($SrchIPAddr) {
                if ($sql) $sql .= " and ";
                $sql .= "( IPAddress like '%$SrchIPAddr%' )";;
        }
	$cj = " and ";
	foreach ($SrchLevel as $k => $v) {
                if ($sql) { $sql .= $cj; }
		if ($cj==" and ") $sql .= " ( ";
		$cj = " or ";
                $sql .= "Level='$v'";
	}
	if ($cj==" or ") $sql .= " ) ";
        $query = $sql;
   

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"EventTime",
			"Program",
			"IPAddress",
			"UserName",
			"Description",
			"Level");
        $t->map_cols = array(
			"EventTime"=>"Event Time",
			"Program"=>"Program",
			"IPAddress"=>"IP Address",
			"UserName"=>"User Name",
			"Description"=>"Description",
			"Level"=>"Level");
	$q->map_cols = array(
                        "EventTime"=>"Event Time",
                        "Program"=>"Program",
                        "IPAddress"=>"IP Address",
                        "UserName"=>"User Name",
                        "Description"=>"Description",
                        "ExtraInfo"=>"Extra Info",
                        "Level"=>"Level");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q_EventLog)) {
    $q_EventLog = new EventLog_Sql_Query;     // We make one
    $q_EventLog->conditions = 1;     // ... with a single condition (at first)
    $q_EventLog->translate  = "on";  // ... column names are to be translated
    $q_EventLog->container  = "on";  // ... with a nice container table
    $q_EventLog->variable   = "on";  // ... # of conditions is variable
    $q_EventLog->lang       = "en";  // ... in English, please
    $q_EventLog->primary_key = "id";  // let Query engine know primary key
    $q_EventLog->row_count = 10;

    $sess->register("q_EventLog");   // and don't forget this!
  }

  if ($rowcount) {
        $q_EventLog->start_row = $startingwith;
        $q_EventLog->row_count = $rowcount;
  }

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (isset($x)) {
    $query = $q_EventLog->where("x", 1);
  }
  if (!$sortorder) $sortorder="id desc";
  if (!$query) { $query="Description<>'Poll Gateways' order by id desc"; }
/*
  $db->query("SELECT COUNT(*) as total from EventLog where ".$query);
  $db->next_record();
  $total = $db->f("total");
  if ($db->f("total") < ($q_EventLog->start_row - $q_EventLog->row_count))
      { $q_EventLog->start_row = $db->f("total") - $q_EventLog->row_count; }
*/
  if ($q_EventLog->start_row < 0) { $q_EventLog->start_row = 0; }

  #echo "<input name=total value=$total type=hidden>\n</form>\n";

  $query .= " ORDER BY $sortorder";
  $query .= " LIMIT ".$q_EventLog->start_row.",".$q_EventLog->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
//  printf($q_EventLog->form("x", $q->map_cols, "query"));
  printf("<hr>");

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select `id`, `EventTime`, `Program`, `IPAddress`, `UserName`, `Description`, `Level`, `ExtraInfo` from EventLog where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>
