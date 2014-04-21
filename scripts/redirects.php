<?php

function listRedirects($offset = "") {
	global $wiki;
	
    $limit = $wiki->getUser()->hasRight('bot') ? 5000 : 500;
    $q = "action=query&list=querypage&qppage=DoubleRedirects&qplimit=$limit";

    if(!empty($offset)) {
        $q .= "&qpoffset=".$offset;
    }

    $response = $wiki->request('GET', $q);

    $articles = $response['query']['querypage']['results'];

    if(isset($response['query-continue'])) {
        $articles = array_merge($articles, listRedirects($response['query-continue']['querypage']['qpoffset']));
    }

    return $articles;
};

$pages = listRedirects();

foreach($pages as $page) {
    $wikiPage = $wiki->get($page['title']);
    $wikiPage->setContent('#REDIRECT [[' . $page['databaseResult']['tc'] . ']]');
    $wikiPage->save(null, [
        'bot' => true
    ]);
}
