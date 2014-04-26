<?php

if(!defined('STDIN')) {
    die('This script is for command line usage only!');
}

$wiki->block($argv[0], @$argv[1] ?: '1 week');
