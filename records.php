<?php
include('inc/lib.inc');
if (!$export_results) {
  include('header.php');
?>
<script type='text/javascript'>
document.onreadystatechange = function () {
 if (document.readyState == "complete") {
  //document is ready
  var contents=document.getElementById("content");
  var btn = contents.getElementsByTagName("button");
  for(i=0;i<btn.length;i++){
    if (btn[i].getAttribute('data-toggle')=='modal') {
        target=btn[i].getAttribute('show');
        if (target) {
          if (el = document.getElementById(target)) {
            el.style.display='none';
            btn[i].onclick=function(){
                el = document.getElementById(this.getAttribute('show'));
                el.style.display='inline';
                el.style.visibility='visible';
            }
          }
        }
    }
    if (btn[i].getAttribute('data-dismiss')=='modal') {
        btn[i].onclick=function() {
            this.parentNode.parentNode.style.display='none';
        }
    }
  }
 }
}
</script>
<?php
}

$db = new DB_probind;
$f = new recordsform;

if ($WithSelected) {
        check_edit_perms();
	$WithSelected = explode(" ",$WithSelected);
	if (!ctype_digit(implode("",$id))) die("Invalid id(s)");
	$cond = "WHERE id IN (".implode(",",$id).")".access();
        switch ($WithSelected[0]) {
		case "Change":
			echo "<pre>";
			$field = $WithSelected[1];
			if (!ctype_alpha($field)) die('Invalid Field Name');
			$field = $db->qi($field);
			$sql = "UPDATE records SET $field = ? $cond";
			if ($dev) echo "<h1>".str_replace("?","'".$_POST["new_value"]."'",$sql)."</h1>";
			$db->prepare($sql);
			$db->execute($_POST["new_value"]);
			echo $db->affected_rows()." updated.";
                        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"10; URL=".$sess->self_url()."\">";
			break;
                case "Disable":
			$sql = "UPDATE records SET disabled=1 $cond";
			if ($dev) echo "<h1>$sql</h1>";
			$db->query($sql);
			echo $db->affected_rows()." disabled.";
                        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"10; URL=".$sess->self_url()."\">";
                        break;
		case "Enable":
			$sql = "UPDATE records SET disabled=0 $cond";
			if ($dev) echo "<h1>$sql</h1>";
			$db->query($sql);
			echo $db->affected_rows()." enabled.";
                        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"10; URL=".$sess->self_url()."\">";
                        break;
		case "Move":
			$DeleteAfterCopy = true;
		case "Copy":
			$newzone = $_POST["new_value"];
			if (!ctype_digit($newzone)) die("Invalid destination zone id");
			$db->query("SELECT count(*) FROM records $cond AND type='SOA'");
			if ($db->fetchColumn) { echo "SOA records cannot be moved or copied"; break; }
		        $db->query("INSERT INTO records (domain, zone, ttl, type, pref, data, port, weight, comment, genptr, ctime, mtime) ".
                		   "SELECT domain, $newzone, ttl, type, pref, data, port, weight, '', 1, NOW(), NOW() FROM records $cond");
                        echo $db->affected_rows()." copied.<br>";
			if (!isset($DeleteAfterCopy)) break;
                case "Delete":
			$db->query("INSERT INTO deleted_records SELECT * FROM records $cond");
                        $sql = "DELETE FROM records $cond";
                        if ($dev) echo "<h1>$sql</h1>";
                        $db->query($sql);
                        echo $db->affected_rows()." deleted.";
			echo "<br><hr><form method='post'>";
			echo "<input type='submit' name='WithSelected' value='UnDelete'>\n";
			foreach ($id as $val) echo "<input type='hidden' name='id[]' value='$val'>\n";
			echo "</form>\n";
                        break;
                case "UnDelete":
			$db->query("INSERT INTO records SELECT * FROM deleted_records $cond");
                        echo $db->affected_rows()." un-deleted.";
                        $db->query("DELETE FROM deleted_records $cond");
			break;
                case "Print";
                        foreach ($id as $row) {
				echo "<div class='float_left'>\n";
                                $f = new recordsform;
                                $f->find_values($row);
                                $f->freeze();
                                $f->display();
				echo "\n</div>\n";
                        }
			echo "\n<br style='clear: both;'>\n";
                        break;
        }
        echo "&nbsp<a href=\"".$sess->self_url();
        echo "\">Back to records.</a><br>\n";
        page_close();
        exit;
}

