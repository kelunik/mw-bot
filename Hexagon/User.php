<?php

namespace Hexagon;

class User {
    private $wiki;
    private $username;
    private $rights;
    private $hasMessage;

    public function __construct(MediaWiki $wiki, $username, $password) {
        $this->wiki = $wiki;
        $this->username = $username;

        try {
            $this->login($username, $password);
        } catch(LoginException $e) {
            Console::error("Your Login Attempt was not successful.");
            Console::error("Reason: " . $e->getMessage());
            exit();
        }

        $this->getRights(true);
    }

    private function login($username, $password) {
        $loginData = [
            'action' => 'login',
            'lgname' => $username,
            'lgpassword' => $password,
        ];

        $response = $this->wiki->post($loginData);

        if($response['login']['result'] == 'NeedToken') {
            $loginData['lgtoken'] = $response['login']['token'];

            $response = $this->wiki->post($loginData);
            $result = $response['login']['result'];

            if($result === 'Success') {
                return TRUE;
            }

            if($result === 'NotExists') {
                throw new LoginException("There's no such user.");
            }

            if($result === 'WrongPass') {
                throw new LoginException("You typed a wrong password.");
            }

            throw new LoginException("Unknown Reason.");
        }
    }

    public function getRights($forceupdate = false) {
        if (!isset($this->rights) or $forceupdate) {
            $query = "action=query&meta=userinfo&uiprop=hasmsg|rights";
            $response = $this->wiki->get($query);

            $this->rights = $response['query']['userinfo']['rights'];
            $this->hasMessage = isset($response['query']['userinfo']['messages']);
        }

        return $this->rights;
    }

    public function hasRight($right) {
        return in_array($right, $this->getRights());
    }

    public function hasMessage($forceupdate = false) {
        $this->getRights($forceupdate);

        return $this->hasMessage;
    }

    public function getUsername() {
        return $this->username;
    }
} 