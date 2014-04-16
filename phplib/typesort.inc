<?php

class typesortform extends tpl_form {
  var $table = "typesort";
  var $key = "";
  var $key_field = ""; # if different to $key
  var $classname = "typesortform";
  var $database_class = "DB_probind";
  var $default_query = "Id>''";  

  function setup_fields () {
    $this->form_data->add_element(array("type"=>"text","name"=>"type","maxlength"=>"10","size"=>"3"
		,"minlength"=>1,"length_e"=>"Type must not be blank.  Required field.",
		"field"=>"type","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"ord","size"=>"12",
		"valid_regex"=>"^[0-9|\-]+$","field"=>"ord",
		"valid_e"=>"Ord must be a whole number containing digits 0-9 only. May also start with negative symbol -",
		"extrahtml"=>""));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
  }

}
class typesortTable extends Table {
  var $classname = "typesortTable";
  var $sql_table = "typesort";
  var $primary_key = "";
  var $primary_field = "";
  var $all_fields = array(
			"type",
			"ord",
			);

  /* comment out or delete some of these default entries so that the table isn't too wide for the screen */
  var $default = array(
			"type",
			"ord",
			);

  var $numeric_fields = array(
			"ord",
			);

  // These fields will be searchable and displayed in results.
  // Format is "RealFieldName"=>"Field Name Formatted For Display",
  var $map_cols = array(
			"type"=>"Type",
			"ord"=>"Ord",
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
class typesort_Sql_Query extends Sql_Query {
  var $classname = "typesort_Sql_Query";
  var $primary_key = "";
  var $primary_field = "";
  var $table = "typesort";
}

