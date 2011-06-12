<?php

require_once 'php/utils/Content.php';
require_once 'php/utils/Database.php';
require_once 'php/utils/BaseConvert.php';
require_once 'php/utils/Words.php';
require_once 'php/Logout.php';
require_once 'php/SessionManager.php';
require_once 'php/Person.php';
require_once 'php/ui/Top.php';
require_once 'php/ui/Bottom.php';
require_once 'php/ui/ViewIntroduction.php';

class PageViewIntroduction {

	private $db;
	private $userId;
	private $userDetails;
	private $id;
	private $targetUser;
	private $introduction;

	public function __construct() {

		session_start();

		// Connect to the database
		$this->db = Database::getInstance();

		$this->validateIntroductionParams();

		// Get the website user
		$this->userId = SessionManager::getInstance()->getUserId();
		if (!empty($this->userId)) {
			$userDetailsQ = $this->db->prepare('SELECT p.email, f.id as facebook_id, f.access_token as facebook_access_token, l.id as linkedin_id, l.access_token as linkedin_access_token FROM person p LEFT JOIN facebook f ON p.id = f.person_id LEFT JOIN linkedin l ON p.id = l.person_id WHERE p.id = :id');
			$userDetailsQ->execute(array(':id' => $this->userId));
			$this->userDetails = $userDetailsQ->fetch(PDO::FETCH_ASSOC);
		}

		// If there is a target user for this URL and the logged in user does not match the target user, log them out
		if (isset($this->targetUser) && isset($this->userId) && $this->targetUser != $this->userId) {
			Debug::l('Logging out because target user is not logged in user');
			new Logout();
			// Refresh page with user logged out
			header('Location: '.APP_URL.$_SERVER['REQUEST_URI']);
			exit;
		}

		if (empty($this->userId)) {
			// No user is logged in. Display login options
			echo $this->displayLoginOptions();
			exit;
		}

		// The user is logged in. Make sure they have access to this introduction.
		$introductionQ = $this->db->prepare('SELECT time, introducer_id, introducee1_id, introducee1_notified, introducee1_read, introducee2_id, introducee2_notified, introducee2_read FROM introduction WHERE id = :id AND (introducer_id = :user_id OR introducee1_id = :user_id OR introducee2_id = :user_id)');
		$introductionQ->execute(array(':id' => $this->id, ':user_id' => $this->userId));
		$this->introduction = $introductionQ->fetch(PDO::FETCH_ASSOC);
		if (empty($this->introduction)) {
			error_log('The user '.$this->userId.' does not have access to the introduction '.$this->id);
			new Logout();
			header('Location: '.APP_URL.'/'.Content::l().'/');
			exit;
		}

		echo $this->displayIntroduction();
	}

	private function validateIntroductionParams() {

		if (empty($_GET['base62IntroductionId']) && empty($_GET['base62LinkId'])) {
			Debug::l('No introduction id or link id');
			header('Location: '.APP_URL.'/'.Content::l().'/');
			exit;
		}

		if (!empty($_GET['base62IntroductionId'])) {

			// The page has been passed an introduction ID
			if (preg_match('/^[0-9a-zA-Z]+$/', $_GET['base62IntroductionId']) == 0) {
				Debug::l('Invalid introduction id. Not base 62 compatible.');
				header('Location: '.APP_URL.'/'.Content::l().'/');
				exit;
			}

			// Convert the ID from base 62 to base 10
			$password = substr($_GET['base62IntroductionId'], 0, 3);
			$this->id = BaseConvert::base62ToBase10(substr($_GET['base62IntroductionId'], 3));

			// Make sure this introduction ID exists and the password is correct
			$introDetailsQ = $this->db->prepare('SELECT link_password FROM introduction WHERE id = :id');
			$introDetailsQ->execute(array(':id' => $this->id));
			$introDetails = $introDetailsQ->fetch(PDO::FETCH_ASSOC);

			if (empty($introDetails['link_password']) || $introDetails['link_password'] != $password) {
				Debug::l("That introduction id '$this->id' does not exist or the password '$password' is incorrect.");
				header('Location: '.APP_URL.'/'.Content::l().'/');
				exit;
			}

		} else {
			// The page has been passed a base 62 encoded link ID

			if (preg_match('/^[0-9a-zA-Z]+$/', $_GET['base62LinkId']) == 0) {
				Debug::l('Invalid link id. Not base 62 compatible.');
				header('Location: '.APP_URL.'/'.Content::l().'/');
				exit;
			}

			// Convert the ID from base 62 to base 10
			$password = substr($_GET['base62LinkId'], 0, 3);
			$linkId = BaseConvert::base62ToBase10(substr($_GET['base62LinkId'], 3));

			// Make sure this link ID exists
			$introDetailsQ = $this->db->prepare('SELECT introduction_id, person_id, link_password FROM link WHERE id = :id');
			$introDetailsQ->execute(array(':id' => $linkId));
			$introDetails = $introDetailsQ->fetch(PDO::FETCH_ASSOC);

			if (empty($introDetails['link_password']) || $introDetails['link_password'] != $password) {
				Debug::l("That link id '$linkId' does not exist or the password '$password' is incorrect.");
				header('Location: '.APP_URL.'/'.Content::l().'/');
				exit;
			}

			$this->id = $introDetails['introduction_id'];
			$this->targetUser = $introDetails['person_id'];
		}
	}

