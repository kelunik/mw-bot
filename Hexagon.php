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

while(true) {
    $cmd = Hexagon\Console::prompt($wiki->getBashString());

    if($cmd == "exit" || $cmd == "logout") {
        break;
    }

    if(file_exists(__DIR__ . "/" . $cmd)) {
        include __DIR__ . "/" . $cmd;
    }
}