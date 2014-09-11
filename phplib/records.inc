<?php

class recordsform extends tpl_form {
  var $table = "records";
  var $key = "id";
  var $key_field = "id"; # if different to $key
  var $classname = "recordsform";
  var $database_class = "DB_probind";
  var $default_query = "records.disabled=0";  

  function setup_fields () {
    $this->form_data->add_element(array("type"=>"hidden","name"=>"id","field"=>"id","size"=>"12"));
    $this->form_data->add_element(array("type"=>"text","name"=>"zone","size"=>"12",
		"valid_regex"=>"^[0-9]+$","field"=>"zone",
		"extrahtml"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"domain","maxlength"=>"100","size"=>"33"
		,"minlength"=>1, "length_e"=>"RequiredField",
		"field"=>"domain","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"ttl","maxlength"=>"15","size"=>"5",
		"field"=>"ttl","extrahtml"=>"","value"=>"4H"
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"type","maxlength"=>"10","size"=>"3"
		,"minlength"=>1, "length_e"=>"RequiredField",
		"field"=>"type","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"pref","maxlength"=>"5","size"=>"1",
		"field"=>"pref","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"data","maxlength"=>"448","size"=>"85"
		,"minlength"=>1, "length_e"=>"\"+message['RequiredField'].replace(/%s/g,'Data')+\"",
		"field"=>"data","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"comment","maxlength"=>"32","size"=>"10",
		"field"=>"comment","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"date","name"=>"mtime","maxlength"=>"","size"=>"0",
		"field"=>"mtime","value"=>"CURRENT_TIMESTAMP"));
    $this->form_data->add_element(array("type"=>"date","name"=>"ctime","maxlength"=>"","size"=>"0",
		"field"=>"ctime","value"=>"0000-00-00 00:00:00"));
    $this->form_data->add_element(array("type"=>"text","name"=>"weight","maxlength"=>"5","size"=>"1",
		"valid_regex"=>"^[0-9]+$|^$","field"=>"weight",
		"valid_e"=>"Weight is a whole number 0 to 65535",
		"extrahtml"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"port","maxlength"=>"5","size"=>"1",
		"valid_regex"=>"^[0-9]+$|^$","field"=>"port",
		"valid_e"=>"Port is a whole number 0 to 65535",
		"extrahtml"=>""));
    $this->form_data->add_element(array("type"=>"checkbox","name"=>"genptr",
		"field"=>"genptr", "extrahtml"=>"",
		"options"=>array("0"=>"0","1"=>"1"), "value"=>"value", "checked"=>"checked"));
    $this->form_data->add_element(array("type"=>"select","name"=>"disabled",
		"field"=>"disabled", "extrahtml"=>"",
		"options"=>array( array("label"=>"Yes","value"=>"1"),
				  array("label"=>"No","value"=>"0"),
				), "value"=>"No"));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
    $this->form_data->add_element(array("type"=>"reset","name"=>"reset","value"=>"Undo"));
    $this->form_data->before = <<< JAVASCRIPT

e = f.elements["domain"];
if (e.value.length<1) { e.value='@'; }
if (e.value!='@' && e.value!='*') {
  if (window.RegExp) {
    regex = new RegExp(domreg);
    if (!regex.test(e.value)) {
      alert(message['InvalidDomainName'].replace(/\%s/g,e.value));
      e.focus();
      return(false);
    }
  }
}
data = f.elements["data"];
type = f.elements["type"];
type_val = type.options[type.selectedIndex].value;
switch(type_val) {
    case "A":
	if (window.RegExp) {
	    regex = new RegExp(ipv4);
	 result=regex.test(data.value);
	    if (!result) {
      		alert(message['InvalidIpAddress'].replace(/\%s/g,data.value));
		data.focus();
		return(false);
	    }
	}
	break;
    case "AAAA":
	if (window.RegExp) {
	    regex = new RegExp(ipv6);
	    if (!regex.test(data.value)) {
      		alert(message['InvalidIpAddress'].replace(/\%s/g,data.value));
		data.focus();
		return(false);
	    }
	}
	break;
    case "SRV":
	e = f.elements["weight"];
	if (e.value.length<1) {
		alert(message['WeightRequired']);
		e.focus();
		return(false);
	}
	e = f.elements["port"];
	if (e.value.length<1) {
		alert(message['PortRequired']);
		e.focus();
		return(false);
	}
	//no break, SRV needs pref too;
    case "MX":
	e = f.elements["pref"];
	if (e.value.length<1) {
		alert(message['PriorityRequired']);
		e.focus();
		return(false);
	}
	break;
    case "CNAME":
	e = f.elements["zone"];
	zone = e.options[e.selectedIndex].innerHTML.toLowerCase() + ".";
	e = f.elements["domain"]
	domval = e.value.toLowerCase();
	if (domval == zone || domval == '@') {
		alert(message['BadCNAME']);
		e.focus();
	}
	break;
}
switch(type_val) {
    case "SRV":
	break;
    default:
	f.elements["pref"].value="";
	// no break;
    case "MX":
	f.elements["weight"].value="";
	f.elements["port"].value="";
}
//    alert("ok");  //debug
JAVASCRIPT;
  }

