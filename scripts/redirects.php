<?php

$pages = $wiki->getDoubleRedirects();

foreach($pages as $page) {
    $p = $wiki->get($page['title']);
    $p->setContent('#REDIRECT [[' . $page['databaseResult']['tc'] . ']]');
    $p->save(null, ['bot' => true]);
}
