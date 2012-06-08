<?php 

$db   = new $_ENV["DatabaseClass"];
$tab  = "session_stats";

$now = date("YmdHis", time());
$query = sprintf("insert into %s ( name,  sid, start_time, referer, addr, user_agent ) values ( '%s', '%s',       '%s',    '%s', '%s',       '%s' )",
  $tab,
  $sess->name,
  $sess->id,
  $now,
  @$_SERVER['HTTP_REFERER'],
  $_SERVER['REMOTE_ADDR'],
  $_SERVER['HTTP_USER_AGENT']);

#$db->query($query);
?>
