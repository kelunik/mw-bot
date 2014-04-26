<?php

if(!defined('STDIN')) {
    die('This script is for command line usage only!');
}

$title = $argv[0];

$wiki->get($title)->protect([
    'edit' => 'sysop',
    'move' => 'sysop'
], '10 minutes', ['bot' => false]);
