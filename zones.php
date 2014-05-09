<?php
include('inc/lib.inc');
include_once('phplib/records.inc');
if (!$export_results) include('header.php');


/*
if ($export_results) {
        page_open(array("sess"=>$_ENV["SessionClass"],"auth"=>$_ENV["AuthClass"],"perm"=>$_ENV["PermClass"],"silent"=>"silent"));
} else {
        page_open(array("sess"=>$_ENV["SessionClass"],"auth"=>$_ENV["AuthClass"],"perm"=>$_ENV["PermClass"]));
	#if ($Field) include("pophead.ihtml"); else include("head.ihtml");
	if ($zones_group_by) $by = " by $zones_group_by"; else $by="";
#	echo "<span class='big'>Probind Zones$by</span>";
	if (empty($Field)) include("header.php");
}
check_view_perms();
*/
$db = new DB_probind;

class Zone_form extends zonesform {
}
$f = new Zone_form;

get_request_values('zone,domain');
if (ctype_digit("$zone")) {
	$id=$zone;
	$cmd='Edit';
}
?><style>
table.recordsTable tr td {
   height: 20px;
} 
table tr.notEdit td input[type=checkbox], 
table tr.notEdit td input[type=submit], 
table tr.notEdit td input[type=reset] {
	display: none;
} 
table tr.notEdit td select, 
table tr.notEdit td input {
	border: 0px;
}
.hide {
	display: none;
}
</style>
<script type='text/javascript'>
document.onreadystatechange = function () {
 if (document.readyState == "complete") {
  //document is ready
  var has_changed = 0;
  var editRow = 0;
  var rtab = document.getElementById("recordsTable");
  if (rtab) {
   var a = rtab.getElementsByTagName("tr");
   for(i=0;i<a.length;i++){
    if (a[i].className=='notEdit') {
	a[i].onclick=function(){
	    if (has_changed) {
		if (this != editRow) alert('Please Save or Undo the other row first');
	    } else {
	   	if (editRow) {
		    editRow.className='notEdit';
	   	}
		this.className='EditOk';
		editRow = this;
		b = this.getElementsByTagName("input");
		for(j=0;j<b.length;j++){
			if (b[j].type=='reset') resetButton=b[j];
			b[j].onchange=function(){
				has_changed=1;
				last_changed=this;
			};
		}
		b = this.getElementsByTagName("select");
		for(j=0;j<b.length;j++){
			if (b[j].type=='reset') resetButton=b[j];
			b[j].onchange=function(){
				has_changed=1;
				last_changed=this;
				if (this.options[this.selectedIndex].value=='SRV') {
					this.form.elements['port'].style.display='inline';
					this.form.elements['port'].style.visibility='visible';
					this.form.elements['weight'].style.display='inline';
					this.form.elements['weight'].style.visibility='visible';
				} else {
					this.form.elements['port'].style.display='none';
					this.form.elements['weight'].style.display='none';
				}
			};
		}
	    }
	};
    }
   }
  }
  var contents=document.getElementById("content");
  var r = contents.getElementsByTagName("form");
  for(i=0;i<r.length;i++){
    if (r[i].name=='recordsform') {
	r[i].onreset=function(){
	   if (editRow) {
		editRow.className='notEdit';
	   }
	   has_changed = 0;
	}
    }
  }
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
  var sel=contents.getElementsByTagName("select");
  for(i=0;i<sel.length;i++){
    if(sel[i].name=='type'){
      sel[i].onblur=function(){
	var rectype=this.options[this.selectedIndex].value;
	el = this.form.elements['genptr'];
	if(rectype=='A'||rectype=='AAAA'){
	  el.style.display='inline';
	  el.style.visibility='visible';
	} else {
	  el.style.display='none';
	};
      };
    }
  }
 }
}

</script>
<?php

