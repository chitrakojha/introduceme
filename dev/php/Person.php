<?php

require_once 'php/utils/Database.php';

/**
 * The person class is used to represent the data of a user
 */
class Person {

	private $id;
	private $name;
	private $email;
	private $facebookId;
	private $linkedInId;
	private $twitterId;
	private $linkedInPicture;
	private $twitterPicture;
	private $twitterScreenName;
	private $twitterProtected;

	public function construct($input) {
		$this->id = array_key_exists('id', $input) ? $input['id'] : null;
		$this->name = array_key_exists('name', $input) ? $input['name'] : null;
		$this->email = array_key_exists('email', $input) ? $input['email'] : null;
		$this->facebookId = array_key_exists('facebookId', $input) ? $input['facebookId'] : null;
		$this->linkedInId = array_key_exists('linkedInId', $input) ? $input['linkedInId'] : null;
		$this->twitterId = array_key_exists('twitterId', $input) ? $input['twitterId'] : null;
	}

	public function getDataFromId($value) {
		$db = Database::getInstance();
		$this->id = $value;
		$userDetailsQ = $db->prepare('SELECT p.name, p.email, l.id as linkedin_id, f.id as facebook_id, t.id as twitter_id FROM person p LEFT JOIN linkedin l ON p.id = l.person_id LEFT JOIN facebook f ON p.id = f.person_id LEFT JOIN twitter t ON p.id = t.person_id WHERE p.id = :id');
		$userDetailsQ->execute(array(':id' => $value));
		$userDetails = $userDetailsQ->fetch(PDO::FETCHASSOC);
		if (empty($userDetails)) {
			return false;
		}
		$this->name = $userDetails['name'];
		$this->email = $userDetails['email'];
		$this->facebookId = $userDetails['facebook_id'];
		$this->linkedInId = $userDetails['linkedin_id'];
		$this->twitterId = $userDetails['twitter_id'];
	}

	public function getDataFromFacebookId($value) {
		$db = Database::getInstance();
		$this->facebookId = $value;
		$userDetailsQ = $db->prepare('SELECT p.id, p.name, p.email, l.id as linkedin_id, t.id as twitter_id FROM facebook f, person p LEFT JOIN linkedin l ON p.id = l.person_id LEFT JOIN twitter t ON p.id = t.person_id WHERE p.id = f.person_id AND f.id = :facebook_id');
		$userDetailsQ->execute(array(':facebook_id' => $value));
		$userDetails = $userDetailsQ->fetch(PDO::FETCHASSOC);
		if (empty($userDetails)) {
			return false;
		}
		$this->id = $userDetails['id'];
		$this->name = $userDetails['name'];
		$this->email = $userDetails['email'];
		$this->linkedInId = $userDetails['linkedin_id'];
		$this->twitterId = $userDetails['twitter_id'];
	}

	public function getDataFromLinkedInId($value) {
		$db = Database::getInstance();
		$this->linkedInId = $value;
		$userDetailsQ = $db->prepare('SELECT p.id, p.name, p.email, f.id as facebook_id, t.id as twitter_id FROM linkedin l, person p LEFT JOIN facebook f ON p.id = f.person_id LEFT JOIN twitter t ON p.id = t.person_id WHERE p.id = l.person_id AND l.id = :linkedin_id');
		$userDetailsQ->execute(array(':linkedin_id' => $value));
		$userDetails = $userDetailsQ->fetch(PDO::FETCHASSOC);
		if (empty($userDetails)) {
			return false;
		}
		$this->id = $userDetails['id'];
		$this->name = $userDetails['name'];
		$this->email = $userDetails['email'];
		$this->facebookId = $userDetails['facebook_id'];
		$this->twitterId = $userDetails['twitter_id'];
	}

	public function getDataFromTwitterId($value) {
		$db = Database::getInstance();
		$this->twitterId = $value;
		$userDetailsQ = $db->prepare('SELECT p.id, p.name, p.email, f.id as facebook_id, l.id as linkedin_id FROM twitter t, person p LEFT JOIN facebook f ON p.id = f.person_id LEFT JOIN linkedin l ON p.id = l.person_id WHERE p.id = t.person_id AND t.id = :twitter_id');
		$userDetailsQ->execute(array(':twitter_id' => $value));
		$userDetails = $userDetailsQ->fetch(PDO::FETCHASSOC);
		if (empty($userDetails)) {
			return false;
		}
		$this->id = $userDetails['id'];
		$this->name = $userDetails['name'];
		$this->email = $userDetails['email'];
		$this->facebookId = $userDetails['facebook_id'];
		$this->linkedInId = $userDetails['linkedin_id'];
	}

