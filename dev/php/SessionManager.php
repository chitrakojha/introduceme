<?php

require_once 'php/clients/facebook.php';

/**
 * The SessionManager is used to check if there is an existing session for the user.
 * It checks the following places: $_SERVER, Facebook.
 */
class SessionManager {

	private static $instance;
	private $facebook;
	private $userId;

	// Singleton get instance
	public static function getInstance() {
		if (!(self::$instance instanceof self)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function checkSessionsForUser() {

		// Check the session for a logged in user
		if (!empty($_SESSION['loggedInPersonId'])) {
			return $_SESSION['loggedInPersonId'];
		}

		$db = Database::getInstance();

		// See if there is a Facebook session
		$facebookUser = $this->getFacebook()->getUser();

		if (!empty($facebookUser)) {
			// See if this Facebook user is already in the database
			$userDetailsQ = $db->prepare('SELECT p.id FROM person p, facebook f WHERE p.id = f.person_id AND f.id = :facebook_id');
			$userDetailsQ->execute(array(':facebook_id' => $facebookUser));
			$userDetails = $userDetailsQ->fetch(PDO::FETCH_ASSOC);
			if ($userDetails) {
				// Save the user's person_id to the session
				Debug::l('SessionManager :: Got the active session from Facebook.');
				$_SESSION['loggedInPersonId'] = $userDetails['id'];
				return $_SESSION['loggedInPersonId'];
			}
		}

		// No session was found so return null
		return null;
	}

	public function getFacebook() {
		if (empty($this->facebook)) {
			// Create a Facebook object
			$this->facebook = new Facebook(array(
				'appId'  => FB_APP_ID,
				'secret' => FB_SECRET,
				'cookie' => true,
			));
			Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false;
			Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYHOST] = 2;
		}
		return $this->facebook;
	}

	public function getUserId() {
		if (empty($this->userId)) {
			$this->userId = $this->checkSessionsForUser();
		}
		return $this->userId;
	}

	// Do not allow an explicit call of the constructor: $v = new Singleton();
	final private function __construct() { }

	// Do not allow the clone operation: $x = clone $v;
	final private function __clone() { }
}

