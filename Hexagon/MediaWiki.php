<?php

namespace Hexagon;

use Artax;

class MediaWiki {
    private $http = null;
    private $url = null;
    private $user = null;
    private $token = null;

    public function __construct(Artax\Client $http, $url) {
        $this->http = $http;
        $this->url = $url;
    }

    public function get($query) {
        $request = new Request;
        $request->setMethod('GET');
        $request->setUri($this->url . "?format=php&" . $query);

        $response = $this->http->request($request);

        if($response->getStatus() === 200) {
            return unserialize($response->getBody());
        } else {
            return FALSE;
        }
    }

    public function post($data) {
        $body = new FormBody;

        foreach($data as $key => $value) {
            $body->addField($key, urlencode($value));
        }

        $request = new Request;
        $request->setMethod('POST');
        $request->setUri($this->url . "?format=php");
        $request->setBody($body);

        $response = $this->http->request($request);

        if($response->getStatus() === 200) {
            return unserialize($response->getBody());
        } else {
            return FALSE;
        }
    }

    public function getPage($title) {
        return new Page($title, $this);
    }

    public function login($username, $password) {
        try {
            $this->user = new User($this, $username, $password);
        } catch(Exception $e) {
            Console::error($e->getMessage());
        }
    }

    public function getUser() {
        return $this->user;
    }

    // https://www.mediawiki.org/wiki/API:Tokens
    public function getToken($key) {
        if(is_null($this->token)) {
            $response = $this->get('action=tokens&type=block|delete|edit|email|import|move|options|patrol|protect|unblock|watch');

            foreach($response['tokens'] as $k => $v) {
                $k = substr($k, 0, -5);
                $this->token[$k] = $v;
            }
        }

        return $this->token[$key];
    }

    public function getBashString() {
        if(!is_null($this->user)) {
            $str = $this->user->getUsername();
        } else {
            $str = "anonymous";
        }

        return "$str $";
    }
} 