<?php

if(!defined('STDIN')) {
    die('This script is for command line usage only!');
}

if(sizeof($argv) !== 1) {
echo <<<MESSAGE
#
# usage: mwbot remove-template "TEMPLATENAME"
#
# sample: mwbot remove-template "Template:Documentationpage"
#

MESSAGE;
exit;
}


// TODO replace {{T}} also replaces {{TO}}
$removeTemplate = function($template, $text) use (&$removeTemplate) {
	$needle = "{{{$template}";
	$result = "";
	
	if(($off = strpos($text, $needle)) !== false) {
		$opened = 2;
		$closed = $i = 0;
		
		$size = strlen($text);
		
		$start = $off == 0 ? '' : substr($text, 0, $off - 1);
		$leftover = substr($text, $off + 2);
		
		while($opened > $closed && $i < $size) {
			if($leftover[$i] === "{") $opened++;
			if($leftover[$i] === "}") $closed++;
			
			$i++;
		}
		
		if($i < $size || $opened === $closed && $leftover[$i - 1] !== "}") {
			return $removeTemplate($template, $start.substr($leftover, $i));
		}
	}
	
	return $text;
};

$pages = $wiki->listEmbeddedIn($argv[0]);

list($namespace, $template) = explode(':', $argv[0], 2);

if(!empty($namespace)) {
	$namespaces = $wiki->request('GET', [
		'action'	=> 'query',
		'meta'		=> 'siteinfo',
		'siprop'	=> 'namespaces'
	])['query']['namespaces'];
	
	foreach($namespaces as $ns) {
		if($ns['*'] === $namespace || isset($ns['canonical']) && $ns['canonical'] === $namespace) {
			$namespace = $ns;
			break;
		}
	}
} else {
	$namespace = ['*' => $namespace];
}

if(!is_array($namespace)) {
	die("You have probably typed a non-existent namespace.");
}

foreach($pages as $page) {
	$p = $wiki->get($page['title']);
	$t = $p->getContent();
	$t = $removeTemplate("{$namespace['*']}:{$template}", $t);
	
	if(isset($namespace['canonical'])) {
		$t = $removeTemplate("{$namespace['canonical']}:{$template}", $t);
		
		if($namespace['canonical'] === 'Template') {
			$t = $removeTemplate("{$template}", $t);
		}
	}
	
	$p->setContent($t)->save("removed {{{$argv[0]}}}", ['bot' => true]);
}
