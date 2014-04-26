<?php

if(!defined('STDIN')) {
    die('This script is for command line usage only!');
}

$p = $wiki->get('Sample');
$p->setContent($p->getContent() . '.');
$p->save('basic sample edit');