$count = 0;
class MyRecordView extends recordsform {
  # normally, form layout is stored in a separate include file, but I want to have a form for every record,
  # I thought it was bad form to include many times over and over, so I override the display function with
  # this little bit of code to display the record in a table row.
  function display($row=array()) {
    global $count;
    $this->setup();
    $this->form_data->start("recordsform");
    echo "<tr class=notEdit><td>";
    $sel = " selected='selected'"; $yesSel=$noSel="";
    if ($row["disabled"]) $noSel = $sel; else $yesSel=$sel;
    echo "<SELECT name=disabled><option value=0$yesSel>on</option><option value=1$noSel>off</option><option value=2>del</option></SELECT></td>";
    echo "<input type=hidden name=zone value='".$row['zone']."'>";
    if ($row['type']=='SOA') $this->freeze(array('domain','data','pref','type','port','weight'));
    foreach (explode(',','domain,ttl,type,pref,genptr,data,comment') as $key) {
	echo "<td>";
	if ($key=='ttl') $row['ttl']=seconds_to_ttl($row['ttl']);
	$val = $row[$key];
        $this->form_data->elements[$key]["ob"]->value=$val;
	if ($key=='genptr') {
	  switch($row['type']) {
	    case "Add New":	
		$val = 'checked'; //flow on
	    case "A":
	    case "AAAA":	
        	$this->form_data->elements[$key]["ob"]->value=1;
		$this->form_data->elements[$key]["ob"]->checked = $val;
		break;
	    default:
		$this->form_data->elements[$key]["ob"]->class="hide";
		$this->form_data->elements[$key]["ob"]->value=$val;
          }
	}
        $this->form_data->elements[$key]["ob"]->action="ipe";		# PHPLIB's "In Place Edit"
	if ($key=='data') {
		if ($row['type']<>'SRV') {
			$this->form_data->elements["weight"]["ob"]->extrahtml="style='display:none'";
			$this->form_data->elements["port"]["ob"]->extrahtml="style='display:none'";
		}
		$this->form_data->elements["weight"]["ob"]->value = $row["weight"];
		$this->form_data->elements["port"]["ob"]->value = $row["port"];
		$this->form_data->show_element("weight");
		$this->form_data->show_element("port");
	}
        if ($this->form_data->elements[$key]["ob"]->multiple) {
                foreach ($this->form_data->elements[$key]["ob"]->options as $option) {
                        $this->form_data->show_element($key,$option);
                        echo " $option<br>\n";
                }
        } else $this->form_data->show_element($key,$val);
	echo "</td>";
    }
    echo "<td>";
    $this->form_data->show_element('submit','Save');
    echo " ";
    $this->form_data->show_element('reset','Undo');
    echo "</td>";
    if ($count++>0) $this->form_data->jvs_name=false;
    $this->form_data->elements["zone"]["ob"]->value=$row['zone'];
    $this->form_data->elements["id"]["ob"]->value=$row['id'];
    $this->form_data->finish();
    echo "</tr>\n";
    return true;
  }
}

function short_date($var) {
	$dval = isset($GLOBALS[$var]) ? $GLOBALS[$var] : $var;
        if (date("Y-m-d") == substr($dval,0,10)) {
                // Date Field (Today)
                return substr($dval,11,10);	# HH:MM:SS
        } else {
                // Date Field (Not Today)
                return date("d M Y",strtotime($dval));   # DD Mmm YYYY 
        }   
}

if ($WithSelected) {
        check_edit_perms();
        switch ($WithSelected) {
                case "Delete":
			if (array_search('zones',$_ENV['no_edit'])) {
				echo "No Delete Allowed";
			} else {
                        	$sql = "DELETE FROM zones WHERE id IN (";
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
                                $f = new zonesform;
                                $f->find_values($row);
                                $f->freeze();
                                $f->display();
				echo "\n</div>\n";
                        }
			echo "\n<br style='clear: both;'>\n";
                        break;
        }
        echo "&nbsp<a href=\"".$sess->self_url();
        echo "\">Back to zones.</a><br>\n";
        page_close();
        exit;
}

