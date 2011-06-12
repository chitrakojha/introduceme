<?php

require_once 'php/utils/Content.php';
require_once 'php/utils/Database.php';

class LoginGeneralCallback {

	protected $db;
	protected $userDetails;
	protected $network = 'General';
	protected $name;
	protected $accessToken;

	public function __construct() {
		session_start();
		$this->db = Database::getInstance();
		$this->getUserIdFromApi();
		$this->getUserDetailsFromDb();
		$this->insertOrUpdateUser();

		// Redirect the user back to the page they requested
		if (!empty($_GET['nextPage'])) {
			header('Location: '.APP_URL.'/'.$_GET['nextPage']);
		} else {
			header('Location: '.APP_URL.'/'.Content::l().'/');
		}
	}

	protected function getUserIdFromApi() {
		throw new Exception('getUserIdFromApi function must be overridden');
	}

	// See if this user is already in the database
	protected function getUserDetailsFromDb() {
		throw new Exception('getUserDetailsFromDb function must be overridden');
	}

	final protected function insertOrUpdateUser() {
		// Is there an existing user with this profile?
		if (!empty($this->userDetails)) {

			// Is this profile used by a different user?
			if (!empty($_SESSION['loggedInPersonId']) && $_SESSION['loggedInPersonId'] != $this->userDetails['person_id']) {
				Debug::l('That '.$this->network.' profile is already linked to a different user account');
				$_SESSION['mergeNetwork'] = $this->network;
				$_SESSION['mergeOtherAccount'] = $this->userDetails['person_id'];
				$this->updateAccessToken();
				header('Location: '.APP_URL.'/'.Content::l().'/merge-accounts/');
				exit;

			} else {
				Debug::l('Returning user has logged in with '.$this->network.' again');
				$this->updateAccessToken();
				// Update the person's name if it has been loaded
				if (!empty($this->name)) {
					$updateNameQ = $this->db->prepare('UPDATE person SET name = :name WHERE id = :id');
					$updateNameQ->execute(array(
						':name' => $this->name,
						':id'   => $this->userDetails['person_id']
					));
				}
				// Save the user's id to the session
				$_SESSION['loggedInPersonId'] = $this->userDetails['person_id'];
			}

		} else { // This profile hasn't been added to the database before

			// Save the network name to the session so we can display a thankyou message
			$_SESSION['connectedWithNewNetwork'] = $this->network;

			// Is a user already logged in?
			if (!empty($_SESSION['loggedInPersonId'])) {
				Debug::l('Returning user connected '.$this->network.' to their account for the first time');
				$this->insertProfile();
			} else {
				Debug::l('New user has logged in with '.$this->network);
				if (empty($this->name)) {
					$this->loadName();
				}
				$this->insertPerson();
				$this->insertProfile();
			}
		}
	}

	protected function updateAccessToken() {
		throw new Exception('updateAccessToken function must be overridden');
	}

	protected function insertProfile() {
		throw new Exception('insertProfile function must be overridden');
	}

	protected function loadName() {
		throw new Exception('loadName function must be overridden if the name is not set in getUserIdFromApi');
	}

	protected function insertPerson() {
		$insertQ = $this->db->prepare('INSERT INTO person (name) VALUES (:name)');
		$insertQ->execute(array(':name' => $this->name));
		$_SESSION['loggedInPersonId'] = $this->db->lastInsertId();
	}

	protected function exitWithMessage($message) {
		Debug::l($message);
		echo $message;
		exit;
	}
}