  /// PHP
  function validate_input() {
        $db = new $this->database_class;
        $val_ok = true;
        global $DOMAIN_RE, $IPNO_RE, $IPV6_RE, $IPV4_RE;
	extract($_POST,EXTR_PREFIX_ALL,"post");
        if (!strlen($post_domain))
                $post_domain = '@';
        if ($post_domain != '@'
        && $post_domain != '*'
        && !preg_match("/$DOMAIN_RE/i", $post_domain))
                $this->add_error(trans("InvalidDomain",$post_domain));
        if (!preg_match("/^[a-zA-Z]+$/", $post_type))
                $this->add_error(trans("InvalidType"));
        if (!strlen($post_data))
                $this->add_error(trans("RequiredField","Data"));
        if ($post_type == 'A') {
                $rc = preg_match($IPV4_RE, $post_data, $bytes);
                if (!$rc || $bytes[0]<0 || $bytes[0]>255
                 || $bytes[1]<0 || $bytes[1]>255
                 || $bytes[2]<0 || $bytes[2]>255
                 || $bytes[3]<0 || $bytes[3]>255)
        	        $this->add_error(trans("InvalidIpAddress",$post_data));
        }
        if ($post_type =='AAAA') {
                if (!preg_match($IPV6_RE, $post_data))
        	        $this->add_error(trans("InvalidIpAddress",$post_data));
        }
	$_POST["ttl"] = ttl_to_seconds($post_ttl);
        $type = strtoupper($post_type);
        if ($type != 'MX')
                $post_pref = '';
        elseif ($post_pref < 0 || $post_pref > 65535 || !strlen($post_pref))
                $this->add_error(trans("InvalidUnsignedInt16","Pref"));
        //
        // This modification makes wrong result in the 'import' so turn it off. We always can use it on the high level if necessary.
        //if (strchr($data, ".") && ($type != "TXT") && !valid_ip($data) && $data[strlen($data)-1] != ".")
        //      $data .= ".";
        //
        $zone = get_zone($post_zone);
        if ($post_type == 'CNAME' && (($zone['domain']."." == $post_domain) || $post_domain == '@'))
                $this->add_error(trans("BadCNAME"));
        if (preg_match("/\.$/i", $post_domain)
        && !preg_match("/".$zone['domain'].'.$/i', $post_domain))
		$this->add_error(trans("DomainOutsideZone",$post_domain));
	if (count($this->errors)>0) $val_ok=false;
        return $val_ok;

  }

}
class recordsTable extends Table {
  var $classname = "recordsTable";
  var $sql_table = "records";
  var $primary_key = "id";
  var $primary_field = "id";
  var $all_fields = array(
			"zone",
			"domain",
			"ttl",
			"type",
			"pref",
			"data",
			"comment",
			"mtime",
			"ctime",
			"weight",
			"port",
			"genptr",
			"disabled",
			);

  /* comment out or delete some of these default entries so that the table isn't too wide for the screen */
  var $default = array(
			"zone",
			"domain",
			"ttl",
			"type",
			"pref",
			"data",
			"comment",
			"mtime",
			"ctime",
			"weight",
			"port",
			"genptr",
			"disabled",
			);

  var $numeric_fields = array(
			"zone",
			"mtime",
			"ctime",
			"weight",
			"port",
			"genptr",
			"disabled",
			);

  // These fields will be searchable and displayed in results.
  // Format is "RealFieldName"=>"Field Name Formatted For Display",
  var $map_cols = array(
			"zone"=>"Zone",
			"domain"=>"Domain",
			"ttl"=>"TTL",
			"type"=>"Type",
			"pref"=>"Pref",
			"data"=>"Data",
			"comment"=>"Comment",
			"mtime"=>"Modified",
			"ctime"=>"Created",
			"weight"=>"Weight",
			"port"=>"Port",
			"genptr"=>"Generate PTR",
			"disabled"=>"Disabled",
			);

  // PHP money_format modifier for field; 
  // see http://www.php.net/manual/en/function.money-format.php
  var $format = array(
			'field'=>'format', 		# eg:
			'money'=>'%n',   		# local specific money format
			'percent'=>'%!.0n%%',   	# whole number with a % sign on the end
			'whole_number'=>'%!.0n',	# .p precision 
			'ttl'=>'%!.0n',	
			#  '%=*(#10.2n'		($********1,234.57)
			#  '%=*^-14#8.2i'	Eu 1234,56****
			);

  // Extra HTML to be sent out with the elements listed
  var $extra_html = array(
			'fieldname'=>'extrahtml',
			);

  // Specific alignment to override the auto alignment for the field
  var $align      = array(
			'ttl'=>'right', 
			'otherfield'=>'center',
			); 
}
class records_Sql_Query extends Sql_Query {
  var $classname = "records_Sql_Query";
  var $primary_key = "id";
  var $primary_field = "id";
  var $table = "records";
}

