<?php

require_once 'php/utils/Content.php';
require_once 'php/utils/Database.php';
require_once 'php/SessionManager.php';
require_once 'php/ui/Top.php';
require_once 'php/ui/Bottom.php';

class PageSettings {

	private $db;
	private $userId;
	private $userDetails;

	public function __construct() {

		session_start();

		// Connect to the database
		$this->db = Database::getInstance();

		// Get the website user
		$this->userId = SessionManager::getInstance()->getUserId();
		if (empty($this->userId)) {
			Debug::l('No user logged in');
			header('Location: '.Content::getInstance()->getRootUrl());
			exit;
		}

		$userDetailsQ = $this->db->prepare('SELECT p.email, f.id as facebook_id, f.access_token as facebook_access_token, l.id as linkedin_id, l.access_token as linkedin_access_token, t.id as twitter_id, t.access_token as twitter_access_token FROM person p LEFT JOIN facebook f ON p.id = f.person_id LEFT JOIN linkedin l ON p.id = l.person_id LEFT JOIN twitter t ON p.id = t.person_id WHERE p.id = :id');
		$userDetailsQ->execute(array(':id' => $this->userId));
		$this->userDetails = $userDetailsQ->fetch(PDO::FETCH_ASSOC);

		$top = new Top('', 'settingsPage');
		echo $top->getOutput();

		echo '<h1>'.Content::c()->settings->title.'</h1>'.
			'<h2>'.Content::c()->settings->profiles.'</h2>'.
			$this->showConnectedProfiles().
			'<h2>'.Content::c()->settings->email.'</h2>'.
			'<form id="formEmail" class="clearfix">'.
				'<input type="email" name="email" id="email" value="'.$this->userDetails['email'].'" placeholder="'.Content::c()->view->email_request->placeholder.'" />'.
				'<input id="submitEmail" class="button" type="submit" value="'.Content::c()->settings->submit.'" />'.
			'</form>'.
			'';

		$script = '<script>'.
			'var introduceme = (function (module) {'.
				'module.content = module.content || {};'.
				'module.content.success = "'.Content::c()->settings->success.'";'.
				'module.content.saved = "'.Content::c()->settings->saved.'";'.
				'return module;'.
			'}(introduceme || {}));'.
		'</script>';
		$bottom = new Bottom($script);
		echo $bottom->getOutput();
	}

	private function showConnectedProfiles() {
		$output = '<div class="clearfix networks">';
		$facebookLoginUrl = SessionManager::getInstance()->getFacebook()->getLoginUrl(array('redirect_uri' => APP_URL.'/'.Content::l().'/login/facebookcallback/'.Content::l().'/settings/', 'scope' => 'publish_stream'));
		$linkedInLoginUrl = APP_URL.'/'.Content::l().'/login/linkedin/'.Content::l().'/settings/';
		$twitterLoginUrl = APP_URL.'/'.Content::l().'/login/twitter/'.Content::l().'/settings/';

		// Facebook
		$output .= '<div class="clearfix">';
		if ($this->userDetails['facebook_access_token']) {
			$output .= '<a href="'.$facebookLoginUrl.'" id="loginFacebook" class="ir loggedIn">Facebook</a>'.
				'<a href="/'.Content::l().'/ajax/disconnect/?network=Facebook" class="disconnect">'.
				str_replace('SOCIAL_NETWORK_NAME', 'Facebook', Content::c()->settings->disconnect).'</a>';
		} else {
			$output .= '<a href="'.$facebookLoginUrl.'" id="loginFacebook" class="ir">Facebook</a>'.
				'<a href="'.$facebookLoginUrl.'" class="connect">'.
				str_replace('SOCIAL_NETWORK_NAME', 'Facebook', Content::c()->settings->connect).'</a>';
		}

		// LinkedIn
		$output .= '</div><div class="clearfix">';
		if ($this->userDetails['linkedin_access_token']) {
			$output .= '<a href="'.$linkedInLoginUrl.'" id="loginLinkedIn" class="ir loggedIn">LinkedIn</a>'.
				'<a href="/'.Content::l().'/ajax/disconnect/?network=LinkedIn" class="disconnect">'.
				str_replace('SOCIAL_NETWORK_NAME', 'LinkedIn', Content::c()->settings->disconnect).'</a>';
		} else {
			$output .= '<a href="'.$linkedInLoginUrl.'" id="loginLinkedIn" class="ir">LinkedIn</a>'.
				'<a href="'.$linkedInLoginUrl.'" class="connect">'.
				str_replace('SOCIAL_NETWORK_NAME', 'LinkedIn', Content::c()->settings->connect).'</a>';
		}

		// Twitter
		$output .= '</div><div class="clearfix">';
		if ($this->userDetails['twitter_access_token']) {
			$output .= '<a href="'.$twitterLoginUrl.'" id="loginTwitter" class="ir loggedIn">Twitter</a>'.
				'<a href="/'.Content::l().'/ajax/disconnect/?network=Twitter" class="disconnect">'.
				str_replace('SOCIAL_NETWORK_NAME', 'Twitter', Content::c()->settings->disconnect).'</a>';
		} else {
			$output .= '<a href="'.$twitterLoginUrl.'" id="loginTwitter" class="ir">Twitter</a>'.
				'<a href="'.$twitterLoginUrl.'" class="connect">'.
				str_replace('SOCIAL_NETWORK_NAME', 'Twitter', Content::c()->settings->connect).'</a>';
		}
		$output .= '</div></div>';
		return $output;
	}

}

