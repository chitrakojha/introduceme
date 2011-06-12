<?php

require_once 'php/utils/Database.php';
require_once 'php/SessionManager.php';

class AjaxSaveEmail {

	public function __construct() {

		session_start();
		header('Content-type: text/json');

		// Get the website user
		$userId = SessionManager::getInstance()->getUserId();

		// Make sure a user is logged in
		if (empty($userId)) {
			Debug::l('No user logged in');
			$json['result'] = 'false';
			echo json_encode($json);
			exit;
		}

		// Validate input
		if (empty($_POST['email']) || !filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL)) {
			Debug::l('Invalid email');
			$json['result'] = 'false';
			echo json_encode($json);
			exit;
		}

		// Update email address
		$db = Database::getInstance();
		$sth = $db->prepare('UPDATE person SET email = :email WHERE id = :id');
		$sth->execute(array(':email' => $_POST['email'], ':id' => $userId));

		$json['result'] = 'true';
		echo json_encode($json);

	}
}

