<?php

function checkConfig($config) {
	if($config === false) {
		return false;
	}
	
	if(!isset($config['wiki']['name'], $config['wiki']['url'])) {
		return false;
	}
	
	return true;
}

function prompt($str, $hidden = false) {
    if($hidden) {
        system('stty -echo');
    }

    print "$str: ";
    $input = substr(fgets(STDIN), 0, -1);

    if($hidden) {
        system('stty echo');
        print "\n"; // <-- add hidden newline
    }

    return $input;
}
