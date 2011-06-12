<?php

require_once 'php/utils/Debug.php';
require_once 'php/utils/Database.php';
require_once 'php/utils/Content.php';
require_once 'php/SessionManager.php';
require_once 'php/Person.php';
require_once 'php/utils/BaseConvert.php';
require_once 'php/clients/linkedinOAuth.php';
require_once 'php/clients/bitly.php';
require_once 'php/clients/twitterOAuth.php';
require_once 'php/clients/ses.php';


/**
 * Sends messages via email, Facebook or LinkedIn
 *
 * @author keegan
 */
class NotifyManager {

	private $db;
	private $introductionId;
	private $introducee;
	private $other;
	private $userId;
	private $userDetails;
	private $userName;
	private $facebookLoginUrl;
	private $introductionUrl;

	public function __construct($introductionId, $introducee, $other) {
		$this->introductionId = $introductionId;
		$this->introducee = $introducee;
		$this->other = $other;
		$this->userId = SessionManager::getInstance()->getUserId();
		$this->db = Database::getInstance();

		// Get the introducee details
		$userDetailsQ = $this->db->prepare('SELECT p.name, f.id as facebook_id, f.access_token as facebook_access_token, l.id as linkedin_id, l.access_token as linkedin_access_token, t.id as twitter_id, t.access_token as twitter_access_token FROM person p LEFT JOIN facebook f ON p.id = f.person_id LEFT JOIN linkedin l ON p.id = l.person_id LEFT JOIN twitter t ON p.id = t.person_id WHERE p.id = :id');
		$userDetailsQ->execute(array(':id' => $this->userId));
		$this->userDetails = $userDetailsQ->fetch(PDO::FETCH_ASSOC);
		$this->userName = $this->userDetails['name'];

		// Get the personalised link for the introducee
		$linkQ = $this->db->prepare('SELECT id, link_password FROM link WHERE introduction_id = :introduction_id AND person_id = :person_id');
		$linkQ->execute(array(':introduction_id' => $this->introductionId, ':person_id' => $this->introducee->getId()));
		$link = $linkQ->fetch(PDO::FETCH_ASSOC);
		$this->introductionUrl = APP_URL.'/B'.$link['link_password'].BaseConvert::base10ToBase62($link['id']);
	}

	public function sendEmail() {
		$email = str_replace('INTRODUCEE_NAME', $this->introducee->getName(),
			str_replace('OTHER_NAME', $this->other->getName(),
			str_replace('INTRODUCER_NAME', $this->userName,
			str_replace('LINK', $this->introductionUrl.'', Content::getInstance()->getEmail('email-introduction')))));
		$subject = str_replace('INTRODUCEE_NAME', $this->introducee->getName(), str_replace('INTRODUCER_NAME', $this->userName, Content::c()->introduce->email));

		// Send the email with AWS SES
		$ses = new SimpleEmailService(SES_KEY, SES_SECRET);
		$ses->enableVerifyHost(false);
		$ses->enableVerifyPeer(false);
		$m = new SimpleEmailServiceMessage();
		$m->addTo($this->introducee->getEmail());
		$m->setFrom(SES_FROM);
		$m->setSubject($subject);
		$m->setMessageFromString($subject.' '.$this->introductionUrl, $email);
		$result = $ses->sendEmail($m);
		if (!$result) {
			return false;
		}

		// Save the email result to the database
		$sth = $this->db->prepare('INSERT INTO aws_ses (recipient_id, ses_message_id, ses_request_id, introduction_id) VALUES (:recipient_id, :ses_message_id, :ses_request_id, :introduction_id)');
		$sth->execute(array(
			':recipient_id'    => $this->introducee->getId(),
			':ses_message_id'  => $result['MessageId'],
			':ses_request_id'  => $result['RequestId'],
			':introduction_id' => $this->introductionId
		));
		return true;
	}

	public function publishToTwitter() {
		if (empty($this->userDetails['twitter_access_token'])) {
			return false;
		}
		$tweet = '@'.$this->introducee->getTwitterScreenName();
		$remainingChars = 140 - 2 - strlen($tweet) - strlen($this->introductionUrl);
		$tweet .= ' '.substr(str_replace('INTRODUCEE_NAME', $this->other->getName(), Content::c()->introduce->tweet), 0, $remainingChars);
		$tweet .= ' '.$this->introductionUrl;
		try {
			$twitterAccessToken = unserialize($this->userDetails['twitter_access_token']);
			$twitter = new TwitterOAuth(TW_CONSUMER, TW_SECRET, $twitterAccessToken['oauth_token'], $twitterAccessToken['oauth_token_secret']);
			$twitter->post('statuses/update', array('status' => $tweet));
			return true;
		} catch (ErrorException $e) {
			// Could not post to Twitter. Bad access token?
			Debug::l('Error posting to Twitter '.$e);
		}
		return false;
	}

