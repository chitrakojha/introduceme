<?php

require_once 'php/utils/Content.php';
require_once 'php/clients/linkedinOAuth.php';

class LoginLinkedIn {

	public function __construct() {

		session_start();

		// Create a LinkedIn object
		$linkedInApiConfig = array(
			'appKey'      => LI_API_KEY,
			'appSecret'   => LI_SECRET,
			'callbackUrl' => APP_URL.'/'.Content::l().'/login/linkedincallback/'.(!empty($_GET['nextPage']) ? $_GET['nextPage'] : '')
		);
		$linkedIn = new LinkedIn($linkedInApiConfig);

		// Send a request for a LinkedIn access token
		$response = $linkedIn->retrieveTokenRequest();
		if ($response['success'] === TRUE) {
			// Split up the response and stick the LinkedIn portion in the user session
			$_SESSION['oauth']['linkedin']['request'] = $response['linkedin'];

			// Redirect the user to the LinkedIn authentication/authorisation page to initiate validation.
			header('Location: '.LINKEDIN::_URL_AUTH.$_SESSION['oauth']['linkedin']['request']['oauth_token']);
		} else {
			$this->exitWithMessage('Unable to retrieve access token for LinkedIn');
		}
	}

	private function exitWithMessage($message) {
		Debug::l($message);
		echo $message;
		exit;
	}
}