get_request_values('zone');

if ($submit) {
  switch ($submit) {
   case "Copy": $id="";
   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if (isset($auth)) {
     check_edit_perms();
     if (!$f->validate()) {
        $cmd = $submit;
        echo "<font class='bigTextBold'>$cmd Records</font>\n<hr />\n";
        $f->reload_values();
        $f->display();
        page_close();
        exit;
     }
     else
     {
        echo "Saving....";
        $id = $f->save_values();
	tag_zoneid_updated($zone);
        echo "<b>Done!</b><br />\n";
        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp;<a href=\"".$sess->self_url()."\">Back to records.</a><br />\n";
        page_close();
        exit;
     }
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br />\n";
    }
   case "View":
   case "Back":
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=".$sess->self_url()."\">";
        echo "&nbsp;<a href=\"".$sess->self_url()."\">Back to records.</a><br />\n";
        page_close();
        exit;
   case "Delete":
    if (isset($auth)) {
        check_edit_perms();
        echo "Deleting....";
        $f->save_values();
	tag_zoneid_updated($zone);
        echo "<b>Done!</b><br />\n";
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br />\n";
    }
        if ($zone) $url = $sess->url("zones.php").$sess->add_query(array("zone"=>$zone));
	else $url = $sess->self_url();
        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=$url\">";
        echo "&nbsp;<a href=\"$url\">Back</a><br />\n";
        page_close();
        exit;
   default:
	include("search.php");
  }
} else {
    if ($id) {
	$f->find_values($id);
	$ttl=seconds_to_ttl($ttl);
    } else {
	include("search.php");
    }
}


if ($export_results) $f->setup();
else {	
	$f->javascript();
	javascript_translations($language);
}

if ($cmd=='HideQuery') {
        unset($q_records);
        $cmd='Default';
}

