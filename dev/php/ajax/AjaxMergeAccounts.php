<?php

require_once 'php/utils/Database.php';
require_once 'php/SessionManager.php';

class AjaxMergeAccounts {

	public function __construct() {

		session_start();

		$db = Database::getInstance();

		if (empty($_SESSION['mergeOtherAccount']) || empty($_SESSION['mergeNetwork'])) {
			Debug::l('Error merging account: missing session vars');
			header('Location: '.APP_URL.'/'.Content::l().'/');
			exit;
		}

		$mergeOtherAccount = $_SESSION['mergeOtherAccount'];
		$mergeNetwork = $_SESSION['mergeNetwork'];

		// Get the website user
		$userId = SessionManager::getInstance()->getUserId();

		// Require logged in user
		if (empty($userId)) {
			Debug::l('Error merging account: No logged in user');
			header('Location: '.APP_URL.'/'.Content::l().'/');
			exit;
		}

		// Get user details
		$userDetailsQ = $db->prepare('SELECT p.email, f.id as facebook_id, f.access_token as facebook_access_token, l.id as linkedin_id, l.access_token as linkedin_access_token, t.id as twitter_id, t.access_token as twitter_access_token FROM person p LEFT JOIN facebook f ON p.id = f.person_id LEFT JOIN linkedin l ON p.id = l.person_id LEFT JOIN twitter t ON p.id = t.person_id WHERE p.id = :id');
		$userDetailsQ->execute(array(':id' => $userId));
		$userDetails = $userDetailsQ->fetch(PDO::FETCH_ASSOC);

		// Get merging account details
		$mergeId = $_SESSION['mergeOtherAccount'];
		$userDetailsQ->execute(array(':id' => $mergeId));
		$mergeDetails = $userDetailsQ->fetch(PDO::FETCH_ASSOC);

		// Start the merge

		$update = $db->prepare('UPDATE link SET person_id = :new_id WHERE person_id = :old_id');
		$update->execute(array(':new_id' => $userId, ':old_id' => $mergeId));

		$update = $db->prepare('UPDATE message SET writer_id = :new_id WHERE writer_id = :old_id');
		$update->execute(array(':new_id' => $userId, ':old_id' => $mergeId));

		$update = $db->prepare('UPDATE introduction SET introducer_id = :new_id WHERE introducer_id = :old_id');
		$update->execute(array(':new_id' => $userId, ':old_id' => $mergeId));
		$update = $db->prepare('UPDATE introduction SET introducee1_id = :new_id WHERE introducee1_id = :old_id');
		$update->execute(array(':new_id' => $userId, ':old_id' => $mergeId));
		$update = $db->prepare('UPDATE introduction SET introducee2_id = :new_id WHERE introducee2_id = :old_id');
		$update->execute(array(':new_id' => $userId, ':old_id' => $mergeId));

		if (empty($userDetails['email']) && !empty($mergeDetails['email'])) {
			$update = $db->prepare('UPDATE person SET email = :email WHERE id = :id');
			$update->execute(array(':id' => $userId, ':email' => $mergeDetails['email']));
		}

		if ((empty($userDetails['facebook_access_token']) && !empty($mergeDetails['facebook_access_token'])) || (empty($userDetails['facebook_id']) && !empty($mergeDetails['facebook_id']))) {
			// Copy the Facebook profile from the merge account, cascading down to the temp tables
			$delete = $db->prepare('DELETE FROM facebook WHERE person_id = :new_id');
			$delete->execute(array(':new_id' => $userId));
			$update = $db->prepare('UPDATE facebook SET person_id = :new_id WHERE person_id = :old_id');
			$update->execute(array(':new_id' => $userId, ':old_id' => $mergeId));
		}

		if ((empty($userDetails['linkedin_access_token']) && !empty($mergeDetails['linkedin_access_token'])) || (empty($userDetails['linkedin_id']) && !empty($mergeDetails['linkedin_id']))) {
			// Copy the LinkedIn profile from the merge account, cascading down to the temp tables
			$delete = $db->prepare('DELETE FROM linkedin WHERE person_id = :new_id');
			$delete->execute(array(':new_id' => $userId));
			$update = $db->prepare('UPDATE linkedin SET person_id = :new_id WHERE person_id = :old_id');
			$update->execute(array(':new_id' => $userId, ':old_id' => $mergeId));
		}

		if ((empty($userDetails['twitter_access_token']) && !empty($mergeDetails['twitter_access_token'])) || (empty($userDetails['twitter_id']) && !empty($mergeDetails['twitter_id']))) {
			// Copy the Twitter profile from the merge account, cascading down to the temp tables
			$delete = $db->prepare('DELETE FROM twitter WHERE person_id = :new_id');
			$delete->execute(array(':new_id' => $userId));
			$update = $db->prepare('UPDATE twitter SET person_id = :new_id WHERE person_id = :old_id');
			$update->execute(array(':new_id' => $userId, ':old_id' => $mergeId));
		}

		$delete = $db->prepare('DELETE FROM person WHERE id = :old_id');
		$delete->execute(array(':old_id' => $mergeId));

		unset($_SESSION['mergeOtherAccount']);
		unset($_SESSION['mergeNetwork']);

		// Redirect to home page
		$_SESSION['connectedWithNewNetwork'] = $mergeNetwork;
		header('Location: '.APP_URL.'/'.Content::l().'/');

	}

}

