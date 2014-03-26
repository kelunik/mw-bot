<?php

require_once __DIR__ . "/autoload.php";

print <<<WELCOME

####################################################
#    WELCOME TO HEXAGON    |    A MEDIAWIKI BOT    #
####################################################


WELCOME;

$api = "http://mediawiki.org/w/api.php";
$user = Hexagon\Console::prompt("Your Username");
$pass = Hexagon\Console::prompt("Your Password", true);

$http = new Artax\Client;

// add cookie support
$httpExt = new Artax\Ext\Cookies\CookieExtension;
$httpExt->extend($http);


$wiki = new Hexagon\MediaWiki($http, $api);

// login
$wiki->login($user, $pass);

$page = $wiki->getPage('Sample');

// loads page and returns wikitext
$content = $page->getContent();

// replace some text, do your task here
$content = str_replace("Helo World.", "Hello World!", $content);

// set wikitext as text of this page
$page->setContent($content);

// save the page with a summary ("Bot: " will automatically prepended) as a normal bot edit (not minor)
// TODO: options as a assoc array
$page->save("your summary goes here", false, true);