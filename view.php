<?php
require_once('inc/lib.inc');

$html_top = '

<HTML><HEAD>
<TITLE>File viewer</TITLE>
<LINK rel="stylesheet" href="style.css" type="text/css">
</HEAD><BODY bgcolor="ccdd88" >
';

$html_bottom = '
</BODY>
</HTML>
';

#
# MAIN
#
get_input();

if (!empty($INPUT_VARS['error']))
    $color='RED';
else
    $color = 'BLACK';

print $html_top;
$file = false;
if (isset($INPUT_VARS['file'])) $file = $INPUT_VARS['file'];
if (!empty($INPUT_VARS['base']) && $INPUT_VARS['base'] == "LOGS") {
    $base = $LOG_DIR;
	$tbase = "Log file";
}
else {
    $base = $HOST_DIR;
	$tbase = "File";
}
	
if ($file) {
	if (preg_match('/\.\./', $file)) {
    	print "<H3>Incorrect file name $file</H3>\n";
	}

	print "<H3>$tbase: $file</H3>\n";
	print "<HR><PRE>\n";
    print "<FONT COLOR=$color>\n";
	if (file_exists("$base/$file")) {
		print join("",file("$base/$file"));
	} else {
		print "That file does not exist.";
	}
	print "</FONT>\n";
	print "</PRE>\n";
};
print $html_bottom;
?>

