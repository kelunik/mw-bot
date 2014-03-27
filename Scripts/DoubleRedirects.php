<?php

$listDoubleRedirects = function($offset = "") {
    global $wiki, $listDoubleRedirects;

    $limit = $wiki->getUser()->hasRight('bot') ? 5000 : 500;
    $q = "action=query&list=querypage&qppage=DoubleRedirects&qplimit=$limit";

    if(!empty($offset)) {
        $q .= "&qpoffset=".$offset;
    }

    $response = $wiki->get($q);

    $articles = $response['query']['querypage']['results'];

    if(isset($response['query-continue'])) {
        $articles = array_merge($articles, $listDoubleRedirects($response['query-continue']['querypage']['qpoffset']));
    }

    return $articles;
};

$pages = $listDoubleRedirects();

foreach($pages as $page) {
    $wikiPage = $wiki->getPage($page['title']);
    $wikiPage->setContent('#REDIRECT [[' . $page['databaseResult']['tc'] . ']]');
    $wikiPage->save('Doppelte Weiterleitung korrigiert.', [
        'bot' => true,
        'minor' => true
    ]);
}