	public function publishToLinkedIn() {
		if (empty($this->userDetails['linkedin_access_token'])) {
			return false;
		}
		// Create LinkedIn object
		$linkedInApiConfig = array(
			'appKey'      => LI_API_KEY,
			'appSecret'   => LI_SECRET,
			'callbackUrl' => ''
		);
		$linkedIn = new LinkedIn($linkedInApiConfig);
		try {
			$linkedIn->setTokenAccess(unserialize($this->userDetails['linkedin_access_token']));
		} catch (Error $e) {
			Debug::l('Error. Could not set LinkedIn access token. '.$e);
			return false;
		}

		$subject = str_replace('INTRODUCER_NAME', $this->userName, Content::c()->introduce->linkedin->title);
		$message = str_replace('INTRODUCEE_NAME', $this->introducee->getName(), str_replace('INTRODUCER_NAME', $this->userName, str_replace('LINK', $this->introductionUrl, Content::c()->introduce->linkedin->message)));
		$response = $linkedIn->message(array($this->userDetails['linkedin_id']), $subject, $message);
		if ($response['success'] === TRUE) {
			return true;
		} else {
			return false;
		}
	}

	public function publishToFacebook() {
		$this->facebookLoginUrl = SessionManager::getInstance()->getFacebook()->getLoginUrl(array('redirect_uri' => APP_URL.'/'.Content::l().'/login/facebookcallback/en/send-introduction/', 'req_perms' => 'publish_stream'));
		$output = '<h1>'.Content::c()->introduce->one_more_thing->title.'</h1><p class="desc">'.
			str_replace('INTRODUCEE_NAME', $this->introducee->getName(),
			str_replace('SOCIAL_NETWORK_NAME', 'Facebook', Content::c()->introduce->one_more_thing->body)).
			'</p><p><a class="aButton" href="'.$this->facebookLoginUrl.'">'.Content::c()->introduce->one_more_thing->cta.'</a></p>'.
			'<script>_gaq.push(["_trackPageview", "/facebook-permissions-request"]);</script>';
		try {
			$fql = array(
				'method'   => 'fql.query',
				'query'    => 'SELECT publish_stream FROM permissions WHERE uid="'.$this->userDetails['facebook_id'].'"',
				'callback' => ''
			);
			$permissions = SessionManager::getInstance()->getFacebook()->api($fql);
			if (!empty($permissions[0]) && !empty($permissions[0]['publish_stream'])) {
				// Publish story to Facebook
				return $this->finishPublishToFacebook();
			} else {
				Debug::l('Facebook publish_stream permissions have not been granted');
				$this->output($output);
				exit;
			}
		} catch (Exception $e) {
			Debug::l('Error retrieving permissions from Facebook '.$e);
			$this->output($output);
			exit;
		}
	}

	private function finishPublishToFacebook() {
		// See if we can get a picture of the other introducee
		if ($this->other->getLinkedInId() != null) {
			$picture = $this->other->getLinkedInPicture();
		}
		if (!isset($picture) && $this->other->getTwitterId() != null) {
			$picture = $this->other->getTwitterPicture();
		}
		if (!isset($picture) && $this->other->getFacebookId() != null) {
			// Shorten the picture URL with BITLY so we can publish it on Facebook
			$results = bitly_v3_shorten('https://graph.facebook.com/'.$this->other->getFacebookId().'/picture?type=normal', 'j.mp');
			if (!empty($results['url'])) {
				$picture = $results['url'];
			}
		}
		try {
			$params = array(
				'description' => ' ',
				'caption' => str_replace('INTRODUCEE_NAME', $this->other->getName(),
					str_replace('INTRODUCER_NAME', $this->userName, Content::c()->introduce->notification)),
				'link' => $this->introductionUrl,
				'name' => $this->other->getName(),
				'access_token' => $this->userDetails['facebook_access_token']
			);
			if (!empty($picture)) {
				$params['picture'] = $picture;
			}
			$statusUpdate = SessionManager::getInstance()->getFacebook()->api('/'.$this->introducee->getFacebookId().'/feed', 'POST', $params);
		} catch (FacebookApiException $e) {
			// Could not post to Facebook.
			Debug::l('Error posting to Facebook '.$e);
			return false;
		}
		return true;
	}

	private function output($value) {
		$top = new Top('', 'sendIntroductionPage');
		echo $top->getOutput();

		echo '<div class="sendIntroduction">'.$value.'</div>';

		$bottom = new Bottom('');
		echo $bottom->getOutput();
	}

}

