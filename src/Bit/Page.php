<?php

namespace Bit;

class Page {
    private $wiki;
    private $id;
    private $title;
    private $content;
    private $remoteContent;
    private $timestamp;

    public function __construct($title, $wiki) {
        $this->title = $title;
        $this->wiki = $wiki;
    }

    public function getID() {
        if($this->id === null) {
            $title = urlencode($this->title);
            $q = "action=query&prop=revisions&titles=$title&rvlimit=1";

            $response = $this->wiki->request('GET', $q);
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

            $response = $this->wiki->request('GET', $q);
            $page = current($response['query']['pages']);

            if(isset($page['missing'])) {
                $this->content = false; // page doesn't exist
                $this->remoteContent = "";
            } else {
                $this->content = $page['revisions'][0]['*'];
                $this->remoteContent = $page['revisions'][0]['*'];
                $this->timestamp = $page['revisions'][0]['timestamp'];
            }
        }

        return $this->content;
    }

    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    // https://www.mediawiki.org/wiki/API:Edit
    public function save($message = null, $options = []) {
    	if($this->content === $this->remoteContent) {
    		return true; // ignore unmodified pages
    	}
    	
        $defaultOptions = [
            'bot' => false,
            'minor' => true,
            'ignoreConflict' => true,
            'ignoreExclusion' => false,
        ];

        $options = array_merge($defaultOptions, $options);

        if(!is_null($message) && strcasecmp("Bot: ", substr($message, 0, 5)) !== 0) {
            $message = "Bot: $message";
        }

        $minor  = $options['minor'] ? 'minor' : 'notminor';
        $bot    = $options['bot']   ? 'bot'   : 'notbot';

        $data = [
            'action'    => 'edit',
            'title'     => $this->title,
            'text'      => $this->content,
            'md5'       => md5($this->content),
            'summary'   => $message ?: '',
            $minor      => '1',
            $bot        => '1',
            'token'     => $this->wiki->getToken('edit')
        ];

        if(!$options['ignoreConflict']) {
            $data['basetimestamp'] = $this->timestamp;
        }
        
        if(!$options['ignoreExclusion'] && $this->excludes()) {
        	print "skipping " . $this->title . " because I'm excluded.\n";
        	return false;
        }

        $response = $this->wiki->request('POST', $data);

        if(isset($response['edit']['result']) and $response['edit']['result'] === "Success") {
        	if(isset($response['edit']['newtimestamp'])) {
	            $this->timestamp = $response['edit']['newtimestamp'];
	        } else {
	        	print $this->title . " has not been modified.\n";
	        }

            return true;
        }

        if(isset($response['error'])) {
            print " Failure: Couldn't save " . $this->title . "\n";
            print "  Reason: " . $response['error']['info'] . "\n";
            print "    Code: " . $response['error']['code'] . "\n";
			print "\n";
			
            return false;
        }
    }

    // Implementation from http://en.wikipedia.org/wiki/Template:Bots
    public function excludes($user = null) {
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
    
    // https://www.mediawiki.org/wiki/API:Delete
    public static function delete($title, $wiki, $reason = null) {
    	$data = [
			'action' => 'delete',
			'title' => $title,
			'token' => $wiki->getToken('delete')
		];
		
		if(!is_null($reason)) {
			$data['reason'] = $reason;
		}
		
    	$response = $wiki->request('POST', $data);
    	
    	if(isset($response['error'])) {
    		print $response['error']['code'] . ": " . $response['error']['info']."\n";
    		return false;
    	}
    	
    	return true;
    }

    public static function exists($title, $wiki) {
        return (new Page($title, $wiki))->getID() !== null;
    }
} 
