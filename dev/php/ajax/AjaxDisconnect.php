<?php

require_once 'php/utils/Database.php';
require_once 'php/SessionManager.php';

class AjaxDisconnect {

	public function __construct() {

		session_start();

		// Get the website user
		$userId = SessionManager::getInstance()->getUserId();

		// Require logged in user
		if (!isset($userId)) {
			Debug::l('No user logged in');
			header('Location: '.APP_URL.'/'.Content::l().'/settings/');
			exit;
		}

		// Make sure the network param is valid
		if (empty($_GET['network']) || !in_array($_GET['network'], array('Facebook', 'LinkedIn', 'Twitter'))) {
			Debug::l('Bad network param');
			header('Location: '.APP_URL.'/'.Content::l().'/settings/');
			exit;
		}

		// Connect to the database
		$db = Database::getInstance();

		// Remove the network
		switch ($_GET['network']) {

			case 'Facebook':
				$update = $db->prepare('UPDATE facebook SET access_token="" WHERE person_id = :person_id');
				$update->execute(array(':person_id' => $userId));
				break;

			case 'LinkedIn':
				$update = $db->prepare('UPDATE linkedin SET access_token="" WHERE person_id = :person_id');
				$update->execute(array(':person_id' => $userId));
				break;

			case 'Twitter':
				$update = $db->prepare('UPDATE twitter SET access_token="" WHERE person_id = :person_id');
				$update->execute(array(':person_id' => $userId));
				break;
		}

		header('Location: '.APP_URL.'/'.Content::l().'/settings/');

	}
}