	private function displayLoginOptions() {
		$output = '';
		$ui = new ViewIntroduction();

		if (isset($this->targetUser)) {
			// Get the details of the introducer
			$introducerDetailsQ = $this->db->prepare('SELECT p.name FROM person p, introduction i WHERE p.id=i.introducer_id AND i.id = :id');
			$introducerDetailsQ->execute(array(':id' => $this->id));
			$introducerDetails = $introducerDetailsQ->fetch(PDO::FETCH_ASSOC);
			$introducerName = $introducerDetails['name'];

			// Get the details of the target user
			$targetUserDetailsQ = $this->db->prepare('SELECT p.name, f.id as facebook_id, l.id as linkedin_id, t.id as twitter_id FROM person p LEFT JOIN facebook f ON p.id = f.person_id LEFT JOIN linkedin l ON p.id = l.person_id LEFT JOIN twitter t ON p.id = t.person_id WHERE p.id = :id');
			$targetUserDetailsQ->execute(array(':id' => $this->targetUser));
			$targetUserDetails = $targetUserDetailsQ->fetch(PDO::FETCH_ASSOC);
			$targetUserName = $targetUserDetails['name'];
			$acceptedLoginServices = array();
			if (!empty($targetUserDetails['facebook_id'])) {
				$acceptedLoginServices[] = 'Facebook';
			}
			if (!empty($targetUserDetails['linkedin_id'])) {
				$acceptedLoginServices[] = 'LinkedIn';
			}
			if (!empty($targetUserDetails['twitter_id'])) {
				$acceptedLoginServices[] = 'Twitter';
			}

			// Get the details of the other introducee
			$otherIntroduceeDetailsQ = $this->db->prepare('SELECT p.name, f.id as facebook_id, l.id as linkedin_id, t.id as twitter_id FROM introduction i, person p LEFT JOIN facebook f ON p.id = f.person_id LEFT JOIN linkedin l ON p.id = l.person_id LEFT JOIN twitter t ON p.id = t.person_id WHERE i.id = :introd_id AND ((i.introducee2_id = :id AND p.id = i.introducee1_id) OR (i.introducee1_id = :id AND p.id = i.introducee2_id))');
			$otherIntroduceeDetailsQ->execute(array(':introd_id' => $this->id, ':id' => $this->targetUser));
			$otherIntroduceeDetails = $otherIntroduceeDetailsQ->fetch(PDO::FETCH_ASSOC);
			$otherIntroduceeName = $otherIntroduceeDetails['name'];
			$picture = '';
			if (!empty($otherIntroduceeDetails['facebook_id'])) {
				$picture = '<img src="https://graph.facebook.com/'.$otherIntroduceeDetails['facebook_id'].'/picture?type=normal" alt="'.$otherIntroduceeName.'" />';
			}
			if (empty($picture) && !empty($otherIntroduceeDetails['linkedin_id'])) {
				$linkedInPicQ = $this->db->prepare('SELECT picture_url FROM temp_linkedin WHERE linkedin_id = :linkedin_id');
				$linkedInPicQ->execute(array(':linkedin_id' => $otherIntroduceeDetails['linkedin_id']));
				$linkedInPic = $linkedInPicQ->fetch(PDO::FETCH_ASSOC);
				if (!empty($linkedInPic['picture_url'])) {
					$picture = '<img src="'.$linkedInPic['picture_url'].'" alt="'.$otherIntroduceeName.'" />';
				}
			}
			if (empty($picture) && !empty($otherIntroduceeDetails['twitter_id'])) {
				$twitterPicQ = $this->db->prepare('SELECT picture_url FROM temp_twitter WHERE twitter_id = :twitter_id');
				$twitterPicQ->execute(array(':twitter_id' => $otherIntroduceeDetails['twitter_id']));
				$twitterPic = $twitterPicQ->fetch(PDO::FETCH_ASSOC);
				if (!empty($twitterPic["picture_url"])) {
					$picture = '<img src="'.$twitterPic['picture_url'].'" alt="'.$otherIntroduceeName.'" />';
				}
			}

			$title = str_replace('OTHER_NAME', $otherIntroduceeName,
				str_replace('INTRODUCEE_NAME', $targetUserName,
				str_replace('INTRODUCER_NAME', $introducerName, Content::c()->view->login->title_targeted)));
		} else {
			// No target user. Generic login page with all login options.
			$title = Content::c()->view->login->title;
			$picture = '';
			$acceptedLoginServices = array('Facebook', 'LinkedIn', 'Twitter');
		}

		$output .= $ui->top();
		$pleaseLogin = str_replace('SOCIAL_NETWORK_NAME', Words::arrayToList($acceptedLoginServices, Content::c()->or), Content::c()->view->login->login);
		$output .= '<div class="login clearfix">'.$picture.'<h1>'.$title.'</h1>'.
			'<p class="pleaseLogin">'.$pleaseLogin.'</p>'.
			'<div class="loginIcons">';

		if (!empty($_GET['base62LinkId'])) {
			$nextPage = 'B'.$_GET['base62LinkId'];
		} else {
			$nextPage = 'A'.$_GET['base62IntroductionId'];
		}

		if (in_array('Facebook', $acceptedLoginServices)) {
			$facebookLoginUrl = SessionManager::getInstance()->getFacebook()->getLoginUrl(array('redirect_uri' => APP_URL.'/'.Content::l().'/login/facebookcallback/'.$nextPage));
			$output .= '<a id="loginFacebook" class="ir" href="'.$facebookLoginUrl.'">Facebook</a>';
		}
		if (in_array('LinkedIn', $acceptedLoginServices)) {
			$output .= '<a id="loginLinkedIn" class="ir" href="/'.Content::l().'/login/linkedin/'.$nextPage.'">LinkedIn</a>';
		}
		if (in_array('Twitter', $acceptedLoginServices)) {
			$output .= '<a id="loginTwitter" class="ir" href="/'.Content::l().'/login/twitter/'.$nextPage.'">Twitter</a>';
		}
		$output .= '</div>';

		if (!empty($this->targetUser)) {
			$output .= '<div class="faqsContainer">'.
				'<p id="btnFaqs"><a href="#">'.Content::c()->view->login->help.'</a></p>'.
				'<div id="faqs"><h2>'.Content::c()->view->login->faqs->what->title.'</h2>'.
				'<p>'.Content::c()->view->login->faqs->what->body.'</p>';
			if (count($acceptedLoginServices) == 1) {
				$output .= '<h2>'.str_replace('SOCIAL_NETWORK_NAME', $acceptedLoginServices[0], Content::c()->view->login->faqs->why->title).'</h2>'.
					'<p>'.str_replace('SOCIAL_NETWORK_NAME', $acceptedLoginServices[0],
					str_replace('TARGET_NAME', $targetUserName,
					str_replace('INTRODUCER_NAME', $introducerName,
					str_replace('INTRODUCEE_NAME', $otherIntroduceeName, Content::c()->view->login->faqs->why->body)))).'</p>';
			}
			$output .= '<h2>'.Content::c()->view->login->faqs->spam->title.'</h2>'.
				'<p>'.str_replace('INTRODUCER_NAME', $introducerName,
				str_replace('INTRODUCEE_NAME', $otherIntroduceeName, Content::c()->view->login->faqs->spam->body)).'</p>';
			$output .= '</div></div>';
		}
		$output .= '</div>';

		$script = '<script>'.
				'$(document).ready(function() {'.
					'_gaq.push(["_trackPageview", "/view-introduction/not-logged-in"]);'.
					'$("#btnFaqs a").click(function(e) {'.
						'e.preventDefault();'.
						'_gaq.push(["_trackPageview", "/view-introduction/learn-more"]);'.
						'$("#btnFaqs").hide();'.
						'$("#faqs").slideDown();'.
					'});'.
					'$("#loginFacebook").click(function() {'.
						'_gaq.push(["_trackPageview", "/view-introduction/click-login/facebook"]);'.
						'return true;'.
					'});'.
					'$("#loginLinkedIn").click(function() {'.
						'_gaq.push(["_trackPageview", "/view-introduction/click-login/linkedin"]);'.
						'return true;'.
					'});'.
					'$("#loginTwitter").click(function() {'.
						'_gaq.push(["_trackPageview", "/view-introduction/click-login/twitter"]);'.
						'return true;'.
					'});'.
				'});'.
			'</script>';
		$bottom = new Bottom($script);
		$output .= $bottom->getOutput();
		return $output;
	}

