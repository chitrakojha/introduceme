<?php

require_once 'php/clients/facebook.php';
require_once 'php/utils/Database.php';
require_once 'php/SessionManager.php';

class Logout {

	public function __construct() {

		// Delete the cached friends. The user might be logging out to try to refresh the friend list
		$userId = SessionManager::getInstance()->getUserId();
		if (isset($userId)) {
			$db = Database::getInstance();
			$sth = $db->prepare('DELETE FROM temp_friends WHERE facebook_id=(SELECT id FROM facebook WHERE person_id = :person_id) OR linkedin_id=(SELECT id FROM linkedin WHERE person_id = :person_id) OR twitter_id=(SELECT id FROM twitter WHERE person_id = :person_id)');
			$sth->execute(array(':person_id' => $userId));
		}

		// Clear website session
		setcookie('PHPSESSID', '', time()-3600);
		session_destroy();

	}

}

