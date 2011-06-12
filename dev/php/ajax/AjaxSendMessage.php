<?php

require_once 'php/utils/Database.php';
require_once 'php/SessionManager.php';
require_once 'php/Person.php';
require_once 'php/utils/BaseConvert.php';
require_once 'php/clients/ses.php';

class AjaxSendMessage {

	private $ses;
	private $db;
	private $messageId;

	public function __construct() {

		session_start();
		header('Content-type: text/json');

		// Get the website user
		$userId = SessionManager::getInstance()->getUserId();

		$json['result'] = 'true';

		// Make sure a user is logged in
		if (empty($userId)) {
			$json['result'] = 'false';
			echo json_encode($json);
			exit;
		}

		// Validate input
		if (empty($_POST['message']) || empty($_POST['id'])) {
			$json['result'] = 'false';
			echo json_encode($json);
			exit;
		}

		$message = htmlentities(trim($_POST['message']), ENT_QUOTES, 'UTF-8');

		if (empty($message)) {
			$json['result'] = 'false';
			echo json_encode($json);
			exit;
		}

		$this->db = Database::getInstance();

		$introductionQ = $this->db->prepare('SELECT introducer_id, introducee1_id, introducee2_id, link_password FROM introduction WHERE id = :id AND (introducer_id = :user_id OR introducee1_id = :user_id OR introducee2_id = :user_id)');
		$introductionQ->execute(array(':id' => $_POST['id'], ':user_id' => $userId));
		$introduction = $introductionQ->fetch(PDO::FETCH_ASSOC);
		if (!$introduction) {
			$json['result'] = 'false';
			echo json_encode($json);
			exit;
		}

		// Add message to the database
		$insertMessageQ = $this->db->prepare('INSERT INTO message (body, time, introduction_id, writer_id) VALUES (:body, NOW(), :introduction_id, :writer_id)');
		$insertMessageQ->execute(array(
			':body'            => $message,
			':introduction_id' => $_POST['id'],
			':writer_id'       => $userId
		));
		$this->messageId = $this->db->lastInsertId();

		// Send emails to the other two people involved with this introduction
		$introducer = new Person(array());
		$introducer->getDataFromId($introduction['introducer_id']);
		$introducee1 = new Person(array());
		$introducee1->getDataFromId($introduction['introducee1_id']);
		$introducee2 = new Person(array());
		$introducee2->getDataFromId($introduction['introducee2_id']);

		if ($introducer->getId() == $userId) {
			$user = $introducer;
		} elseif ($introducee1->getId() == $userId) {
			$user = $introducee1;
		} elseif ($introducee2->getId() == $userId) {
			$user = $introducee2;
		}

		$introductionUrl = APP_URL.'/A'.$introduction['link_password'].BaseConvert::base10ToBase62($_POST['id']);

		$this->ses = new SimpleEmailService(SES_KEY, SES_SECRET);
		$this->ses->enableVerifyHost(false);
		$this->ses->enableVerifyPeer(false);

		if ($introducer->getId() != $userId) {
			$emailAddress = $introducer->getEmail();
			if (!empty($emailAddress)) {
				$html = str_replace('PERSON_NAME', $introducer->getName(),
					str_replace('WRITER_NAME', $user->getName(),
					str_replace('INTRODUCEE1', $introducee1->getName(),
					str_replace('INTRODUCEE2', $introducee2->getName(),
					str_replace('MESSAGE', $message,
					str_replace('LINK', $introductionUrl, Content::getInstance()->getEmail('email-message')))))));
				$subject = str_replace('PERSON', $user->getName(), Content::c()->view->message_notification);
				$this->sendEmail($introducer->getId(), $emailAddress, $subject, $subject.' '.$introductionUrl, $html);
			}
		}

		if ($introducee1->getId() != $userId) {
			$emailAddress = $introducee1->getEmail();
			if (!empty($emailAddress)) {
				$html = str_replace('PERSON_NAME', $introducee1->getName(),
					str_replace('WRITER_NAME', $user->getName(),
					str_replace('INTRODUCEE1', Content::c()->view->you_lowercase,
					str_replace('INTRODUCEE2', $introducee2->getName(),
					str_replace('MESSAGE', $message,
					str_replace('LINK', $introductionUrl, Content::getInstance()->getEmail('email-message')))))));
				$subject = str_replace('PERSON', $user->getName(), Content::c()->view->message_notification);
				$this->sendEmail($introducee1->getId(), $emailAddress, $subject, $subject.' '.$introductionUrl, $html);
			}
		}

		if ($introducee2->getId() != $userId) {
			$emailAddress = $introducee2->getEmail();
			if (!empty($emailAddress)) {
				$html = str_replace('PERSON_NAME', $introducee2->getName(),
					str_replace('WRITER_NAME', $user->getName(),
					str_replace('INTRODUCEE1', $introducee1->getName(),
					str_replace('INTRODUCEE2', Content::c()->view->you_lowercase,
					str_replace('MESSAGE', $message,
					str_replace('LINK', $introductionUrl, Content::getInstance()->getEmail('email-message')))))));
				$subject = str_replace('PERSON', $user->getName(), Content::c()->view->message_notification);
				$this->sendEmail($introducee2->getId(), $emailAddress, $subject, $subject.' '.$introductionUrl, $html);
			}
		}

		// Output confirmation
		$json['message'] = $message;
		$time = time();
		$json['time'] = '<time class="timeago" datetime="'.date('c', $time).'">'.date('j F, Y', $time).'</time>';
		echo json_encode($json);
	}

	private function sendEmail($recipientId, $to, $subject, $text, $html) {
		// Send the email with AWS SES
		$email = new SimpleEmailServiceMessage();
		$email->addTo($to);
		$email->setFrom(SES_FROM);
		$email->setSubject($subject);
		$email->setMessageFromString($text, $html);
		$result = $this->ses->sendEmail($email);

		// Save the email result to the database
		$recordQ = $this->db->prepare('INSERT INTO aws_ses (recipient_id, ses_message_id, ses_request_id, message_id) VALUES (:recipient_id, :ses_message_id, :ses_request_id, :message_id)');
		$recordQ->execute(array(
			':recipient_id'   => $recipientId,
			':ses_message_id' => $result['MessageId'],
			':ses_request_id' => $result['RequestId'],
			':message_id'     => $this->messageId
		));
	}
}