	public function getLinkedInPicture() {
		if (empty($this->linkedInId)) {
			return null;
		}
		if (empty($this->linkedInPicture)) {
			$db = Database::getInstance();
			$userDetailsQ = $db->prepare('SELECT picture_url FROM temp_linkedin WHERE linkedin_id = :linkedin_id');
			$userDetailsQ->execute(array(':linkedin_id' => $this->linkedInId));
			$userDetails = $userDetailsQ->fetch(PDO::FETCHASSOC);
			if (!empty($userDetails['picture_url'])) {
				$this->linkedInPicture = $userDetails['picture_url'];
			}
		}
		return $this->linkedInPicture;
	}

	public function getTwitterPicture() {
		if (empty($this->twitterId)) {
			return null;
		}
		if (empty($this->twitterPicture)) {
			$this->getTwitterTemp();
		}
		return $this->twitterPicture;
	}

	public function getTwitterScreenName() {
		if (empty($this->twitterId)) {
			return null;
		}
		if (empty($this->twitterScreenName)) {
			$this->getTwitterTemp();
		}
		return $this->twitterScreenName;
	}

	public function getTwitterProtected() {
		if (empty($this->twitterId)) {
			return null;
		}
		if (empty($this->twitterProtected)) {
			$this->getTwitterTemp();
		}
		return $this->twitterProtected;
	}

	private function getTwitterTemp() {
		$db = Database::getInstance();
		$userDetailsQ = $db->prepare('SELECT picture_url, screen_name, protected FROM temp_twitter WHERE twitter_id = :twitter_id');
		$userDetailsQ->execute(array(':twitter_id' => $this->twitterId));
		$userDetails = $userDetailsQ->fetch(PDO::FETCHASSOC);
		if (!empty($userDetails['picture_url'])) {
			$this->twitterPicture = $userDetails['picture_url'];
		}
		if (!empty($userDetails['screen_name'])) {
			$this->twitterScreenName = $userDetails['screen_name'];
		}
		if (!empty($userDetails['protected'])) {
			$this->twitterProtected = $userDetails['protected'];
		}
	}

	public function addToDatabase() {
		if (!isset($this->id)) {
			if (isset($this->facebookId)) {
				$this->getDataFromFacebookId($this->facebookId);
			}
			if (isset($this->linkedInId)) {
				$this->getDataFromLinkedInId($this->linkedInId);
			}
			if (isset($this->twitterId)) {
				$this->getDataFromTwitterId($this->twitterId);
			}
			$db = Database::getInstance();
			$insert = $db->prepare('INSERT INTO person (name) VALUES (:name)');
			$insert->execute(array(':name' => $this->name));
			$this->id = $db->lastInsertId();
			if (!empty($this->facebookId)) {
				$insert = $db->prepare('INSERT INTO facebook (id, person_id) VALUES (:facebook_id, :person_id)');
				$insert->execute(array(':facebook_id' => $this->facebookId, ':person_id' => $this->id));
			}
			if (!empty($this->linkedInId)) {
				$insert = $db->prepare('INSERT INTO linkedin (id, person_id) VALUES (:linkedin_id, :person_id)');
				$insert->execute(array(':linkedin_id' => $this->linkedInId, ':person_id' => $this->id));
			}
			if (!empty($this->twitterId)) {
				$insert = $db->prepare('INSERT INTO twitter (id, person_id) VALUES (:twitter_id, :person_id)');
				$insert->execute(array(':twitter_id' => $this->twitterId, ':person_id' => $this->id));
			}
		}
	}

	public function getSummaryArray() {
		$result = array();
		$result['id'] = $this->id;
		$result['name'] = $this->name;
		$result['email'] = $this->email;
		$result['facebookId'] = $this->facebookId;
		$result['linkedInId'] = $this->linkedInId;
		$result['twitterId'] = $this->twitterId;
		return $result;
	}

	public function getId() {
		return $this->id;
	}
	public function setId($value) {
		$this->id = $value;
	}

	public function getName() {
		return $this->name;
	}
	public function setName($value) {
		$this->name = $value;
	}

	public function getEmail() {
		return $this->email;
	}
	public function setEmail($value) {
		$this->email = $value;
	}

	public function getFacebookId() {
		return $this->facebookId;
	}
	public function setFacebookId($value) {
		$this->facebookId = $value;
	}

	public function getLinkedInId() {
		return $this->linkedInId;
	}
	public function setLinkedInId($value) {
		$this->linkedInId = $value;
	}

	public function getTwitterId() {
		return $this->twitterId;
	}
	public function setTwitterId($value) {
		$this->twitterId = $value;
	}

}

