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
		"valid_regex"=>"^[0-9|\-]+$","field"=>"zone",
		"valid_e"=>"Zone must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"domain","maxlength"=>"100","size"=>"33"
		,"minlength"=>1,"length_e"=>"Domain must not be blank.  Required field.",
		"field"=>"domain","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"ttl","maxlength"=>"15","size"=>"5",
		"field"=>"ttl","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"type","maxlength"=>"10","size"=>"3"
		,"minlength"=>1,"length_e"=>"Type must not be blank.  Required field.",
		"field"=>"type","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"pref","maxlength"=>"5","size"=>"1",
		"field"=>"pref","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"data","maxlength"=>"255","size"=>"85"
		,"minlength"=>1,"length_e"=>"Data must not be blank.  Required field.",
		"field"=>"data","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"comment","maxlength"=>"32","size"=>"10",
		"field"=>"comment","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"date","name"=>"mtime","maxlength"=>"","size"=>"0"
		,"minlength"=>1,"length_e"=>"Mtime must not be blank.  Required field.",
		"field"=>"mtime","extrahtml"=>" onBlur=\"ajax_update_element(this,'find.php?dt=')\""
		,"valid_e"=>"Invalid Date","valid_regex"=>"timestamp","value"=>"CURRENT_TIMESTAMP"));
    $this->form_data->add_element(array("type"=>"date","name"=>"ctime","maxlength"=>"","size"=>"0"
		,"minlength"=>1,"length_e"=>"Ctime must not be blank.  Required field.",
		"field"=>"ctime","extrahtml"=>" onBlur=\"ajax_update_element(this,'find.php?dt=')\""
		,"valid_e"=>"Invalid Date","valid_regex"=>"timestamp","value"=>"0000-00-00 00:00:00"));
    $this->form_data->add_element(array("type"=>"text","name"=>"weight","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$|^$","field"=>"weight",
		"extrahtml"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"port","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$|^$","field"=>"port",
		"extrahtml"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"genptr","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$","field"=>"genptr",
		"extrahtml"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"disabled","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$","field"=>"disabled",
		"extrahtml"=>"","value"=>"0"));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
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
			"ttl"=>"Ttl",
			"type"=>"Type",
			"pref"=>"Pref",
			"data"=>"Data",
			"comment"=>"Comment",
			"mtime"=>"Mtime",
			"ctime"=>"Ctime",
			"weight"=>"Weight",
			"port"=>"Port",
			"genptr"=>"Genptr",
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

