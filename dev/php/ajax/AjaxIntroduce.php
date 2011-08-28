<?php

require_once 'php/utils/Database.php';
require_once 'php/SessionManager.php';
require_once 'php/Person.php';
require_once 'php/utils/BaseConvert.php';
require_once 'php/clients/linkedinOAuth.php';
require_once 'php/clients/twitterOAuth.php';

/**
 * This AJAX script will create an introduction.
 * If an access token is invalid, it will return instructions for a Javascript redirect to the Facebook auth page.
 */
class AjaxIntroduce {

	public function __construct() {

		session_start();
		header('Content-type: text/json');

		// Get the website user
		$userId = SessionManager::getInstance()->getUserId();

		$json['result'] = 'true';

		// Make sure a user is logged in
		if (!isset($userId)) {
			$json['result'] = 'false';
			$json['title'] = (string)Content::c()->errors->session->title;
			$json['message'] = (string)Content::c()->errors->session->no_session;
			echo json_encode($json);
			exit;
		}

		// Validate input
		if (empty($_POST['introducee1Name']) || (empty($_POST['introducee1FacebookId']) && empty($_POST['introducee1LinkedInId']) && empty($_POST['introducee1TwitterId'])) || 
		empty($_POST['introducee2Name']) || (empty($_POST['introducee2FacebookId']) && empty($_POST['introducee2LinkedInId']) && empty($_POST['introducee2TwitterId']))) {
			$json['result'] = 'false';
			$json['title'] = (string)Content::c()->errors->input->title;
			$json['message'] = (string)Content::c()->errors->input->introduction_not_created;
			echo json_encode($json);
			exit;
		}

		// Make sure the introducees are unique
		if (
			(!empty($_POST['introducee1FacebookId']) && !empty($_POST['introducee2FacebookId']) && $_POST['introducee1FacebookId'] == $_POST['introducee2FacebookId']) ||
			(!empty($_POST['introducee1LinkedInId']) && !empty($_POST['introducee2LinkedInId']) && $_POST['introducee1LinkedInId'] == $_POST['introducee2LinkedInId']) ||
			(!empty($_POST['introducee1TwitterId']) && !empty($_POST['introducee2TwitterId']) && $_POST['introducee1TwitterId'] == $_POST['introducee2TwitterId'])
		) {
			$json['result'] = 'false';
			$json['title'] = (string)Content::c()->errors->input->title;
			$json['message'] = (string)Content::c()->errors->input->introduce_to_self;
			echo json_encode($json);
			exit;
		}

		// Connect to the database
		$db = Database::getInstance();

		$introducee1 = new Person(array(
			'name' => $_POST['introducee1Name'],
			'facebookId' => !empty($_POST['introducee1FacebookId']) ? $_POST['introducee1FacebookId'] : '',
			'linkedInId' => !empty($_POST['introducee1LinkedInId']) ? $_POST['introducee1LinkedInId'] : null,
			'twitterId' => !empty($_POST['introducee1TwitterId']) ? $_POST['introducee1TwitterId'] : null
		));
		$introducee2 = new Person(array(
			'name' => $_POST['introducee2Name'],
			'facebookId' => !empty($_POST['introducee2FacebookId']) ? $_POST['introducee2FacebookId'] : '',
			'linkedInId' => !empty($_POST['introducee2LinkedInId']) ? $_POST['introducee2LinkedInId'] : null,
			'twitterId' => !empty($_POST['introducee2TwitterId']) ? $_POST['introducee2TwitterId'] : null
		));

		// See if the introducees are already in our database, that would be nice!
		if (!empty($_POST['introducee1FacebookId'])) {
			$introducee1->getDataFromFacebookId($_POST['introducee1FacebookId']);
		} elseif (!empty($_POST['introducee1LinkedInId'])) {
			$introducee1->getDataFromLinkedInId($_POST['introducee1LinkedInId']);
		} elseif (!empty($_POST['introducee1TwitterId'])) {
			$introducee1->getDataFromTwitterId($_POST['introducee1TwitterId']);
		}
		if (!empty($_POST['introducee2FacebookId'])) {
			$introducee2->getDataFromFacebookId($_POST['introducee2FacebookId']);
		} elseif (!empty($_POST['introducee2LinkedInId'])) {
			$introducee2->getDataFromLinkedInId($_POST['introducee2LinkedInId']);
		} elseif (!empty($_POST['introducee2TwitterId'])) {
			$introducee2->getDataFromTwitterId($_POST['introducee2TwitterId']);
		}

		// Make sure the introducees are still unique
		if (
			($introducee1->getFacebookId() != null && $introducee1->getFacebookId() == $introducee2->getFacebookId()) ||
			($introducee1->getLinkedInId() != null && $introducee1->getLinkedInId() == $introducee2->getLinkedInId()) ||
			($introducee1->getTwitterId() != null && $introducee1->getTwitterId() == $introducee2->getTwitterId())
		) {
			$json['result'] = 'false';
			$json['title'] = (string)Content::c()->errors->input->title;
			$json['message'] = (string)Content::c()->errors->input->introduce_to_self;
			echo json_encode($json);
			exit;
		}

		// If the introducees aren't in the database yet, add them
		$introducee1->addToDatabase();
		$introducee2->addToDatabase();

		// If the introducees are on LinkedIn, add their public profile URL and picture to the DB
		if ($introducee1->getLinkedInId() != null || $introducee2->getLinkedInId() != null) {
			// Connect to LinkedIn API
			$sth = $db->prepare('SELECT id, access_token FROM linkedin WHERE person_id = :person_id');
			$sth->execute(array(':person_id' => $userId));
			$userDetails = $sth->fetch(PDO::FETCH_ASSOC);
			if (!empty($userDetails['access_token'])) {
				$linkedInAccessToken = $userDetails['access_token'];
				// Create LinkedIn object
				$API_CONFIG = array(
					'appKey'        => LI_API_KEY,
					'appSecret'     => LI_SECRET,
					'callbackUrl'   => ''
				);
				$OBJ_linkedin = new LinkedIn($API_CONFIG);
				$OBJ_linkedin->setTokenAccess(unserialize($linkedInAccessToken));
				// Which introducees are on LinkedIn?
				$profilesToRequest = array();
				if ($introducee1->getLinkedInId() != null) {
					$profilesToRequest[] = 'id='.$introducee1->getLinkedInId();
				}
				if ($introducee2->getLinkedInId() != null) {
					$profilesToRequest[] = 'id='.$introducee2->getLinkedInId();
				}
				try {
					$linkedInProfiles = $OBJ_linkedin->profileNew('::('.implode(',', $profilesToRequest).'):(id,public-profile-url,picture-url)' );
				} catch (ErrorException $e) { }
				if ($linkedInProfiles['success'] === TRUE) {
					$linkedInProfiles['linkedin'] = new SimpleXMLElement($linkedInProfiles['linkedin']);
					if ($linkedInProfiles['linkedin']->getName() == 'people') {
						foreach ($linkedInProfiles['linkedin']->person as $person) {
							$id = (string)$person->id;
							$url = (string)$person->{'public-profile-url'};
							$pic = (string)$person->{'picture-url'};
							if ($id && ($url || $pic)) {
								$update = $db->prepare('REPLACE INTO temp_linkedin SET linkedin_id = :linkedin_id, time=NOW(), profile_url = :profile_url, picture_url = :picture_url');
								$update->execute(array(':linkedin_id' => $id, ':profile_url' => $url, ':picture_url' => $pic));
							}
						}
					}
				}
			}
		}

		// If the introducees are on Twitter, add their screen name and picture to the DB
		if ($introducee1->getTwitterId() != null || $introducee2->getTwitterId() != null) {
			// Which introducees are on Twitter?
			$profilesToRequest = array();
			if ($introducee1->getTwitterId() != null) {
				$profilesToRequest[] = $introducee1->getTwitterId();
			}
			if ($introducee2->getTwitterId() != null) {
				$profilesToRequest[] = $introducee2->getTwitterId();
			}
			// Connect to Twitter API
			$sth = $db->prepare('SELECT id, access_token FROM twitter WHERE person_id = :person_id');
			$sth->execute(array(':person_id'=>$userId));
			$userDetails = $sth->fetch(PDO::FETCH_ASSOC);
			if (!empty($userDetails['access_token'])) {
				$twitterAccessToken = unserialize($userDetails['access_token']);
				try {
					$twitter = new TwitterOAuth(TW_CONSUMER, TW_SECRET, $twitterAccessToken['oauth_token'], $twitterAccessToken['oauth_token_secret']);
					$twitter->format = 'json';
					$twitterProfiles = $twitter->get('users/lookup', array('user_id' => implode(',', $profilesToRequest)));
					foreach ($twitterProfiles as $friend) {
						$id = (string)$friend->id;
						$screenName = (string)$friend->screen_name;
						$pic = (string)$friend->profile_image_url;
						$protected = (string)$friend->protected;
						if ($id && ($screenName || $pic || $protected)) {
							$update = $db->prepare('REPLACE INTO temp_twitter SET twitter_id = :twitter_id, time=NOW(), screen_name = :screen_name, picture_url = :picture_url, protected = :protected');
							$update->execute(array(':twitter_id' => $id, ':screen_name' => $screenName, ':picture_url' => $pic, ':protected' => $protected));
						}
					}
				} catch (ErrorException $e) {
					// Could not post to Twitter. Bad access token?
					Debug::l('Error posting to Twitter ' . $e);
				}
			}
		}

		$linkPassword = BaseConvert::generatePassword();

		// Add the introduction to the database
		$insert = $db->prepare('INSERT INTO introduction (introducer_id, introducee1_id, introducee2_id, time, link_password) VALUES (:introducer_id, :introducee1_id, :introducee2_id, NOW(), :link_password)');
		$insert->execute(array(':introducer_id' => $userId, ':introducee1_id' => $introducee1->getId(), ':introducee2_id' => $introducee2->getId(), ':link_password' => $linkPassword));
		$introId = $db->lastInsertId();

		// Add the links for each introducee
		$linkPassword1 = BaseConvert::generatePassword();
		$linkPassword2 = BaseConvert::generatePassword();
		$insert = $db->prepare('INSERT INTO link (introduction_id, person_id, link_password) VALUES (:introduction_id, :person_id, :link_password)');
		$insert->execute(array(':introduction_id' => $introId, ':person_id' => $introducee1->getId(), ':link_password' => $linkPassword1));
		$insert->execute(array(':introduction_id' => $introId, ':person_id' => $introducee2->getId(), ':link_password' => $linkPassword2));

		// If there is a message, add it to the database
		if (!empty($_POST["message"])) {
			$message = htmlentities(trim($_POST['message']), ENT_QUOTES, 'UTF-8');
			if (!empty($message)) {
				$insert = $db->prepare('INSERT INTO message (body, time, introduction_id, writer_id) VALUES (:body, NOW(), :introduction_id, :writer_id)');
				$insert->execute(array(':body' => $message, ':introduction_id' => $introId, ':writer_id' => $userId));
			}
		}

		// Return the success message, which will tell the Javascript to redirect the user to the send-introduction page
		$json['result'] = 'true';
		$json['link'] = APP_URL.'/'.Content::l().'/send-introduction/';
		$json['time'] = Debug::getInstance()->getTimeElapsed();
		echo json_encode($json);

	}

}

