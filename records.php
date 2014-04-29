<?php
include('inc/lib.inc');
include_once('phplib/records.inc');
$_ENV["MyForeignKeys"]="LinkedTables";
$_ENV["MyForeignKeysDB"]="DB_probind";
error_reporting(E_ALL^(E_STRICT|E_NOTICE));
#$EditMode='on';


/*
if ($export_results) {
        page_open(array("sess"=>$_ENV["SessionClass"],"auth"=>$_ENV["AuthClass"],"perm"=>$_ENV["PermClass"],"silent"=>"silent"));
} else {
        page_open(array("sess"=>$_ENV["SessionClass"],"auth"=>$_ENV["AuthClass"],"perm"=>$_ENV["PermClass"]));
	#if ($Field) include("pophead.ihtml"); else include("head.ihtml");
	if ($records_group_by) $by = " by $records_group_by"; else $by="";
	echo "<LINK rel=\"stylesheet\" href=\"style.css\" type=\"text/css\">\n";
	echo "<BODY bgcolor=\"#999966\">";
	echo "<style> .hide {display:none;} </style>";
	echo "<span class='big'>Probind Records$by</span>";
	if (empty($Field)) include("menu.php");
}
check_view_perms();
*/
include('header.php');

$db = new DB_probind;
$f = new recordsform;

if ($WithSelected) {
        check_edit_perms();
        switch ($WithSelected) {
                case "Delete":
			if (array_search('records',$_ENV['no_edit'])) {
				echo "No Delete Allowed";
			} else {
                        	$sql = "DELETE FROM records WHERE id IN (";
                        	$sql .= implode(",",$id);
                        	$sql .= ")";
                        	if ($dev) echo "<h1>$sql</h1>";
                        	$db->query($sql);
                        	echo $db->affected_rows()." deleted.";
		    	}
                        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"10; URL=".$sess->self_url()."\">";
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
        if ($Field) {
                $text = $_POST["AddressLine1"].", ".$_POST["AddressLine2"].", ".$_POST["AddressLine3"].", ".$_POST["City"];
?><script>
if (window.opener) {
        window.opener.addOption("<?php echo $Field; ?>","<?php echo $text; ?>","<?php echo $id; ?>");
        window.close();
}
</script><?php
        }
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
        echo "<b>Done!</b><br />\n";
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br />\n";
    }
        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp;<a href=\"".$sess->self_url()."\">Back to records.</a><br />\n";
        page_close();
        exit;
   default:
	include("search.php");
  }
} else {
    if ($id) {
	$f->find_values($id);
    } else {
	include("search.php");
    }
}


