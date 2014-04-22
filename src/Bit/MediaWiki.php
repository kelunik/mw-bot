<?php

namespace Bit;

use Artax;

class MediaWiki {
    private $http;
    private $url;
    private $user = null;
    private $token = null;

    public function __construct(Artax\Client $http, $url) {
        $this->http = $http;
        $this->url = $url;
    }

    public function request($method, $data) {
		$method = strtoupper($method);
		
		if($method === 'GET') {
			if(is_array($data)) {
				$q = "";
				
				foreach($data as $key => $value) {
					$q .= "&".$key."=".urlencode($value);
				}
				
				$data = substr($q, 1);
			}
			
			return $this->getRequest($data);
		} else if($method === 'POST') {
			return $this->postRequest($data);
		} else {
			throw new Exception("unknown method: " . $method);
		}
    }
    
    private function getRequest($data) {
        $request = new Artax\Request;
        $request->setMethod('GET');
        $request->setUri($this->url . "?format=php&" . $data);

        $response = $this->http->request($request);

        if($response->getStatus() === 200) {
            return unserialize($response->getBody());
        } else {
            return FALSE;
        }
    }

    public function postRequest($data) {
    	$body = new Artax\FormBody;

        foreach($data as $key => $value) {
            $body->addField($key, $value);
        }

        $request = new Artax\Request;
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
	
	// https://www.mediawiki.org/wiki/API:Login
    public function login($username, $password) {
    	$this->user = new User($this, $username, $password);
    }

    public function getUser() {
        return $this->user;
    }

    // https://www.mediawiki.org/wiki/API:Tokens
    public function getToken($key, $forceupdate = false) {
        if(is_null($this->token) or $forceupdate) {
            $response = $this->request('GET', 'action=tokens&type='.urlencode('block|delete|edit|email|import|move|options|patrol|protect|unblock|watch'));

            foreach($response['tokens'] as $k => $v) {
                $k = substr($k, 0, -5);
                $this->token[$k] = $v;
            }
        }

        return $this->token[$key];
    }
    
    public function get($title) {
    	return new Page($title, $this);
    }
    
    public function getDoubleRedirects($offest = "") {
		$limit = $this->getUser()->hasRight('bot') ? 5000 : 500;
		$q = "action=query&list=querypage&qppage=DoubleRedirects&qplimit=$limit";

		if(!empty($offset)) {
		    $q .= "&qpoffset=".$offset;
		}

		$response = $this->request('GET', $q);
		$pages = $response['query']['querypage']['results'];

		if(isset($response['query-continue'])) {
			$offset = $response['query-continue']['querypage']['qpoffset'];
			$more = $this->getDoubleRedirects($offset);
		    $pages = array_merge($pages, $more);
		}

		return $pages;
    }
} 
