<?php

require_once 'php/login/LoginGeneralCallback.php';
require_once 'php/clients/linkedinOAuth.php';

class LoginLinkedInCallback extends LoginGeneralCallback {

	protected $network = 'LinkedIn';
	protected $linkedInId;

	protected function getUserIdFromApi() {
		// Create a LinkedIn object
		$linkedInApiConfig = array(
			'appKey'      => LI_API_KEY,
			'appSecret'   => LI_SECRET,
			'callbackUrl' => APP_URL.'/'.Content::l().'/login/linkedincallback/'.(!empty($_GET['nextPage']) ? $_GET['nextPage'] : '')
		);
		$linkedIn = new LinkedIn($linkedInApiConfig);

		try {
			$response = $linkedIn->retrieveTokenAccess($_GET['oauth_token'], $_SESSION['oauth']['linkedin']['request']['oauth_token_secret'], $_GET['oauth_verifier']);
		} catch (Error $e) {
			Debug::l('Error. Could not retrieve LinkedIn access token. '.$e);
			header('Location: '.APP_URL.'/'.Content::l().'/login/linkedin/');
			exit;
		}
		if ($response['success'] === TRUE) {
			// The request went through without an error, gather user's access tokens
			$_SESSION['oauth']['linkedin']['access'] = $response['linkedin'];
			// Set the user as authorized for future quick reference
			$_SESSION['oauth']['linkedin']['authorized'] = true;
		} else {
			$this->exitWithMessage('Error. The OAuth access token was not retrieved. '.print_r($response, 1));
		}
		$this->accessToken = serialize($response['linkedin']);

		/*
		Retrieve the user ID
		The XML response will look like one of these:

		<person>
		  <id>8GhzNjjaOi</id>
		</person>

		<error>
		  <status>401</status>
		  <timestamp>1288518358054</timestamp>
		  <error-code>0</error-code>
		  <message>[unauthorized]. The token used in the OAuth request is not valid.</message>
		</error>
		*/

		try {
			$response = $linkedIn->profile('~:(id,first-name,last-name)');
			if ($response['success'] === TRUE) {
				$response['linkedin'] = new SimpleXMLElement($response['linkedin']);
				if ($response['linkedin']->getName() != 'person') {
					Debug::l('Error. Could not retrieve person data from LinkedIn. '.print_r($response, 1));
					header('Location: '.APP_URL.'/'.Content::l().'/login/linkedin/');
					exit;
				}
			} else {
				Debug::l('Error. Could not retrieve person data from LinkedIn. '.print_r($response, 1));
				header('Location: '.APP_URL.'/'.Content::l().'/login/linkedin/');
				exit;
			}

			$this->linkedInId = (string)$response['linkedin']->id;
			$this->name = $response['linkedin']->{'first-name'}.' '.$response['linkedin']->{'last-name'};
		} catch (Error $e) {
			Debug::l('Error. Could not retrieve person ID from LinkedIn. '.$e);
			header('Location: '.APP_URL.'/'.Content::l().'/login/linkedin/');
			exit;
		}
	}

	// See if this user is already in the database
	protected function getUserDetailsFromDb() {
		$userDetailsQ = $this->db->prepare('SELECT person_id FROM linkedin WHERE id = :linkedin_id');
		$userDetailsQ->execute(array(':linkedin_id' => $this->linkedInId));
		$this->userDetails = $userDetailsQ->fetch(PDO::FETCH_ASSOC);
	}

	protected function updateAccessToken() {
		$updateQ = $this->db->prepare('UPDATE linkedin SET access_token = :access_token WHERE id = :linkedin_id');
		$updateQ->execute(array(
			':access_token' => $this->accessToken,
			':linkedin_id'  => $this->linkedInId
		));
	}

	protected function insertProfile() {
		$updateQ = $this->db->prepare('INSERT INTO linkedin (id, access_token, person_id) VALUES (:linkedin_id, :access_token, :person_id)');
		$updateQ->execute(array(
			':linkedin_id'  => $this->linkedInId,
			':access_token' => $this->accessToken,
			':person_id'    => $_SESSION['loggedInPersonId']
		));
	}

}

