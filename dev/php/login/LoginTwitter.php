<?php

require_once 'php/utils/Content.php';
require_once 'php/clients/twitterOAuth.php';

class LoginTwitter {

	public function __construct() {

		session_start();

		// Create TwitterOAuth object with app key/secret
		$twitter = new TwitterOAuth(TW_CONSUMER, TW_SECRET);
		$callback = APP_URL.'/'.Content::l().'/login/twittercallback/'.(!empty($_GET['nextPage']) ? $_GET['nextPage'] : '');

		// Get temporary credentials
		$requestToken = $twitter->getRequestToken($callback);

		// Save temporary credentials to session
		$_SESSION['twitterOAuthToken'] = $token = $requestToken['oauth_token'];
		$_SESSION['twitterOAuthTokenSecret'] = $requestToken['oauth_token_secret'];
 
		// If last connection failed don't display authorization link
		if ($twitter->http_code == 200) {

			// Build authorize URL and redirect user to Twitter
			$url = $twitter->getAuthorizeURL($token);
			header('Location: '.$url); 
			exit;

		} else {
			$this->exitWithMessage('Could not connect to Twitter. Refresh the page or try again later.');
		}
	}

	private function exitWithMessage($message) {
		Debug::l($message);
		echo $message;
		exit;
	}
}

