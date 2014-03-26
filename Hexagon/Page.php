<?php

namespace Hexagon;

class Page {
    private $wiki;
    private $id;
    private $title;
    private $content;
    private $timestamp;

    public function __construct($title, $wiki) {
        $this->title = $title;
        $this->wiki = $wiki;
    }

    public function getID() {
        if($this->id === null) {
            $title = urlencode($this->title);
            $q = "action=query&prop=revisions&titles=$title&rvlimit=1";

            $response = $this->wiki->get($q);
            $page = current($response['query']['pages']);

            if(!isset($page['missing'])) {
                $this->id = $page['pageid'];
            }
        }

        return $this->id;
    }

    // https://www.mediawiki.org/wiki/API:Properties#Revisions:_Example
    public function getContent($rev = null) {
        if($this->content === null) {
            $title = urlencode($this->title);
            $q = "action=query&prop=revisions&titles=$title&rvlimit=1&rvprop=content|timestamp";

            if(!is_null($rev)) {
                $q .= "&rvstartid=".$rev;
            }

            $response = $this->wiki->get($q);
            $page = current($response['query']['pages']);

            if(isset($page['missing'])) {
                $this->content = false; // page doesn't exist
            } else {
                $this->content = $page['revisions'][0]['*'];
                $this->timestamp = $page['revisions'][0]['timestamp'];
            }
        }

        return $this->content;
    }

    public function setContent($content) {
        $this->content = $content;
    }

    // https://www.mediawiki.org/wiki/API:Edit
    public function save($message, $minor = false, $bot = false, $checkConflict = false) {
        if(strcasecmp("Bot: ", substr($message, 0, 5)) !== 0) {
            $message = "Bot: $message";
        }

        $minor  = $minor    ? 'minor'   : 'notminor';
        $bot    = $bot      ? 'bot'     : 'notbot';

        $data = [
            'action'    => 'edit',
            'title'     => $this->title,
            'text'      => $this->content,
            'md5'       => md5($this->content),
            'summary'   => $message,
            $minor      => '1',
            $bot        => '1',
            'token'     => $this->wiki->getToken('edit')
        ];

        if($checkConflict) {
            $data['basetimestamp'] = $this->timestamp;
        }

        $response = $this->wiki->post($data);

        if(isset($response['edit']['result']) and $response['edit']['result'] === "Success") {
            $this->timestamp = $response['edit']['newtimestamp'];

            return true;
        }

        if(isset($response['error'])) {
            Console::error(" Failure: Couldn't save " . $this->title);
            Console::error("  Reason: " . $response['error']['info']);
            Console::error("    Code: " . $response['error']['code']);
            Console::error("");

            return false;
        }
    }

    // Implementation from http://en.wikipedia.org/wiki/Template:Bots
    public function isExcluded($user = null) {
        if(is_null($user)) {
            $user = $this->wiki->getUser()->getUsername();
        }

        if(preg_match('/\{\{(nobots|bots\|allow=none|bots\|deny=all|bots\|optout=all|bots\|deny=.*?'.preg_quote($user, '/').'.*?)\}\}/iS', $this->getContent())) {
            return true;
        }

        if(preg_match('/\{\{(bots\|allow=all|bots\|allow=.*?'.preg_quote($user, '/').'.*?)\}\}/iS', $this->getContent())) {
            return false;
        }

        if(preg_match('/\{\{(bots\|allow=.*?)\}\}/iS', $this->getContent())) {
            return true;
        }

        return false;
    }

    public static function exists($title, $wiki) {
        return (new Page($title, $wiki))->getID() !== null;
    }
} 