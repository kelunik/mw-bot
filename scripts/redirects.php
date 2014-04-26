<?php

if(!defined('STDIN')) {
    die('This script is for command line usage only!');
}

$pages = $wiki->listDoubleRedirects();

foreach($pages as $page) {
    $p = $wiki->get($page['title']);
    $p->setContent('#REDIRECT [[' . $page['databaseResult']['tc'] . ']]');
    $p->save(null, ['bot' => true]);
}
