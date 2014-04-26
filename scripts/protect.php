<?php

if(!defined('STDIN')) {
    die('This script is for command line usage only!');
}

$title = $argv[0];

$wiki->get($title)->protect([
    'edit' => 'sysop',
    'move' => 'sysop'
], @$argv[1] ?: '1 week', ['bot' => false]);
