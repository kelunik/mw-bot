<?php

namespace Bit;

use Artax;

class MediaWiki {
    private $http;
    private $url;
    private $user;
    private $token;

    public function __construct(Artax\Client $http, $url) {
        $this->http = $http;
        $this->url = $url;
    }

    public function request($method, $data) {
		$method = strtoupper($method);
		
		if(is_array($data)) {
		    $data = array_filter($data, function($v) {
		        if($v === null || $v === false) {
		        	return false;
		        }
		        
		        return true;
		    });
		    
		    if($method === 'GET') {
		    	$data = http_build_query($data);
		    }
		}
		
		if($method === 'GET') {
			return $this->getRequest($data);
		} else if($method === 'POST') {
			return $this->postRequest($data);
		} else {
			throw new \InvalidArgumentException(
				"Unknown request method: " . $method
			);
		}
    }
    
    private function getRequest($data) {
        $request = new Artax\Request;
        $request->setMethod('GET');
        $request->setUri("{$this->url}?format=php&{$data}");

        $response = $this->http->request($request);

        if($response->getStatus() !== 200) {
        	$status = $response->getStatus();
        	$reason = $response->getReason();
        	
        	throw new RuntimeException("Request failed: {$status} {$reason}");
        }
        
        return unserialize($response->getBody());
    }

    public function postRequest($data) {
    	$body = new Artax\FormBody;

        foreach($data as $key => $value) {
            $body->addField($key, $value);
        }

        $request = new Artax\Request;
        $request->setMethod('POST');
        $request->setUri("{$this->url}?format=php");
        $request->setBody($body);
		
        $response = $this->http->request($request);

        if($response->getStatus() !== 200) {
        	$status = $response->getStatus();
        	$reason = $response->getReason();
        	
        	throw new RuntimeException("Request failed: {$status} {$reason}");
        }
        
        return unserialize($response->getBody());
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
        	$types = 'block|delete|edit|email|import|move|options|patrol|protect|unblock|watch';
            $response = $this->request('GET', 'action=tokens&type='.urlencode($types));

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
    
    public function uploadFile($remoteName, $localPath, array $options = []) {
        $options = array_merge([
            'bot' 		=> false,
            'comment' 	=> null,
            'text' 		=> null
        ], $options);
    	
    	if(!file_exists($localPath)) {
    		new \RuntimeException(
    			"the file does not exist: {$localPath}"
    		);
    	}
    	
    	$body = new Artax\FormBody;
    	
    	// add this before the token to ensure that file is sent completely
    	// otherwise it will result in a notoken error
    	$body->addFileField('file', $localPath);
		$body->addAllFields([
    		'action' 			=> 'upload',
    		'filename' 			=> $remoteName,
    		'comment' 			=> $options['comment'] ?: '',
    		'text' 				=> $options['text'] ?: '',
    		'ignorewarnings' 	=> 1,
    		'bot' 				=> (int) $options['bot'],
    		'token' 			=> $this->getToken('edit')
    	]);
        
        $request = (new Artax\Request)
        		->setMethod("POST")
        		->setUri("{$this->url}?format=php")
        		->setBody($body);
		
        $response = $this->http->request($request);

        if($response->getStatus() === 200) {
            $response = unserialize($response->getBody());
        } else {
            return false;
        }
        
        if(isset($response['upload']['result']) && $response['upload']['result'] === 'Success') {
        	return true;
    	}
    	
    	return false;
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