if ($submit) {
  if ($_POST["form_name"]=='recordsform') {
	if ($_POST["disabled"]=='2') {  // deleting records need to be confirmed.
        	$URL = $sess->url('/records.php').$sess->add_query(array("cmd"=>"Delete","id"=>$_POST["id"],"zone"=>$zone));
        	echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=$URL\">";
        	echo "&nbsp;<a href=\"$URL\">Delete Record</a><br />\n";
        	page_close();
        	exit;
	}
	$f=new recordsform;
  }
  switch ($submit) {
   case "Copy": $id="";
   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
	if ($submit=='Add') $_POST['ctime'] = 'now';
   case "Edit":
    if (isset($auth)) {
     check_edit_perms();
     if (!$f->validate()) {
        $cmd = $submit;
        echo "<font class='bigTextBold'>$cmd Zones</font>\n<hr />\n";
        $f->reload_values();
        $f->display();
        page_close();
        exit;
     }
     else
     {
        echo "Saving....";
        $id = $f->save_values();
	tag_zoneid_updated($id);
	if ($submit=='Add') {
		if ($id) $db->query("INSERT INTO records (domain, zone, ttl, type, pref, data, port, weight, comment, genptr, ctime, mtime) SELECT domain, $id, ttl, type, pref, data, port, weight, '', 1, NOW(), NOW() FROM records WHERE zone=(SELECT id FROM zones WHERE domain='TEMPLATE')");
	}
        echo "<b>Done!</b><br />\n";
	$url = $sess->self_url().$sess->add_query(array("zone"=>$zone));
        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=$url\">";
        echo "&nbsp;<a href=\"$url\">Back to zones.</a><br />\n";
        page_close();
        exit;
     }
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br />\n";
    }
   case "View":
   case "Back":
	$url = $sess->self_url().$sess->add_query(array("zone"=>$zone));
        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=$url\">";
        echo "&nbsp;<a href=\"$url\">Back to zones.</a><br />\n";
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
	$url = $sess->self_url().$sess->add_query(array("zone"=>$zone));
        if (!$dev) echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=$url\">";
        echo "&nbsp;<a href=\"$url\">Back to zones.</a><br />\n";
        page_close();
        exit;

   default:
	include("search.php");
  }
} else {
    if ($id) {
	if (!$f->find_values($id)) $cmd="NotFound"; else
	if ($master) {  # master is another server so we're the slave
		$f->classname = "slave_form"; # so we need a different form.
	} else {
		$f->classname = "master_form";
	}
    } else {
	if ($domain) { 
		$f->find_values($domain,'domain'); 
		$cmd='Edit'; 
		$zone = $id;
		if ($master) { $f->classname = "slave_form"; } else { $f->classname = "master_form"; }
	} else include("search.php");
    }
}


if ($export_results) $f->setup();
else {
	$f->javascript();
	javascript_translations($language);
}

if ($cmd=='HideQuery') {
	unset($q_zones);
	$cmd='Default';
}