switch ($cmd) {
    case "View":
    case "Delete":
	$f->freeze();
    case "Add":
    case "Copy":
	if ($cmd=="Copy") $id="";
    case "Edit":
	#echo "<font class='bigTextBold'>$cmd Records</font>\n<hr />\n";
	$f->display();
	if ($orig_cmd=="View") $f->showChildRecords();
	break;
    case "ShowQuery":
	// When we hit this page the first time,
	// there is no $q.
	if (!isset($q_records)) {
	    $q_records = new records_Sql_Query;     // We make one
	    $q_records->conditions = 1;     // ... with a single condition (at first)
	    $q_records->translate  = "on";  // ... column names are to be translated
	    $q_records->container  = "on";  // ... with a nice container table
	    $q_records->variable   = "on";  // ... # of conditions is variable
	    $q_records->lang       = "en";  // ... in English, please
	    $q_records->extra_cond = "";  
	    $q_records->default_query = "records.disabled=0";  
	    $q_records->default_sortorder = "domain";  

	    $sess->register("q_records");   // and don't forget this!
	    $sess->register("records_x");
	}

	if ($rowcount) {
            $q_records->start_row = $startingwith;
            $q_records->row_count = $rowcount;
	}
        #  flow through
    default:
	$cmd="Query";
	$t = new recordsTable;
	$t->heading = 'on';
	$t->sortable = 'on';
	$t->trust_the_data = false;   /* if true, send raw data without htmlspecialchars */
	$t->limit = 50; 	 /* max length of field data before trucation and add ... */
    	$t->add_extra = 'on';   /* or set to base url of php file to link to, defaults to PHP_SELF */
    #   $t->add_extra = "SomeFile.php";                           # use defaults, but point to a different target file.
    #   $t->add_extra = array("View","Edit","Copy","Delete");     # just specify the command names.
    #   $t->add_extra = array(                                    # or specify parameters as well.
    #                      "View" => array("target"=>"PayPal.php","key"=>"id","perm"=>"admin","display"=>"view","class"=>"ae_view"),
    #                      );
#	$t->add_total = 'on';   /* add a grand total row to the bottom of the table on the numeric columns */
	$t->add_insert = $f->classname;  /* Add a blank row ontop of table allowing insert or search */
	$t->add_insert_buttons = 'Add,Search';   /* Control which buttons appear on the add_insert row eg: Add,Search */
	/* See below - EditMode can also be turned on/off by user if section below uncommented */
	#$t->edit = $f->classname;   /* Allow rows to be editable with a save button that appears onchange */
	#$t->ipe_table = 'records';   /* Make in place editing changes immediate without a save button */
	$t->checkbox_menu = Array('Print','Delete','Enable','Disable');
	$t->check = 'id';  /* Display a column of checkboxes with value of key field*/
	#$t->extra_html = array('fieldname'=>'extrahtml');  			/* better to put this in .inc */
	#$t->align      = array('fieldname'=>'right', 'otherfield'=>'center');	/* better to put this in .inc */



        if (array_key_exists("records_fields",$_REQUEST)) {
		$records_fields = $_REQUEST["records_fields"];
		$records_funcs = $_REQUEST["records_funcs"];
#		$records_group_by = $_REQUEST["GroupBy"];
                $sess->register("records_fields,records_funcs,records_group_by");
	}
        if (empty($records_fields)) {
                $records_fields = array_first_chunk($t->default,6,11);
		$records_funcs = array();
		$records_group_by = "";
                $sess->register("records_fields,records_funcs,records_group_by");
        }
	if (in_array(@$LocField,$records_fields)) displayLocSelect($f->classname,$LocField);
        
        $t->fields = $records_fields;
#	$t->GroupBy = $records_group_by;
	$t->funcs = array();
	foreach($records_funcs as $func ) if ($func) {
		list($func,$field) = explode(":",$func);
		$t->funcs[$field]=$func;
	}
	
        if (!$export_results) {
          echo "Output to:";
          echo "&nbsp;<input name='ExportTo' type='radio' checked='checked' value='' onclick=\"javascript:export_results('');\"> Here";
          echo "&nbsp;<input name='ExportTo' type='radio' onclick=\"javascript:export_results('Excel2007');\"> Excel 2007&nbsp;\n";
	  echo "&nbsp;<input name='ExportTo' type='radio' onclick=\"javascript:export_results('CSV');\"> CSV";


          echo "\n<button show='ColumnChooser' data-toggle='modal'>Column Chooser</button>\n";
          echo "<div id='ColumnChooser' class='modal' style='display:none'>\n";
          echo "  <div class='modal-header'>\n   <button type='button' class='close' data-dismiss='modal'>×</button>\n";
          echo "   <h3>Column Chooser</h3>\n  </div>\n  <div class='modal-body'>";
          echo " <form id=ColumnSelector method='post'>\n";

	  $gb = $fcount = 0;
          foreach ($t->all_fields as $field) {
		$fcount++;
                if (in_array($field,$records_fields,TRUE)) $chk = "checked='checked'"; else $chk="";
                if (array_key_exists($field,$t->funcs)) $func = $t->funcs[$field].":".$field; else $func="";
                echo "\n<input id='cb$fcount' type='checkbox' $chk name=records_fields[] value='$field' />";
                echo "\n<input id='hf$fcount' type='hidden' name=records_funcs[] value='$func' />";
		$field_display = $func ? $func : $field;
                echo "<span id='span_$fcount'";
                if (in_array($field,$t->numeric_fields)) {
			$gb++;
                        echo " class='popup' data-id='$fcount' data-field='$field'";
                }
                echo ">$field_display</span><br />";
          }
          $foot = "";
          if ($sess->have_edit_perm()) {
            if ($EditMode=='on') {
                $on='checked="checked"'; $off='';
		$t->edit = 'recordsform';   
		# $t->ipe_table = 'records';   #uncomment this for immediate table update (no save button)
            } else {
                $off='checked="checked"'; $on='';
            }
	    $foot = " &nbsp; Edit Mode <input type='radio' name='EditMode' value='on' $on> On <input type='radio' name='EditMode' value='off' $off /> Off &nbsp; ";
	    #if ($gb) $foot .=  " Group By <input name=GroupBy value='$t->GroupBy'>";
          } else {
            $EditMode='';
          }

          echo "\n  </div>\n  <div class='modal-footer'>\n";
          echo "  <input type=submit class='btn btn-primary' value='Set'>\n  </div>\n </form>";
          echo "\n</div>";
?>
<style>
#ZoneChooser .modal-body,
#ZoneChooser .modal-header,
#ZoneChooser .modal-footer {
	width:400px;
}
#ZoneChooser .modal-body {
	min-height: 300px;
}
</style>
<script type='text/javascript'>
function update_main_form(f) {
	var main_form = document.getElementById('ResultsTable');
        var el = document.createElement('input');
        el.type='hidden';
        el.name='new_value';
	e = f.elements["domain"];
	if (e.selectedIndex){el.value=e.options[e.selectedIndex].value}else{el.value=e.value};
        main_form.appendChild(el);
	main_form.submit();
	x=main_form.elements["WithSelected"]; 
alert(x.options[x.selectedIndex].value);
	return false;
}
</script>
<?php
          echo "<div id='ZoneChooser' class='modal' style='display:none'>\n";
	  echo "<form name=zonechooser onsubmit=update_main_form(this) method=none>\n";
          echo "  <div class='modal-header'>\n   <button type='button' class='close' data-dismiss='modal'>×</button>\n";
          echo "   <h3 id='zchdr'>Zone Chooser</h3>\n  </div>\n  <div class='modal-body'>";

	  $zf = new zonesform;
	  $zf->setup();
	  $zf->link("zones","id","domain","domain","0","select target domain...",access(),"");
	  $zf->form_data->show_element('domain');

          echo "\n  </div>\n  <div class='modal-footer'>\n";
          echo "  <input type=submit class='btn btn-primary' value='Confirm'>\n  </div>\n </form>";
          echo "\n</div>";

	}

  $query = "";
  if ($submit=='Search') $query = $f->search($t);   // create sql query from form posted values.

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don't set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (array_key_exists("records_x",$_POST)) {
    get_request_values("records_x");
    $query = $q_records->where("records_x", 1);
    $join = $q_records->join;
    $hideQuery = "";
  } else {
    $join = "";
    $hideQuery = "style='display:none'";
  }

  if ($Format = $export_results) {
        $custom_query = array_key_exists("custom_query",$_POST) ? $_POST["custom_query"] : "";

        require_once "/usr/share/pear/PHPExcel/PHPExcel.php";

        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory;
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod);

        $locale = 'en_us';
        $validLocale = PHPExcel_Settings::setLocale($locale);

        $workbook = new PHPExcel();
        $workbook->setActiveSheetIndex(0);
        $worksheet1 = $workbook->getActiveSheet();
        $worksheet1->setTitle('records');

        $cols = count($t->fields);
        $range = "A1:" . chr(64+$cols) . "1";
        $worksheet1->getStyle($range)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB(PHPExcel_Style_Color::COLOR_DARKGREEN);
        $worksheet1->getStyle($range)->getAlignment()->setHorizontal('center');
        $worksheet1->getStyle($range)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);

        $r = 1;
        $col = "A";
        foreach ($t->fields as $field) {
                if (!isset($f->form_data->elements[$field]["ob"])) {var_dump($f->form_data->elements[$field]); exit; }
                $el = $f->form_data->elements[$field]["ob"];
                if (!$size=@$el->size) {
                        $size = 5;
                        if (!isset($el->options)) {
                                $size = strlen($el->value);
                        } else
                        foreach($el->options as $option) {
				if (is_array($option)) $len=strlen($option["label"]);
                                else $len = strlen($option);
                                if ($len>$size) $size = $len;
                        }
                }
                $worksheet1->getColumnDimension($col)->setWidth($size);
                $worksheet1->getCell("$col$r")->setValue($t->map_cols[$field]);
                $col++;
        }

        $sql = "SELECT * FROM records $custom_query WHERE $query";
	if (isset($t->GroupBy)) $sql .= " group by ".$t->GroupBy;
        $db->query($sql);
        while ($db->next_record()) {
                $r++;
                $col = "A";
                foreach ($t->fields as $field) {
                        $worksheet1->getCell("$col$r")->setValue($db->f($field));
                        $col++;
                }
        }

        switch($Format) {
            case "Excel2007":
                $ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
                $FileExt = "xlsx";
                break;
            case "CSV":
                $ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
                $FileExt = "csv";
                break;
        }

        header("Content-Type: $ContentType");
        header("Content-Disposition: attachment;filename=\"records.$FileExt\"");
        header("Cache-Control: max-age=0");

        $objWriter = PHPExcel_IOFactory::createWriter($workbook, $Format);
        $objWriter->save('php://output');
        exit;
  }

