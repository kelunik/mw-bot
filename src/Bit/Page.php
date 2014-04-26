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
    public function save($reason = null, $options = []) {
    	$options = array_merge([
            'bot' => false,
            'minor' => false,
            'ignoreConflict' => true,
            'ignoreExclusion' => false,
        ], $options);
        
        if($this->content === $this->remoteContent) {
    		return true; // ignore unmodified pages
    	}
    	
		if($this->excludes() && !$options['ignoreExclusion']) {
			print sprintf("skipping %s because of bot excluion", $this->title) . "\n";
			return false;
		}

        $response = $this->wiki->request('POST', [
			'action'    	=> 'edit',
			'title'     	=> $this->title,
			'text'      	=> $this->content,
			'md5'       	=> md5($this->content),
			'summary'		=> $reason,
			'bot'			=> (bool) $options['bot'],
			'minor' 		=> (bool) $options['minor'],
			'basetimestamp' => $options['ignoreConflict'] ? null : $this->timestamp,
			'token'			=> $this->wiki->getToken('edit')
		]);

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
		
		$user = preg_quote($user, '/');
		
        if(preg_match("/\{\{(nobots|bots\|allow=none|bots\|deny=all|bots\|optout=all|bots\|deny=.*?{$user}.*?)\}\}/iS", $this->getContent())) {
            return true;
        }

        if(preg_match("/\{\{(bots\|allow=all|bots\|allow=.*?{$user}.*?)\}\}/iS", $this->getContent())) {
            return false;
        }

        if(preg_match('/\{\{(bots\|allow=.*?)\}\}/iS', $this->getContent())) {
            return true;
        }

        return false;
    }
    
    // https://www.mediawiki.org/wiki/API:Delete
    public function delete($reason = null) {
    	$response = $this->wiki->request('POST', [
			'action' 	=> 'delete',
			'title' 	=> $this->title,
			'reason' 	=> $reason,
			'token' 	=> $this->wiki->getToken('delete')
		]);
    	
    	if(isset($response['error'])) {
    		print $response['error']['code'] . ": " . $response['error']['info']."\n";
    		return false;
    	}
    	
    	return true;
    }
    
    // https://www.mediawiki.org/wiki/API:Move
    public function move($new_title, $reason = null, $options = []) {
	    $options = array_merge([
            'movetalk' => true,
            'movesubpages' => true,
            'suppressredirect' => false,
        ], $options);
        
    	$response = $this->wiki->request('POST', [
			'action' 		=> 'move',
			'from' 			=> $this->title,
			'to' 			=> $new_title,
			'reason' 		=> $reason ?: '',
			'token' 		=> $this->wiki->getToken('delete'),
			'movetalk'		=> (bool) $options['movetalk'],
			'movesubpages'	=> (bool) $options['movesubpages'],
			'noredirect'	=> (bool) $options['suppressredirect']
		]);
    	
    	if(isset($response['error'])) {
    		print $response['error']['code'] . ": " . $response['error']['info']."\n";
    		return false;
    	}
    	
    	$this->title = $new_title;
    	return true;
    }
    
    // https://www.mediawiki.org/wiki/API:Rollback
    public function rollback($reason = null, $markbot = false) {
	    $response = $this->wiki->request('GET', [
	    	'action' 	=> 'query',
	    	'prop' 		=> 'revisions',
	    	'rvtoken' 	=> 'rollback',
	    	'titles' 	=> $this->title
	    ]);
	    
	    $page 		= current($response['query']['pages']);
	    $token 		= $page['revisions'][0]['rollbacktoken'];
	    $rev_id 	= $page['revisions'][0]['revid'];
	    $rev_user 	= $page['revisions'][0]['user'];
	    
	    $response = $this->wiki->request('POST', [
	    	'action' 	=> 'rollback',
	    	'title' 	=> $this->title,
	    	'user' 		=> $rev_user,
	    	'summary' 	=> $reason,
	    	'token' 	=> $token,
	    	'markbot' 	=> (bool) $markbot
	    ]);
	    
	    if(isset($response['error'])) {
    		print $response['error']['code'] . ": " . $response['error']['info']."\n";
    		return false;
    	}
    	
    	return true;
    }
    
    // https://www.mediawiki.org/wiki/API:Protect
    public function protect($protections, $expiry = 'infinite', array $options = []) {
        $options = array_merge([
            'reason' => null,
            'cascade' => false,
            'bot' => false,
        ], $options);
        
        if(is_array($protections)) {
	        $protections = http_build_query($protections, '', '|');
	    }
		
    	$response = $this->wiki->request('POST', [
			'action' 		=> 'protect',
			'title' 		=> $this->title,
			'protections' 	=> $protections,
			'expiry' 		=> $expiry,
			'reason' 		=> $options['reason'],
			'bot' 			=> (int) $options['bot'],
			'cascade' 		=> (bool) $options['cascade'],
			'token' 		=> $this->wiki->getToken('protect')
		]);
    	
    	if(isset($response['error'])) {
    		print $response['error']['code'] . ": " . $response['error']['info']."\n";
    		return false;
    	}
    	
    	return true;
    }

    public function exists() {
        return $this->getID() !== null;
    }
} 
