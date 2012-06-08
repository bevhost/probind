<?php

# Usage:
#   $optionhash = getopts($argv, 'a:bc');
# -a takes arg. -b & -c not. Sets keys in $optionshash

function getopts(&$argv, $argspec) 
{
	$errs = 0;
	array_shift($argv);
	$args = preg_split("/ */", $argspec, -1, PREG_SPLIT_NO_EMPTY);
	while (($item = $argv[0])
	&& preg_match("/^-(.)(.*)/", $item, $matches)) {
		list($full, $first, $rest) = $matches;
		$pos = strpos($argspec, $first);
# print "item = '$item', argv = '$argv[0]', first = '$first', rest  = '$rest'\n";
		if ($pos >= 0) {
			if ($args[$pos+1] && ($args[$pos+1] == ':')) {
				array_shift($argv);
				if ($rest == '') {
					if (!$argv) ++$errs;
					$rest = array_shift($argv);
				}
				$values[$first] = $rest;
			} else {
				$values[$first] = 1;
				if ($rest == '') {
					array_shift($argv);
				} else {
					$argv[0] = "-$rest";
				}
			}
		} else {
			print "Unknown option: $first\n";
			++$errs;
			if ($rest != '')
				$argv[0] = "-$rest";
			else
				array_shift($argv);
		}
	}
	return $values;
}