	private function displayIntroduction() {
		$output = '';
		$introTime = strtotime($this->introduction['time']);
		$ui = new ViewIntroduction();
		$output .= $ui->top();
		$script = '<script>'.
				'$(document).ready(function() {'.
					'_gaq.push(["_trackPageview", "/view-introduction/logged-in"]);'.
				'});'.
				'im.youWrote = "'.str_replace('PERSON', Content::c()->view->you, Content::c()->view->wrote).'";'.
				'im.userType = "'.(($this->userId == $this->introduction['introducer_id']) ? 'introducer' : 'introducee').'";'.
			'</script>'.
			'<script src="/js/view-introduction.js?v=2"></script>'.
			'<script src="http://www.linkedin.com/js/public-profile/widget-os.js"></script>';

		if ($this->userId == $this->introduction['introducer_id']) { // The user is the introducer
			$introducee1 = new Person(array());
			$introducee1->getDataFromId($this->introduction['introducee1_id']);
			$introducee2 = new Person(array());
			$introducee2->getDataFromId($this->introduction['introducee2_id']);

			$output .= '<div class="col1">'.
				'<h1>'.str_replace('INTRODUCEE1', $introducee1->getName(),
					str_replace('INTRODUCEE2', $introducee2->getName(), Content::c()->view->title_introducer)).'</h1>'.
				$ui->introductionTime($introTime);

			// If this introduction was sent in the last 10x60 seconds display a confirmation message
			if ($introTime + (10 * 60) > time()) {
				$output .= $ui->confirmation($introducee1, $introducee2, $this->introduction['introducee1_notified'], $this->introduction['introducee2_notified']);
			}

			// If we don't have the user's email, show a form requesting it
			if (empty($this->userDetails['email'])) {
				$output .= $ui->emailForm($introducee1, $introducee2);
			}

			// Show a message input form
			$output .= $ui->messageBox($introducee1, $introducee2, $this->id);

			// Show messages
			$messagesQ = $this->db->prepare('SELECT body, time, writer_id FROM message WHERE introduction_id = :introduction_id ORDER BY time DESC');
			$messagesQ->execute(array(':introduction_id' => $this->id));
			$messages = $messagesQ->fetchAll(PDO::FETCH_ASSOC);
			$len = count($messages);
			$output .= '<div class="displayingMessages" '.($len == 0 ? 'style="display:none;"' : '').'>'.
				str_replace('PERSON1', $introducee1->getName(),
				str_replace('PERSON2', $introducee2->getName(), Content::c()->view->displaying_messages)).'</div>'.
				'<div id="messages">';
			if ($len > 0) {
				for ($i=0; $i<$len; $i++) {
					$writer = '';
					if ($messages[$i]['writer_id'] == $this->userId) {
						$writer = Content::c()->view->you;
					} elseif ($messages[$i]['writer_id'] == $introducee1->getId()) {
						$writer = $introducee1->getName();
					} elseif ($messages[$i]['writer_id'] == $introducee2->getId()) {
						$writer = $introducee2->getName();
					}
					$output .= $ui->message($writer, strtotime($messages[$i]['time']), $messages[$i]['body']);
				}
			}
			$output .= '</div>'. // #messages DIV
				'</div><div class="col2">'.
				$ui->socialProfile($introducee1). // Show introducee 1 profile
				$ui->socialProfile($introducee2). // Show introducee 2 profile
				'</div>'; // .col2
			$bottom = new Bottom($script);
			$output .= $bottom->getOutput();
			return $output;

		} elseif ($this->userId == $this->introduction['introducee1_id']) { // The user is introducee 1
			$introducee = new Person(array());
			$introducee->getDataFromId($this->introduction['introducee2_id']);
			// Mark the introduction as read for introducee1
			if ($this->introduction['introducee1_read'] == 0) {
				$sth = $this->db->prepare('UPDATE introduction SET introducee1_read = 1 WHERE id = :introduction_id');
				$sth->execute(array(':introduction_id' => $this->id));
			}

		} else { // The user is introducee 2
			$introducee = new Person(array());
			$introducee->getDataFromId($this->introduction['introducee1_id']);
			// Mark the introduction as read for introducee2
			if ($this->introduction['introducee2_read'] == 0) {
				$sth = $this->db->prepare('UPDATE introduction SET introducee2_read = 1 WHERE id = :introduction_id');
				$sth->execute(array(':introduction_id' => $this->id));
			}
		}

		$introducer = new Person(array());
		$introducer->getDataFromId($this->introduction['introducer_id']);

		$output .= '<div class="col1">'.
			'<h1>'.str_replace('INTRODUCER', $introducer->getName(),
				str_replace('INTRODUCEE', $introducee->getName(), Content::c()->view->title_introducee)).'</h1>'.
			$ui->introductionTime($introTime);

		// If we don't have the user's email, show a form requesting it
		if (empty($this->userDetails['email'])) {
			$output .= $ui->emailForm($introducer, $introducee);
		}

		// Show a message input form
		$output .= $ui->messageBox($introducer, $introducee, $this->id);

		// Show messages
		$output .= '<div id="messages">';
		$messagesQ = $this->db->prepare('SELECT body, time, writer_id FROM message WHERE introduction_id = :introduction_id ORDER BY time DESC');
		$messagesQ->execute(array(':introduction_id' => $this->id));
		$messages = $messagesQ->fetchAll(PDO::FETCH_ASSOC);
		$len = count($messages);
		if ($len > 0) {
			$output .= '<div class="displayingMessages">'.str_replace('PERSON1', $introducer->getName(),
				str_replace('PERSON2', $introducee->getName(), Content::c()->view->displaying_messages)).'</div>';
			for ($i=0; $i<$len; $i++) {
				$writer = '';
				if ($messages[$i]['writer_id'] == $this->userId) {
					$writer = Content::c()->view->you;
				} elseif ($messages[$i]['writer_id'] == $introducer->getId()) {
					$writer = $introducer->getName();
				} elseif ($messages[$i]['writer_id'] == $introducee->getId()) {
					$writer = $introducee->getName();
				}
				$output .= $ui->message($writer, strtotime($messages[$i]['time']), $messages[$i]['body']);
			}
		}
		$output .= '</div>'. // #messages DIV
			'</div><div class="col2">'.
			$ui->socialProfile($introducee). // Show introducee profile
			'</div>'; // .col2
		$bottom = new Bottom($script);
		$output .= $bottom->getOutput();
		return $output;

	}

}

