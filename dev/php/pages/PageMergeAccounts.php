<?php

require_once 'php/utils/Content.php';
require_once 'php/utils/Database.php';
require_once 'php/ui/Top.php';
require_once 'php/ui/Bottom.php';
require_once 'php/SessionManager.php';
require_once('php/clients/linkedinOAuth.php');
require_once('php/clients/twitterOAuth.php');

class PageMergeAccounts {

	private $db;
	private $mergeNetwork;

	public function __construct() {

		session_start();

		$this->db = Database::getInstance();

		if (empty($_SESSION['mergeOtherAccount']) || empty($_SESSION['mergeNetwork'])) {
			Debug::l('Error merging account: missing session vars');
			header('Location: '.APP_URL.'/'.Content::l().'/');
			exit;
		}

		$this->mergeNetwork = $_SESSION['mergeNetwork'];
		$mergeOtherAccount = $_SESSION['mergeOtherAccount'];

		// Get the website user
		$userId = SessionManager::getInstance()->getUserId();
		if (!isset($userId)) {
			// No user logged in
			Debug::l('No user logged in');
			header('Location: '.APP_URL.'/'.Content::l().'/');
			exit;
		}

		// Load user data
		$userDetailsQ = $this->db->prepare('SELECT f.id as facebook_id, f.access_token as facebook_access_token, l.id as linkedin_id, l.access_token as linkedin_access_token, t.id as twitter_id, t.access_token as twitter_access_token FROM person p LEFT JOIN facebook f ON p.id = f.person_id LEFT JOIN linkedin l ON p.id = l.person_id LEFT JOIN twitter t ON p.id = t.person_id WHERE p.id = :id');
		$userDetailsQ->execute(array(':id' => $userId));
		$userDetails = $userDetailsQ->fetch(PDO::FETCH_ASSOC);
		$profiles = $this->loadProfiles($userDetails, true);

		// Load data for other account
		$userDetailsQ->execute(array(':id' => $mergeOtherAccount));
		$otherAccount = $userDetailsQ->fetch(PDO::FETCH_ASSOC);
		array_merge($profiles, $this->loadProfiles($otherAccount, false));

		$top = new Top('', 'mergeAccountsPage');
		echo $top->getOutput();

		echo '<h1>'.str_replace('SOCIAL_NETWORK_NAME', $this->mergeNetwork, Content::c()->merge_accounts->notice).'</h1>'.
			'<p class="question">'.(count($profiles) == 2 ? Content::c()->merge_accounts->question_two_profiles : Content::c()->merge_accounts->question_more_profiles).'</p>';

		foreach ($profiles as $profile) {
			echo $profile;
		}

		echo '<form action="/'.Content::l().'/logout/" method="post" class="no">'.
				'<input type="submit" class="button" value="'.Content::c()->merge_accounts->n.'" />'.
			'</form>'.
			'<form action="/'.Content::l().'/ajax/merge-accounts/" method="post" class="yes">'.
				'<input type="submit" class="button" value="'.Content::c()->merge_accounts->y.'" />'.
			'</form>'.
			'<p class="note">'.Content::c()->merge_accounts->note.'</p>';

		$bottom = new Bottom('');
		echo $bottom->getOutput();
	}

	// Load data summarising a person's profiles
	private function loadProfiles($person, $personIsUser) {

		$profiles = array();

		if (!empty($person['facebook_access_token']) && (!$personIsUser || $this->mergeNetwork != 'Facebook')) {
			try {
				//$params = array('access_token' => $user['facebook_access_token']);
				$facebookProfile = SessionManager::getInstance()->getFacebook()->api('/'.$person['facebook_id']);
			} catch (FacebookApiException $e) {
				Debug::l('Error loading Facebook profile for '.($personIsUser ? 'current' : 'other').' user. '.$e);
			}
			if (isset($facebookProfile)) {
				$profiles[] = '<a href="'.$facebookProfile['link'].'" target="_blank" class="profile"><img src="https://graph.facebook.com/'.$person['facebook_id'].'/picture?type=square" /> '.$facebookProfile['name'].' on Facebook</a>';
			}
		}

		if (!empty($person['linkedin_access_token']) && (!$personIsUser || $this->mergeNetwork != 'LinkedIn')) {
			$API_CONFIG = array( 'appKey' => LI_API_KEY, 'appSecret' => LI_SECRET, 'callbackUrl' => '');
			$OBJ_linkedin = new LinkedIn($API_CONFIG);
			$OBJ_linkedin->setTokenAccess(unserialize($person['linkedin_access_token']));
			try {
				$linkedInProfile = $OBJ_linkedin->profile('id='.$person['linkedin_id'].':(first-name,last-name,public-profile-url,picture-url)');
			} catch (ErrorException $e) {
				Debug::l('Error loading LinkedIn profile for '.($personIsUser ? 'current' : 'other').' user. '.$e);
			}
			if ($linkedInProfile['success'] === TRUE) {
				$linkedInProfile['linkedin'] = new SimpleXMLElement($linkedInProfile['linkedin']);
				if ($linkedInProfile['linkedin']->getName() == 'person') {
					$li_pr = (string)$linkedInProfile['linkedin']->{'public-profile-url'};
					$li_pi = (string)$linkedInProfile['linkedin']->{'picture-url'};
					$li_fn = (string)$linkedInProfile['linkedin']->{'first-name'};
					$li_ln = (string)$linkedInProfile['linkedin']->{'last-name'};
					$profiles[] = '<a href="'.$li_pr.'" target="_blank" class="profile"><img src="'.$li_pi.'" /> '.$li_fn.' '.$li_ln.' on LinkedIn</a>';
				}
			}
		}

		if (!empty($person['twitter_access_token']) && ($personIsUser || $this->mergeNetwork != 'Twitter')) {
			try {
				$twitterAccessToken = unserialize($person['twitter_access_token']);
				$twitter = new TwitterOAuth(TW_CONSUMER, TW_SECRET, $twitterAccessToken['oauth_token'], $twitterAccessToken['oauth_token_secret']);
				$twitter->format = 'json';
				$twitterProfile = $twitter->get('users/show', array('user_id' => $person['twitter_id']));
			} catch (ErrorException $e) {
				Debug::l('Error loading Twitter profile for '.($personIsUser ? 'current' : 'other').' user. '.$e);
			}
			if (isset($twitterProfile)) {
				$profiles[] = '<a href="http://twitter.com/'.$twitterProfile->screen_name.'" target="_blank" class="profile"><img src="'.$twitterProfile->profile_image_url.'" /> @'.$twitterProfile->screen_name.' on Twitter</a>';
			}
		}
		
		return $profiles;
	}

}