if (isset($q_records)) {
  if (empty($sortorder)) $sortorder = empty($q_records->last_sortorder) ? $q_records->default_sortorder : $q_records->last_sortorder ;
  if (empty($query))   $query     = empty($q_records->last_query)     ? $q_records->default_query     : $q_records->last_query ;
  if (isset($q_records->last_query)) $join = $q_records->join;
  $q_records->last_query = $query;
  $q_records->last_sortorder = $sortorder;
/*
  $db->query("SELECT COUNT(*) as total from ".$db->qi("records")." where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q_records->start_row - $q_records->row_count))
      { $q_records->start_row = $db->f("total") - $q_records->row_count; }
*/ 
  if ($q_records->start_row < 0) { $q_records->start_row = 0; }
} 

        if (empty($sortorder))  $sortorder = 'domain';
        if (empty($query))      $query = 'NOT records.disabled';
        if (empty($row_count))  $row_count = 500;
        if (empty($start_row))  $start_row = 0;

#  $f->sort_function_maps = array(  /* use a function to sort values for specified fields */
#      "ip_addr"=>"inet_aton",  
#      );

  $query .= access();

  if (strpos(strtolower($query),"group by")===false) {
	if (isset($t->GroupBy)) {
		$query .= " group by ".$t->GroupBy;
	 	$t->add_extra = false;
	}
  }
  if (strpos(strtolower($query),"order by")===false) {
	if ($so=$f->order_by($sortorder)) $query .= " order by ".$so;
  }

  $query .= " LIMIT $row_count OFFSET $start_row";

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
  $mode = "'hide'";

