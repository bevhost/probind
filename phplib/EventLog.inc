<?php

class EventLogform extends tpl_form {
  var $table = "EventLog";
  var $key = "id";
  var $key_field = "id";
  var $classname = "EventLogform";
  var $database_class = "DB_probind";

  function setup_fields () {
    $this->form_data->add_element(array("type"=>"hidden","name"=>"id","field"=>"id","size"=>"12"));
    $this->form_data->add_element(array("type"=>"date","name"=>"EventTime","maxlength"=>"","size"=>"0"
		,"minlength"=>1,"length_e"=>"Event Time must not be blank.  Required field.",
		"field"=>"EventTime","extrahtml"=>" onBlur=\"ajax_update_element(this,'find.php?dt=')\""
		,"valid_e"=>"Invalid Date","valid_regex"=>"timestamp","value"=>"CURRENT_TIMESTAMP"));
    $this->form_data->add_element(array("type"=>"text","name"=>"Program","maxlength"=>"64","size"=>"21"
		,"minlength"=>1,"length_e"=>"Program must not be blank.  Required field.",
		"field"=>"Program","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"IPAddress","maxlength"=>"20","size"=>"6",
		"field"=>"IPAddress","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"UserName","maxlength"=>"100","size"=>"33"
		,"minlength"=>1,"length_e"=>"User Name must not be blank.  Required field.",
		"field"=>"UserName","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"Description","maxlength"=>"100","size"=>"33"
		,"minlength"=>1,"length_e"=>"Description must not be blank.  Required field.",
		"field"=>"Description","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"textarea","name"=>"ExtraInfo","rows"=>"5","cols"=>"50",
		"field"=>"ExtraInfo","extrahtml"=>""));
    $this->form_data->add_element(array("type"=>"select","name"=>"Level","field"=>"Level",
		"extrahtml"=>"class='dropdownMenu'",
		"options"=>array('Info','Warning','Error','Debug'),"value"=>"Info"));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
  }

}
class EventLogTable extends Table {
  var $classname = "EventLogTable";
  var $sql_table = "EventLog";
  var $primary_key = "id";
  var $primary_field = "id";
  var $all_fields = array(
			"EventTime",
			"Program",
			"IPAddress",
			"UserName",
			"Description",
			"Level");

  /* comment out or delete some of these default entries so that the table isn't too wide for the screen */
  var $default = array(
			"EventTime",
			"Program",
			"IPAddress",
			"UserName",
			"Description",
			"Level");

  // These fields will be searchable and displayed in results.
  // Format is "RealFieldName"=>"Field Name Formatted For Display",
  var $map_cols = array(
			"EventTime"=>"Event Time",
			"Program"=>"Program",
			"IPAddress"=>"I P Address",
			"UserName"=>"User Name",
			"Description"=>"Description",
			"Level"=>"Level");
}
class EventLog_Sql_Query extends Sql_Query {
  var $classname = "EventLog_Sql_Query";
  var $primary_key = "id";
  var $primary_field = "id";
}

