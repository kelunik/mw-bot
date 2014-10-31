<?php

namespace Bit;

class User {
    private $wiki;
    private $username;
    private $rights;
    private $hasMessage;

    public function __construct(MediaWiki $wiki, $username, $password) {
        $this->wiki = $wiki;
        $this->username = $username;

        $this->login($username, $password);
        $this->getRights(true);
    }

	// https://www.mediawiki.org/wiki/API:Login
    private function login($username, $password) {
        $data = [
            'action' => 'login',
            'lgname' => $username,
            'lgpassword' => $password,
        ];

        $response = $this->wiki->request('POST', $data);

        if($response['login']['result'] !== 'NeedToken') {
        	throw new LoginException("Unknown Reason.");
        }

        $data['lgtoken'] = $response['login']['token'];

        $response = $this->wiki->request('POST', $data);
        $result = $response['login']['result'];

        switch($result) {
        	case 'Success'   : return true;
        	case 'NotExists' : throw new LoginException("Account does not exist!");
        	case 'WrongPass' : throw new LoginException("Password was incorrect!");
        	case 'Throttled' : throw new LoginException("Too many failed attempts!");
        	case 'Blocked'   : throw new LoginException("Account has been blocked!");
        	default          : throw new LoginException("Unknown reason: $result");
        }
    }

    public function getRights($forceupdate = false) {
        if (!isset($this->rights) or $forceupdate) {
            $query = "action=query&meta=userinfo&uiprop=hasmsg|rights";
            $response = $this->wiki->request('GET', $query);

            $this->rights = $response['query']['userinfo']['rights'];
            $this->hasMessage = isset($response['query']['userinfo']['messages']);
        }

        return $this->rights;
    }

    public function hasRight($right, $forceupdate = false) {
        return in_array($right, $this->getRights($forceupdate));
    }

    public function hasMessage($forceupdate = false) {
        $this->getRights($forceupdate);

        return $this->hasMessage;
    }

    public function getUsername() {
        return $this->username;
    }
}