if (isset($q_records)) {
    echo "\n<button onclick=\"location='".$sess->self_url().$sess->add_query(array("cmd"=>"HideQuery"))."'\">Hide Advanced Custom Query</button>\n";
} else {
    echo "\n<button onclick=\"location='".$sess->self_url().$sess->add_query(array("cmd"=>"ShowQuery"))."'\">Show Advanced Custom Query</button>\n";
}
    echo "\n<button onclick=\"location='".$sess->self_url().$sess->add_query(array("cmd"=>"Add"))."'\">Add New Record</button>\n";
    echo "<hr />\n\n";

if (isset($q_records)) {
  printf($q_records->form("records_x", $t->map_cols, "query"));
  if (array_key_exists("more_0",$records_x)) {$query=""; $mode="'show'";}
  if (array_key_exists("less_0",$records_x)) {$query=""; $mode="'show'";}
  if (!array_key_exists("records_x",$_POST)) $mode="'hide'";
    echo "<hr />\n\n";
}

  // Do we have a valid query string?
  if ($query) {

    // Do that query
    $sql = $t->select($f,$join).$query;
    $db->query($sql);

    // Dump the results (tagged as CSS class default)
    $t->show_result($db, "default");
    echo $db->num_rows()." records.";

    // examine data to see what extra options can be added to WithSelected dropdown box.
    foreach ($t->same_data as $k=>$v) {
	if ($v) {
	    switch ($k) {
		case "_t1domain":
			$NewWithSelected[] = "Move records to...";
			$NewWithSelected[] = "Copy records to...";
			break;
		case "domain": 
		case "data": 
		case "ttl":
			$NewWithSelected[] = "Change $k $v to...";
	    }
	}
    }
    if (!empty($NewWithSelected)) {
?>
<script type='text/javascript'>
<?php echo "var newOptions=['".implode("','",$NewWithSelected)."'];\n"; ?>
  for (i=0;i<document.forms.length;i++){
    if (document.forms[i].name=='ResultsTable') {
      if (el=document.forms[i].elements["WithSelected"]) {
	for (j=0;j<newOptions.length;j++) {
	   var option = document.createElement("option");
	   option.text = newOptions[j];
	   option.value = newOptions[j];
	   el.add(option,el[el.options.length]);
	}	
      }
    }
  };
</script>
<?php
    }
  }
} // switch $cmd
page_close();
?>
