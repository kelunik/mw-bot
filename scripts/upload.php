<?php

if(!defined('STDIN')) {
    die('This script is for command line usage only!');
}

$wiki->uploadFile($argv[0], $argv[1]);