$noShow=false;
switch ($cmd) {
    case "NotFound":
	echo "Zone $id not found";
	break;
    case "View":
    case "Delete":
	$f->freeze();
    case "Add":
	if ($cmd=='Add') {
		$f->find_values('TEMPLATE','domain');   # get default values.
		unset($id);
		unset($domain); #these fields in template must be ignored.
		unset($ctime);
		unset($mtime);
		$f->freeze(array('ctime','mtime'));  # user cannot set these.
	}
    case "Copy":
	if ($cmd=="Copy") $id="";
	$noShow=true;
    case "Edit":
	$retry=$f->form_data->elements['retry']['ob']->value=seconds_to_ttl($retry);
	$refresh=$f->form_data->elements['refresh']['ob']->value=seconds_to_ttl($refresh);
	$expire=$f->form_data->elements['expire']['ob']->value=seconds_to_ttl($expire);
	$f->display();
	if (!empty($master) or $noShow) { 	# If there is a master then we hold no local data.
		echo "</TABLE>";
		break;				# stop here
	}
	echo "<tr><td width=5%></td>
        <TH align=left width=25%>".trans('Domain')."</TH>
        <TH align=left width=8%>".trans('TTL')."</TH>
        <TH align=left width=5%>".trans('Type')."</TH>
        <TH align=left width=4%>".trans('Pref')."</TH>
        <TH align=left width=2%>".trans('Ptr')."?</TH>
        <TH align=left width=25%>".trans('Data')."</TH>
        <TH align=left width=16%>".trans('Comment')."</TH>
        <TH width=10%>\n        </TH>\n</TR>";

	$PTRs = array();  #contains real PTR records, so we can fill in the blanks later with auto generated ones.

	#SELECT id, zone, domain, ttl, records.type, pref, port, weight, data, genptr, comment, lpad(pref, 5, '0') AS sortpref, records.disabled
        $db->query("
	SELECT records.*, lpad(pref, 5, '0') AS sortpref
	FROM records
	JOIN typesort USING (type)
	WHERE zone = $id 
	ORDER BY typesort.ord, domain, sortpref");
	while ($db->next_record()) {
		$f = new MyRecordView;
		$f->display($row=$db->Record);
		switch($row['type']){
		    case "SOA":	
			$soa_ttl = $row['ttl'];
			break;
		    case "PTR": 
			if ($d = $row['domain']) $PTRs[$d]=true;
			break;
		}
	}
	foreach ($row as $k=>$v) $row[$k]=''; 
	$row['type']='Add New'; 
	$row['zone']=$id;
	$f = new MyRecordView; 
	$f->display($row);  // blank record for adding
	echo "</TABLE>";

	# show implied records.
	$servers = published_servers();
	$ttl = default_ttl($id);
	echo "<PRE>";
	echo auto_nsrecs($domain, seconds_to_ttl($ttl), $servers);
	if (preg_match("/\.ip6\.arpa(\.)?$/", $domain))
		echo auto_ip6_ptrs($domain, seconds_to_ttl($soa_ttl), $PTRs);
	if (preg_match("/\.in-addr\.arpa(\.)?$/", $domain))
		echo auto_ptrs($domain, seconds_to_ttl($soa_ttl), $PTRs);
	echo "</PRE>";
	break;
    case "ShowQuery":  // Advanced Custom Query
	// When we hit this page the first time,
	// there is no $q.
	if (!isset($q_zones)) {
	    $q_zones = new zones_Sql_Query;     // We make one
	    $q_zones->conditions = 1;     // ... with a single condition (at first)
	    $q_zones->translate  = "on";  // ... column names are to be translated
	    $q_zones->container  = "on";  // ... with a nice container table
	    $q_zones->variable   = "on";  // ... # of conditions is variable
	    $q_zones->lang       = "en";  // ... in English, please
	    $q_zones->extra_cond = "";  
	    $q_zones->default_query = "Id>''";  
	    $q_zones->default_sortorder = "id desc";  

	    $sess->register("q_zones");   // and don't forget this!
	    $sess->register("zones_x");
	}

	if ($rowcount) {
	    $q_zones->start_row = $startingwith;
	    $q_zones->row_count = $rowcount;
	}
    default:
	$cmd="Query";
	$t = new zonesTable;
	$t->heading = 'on';
	$t->sortable = 'on';
	$t->trust_the_data = false;   /* if true, send raw data without htmlspecialchars */
	$t->limit = 100; 	 /* max length of field data before trucation and add ... */
    #	$t->add_extra = 'on';   /* or set to base url of php file to link to, defaults to PHP_SELF */
    #   $t->add_extra = "SomeFile.php";                           # use defaults, but point to a different target file.
    #   $t->add_extra = array("View","Edit","Copy","Delete");     # just specify the command names.
        $t->add_extra = array("Edit");     # just specify the command names.
    #   $t->add_extra = array(                                    # or specify parameters as well.
    #                      "View" => array("target"=>"PayPal.php","key"=>"id","perm"=>"admin","display"=>"view","class"=>"ae_view"),
    #                      );
	#	$t->add_total = 'on';   /* add a grand total row to the bottom of the table on the numeric columns */
	$t->add_insert = $f->classname;  /* Add a blank row ontop of table allowing insert or search */
	$t->add_insert_buttons = 'Search';   /* Control which buttons appear on the add_insert row eg: Add,Search */
	/* See below - EditMode can also be turned on/off by user if section below uncommented */
	#$t->edit = $f->classname;   /* Allow rows to be editable with a save button that appears onchange */
	#$t->ipe_table = 'zones';   /* Make in place editing changes immediate without a save button */
	#$t->checkbox_menu = Array('Print');
	#$t->check = 'id';  /* Display a column of checkboxes with value of key field*/
	#$t->extra_html = array('fieldname'=>'extrahtml');  			/* better to put this in .inc */
	#$t->align      = array('fieldname'=>'right', 'otherfield'=>'center');	/* better to put this in .inc */


        if (array_key_exists("zones_fields",$_REQUEST)) {
		$zones_fields = $_REQUEST["zones_fields"];
		$zones_funcs = $_REQUEST["zones_funcs"];
		$zones_group_by = @$_REQUEST["GroupBy"];
                $sess->register("zones_fields,zones_funcs,zones_group_by");
	}
        if (empty($zones_fields)) {
                $zones_fields = array_first_chunk($t->default,7,11);
		$zones_funcs = array();
		$zones_group_by = "";
                $sess->register("zones_fields,zones_funcs,zones_group_by");
        }
	if (in_array(@$LocField,$zones_fields)) displayLocSelect($f->classname,$LocField);
        
        $t->fields = $zones_fields;
	$t->GroupBy = $zones_group_by;
	$t->funcs = array();
	foreach($zones_funcs as $func ) if ($func) {
		list($func,$field) = explode(":",$func);
		$t->funcs[$field]=$func;
	}
		
        if (!$export_results) {
          echo "Output to:";
          echo "&nbsp;<input name='ExportTo' type='radio' checked='checked' value='' onclick=\"javascript:export_results('');\"> Here";
          echo "&nbsp;<input name='ExportTo' type='radio' onclick=\"javascript:export_results('Excel2007');\"> Excel 2007&nbsp;\n";
	  echo "&nbsp;<input name='ExportTo' type='radio' onclick=\"javascript:export_results('CSV');\"> CSV";


          echo "\n<button show='ColumnChooser' data-toggle='modal'>Column Chooser</button>\n";
          echo "<div id='ColumnChooser' class='modal hide'>\n";
          echo "  <div class='modal-header'>\n   <button type='button' class='close' data-dismiss='modal'>×</button>\n";
          echo "   <h3>Column Chooser</h3>\n  </div>\n  <div class='modal-body'>";
          echo " <form id=ColumnSelector method='post'>\n";

	  $gb = $fcount = 0;
          foreach ($t->all_fields as $field) {
		$fcount++;
                if (in_array($field,$zones_fields,TRUE)) $chk = "checked='checked'"; else $chk="";
                if (array_key_exists($field,$t->funcs)) $func = $t->funcs[$field].":".$field; else $func="";
                echo "\n<input id='cb$fcount' type='checkbox' $chk name=zones_fields[] value='$field' />";
                echo "\n<input id='hf$fcount' type='hidden' name=zones_funcs[] value='$func' />";
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
		$t->edit = 'zonesform';   
		# $t->ipe_table = 'zones';   #uncomment this for immediate table update (no save button)
            } else {
                $off='checked="checked"'; $on='';
            }
	    $foot = " &nbsp; Edit Mode <input type='radio' name='EditMode' value='on' $on> On <input type='radio' name='EditMode' value='off' $off /> Off &nbsp; ";
	    if ($gb) $foot .=  " Group By <input name=GroupBy value='$t->GroupBy'>";
          } else {
            $EditMode='';
          }

          echo "\n  </div>\n  <div class='modal-footer'>\n";
          echo "  <input type=submit class='btn btn-primary' value='Set'>\n  </div>\n </form>";
          echo "\n</div>";

	}


  if ($submit=='Search') $query = $f->search();   // create sql query from form posted values.

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don't set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (array_key_exists("zones_x",$_POST)) {
    get_request_values("zones_x");
    $query = $q_zones->where("zones_x", 1);
        $startingwith = $q_zones->start_row;
        $rowcount = $q_zones->row_count;
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
        $worksheet1->setTitle('zones');

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

        $sql = "SELECT * FROM zones $custom_query WHERE $query";
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
        header("Content-Disposition: attachment;filename=\"zones.$FileExt\"");
        header("Cache-Control: max-age=0");

        $objWriter = PHPExcel_IOFactory::createWriter($workbook, $Format);
        $objWriter->save('php://output');
        exit;
  }