if ($export_results) $f->setup();
else {	
	$f->javascript();
	javascript_translations($language);
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
	$t->checkbox_menu = Array('Print','Delete');
	$t->check = 'id';  /* Display a column of checkboxes with value of key field*/
	#$t->extra_html = array('fieldname'=>'extrahtml');  			/* better to put this in .inc */
	#$t->align      = array('fieldname'=>'right', 'otherfield'=>'center');	/* better to put this in .inc */



        if (array_key_exists("records_fields",$_REQUEST)) {
		$records_fields = $_REQUEST["records_fields"];
		$records_funcs = $_REQUEST["records_funcs"];
		$records_group_by = $_REQUEST["GroupBy"];
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
	$t->GroupBy = $records_group_by;
	$t->funcs = array();
	foreach($records_funcs as $func ) if ($func) {
		list($func,$field) = explode(":",$func);
		$t->funcs[$field]=$func;
	}
/*	
        if (!$export_results) {
          echo "Output to:";
          echo "&nbsp;<input name='ExportTo' type='radio' checked='checked' value='' onclick=\"javascript:export_results('');\"> Here";
          echo "&nbsp;<input name='ExportTo' type='radio' onclick=\"javascript:export_results('Excel2007');\"> Excel 2007&nbsp;\n";
	  echo "&nbsp;<input name='ExportTo' type='radio' onclick=\"javascript:export_results('CSV');\"> CSV";


          echo "\n<a class='btn' href='#ColumnChooser' data-toggle='modal'>Column Chooser</a>\n";
          echo "<div id='ColumnChooser' class='modal hide'>\n";
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
	    if ($gb) $foot .=  " Group By <input name=GroupBy value='$t->GroupBy'>";
          } else {
            $EditMode='';
          }

          echo "\n  </div>\n  <div class='modal-footer'>\n   <a href='#' class='btn' data-dismiss='modal'>Close</a>\n";
          echo "  $foot<input type=submit class='btn btn-primary' value='Set'>\n  </div>\n </form>";
          echo "\n</div>";
          echo "<script>$('#ColumnChooser').modal();</script>\n";

	}
*/

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
  }

  if ($rowcount) {
        $q_records->start_row = $startingwith;
        $q_records->row_count = $rowcount;
  } else {
        $startingwith = $q_records->start_row;
        $rowcount = $q_records->row_count;
  }

  $query = "";
  if ($submit=='Search') $query = $f->search();   // create sql query from form posted values.

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (array_key_exists("x",$_POST)) {
    get_request_values("x");
    $query = $q_records->where("x", 1);
    $hideQuery = "";
  } else {
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
	if ($t->GroupBy) $sql .= " group by ".$t->GroupBy;
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


  if (empty($sortorder)) $sortorder = empty($q_records->last_sortorder) ? $q_records->default_sortorder : $q_records->last_sortorder ;
  if (empty($query))   $query     = empty($q_records->last_query)     ? $q_records->default_query     : $q_records->last_query ;

  $q_records->last_query = $query;
  $q_records->last_sortorder = $sortorder;
/*
  $db->query("SELECT COUNT(*) as total from ".$db->qi("records")." where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q_records->start_row - $q_records->row_count))
      { $q_records->start_row = $db->f("total") - $q_records->row_count; }
*/ 
  if ($q_records->start_row < 0) { $q_records->start_row = 0; }

#  $f->sort_function_maps = array(  /* use a function to sort values for specified fields */
#      "ip_addr"=>"inet_aton",  
#      );

  $query .= access();

  if (strpos(strtolower($query),"group by")===false) {
	if ($t->GroupBy) {
		$query .= " group by ".$t->GroupBy;
	 	$t->add_extra = false;
	}
  }
  if (strpos(strtolower($query),"order by")===false) {
	if ($so=$f->order_by($sortorder)) $query .= " order by ".$so;
  }

  $query .= " LIMIT ".$q_records->row_count." OFFSET ".$q_records->start_row;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
  $mode = "'hide'";

/*
  echo "\n<a class='btn' href='#customQuery' data-toggle='modal'>Custom Query</a>\n";
  echo "<div id='customQuery' class='modal hide'>\n";

  echo " <div class='modal-header'>\n  <button type='button' class='close' data-dismiss='modal'>×</button>\n";
  echo "  <h3>Query Stats</h3>\n </div>\n <div class='modal-body'>\n";
  printf($q_records->form("x", $t->map_cols, "query"));
  if (array_key_exists("more_0",$x)) {$query=""; $mode="'show'";}
  if (array_key_exists("less_0",$x)) {$query=""; $mode="'show'";}
  if (!array_key_exists("x",$_POST)) $mode="'hide'";

  echo "\n </div>\n <div class='modal-footer'>\n  <a href='#' class='btn' data-dismiss='modal'>Close</a>\n";
  echo "  <a href='#' class='btn btn-primary'>Save changes</a>\n </div>\n</div>";

  echo "<script>$('#customQuery').modal($mode);</script>\n";
*/

  // Do we have a valid query string?
  if ($query) {

    // Do that query
    $sql = $t->select($f).$query;
    $db->query($sql);
    #$db->query("select * from ".$db->qi("records")." where ". $query);

/*
    // Show that condition

    echo "\n<a class='btn' href='#QueryStats' data-toggle='modal'>Query Stats</a>\n";
    echo "<div id='QueryStats' class='modal hide'>\n";

    echo " <div class='modal-header'>\n  <button type='button' class='close' data-dismiss='modal'>×</button>\n";
    echo "  <h3>Query Stats</h3>\n </div>\n <div class='modal-body'>\n";
    printf("  Query Condition = %s<br />\n", $sql);
    printf("  Query Results = %s<br />\n", $db->num_rows());
    echo " </div>\n <div class='modal-footer'>\n";

    echo "  <a href='#' class='btn' data-dismiss='modal'>Close</a>\n";
    echo " </div>\n</div><script>$('#QueryStats').modal();</script>\n";
    echo "\n<a class='btn' href=\"".$sess->self_url().$sess->add_query(array("cmd"=>"Add"))."\">Add New Records</a>\n";
*/
    echo "<hr />\n\n";

    // Dump the results (tagged as CSS class default)
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>
