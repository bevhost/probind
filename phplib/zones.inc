<?php

class zonesform extends tpl_form {
  var $table = "zones";
  var $key = "id";
  var $key_field = "id"; # if different to $key
  var $classname = "zonesform";
  var $database_class = "DB_probind";
  var $default_query = "Id>''";  

  function setup_fields () {
    $this->form_data->add_element(array("type"=>"hidden","name"=>"id","field"=>"id","size"=>"12"));
    $this->form_data->add_element(array("type"=>"text","name"=>"domain","maxlength"=>"100","size"=>"33"
		,"minlength"=>1,"length_e"=>"Domain must not be blank.  Required field.",
		"field"=>"domain","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"serial","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$|^$","field"=>"serial",
		"valid_e"=>"Serial must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"refresh","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$|^$","field"=>"refresh",
		"valid_e"=>"Refresh must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"retry","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$|^$","field"=>"retry",
		"valid_e"=>"Retry must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"expire","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$|^$","field"=>"expire",
		"valid_e"=>"Expire must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"master","maxlength"=>"32","size"=>"10",
		"field"=>"master","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"zonefile","maxlength"=>"80","size"=>"26"
		,"minlength"=>1,"length_e"=>"Zonefile must not be blank.  Required field.",
		"field"=>"zonefile","extrahtml"=>"","class"=>"ipe"
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"options","maxlength"=>"255","size"=>"85"
		,"minlength"=>1,"length_e"=>"Options must not be blank.  Required field.",
		"field"=>"options","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"date","name"=>"mtime","maxlength"=>"","size"=>"0"
		,"minlength"=>1,"length_e"=>"Mtime must not be blank.  Required field.",
		"field"=>"mtime","extrahtml"=>" onBlur=\"ajax_update_element(this,'find.php?dt=')\""
		,"valid_e"=>"Invalid Date","valid_regex"=>"timestamp","value"=>"CURRENT_TIMESTAMP"));
    $this->form_data->add_element(array("type"=>"date","name"=>"ctime","maxlength"=>"","size"=>"0"
		,"minlength"=>1,"length_e"=>"Ctime must not be blank.  Required field.",
		"field"=>"ctime","extrahtml"=>" onBlur=\"ajax_update_element(this,'find.php?dt=')\""
		,"valid_e"=>"Invalid Date","valid_regex"=>"timestamp","value"=>"0000-00-00 00:00:00"));
    $this->form_data->add_element(array("type"=>"text","name"=>"updated","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$|^$","field"=>"updated",
		"valid_e"=>"Updated must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>"",
                "options"=>array( array("label"=>"Yes","value"=>"1"),
                                  array("label"=>"No","value"=>"0"),
                                ), "value"=>"No"));
    $this->form_data->add_element(array("type"=>"text","name"=>"disabled","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$","field"=>"disabled",
		"valid_e"=>"Disabled must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>"",
                "options"=>array( array("label"=>"Yes","value"=>"1"),
                                  array("label"=>"No","value"=>"0"),
                                ), "value"=>"No"));
    $this->form_data->add_element(array("type"=>"text","name"=>"owner","maxlength"=>"32","size"=>"10"
		,"minlength"=>1,"length_e"=>"Owner must not be blank.  Required field.",
		"field"=>"owner","extrahtml"=>"","class"=>"ipe"
		));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
  }

}
class zonesTable extends Table {
  var $classname = "zonesTable";
  var $sql_table = "zones";
  var $primary_key = "id";
  var $primary_field = "id";
  var $all_fields = array(
			"domain",
			"serial",
			"refresh",
			"retry",
			"expire",
			"master",
			"zonefile",
			"options",
			"mtime",
			"ctime",
			"updated",
			"disabled",
			"owner",
			);

  /* comment out or delete some of these default entries so that the table isn't too wide for the screen */
  var $default = array(
			"domain",
			"serial",
			"refresh",
			"retry",
			"expire",
			"master",
			"zonefile",
			"options",
			"mtime",
			"ctime",
			"updated",
			"disabled",
			"owner",
			);

  var $numeric_fields = array(
			"serial",
			"refresh",
			"retry",
			"expire",
			"mtime",
			"ctime",
			"updated",
			"disabled",
			);

  // These fields will be searchable and displayed in results.
  // Format is "RealFieldName"=>"Field Name Formatted For Display",
  var $map_cols = array(
			"domain"=>"Domain",
			"serial"=>"Serial",
			"refresh"=>"Refresh",
			"retry"=>"Retry",
			"expire"=>"Expire",
			"master"=>"Master",
			"zonefile"=>"Zonefile",
			"options"=>"Options",
			"mtime"=>"Mtime",
			"ctime"=>"Ctime",
			"updated"=>"Updated",
			"disabled"=>"Disabled",
			"owner"=>"Owner",
			);

  // PHP money_format modifier for field; 
  // see http://www.php.net/manual/en/function.money-format.php
  var $format = array(
			'field'=>'format', 		# eg:
			'money'=>'%n',   		# local specific money format
			'percent'=>'%!.0n%%',   	# whole number with a % sign on the end
			'whole_number'=>'%!.0n',	# .p precision 
			#  '%=*(#10.2n'		($********1,234.57)
			#  '%=*^-14#8.2i'	Eu 1234,56****
			);

  // Extra HTML to be sent out with the elements listed
  var $extra_html = array(
			'fieldname'=>'extrahtml',
			);

  // Specific alignment to override the auto alignment for the field
  var $align      = array(
			'fieldname'=>'right', 
			'otherfield'=>'center',
			); 
}
class zones_Sql_Query extends Sql_Query {
  var $classname = "zones_Sql_Query";
  var $primary_key = "id";
  var $primary_field = "id";
  var $table = "zones";
}

