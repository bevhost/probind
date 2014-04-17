#!/usr/bin/php -q
<?php
//This script requires the CLI version of PHP, die if the CGI edition tries to run us
if ( php_sapi_name() == 'cgi' )
{
	die ('Unsupported SAPI - please use the CLI binary');
} 

//Files we need to run this process
require_once dirname(dirname(__FILE__)) . '/phplib/prepend.php';
require_once dirname(dirname(__FILE__)) . '/inc/config.php';
require_once dirname(dirname(__FILE__)) . '/inc/clilib.inc';
require_once 'Console/Getopt.php';  //PEAR Console_Getopt

//Global variables
$options = array();
$options['annotate'] = 0;
$options['purge'] = 0;
$options['use_filename'] = 0;
$options['verbose'] = 0;
$conf_input_buffer = ''; //Used to hold lines that are being parsed
$zone_options = '';

function usage($errno = 0) 
{
//Limit line length to 70 characters for console compatibility
	fwrite(STDOUT,"Usage: import [options] filename

Note:
    The input filename must be in BIND 8 or 9 named.conf format.

Options:
    -a Copy the zone file as an annotation into the imported zone
    -v Provide verbose status messages
    -d Delete (purge) zone in ProBIND if imported zone exists
       Default is to merge data
    -F Use zone filenames specified in input named.conf
    -h Print this text

");
exit($errno);
}

// Check that the database is in a sane state for import.
// This should be cleaned up and rolled into a global include.
function verify_database()
{
	$errors = array();
	
	global $MYSQL_DB;
	$tables['annotations'] = 1;
	$tables['blackboard'] = 1;
	$tables['deleted_domains'] = 1;
	$tables['records'] = 1;
	$tables['servers'] = 1;
	$tables['typesort'] = 1;
	$tables['zones'] = 1;

	# Check if the tables are in the database
	$rid = mysql_list_tables($MYSQL_DB);
	for ($i=0; $i < mysql_num_rows($rid); $i++)
	{
		$tables[mysql_tablename($rid, $i)] = 0;
	}
	mysql_free_result($rid);
	
	while ($table = each($tables))
	{
		if ($table['value']) $errors[] = "ERROR: Table '".$table['key']."' is missing from the database.";
	}
	
	# Make sure that at least one BIND server exists
	$rid = sql_query("SELECT * FROM servers");
	if (!mysql_num_rows($rid))
	{
		$errors[] = "ERROR: No BIND servers defined in the database.";
	}
	mysql_free_result($rid);

	# Check that settings are available
	if (count($errors < 1)) //No critical DB errors found
	{
		$errors = database_state();
	}

	# Done
	return $errors;
}

# Return the next token from the $input filehandle
function next_conf_token($input)
{
	global $conf_input_buffer;
	global $options;

	// TRE looks for any of a hypen, word, and/or period OR anything surrounded by double-quotes OR a left OR right curly-brace OR a semi-colon
	$patern_token = '/([-\/\w\.]+|"[^"]*"|\{|\}|;)/';
	
	//This pair removes comments in the bind conf file and normalizes single-quotes to double quotes
	$srch = array("/#.*$/", "/\/\/.*$/", "/'/");
	$repl = array('', '', '"');
	
	//Parse the input file for the next token, normalizing our input
	while (!preg_match($patern_token, $conf_input_buffer, $matches))
	{
		// We hit the end of the file, return false
		if (feof($input))
		{
			return 0;
		}
		
		$tmp = fgets($input, 1024);
		$conf_input_buffer = preg_replace($srch, $repl, $tmp);
	}
	
	//Take our matching line and trim the left portion we matched - this is important so we can keep parsing the line properly 
	$pos = strpos($conf_input_buffer, $matches[1]) + strlen($matches[1]);
	$newlen = strlen($conf_input_buffer) - $pos;
	
	//Our input buffer now includes the next part of the line after the match.
	$conf_input_buffer = substr($conf_input_buffer, $pos, $newlen);
	
	// DEBUG - Keep this? It might be a bit *too* verbose for normal verbosity levels 	
	if($options['verbose']) fwrite(STDOUT, "Matched token: " . $matches[1] . "\n");
	
	//Return the matched token
	return $matches[1];
}

function load_zone($zname, $file, $zid, $zone, $master)
{
	global $SERVERS, $DEFAULT_PTR_DOMAIN, $options;
	
	$origin = "";
	$break = FALSE; //This shouldn't be used anywhere...

	if($options['verbose']) fwrite(STDOUT, "Loading '$file' for zone($zone)...\n");	
	
	//Check for file existance, return from function if it doesn't exist
	if( !($zonefile = fopen($file, 'r')))
	{
		fwrite(STDOUT, "ERROR: Cannot open '$file'\n");
		return;
	}



	
	# Extract the (usually multi-line) SOA
	while (!feof($zonefile) && !$break) {
		$raw = fgets($zonefile, 1024);
		if ($options['annotate'])
			$text .= $raw;
		$cooked = preg_replace('/;.*/', '', chop($raw));
		$fields = preg_split('/\s+/', $cooked, 0, PREG_SPLIT_NO_EMPTY);
		if (!count($fields))
			continue;
		if (strtoupper($fields[0]) == '$TTL') {
			# Get the default TTL spec
			$ttl = $fields[1];
			continue;
		}		
		if (strtoupper($fields[0]) == '$ORIGIN') {
			# Get origin
			$origin = $fields[1];
			continue;
		}		
		
		while ($field = each($fields)) {
			$soa[] = $field[1];
		}
		if (preg_match('/\)/', $cooked))
			break;
	}
	$mname = $soa[3];
	if ( $soa[6] != '(' ) {
	$serial = $soa[6];
	$refresh = ttl_to_seconds($soa[7]);
	$retry = ttl_to_seconds($soa[8]);
	$expire = ttl_to_seconds($soa[9]);
	$ttl = ttl_to_seconds($soa[10]);
	}
	else {
	    $serial = $soa[7];
	    $refresh = ttl_to_seconds($soa[8]);
	    $retry = ttl_to_seconds($soa[9]);
	    $expire = ttl_to_seconds($soa[10]);
	    $ttl = ttl_to_seconds($soa[11]);
	}
	if ($soa[0] == '@') {
		$lastdomain = $origin;
	} else {
		$lastdomain = $soa[0];
	}
	if (!preg_match('/\.$/', $lastdomain) && $origin) {
		if ($origin == '.') {
			$lastdomain = $lastdomain.'.';
		} else {
			$lastdomain = $lastdomain.'.'.$origin;
		}
	}
	$parsecount++;
	$insertcount++;
	if (strtoupper($lastdomain) == strtoupper("$zone."))
		$lastdomain = "@";
	insert_record($zid, $lastdomain, $ttl, 'SOA', '', '', 0);
	if ( $opt['F'])
		update_zone($zid, $serial, $refresh, $retry, $expire, $master);	
	else
		update_zone($zid, $serial, $refresh, $retry, $expire, $master);
	# Get the rest
	while (!feof($zonefile) && !$break) {
		$raw = fgets($zonefile, 1024);
		if ($options['annotate'])
			$text .= $raw;
		$cooked = preg_replace('/;.*/', '', chop($raw));
		$fields = preg_split('/\s+/', $cooked);
		if (!strlen(join('', $fields)))	# skip empty lines
			continue;
		if (strtoupper($fields[0]) == '$TTL') {
			# Get the default TTL spec
			$ttl = $fields[1];
			continue;
		}
		if (strtoupper($fields[0]) == '$ORIGIN') {
			# Get origin
			$origin = $fields[1];
			if (strtoupper($origin) == strtoupper("$zone.")) {
				$origin = "";
			} else {
				$origin = preg_replace("/.$zone\.$/i",'', $origin);
			}
			continue;
		}		
		
		if (preg_match('/^\s/', $cooked)) {
			# record starts with a whitespace
			# more records for the previous domain
			$domain = $lastdomain;
			array_shift($fields); # Loose the empty field
		} else {
			$domain = array_shift($fields);
			if ($domain == '@') {
				$domain = $origin;
			}	
		
			if (!preg_match('/\.$/', $domain) && $origin) {
				$domain = $domain.'.'.$origin;
		} 
		} 
			
		# Check for optional per-RR TTL spec
		$rrttl = ttl_to_seconds($fields[0]);
		if (!strcasecmp(seconds_to_ttl($rrttl), $fields[0])) {
			# Explicit per-RR TTL
			# the ttl is specified with wdms
			array_shift($fields); # loose the ttl field
		} elseif (preg_match('/^\d+$/', $fields[0])) {
			# Explicit per-RR TTL
			# the ttl is specified as seconds
			$rrttl = array_shift($fields);
		} else {
			# Implicit TTL for this RR
			$rrttl = $ttl;
		}

		if (strtoupper($fields[0]) == 'IN') {
			# Get rid of the optional 'IN'
			array_shift($fields);
		}
		$type = strtoupper(array_shift($fields));
		if ($type == 'MX')
			$pref = array_shift($fields);
		else
			$pref = '';
		$data = ltrim(rtrim(join(' ', $fields)));
		if ($type == 'TXT')
			$data = preg_replace('/"/', '', $data);
		$parsecount++;
		$lastdomain = $domain;
		# Skip NS records which will be autogenerated in zonefiles
		# output from mkzonefile
		if ($type == 'NS' && $SERVERS[$data]) {
			continue;
		}
		# Skip PTR records which will be autogenerated in zonefiles
		# output from mkzonefile
		if (preg_match("/\.in-addr.arpa\.?$/", $zone) 
		&& $type == 'PTR' 
		&& preg_match("/^host-\d+-\d+-\d+-$domain\.$DEFAULT_PTR_DOMAIN/", $data)) {
			continue;
		}
		$w = validate_record($zid, $domain, $rrttl, $type, $pref, $data);
		if ($w) {
			print "WARNING: Invalid record: '$cooked', $w\n";
			print " zone '$zone' domain '$domain' ttl '$rrttl'";
			print " type '$type' pref '$pref' data '$data'\n";
			continue;
		}
		insert_record($zid, $domain, $rrttl, $type, $pref, $data, 0);
		$insertcount++;
	}
	fclose($zonefile);
	$comment = preg_replace("/'/", '"', $text);
	if ($options['annotate']) {
		$query = "INSERT INTO annotations (zone, descr) VALUES($zid, '$comment')";
		sql_query($query);
	}
	if ($options['verbose'])
		print "Imported $insertcount of $parsecount resource records.\n";
	return $count;
}

function parse_type($input)
{
	$type = strtolower(next_conf_token($input));
	if ($type != "master" && $type != "slave") {
		fwrite(STDOUT, "WARNING: misplaced token '$type'\n");
		return FALSE;
	}
	next_conf_token($input); # Skip the trailing semicolon
	return $type;
}

function parse_file($input)
{
	$tmp = next_conf_token($input);
	$file = preg_replace('/"/', '', $tmp);
	next_conf_token($input);	# Skip trailing semicolon
	return $file;
}

function parse_masters($input, $type)
{
	if ($type != "slave") {
		print "Warning: Masters section in '$zone', but type is '$type'\n";
		return FALSE;
	}
	next_conf_token($input); # Skip the leading brace
	$masters = strtolower(next_conf_token($input));
	next_conf_token($input); # Skip the trailing brace
	next_conf_token($input); # Skip the trailing semicolon
	print "masters = '$masters'\n";
	return $masters;
}

// Called when a zone entry is found in the named.conf. This function parses the zone entry in
// named.conf and calls what is needed to complete. 
function parse_zone($input)
{
	//Load called options
	global $options;
	
	//Brace level we are parsing at
	$braces = 0;
	
	//We were called because of the "zone" keyword. Get the next token in the stream, which will be the zone name.
	$token = strtolower(next_conf_token($input));
	
	//If the zone is the root server cache entry, skip it.
	if ($token == '"."')
	{
		return;
	}

	// Clean the quotation marks from the zone name
	$zone = preg_replace('/"/', '', $token);

	if($options['verbose']) fwrite(STDOUT, "Found zone: '$zone'\n");

	//Grab the next token and see if it's a zone class, or a brace
	$token = strtolower(next_conf_token($input));
	
	if ($token != '{')
	{
		//We only accept zones in the internet (IN) class. Return with an warning message if the zone is elsewhere
		if ($token != 'in')
		{
			fwrite(STDERR, "WARNING: Zone $zone uses an unsupported zone class or malformed data was encountered. Could not import.\n");
			return;
		}
		
		//Now, get the next token. It needs to be an opening brace, or we exit. 
		$token = strtolower(next_conf_token($input));
	
		if ($token != '{')
		{
			fwrite(STDOUT, "FATAL: Unexpected input after zone class. Exiting.\n");
			exit(1);
		}
	}

	// We got to our first opening braces, increment the counter
	$braces++;

	while ($braces > 0)
	{
		switch ($token = strtolower(next_conf_token($input)))
		{
			case 'type';
				$in_options = 0;
				if(!$type = parse_type($input))
					return;
				break;
			case 'file';
				$in_options = 0;
				if (!$file = parse_file($input))
					return;
				break;
			case 'masters';
				$in_options = 0;
				if (!$masters = parse_masters($input, $type))
					return;
				break;
			case '{':
				$braces++;
				break;
			case '}':
				$braces--;
				if ($braces == 0)
					$in_options = 0;
				break;
			default:
				$in_options = 1;
				break;
	#		default:
	#			print "Warning: misplaced '$token' in $zone, expected 'file', 'type' or 'masters'\n";
		}

		if ($in_options)
		{
			if ($zone_options && $token != ';' )
			{
				$zone_options .= " ";
			}

			$zone_options .= $token;

			if ( $token == "{" || $token == ';')
			{
				$zone_options .= "\n";
			}
		}
	}
	next_conf_token($input); # Skip the trailing semicolon
	if (!$type
	|| (($type == "master") && !$file)
	|| (($type == "slave") && !master)) {
		print "Warning: Cannot parse '$zone'\n";
		return;
	}
	if ($info = get_named_zone($zone)) {
		print "Warning: '$zone' already exists in the database. options = $zone_options\n";
		if (!$options['purge'])
		return;
		$id = $info['id'];
		del_zone($id);				        
	}
	if ( $zone == "0.0.127.in-addr.arpa" ) {
		print "******************************************************************************************************\n";
		print "ATTENTION. Zone 0.0.127.in-addr.arpa imported. Be sure that this zone was not defined in the template!\n";
		print "Delete it from the data base if you do want to keep it in the template\n";
		print "******************************************************************************************************\n";
	}
	if ($options['verbose'])
		print "Adding zone '$zone', master = '$master'\n";
	$zid = add_domain($zone, $masters, $zone_options);
	sql_query("DELETE FROM records WHERE zone = $zid");
	if ($type == "master") 
		$count = load_zone($zone, $file, $zid, $zone, $master);
	if ($options['verbose'])
		print "Loaded '$zone' options=$zone_options\n\n";
}

#
# MAIN
#
# Parse command line and initialize

//Set an unlimited run time - This script can run for quite some time on large environments
set_time_limit(0);

//Print the program title/header
fwrite(STDOUT, "ProBIND Bulk Zone Import Utility\n\n");

//Get our incoming arguments
$argv = Console_Getopt::readPHPArgv();

//The list of our "short" options
$opts = 'avdFh';

//How were we called? We need to figure out which getOpt to call
if ( realpath($_SERVER['argv'][0]) == __FILE__ )
{
	//We were called as "php import", so we use plain getOpt
	$flags = Console_Getopt::getOpt($argv,$opts);
}
else
{
	//We were called as "import" so use getOpt2
	$flags = Console_Getopt::getOpt2($argv,$opts);
}

//parse the options to set variables
$optflags = $flags[0];

if(sizeof($optflags) > 0)
{
	foreach($optflags as $flag)
	{
		switch ($flag[0])
		{
			case 'a':
				$options['annotate'] = 1;
				break;
			case 'v':
				$options['verbose'] = 1;
				break;
			case 'd':
				$options['purge'] = 1;
				break;
			case 'F':
				$options['use_filename'] = 1;
				break;
			case 'h':
				usage();
				break;
		}
	}
}

//Do a quick check to make sure ProBIND is properly configured
if($options['verbose']) fwrite(STDOUT, "Checking database for readyness...\n");

if ($err = verify_database())
{
	fwrite(STDERR,"Aborting due to the following errors:\n");
	foreach ($err as $errmsg)
	{
		fwrite(STDERR,'  ' . $errmsg . "\n");
	}
	exit(1);
}

if($options['verbose']) fwrite(STDOUT, "Okay.\n\n");

//Find entered nameservers that are selected to be NS records for domains ProBIND manages
$rid = sql_query("SELECT hostname FROM servers WHERE mknsrec");

if($options['verbose']) fwrite(STDOUT, "Found ".mysql_num_rows($rid)." servers in the database with NS records.\n");

//Build an array of servers that are slated to have NS records for the imported zones.
while ($server = mysql_fetch_row($rid))
{
	//We add the terminating dot here to make the name a FQDN (see RFC 1034)
	$SERVERS[$server[0]."."] = 1;
}

mysql_free_result($rid);

//Get our input named.conf-format file and determine if it exists
//Get our import zone file
if(sizeof($flags[1]) > 0)
{
	$zonefile = $flags[1][0];
	
	if(file_exists($zonefile))
	{
		$input = fopen($zonefile, "r");		
	}
	else
	{
		fwrite(STDERR, "ERROR: Input file must exist\n");
		exit(1);
	}
}
else
{
	usage(1); 
	exit(1);
}

//Run through the named.conf file scanning for config tokens
while ($token = next_conf_token($input))
{
	//If we hit a zone, call parse_zone
	if ($token == "zone") parse_zone($input);
}

?>
