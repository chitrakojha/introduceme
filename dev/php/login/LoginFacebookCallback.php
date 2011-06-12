<?php

require_once 'php/login/LoginGeneralCallback.php';
require_once 'php/clients/facebook.php';

class LoginFacebookCallback extends LoginGeneralCallback {

	protected $network = 'Facebook';
	protected $facebookId;

	protected function getUserIdFromApi() {
		// Create a Facebook object
		$facebook = new Facebook(array(
			'appId'  => FB_APP_ID,
			'secret' => FB_SECRET,
			'cookie' => true,
		));
		Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false;
		Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYHOST] = 2;

		// See if there is a Facebook session
		$this->facebookId = $facebook->getUser();
		$this->accessToken = $facebook->getAccessToken();

		if (!empty($this->facebookId)) {
			try {
				$facebookAttemptAPI = $facebook->api('/me');
				$this->name = $facebookAttemptAPI['name'];
			} catch (FacebookApiException $e) {
				$this->exitWithMessage('Error: The user data could not be retrieved from Facebook.');
			}
		} else {
			$this->exitWithMessage('Error: The user ID could not be retrieved from Facebook.');
		}
	}

	// See if this user is already in the database
	protected function getUserDetailsFromDb() {
		$userDetailsQ = $this->db->prepare('SELECT person_id FROM facebook WHERE id = :facebook_id');
		$userDetailsQ->execute(array(':facebook_id' => $this->facebookId));
		$this->userDetails = $userDetailsQ->fetch(PDO::FETCH_ASSOC);
	}

	protected function updateAccessToken() {
		$updateQ = $this->db->prepare('UPDATE facebook SET access_token = :access_token WHERE id = :facebook_id');
		$updateQ->execute(array(
			':access_token' => $this->accessToken,
			':facebook_id'  => $this->facebookId
		));
	}

	protected function insertProfile() {
		$updateQ = $this->db->prepare('INSERT INTO facebook (id, access_token, person_id) VALUES (:facebook_id, :access_token, :person_id)');
		$updateQ->execute(array(
			':facebook_id'  => $this->facebookId,
			':access_token' => $this->accessToken,
			':person_id'    => $_SESSION['loggedInPersonId']
		));
	}

}