if (isset($q_zones)) {
  if (empty($sortorder)) $sortorder = empty($q_zones->last_sortorder) ? $q_zones->default_sortorder : $q_zones->last_sortorder ;
  if (empty($query))   $query     = empty($q_zones->last_query)     ? $q_zones->default_query     : $q_zones->last_query ;

  $q_zones->last_query = $query;
  $q_zones->last_sortorder = $sortorder;
/*
  $db->query("SELECT COUNT(*) as total from ".$db->qi("zones")." where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q_zones->start_row - $q_zones->row_count))
      { $q_zones->start_row = $db->f("total") - $q_zones->row_count; }
*/ 
  if ($q_zones->start_row < 0) { $q_zones->start_row = 0; }
}
	if (empty($sortorder)) 	$sortorder = 'domain';
	if (empty($query)) 	$query = 'NOT disabled';
	if (empty($row_count))	$row_count = 500;
	if (empty($start_row))	$start_row = 0;

#  $f->sort_function_maps = array(  /* use a function to sort values for specified fields */
#      "ip_addr"=>"inet_aton",  
#      );

  if (strpos(strtolower($query),"group by")===false) {
	if ($t->GroupBy) {
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


if (isset($q_zones)) {
    echo "\n<button onclick=\"location='".$sess->self_url().$sess->add_query(array("cmd"=>"HideQuery"))."'\">Hide Advanced Custom Query</button>\n";
} else {
    echo "\n<button onclick=\"location='".$sess->self_url().$sess->add_query(array("cmd"=>"ShowQuery"))."'\">Show Advanced Custom Query</button>\n";
}
    echo "\n<button onclick=\"location='".$sess->self_url().$sess->add_query(array("cmd"=>"Add"))."'\">Add New Zone</button>\n";
    echo "<hr />\n\n";

if (isset($q_zones)) {
  printf($q_zones->form("zones_x", $t->map_cols, "query"));
  if (array_key_exists("more_0",$zones_x)) {$query=""; $mode="'show'";}
  if (array_key_exists("less_0",$zones_x)) {$query=""; $mode="'show'";}
  if (!array_key_exists("zones_x",$_POST)) $mode="'hide'";
    echo "<hr />\n\n";
}

  // Do we have a valid query string?
  if ($query) {

    // Do that query
    $sql = $t->select($f).$query;
    $db->query($sql);

    // Dump the results (tagged as CSS class default)
    $t->show_result($db, "default");
    echo $db->num_rows()." zones";
  }
} // switch $cmd
page_close();
?>
</div>
</body>
</html>
