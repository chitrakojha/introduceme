<?php

require_once 'php/login/LoginGeneralCallback.php';
require_once 'php/clients/twitterOAuth.php';

class LoginTwitterCallback extends LoginGeneralCallback {

	protected $network = 'Twitter';
	protected $twitterId;
	protected $twitter;

	protected function getUserIdFromApi() {
		// If the oauth_token is old redirect to the connect page
		if (!isset($_SESSION['twitterOAuthToken']) || !isset($_REQUEST['oauth_token']) || $_SESSION['twitterOAuthToken'] !== $_REQUEST['oauth_token']) {
			Debug::l('Bad Twitter OAuth token');
			header('Location: '.APP_URL.'/'.Content::l().'/login/twitter/');
			exit;
		}

		// Create TwitterOAuth object with app key/secret and token key/secret from default phase
		$this->twitter = new TwitterOAuth(TW_CONSUMER, TW_SECRET, $_SESSION['twitterOAuthToken'], $_SESSION['twitterOAuthTokenSecret']);

		// Request access tokens from twitter
		$twitterAccessToken = $this->twitter->getAccessToken($_REQUEST['oauth_verifier']);

		// Remove no longer needed request tokens
		unset($_SESSION['twitterOAuthToken']);
		unset($_SESSION['twitterOAuthTokenSecret']);

		// If HTTP response is 200 continue otherwise send to connect page to retry
		if ($this->twitter->http_code != 200) {
			Debug::l('Error logging in to Twitter. Could not retrieve access token.');
			header('Location: '.APP_URL.'/'.Content::l().'/login/twitter/');
			exit;
		}

		// The user has been verified and the access tokens can be saved for future use
		$this->twitterId = $twitterAccessToken['user_id'];
		$this->accessToken = serialize($twitterAccessToken);
	}

	// See if this user is already in the database
	protected function getUserDetailsFromDb() {
		$userDetailsQ = $this->db->prepare('SELECT person_id FROM twitter WHERE id = :twitter_id');
		$userDetailsQ->execute(array(':twitter_id' => $this->twitterId));
		$this->userDetails = $userDetailsQ->fetch(PDO::FETCH_ASSOC);
	}

	protected function updateAccessToken() {
		$updateQ = $this->db->prepare('UPDATE twitter SET access_token = :access_token WHERE id = :twitter_id');
		$updateQ->execute(array(
			':access_token' => $this->accessToken,
			':twitter_id'   => $this->twitterId
		));
	}

	protected function insertProfile() {
		$updateQ = $this->db->prepare('INSERT INTO twitter (id, access_token, person_id) VALUES (:twitter_id, :access_token, :person_id)');
		$updateQ->execute(array(
			':twitter_id'   => $this->twitterId,
			':access_token' => $this->accessToken,
			':person_id'    => $_SESSION['loggedInPersonId']
		));
	}

	protected function loadName() {
		$this->twitter->format = 'xml';
		$twitterProfile = $this->twitter->get('users/show', array('user_id' => $this->twitterId));
		$twitterProfileXml = simplexml_load_string($twitterProfile);
		$this->name = '';
		if (!empty($twitterProfileXml->name)) {
			$this->name = $twitterProfileXml->name;
		}
	}

}

