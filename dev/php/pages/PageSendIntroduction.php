<?php

require_once 'php/utils/Content.php';
require_once 'php/utils/Database.php';
require_once 'php/SessionManager.php';
require_once 'php/Person.php';
require_once 'php/NotifyManager.php';
require_once 'php/utils/BaseConvert.php';

class PageSendIntroduction {

	private $db;
	private $introduction;

	public function __construct() {

		session_start();

		// Connect to the database
		$this->db = Database::getInstance();

		// Get the website user
		$userId = SessionManager::getInstance()->getUserId();
		if (empty($userId)) {
			Debug::l('No user logged in');
			header('Location: '.APP_URL.'/'.Content::l().'/');
			exit;
		}

		// Get the introduction that hasn't been sent yet
		$this->introductionQ = $this->db->prepare('SELECT id, introducee1_id, introducee2_id, introducee1_notified, introducee2_notified, link_password FROM introduction WHERE introducer_id = :id AND (introducee1_notified IS NULL OR introducee2_notified IS NULL) ORDER BY time DESC LIMIT 1');
		$this->introductionQ->execute(array(':id'=>$userId));
		$this->introduction = $this->introductionQ->fetch(PDO::FETCH_ASSOC);

		if (empty($this->introduction)) {
			Debug::l('No unsent introductions found');
			header('Location: '.APP_URL.'/'.Content::l().'/');
			exit;
		}

		$introducee1 = new Person(array());
		$introducee1->getDataFromId($this->introduction['introducee1_id']);
		$introducee2 = new Person(array());
		$introducee2->getDataFromId($this->introduction['introducee2_id']);

		// Notify introducee 1
		if (empty($this->introduction['introducee1_notified'])) {
			$notifyManager = new NotifyManager($this->introduction['id'], $introducee1, $introducee2);
			$updateQ = $this->db->prepare('UPDATE introduction SET introducee1_notified = :method WHERE id = :id');
			$this->notifyPerson($notifyManager, $introducee1, $updateQ);
		}

		// Notify introducee 2
		if (empty($this->introduction['introducee2_notified'])) {
			$notifyManager = new NotifyManager($this->introduction['id'], $introducee2, $introducee1);
			$updateQ = $this->db->prepare('UPDATE introduction SET introducee2_notified = :method WHERE id = :id');
			$this->notifyPerson($notifyManager, $introducee2, $updateQ);
		}

		$base62 = BaseConvert::base10ToBase62($this->introduction['id']);

		// Redirect to introduction page
		header('Location: '.APP_URL.'/'.Content::l().'/A'.$this->introduction['link_password'].$base62);
	}

	private function notifyPerson($notifyManager, $person, $updateQ) {
		if ($person->getEmail() != null) {
			if ($notifyManager->sendEmail()) {
				$updateQ->execute(array(':method' => 'e', ':id' => $this->introduction['id']));
				return;
			}
		}
		if ($person->getLinkedInId() != null) {
			if ($notifyManager->publishToLinkedIn()) {
				$updateQ->execute(array(':method' => 'l', ':id' => $this->introduction['id']));
				return;
			}
		}
		if ($person->getFacebookId() != null) {
			if ($notifyManager->publishToFacebook()) {
				$updateQ->execute(array(':method' => 'f', ':id' => $this->introduction['id']));
				return;
			}
		}
		if ($person->getTwitterId() != null) {
			if ($notifyManager->publishToTwitter()) {
				$updateQ->execute(array(':method' => 't', ':id' => $this->introduction['id']));
				return;
			}
		}
	}

}

