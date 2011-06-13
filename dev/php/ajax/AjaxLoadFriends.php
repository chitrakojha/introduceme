<?php

require_once 'php/utils/Database.php';
require_once 'php/SessionManager.php';
require_once 'php/clients/linkedinOAuth.php';
require_once 'php/clients/twitterOAuth.php';

/**
 * This AJAX script will try to find friends of the user
 * It will check Facebook, LinkedIn OR Twitter (only one per page request to prevent timeouts)
 * 
 * Params:
 * - network (set to Facebook, LinkedIn or Twitter)
 */
class AjaxLoadFriends {

	private $db;
	private $userId;
	private $userDetails;
	private $network;

	public function __construct() {

		session_start();
		header('Content-type: text/json');

		Debug::getInstance()->beginProcessing();

		$this->validateInput();

		// Retrieve friends list from the chosen social network
		switch ($this->network) {
			case 'Facebook' :
				$this->loadFacebookFriends();
				break;
			case 'LinkedIn' :
				$this->loadLinkedInFriends();
				break;
			case 'Twitter' :
				$this->loadTwitterFriends();
				break;
		}

	}

	private function validateInput() {
		// Get the website user
		$this->userId = SessionManager::getInstance()->getUserId();

		// Make sure a user is logged in
		if (empty($this->userId)) {
			$json['result'] = 'false';
			$json['title'] = (string)Content::c()->errors->ajax_error;
			$json['message'] = (string)Content::c()->errors->no_session;
			echo json_encode($json);
			exit;
		}

		// Validate input
		if (empty($_GET['network']) || !in_array($_GET['network'], array('Facebook', 'LinkedIn', 'Twitter'))) {
			$json['result'] = 'false';
			$json['title'] = (string)Content::c()->errors->ajax_error;
			$json['message'] = (string)Content::c()->errors->unknown_error;
			echo json_encode($json);
			exit;
		}
		$this->network = $_GET['network'];

		// Connect to the database
		$this->db = Database::getInstance();

		switch ($this->network) {
			case 'Facebook' :
				$userDetailsQ = $this->db->prepare('SELECT id, access_token FROM facebook WHERE person_id = :person_id');
				break;

			case 'LinkedIn' :
				$userDetailsQ = $this->db->prepare('SELECT id, access_token FROM linkedin WHERE person_id = :person_id');
				break;

			case 'Twitter' :
				$userDetailsQ = $this->db->prepare('SELECT id, access_token FROM twitter WHERE person_id = :person_id');
				break;
		}
		$userDetailsQ->execute(array(':person_id' => $this->userId));
		$this->userDetails = $userDetailsQ->fetch(PDO::FETCH_ASSOC);

		// If we don't have an access token for the network requested, redirect the user to the login page for that network
		if (empty($this->userDetails['access_token'])) {
			Debug::l('Access token missing');
			$json['result'] = 'false';
			$json['redirect'] = 'true';
			$json['title'] = (string)Content::c()->errors->auth_error;
			$json['message'] = str_replace('SOCIAL_NETWORK_NAME', $this->network, Content::c()->errors->invalid_access_token);
			echo json_encode($json);
			exit;
		}
	}

	private function loadFacebookFriends() {

		// If we have loaded the user's Facebook friends already in the last 12 hours, just reuse them
		$cacheQ = $this->db->prepare('SELECT facebook_id, linkedin_id, twitter_id, name FROM temp_friend WHERE temp_friends_id = (SELECT id FROM temp_friends WHERE facebook_id = :facebook_id AND time > DATE_SUB(NOW(), INTERVAL 12 HOUR) ORDER BY time DESC LIMIT 1)');
		$cacheQ->execute(array(':facebook_id' => $this->userDetails['id']));
		$cache = $cacheQ->fetchAll(PDO::FETCH_ASSOC);
		$this->printCachedFriendsIfSet($cache);

		// Load Facebook friends
		try {
			$params = array('access_token' => $this->userDetails['access_token']);
			$facebookFriends = SessionManager::getInstance()->getFacebook()->api('/'.$this->userDetails['id'].'/friends', $params);
		} catch (FacebookApiException $e) {
			$this->printAccessTokenError($e);
		}

		// No friends? :-(
		if (count($facebookFriends['data']) == 0) {
			$json['result'] = 'false';
			echo json_encode($json);
			exit;
		}

		// Cache the Facebook friends so we don't have to query the Facebook API again soon
		$insert = $this->db->prepare('INSERT INTO temp_friends (time, facebook_id) VALUES (NOW(), :facebook_id)');
		$insert->execute(array(':facebook_id' => $this->userDetails['id']));
		$tempFriendsId = $this->db->lastInsertId();
		$friendIds = array();
		foreach ($facebookFriends['data'] as $friend) {
			$id = $friend['id'];
			$friendIds[] = $this->db->quote($id);
			$json['friends'][$id]['facebookId'] = $id;
			$json['friends'][$id]['name'] = $friend['name'];
		}
		$extraInfoQ = $this->db->prepare('SELECT f.id as facebookId, l.id as linkedInId, t.id as twitterId FROM facebook f, person p LEFT JOIN linkedin l ON p.id = l.person_id LEFT JOIN twitter t ON p.id = t.person_id WHERE p.id = f.person_id AND f.id IN ('.implode(',', $friendIds).')');
		$extraInfoQ->execute();
		$extraInfo = $extraInfoQ->fetchAll(PDO::FETCH_ASSOC);
		$json = $this->updateTemporaryFriends($json, $extraInfo, $tempFriendsId);

		// Delete old caches of friend list
		$clearQ = $this->db->prepare('DELETE FROM temp_friends WHERE facebook_id = :facebook_id AND time < DATE_SUB(NOW(), INTERVAL 12 HOUR)');
		$clearQ->execute(array(':facebook_id' => $this->userDetails['id']));

		// Output successful result
		$json['result'] = 'true';
		$json['time'] = Debug::getInstance()->getTimeElapsed();
		echo json_encode($json);
		exit;
	}

	private function loadLinkedInFriends() {

		// If we have loaded the user's LinkedIn friends already in the last 12 hours, just reuse them
		$cacheQ = $this->db->prepare('SELECT facebook_id, linkedin_id, twitter_id, name FROM temp_friend WHERE temp_friends_id = (SELECT id FROM temp_friends WHERE linkedin_id = :linkedin_id AND time > DATE_SUB(NOW(), INTERVAL 12 HOUR) ORDER BY time DESC LIMIT 1)');
		$cacheQ->execute(array(':linkedin_id' => $this->userDetails['id']));
		$cache = $cacheQ->fetchAll(PDO::FETCH_ASSOC);
		$this->printCachedFriendsIfSet($cache);

		// Load friends from LinkedIn
		$API_CONFIG = array('appKey' => LI_API_KEY, 'appSecret' => LI_SECRET, 'callbackUrl' => '');
		$OBJ_linkedin = new LinkedIn($API_CONFIG);
		$OBJ_linkedin->setTokenAccess(unserialize($this->userDetails['access_token']));
		try {
			$linkedInFriends = $OBJ_linkedin->profile('id='.$this->userDetails['id'].'/connections:(id,first-name,last-name)');
		} catch (ErrorException $e) {
			$this->printAccessTokenError($e);
		}

		if ($linkedInFriends['success'] === TRUE) {
			$linkedInFriends['linkedin'] = new SimpleXMLElement($linkedInFriends['linkedin']);
		}

		// Catch LinkedIn error
		if ($linkedInFriends['success'] !== TRUE || $linkedInFriends['linkedin']->getName() != 'connections') {
			$this->printAccessTokenError('Success !== TRUE');
		}

		// No friends? :-(
		if (count($linkedInFriends['linkedin']->children()) == 0) {
			$json['result'] = 'false';
			echo json_encode($json);
			exit;
		}

		// Cache the LinkedIn friends so we don't have to query the LinkedIn API again soon
		$insert = $this->db->prepare('INSERT INTO temp_friends (time, linkedin_id) VALUES (NOW(), :linkedin_id)');
		$insert->execute(array(':linkedin_id' => $this->userDetails['id']));
		$tempFriendsId = $this->db->lastInsertId();
		$friendIds = array();
		foreach ($linkedInFriends['linkedin']->person as $friend) {
			$id = (string)$friend->id;
			$friendIds[] = $this->db->quote($id);
			$json['friends'][$id]['linkedInId'] = $id;
			$json['friends'][$id]['name'] = $friend->{'first-name'}.' '.$friend->{'last-name'};
		}
		$extraInfoQ = $this->db->prepare('SELECT f.id as facebookId, l.id as linkedInId, t.id as twitterId FROM linkedin l, person p LEFT JOIN facebook f ON p.id = f.person_id LEFT JOIN twitter t ON p.id = t.person_id WHERE p.id = l.person_id AND l.id IN ('.implode(',', $friendIds).')');
		$extraInfoQ->execute();
		$extraInfo = $extraInfoQ->fetchAll(PDO::FETCH_ASSOC);
		$json = $this->updateTemporaryFriends($json, $extraInfo, $tempFriendsId);

		// Delete old caches of friend list
		$clearQ = $this->db->prepare('DELETE FROM temp_friends WHERE linkedin_id = :linkedin_id AND time < DATE_SUB(NOW(), INTERVAL 12 HOUR)');
		$clearQ->execute(array(':linkedin_id' => $this->userDetails['id']));

		// Output successful result
		$json['result'] = 'true';
		$json['time'] = Debug::getInstance()->getTimeElapsed();
		echo json_encode($json);
		exit;
	}

	private function loadTwitterFriends() {

		// If we have loaded the user's Twitter friends already in the last 12 hours, just reuse them
		$cacheQ = $this->db->prepare('SELECT facebook_id, linkedin_id, twitter_id, name FROM temp_friend WHERE temp_friends_id = (SELECT id FROM temp_friends WHERE twitter_id = :twitter_id AND time > DATE_SUB(NOW(), INTERVAL 12 HOUR) ORDER BY time DESC LIMIT 1)');
		$cacheQ->execute(array(':twitter_id' => $this->userDetails['id']));
		$cache = $cacheQ->fetchAll(PDO::FETCH_ASSOC);
		$this->printCachedFriendsIfSet($cache);

		// Load Twitter friends
		try {
			$this->userDetails['access_token'] = unserialize($this->userDetails['access_token']);
			$twitter = new TwitterOAuth(TW_CONSUMER, TW_SECRET, $this->userDetails['access_token']['oauth_token'], $this->userDetails['access_token']['oauth_token_secret']);
			$twitter->format = 'json';
			$twitterFriends = $twitter->get('friends/ids');
		} catch (ErrorException $e) {
			$this->printAccessTokenError($e);
		}

		$friendIds = array();
		foreach ($twitterFriends as $friend) {
			$id = (string)$friend;
			$friendIds[] = $id;
			$json['friends'][$id]['twitterId'] = $id;
		}

		// No friends? :-(
		if (count($friendIds) == 0) {
			$json['result'] = 'false';
			echo json_encode($json);
			exit;
		}
		
		// Get the user names from the Twitter API
		// The users/lookup function will only return 100 users.
		$resultsPerCall = 100;
		$maxCalls = 10;
		$calls = ceil(count($friendIds) / $resultsPerCall);
		if ($calls > $maxCalls) $calls = $maxCalls;
		$sql = '';
		for ($i = 0; $i < $calls; $i++) {
			$ids = array_slice($friendIds, $i * $resultsPerCall, 100);
			Debug::l('Loading Twitter profiles from '.($i * $resultsPerCall));
			$twitterProfiles = $twitter->get('users/lookup', array('user_id' => implode(',', $ids)));
			foreach ($twitterProfiles as $friend) {
				$id = (string)$friend->id;
				$json['friends'][$id]['name'] = (string)$friend->name;
			}
		}

		// Cache the Twitter friends so we don't have to query the API again soon
		$insert = $this->db->prepare('INSERT INTO temp_friends (time, twitter_id) VALUES (NOW(), :twitter_id)');
		$insert->execute(array(':twitter_id' => $this->userDetails['id']));
		$tempFriendsId = $this->db->lastInsertId();
		$extraInfoQ = $this->db->prepare('SELECT f.id as facebookId, l.id as linkedInId, t.id as twitterId FROM linkedin l, person p LEFT JOIN facebook f ON p.id = f.person_id LEFT JOIN twitter t ON p.id = t.person_id WHERE p.id = l.person_id AND l.id IN ('.implode(',', $friendIds).')');
		$extraInfoQ->execute();
		$extraInfo = $extraInfoQ->fetchAll(PDO::FETCH_ASSOC);
		$json = $this->updateTemporaryFriends($json, $extraInfo, $tempFriendsId);

		// Delete old caches of friend list
		$clear = $this->db->prepare('DELETE FROM temp_friends WHERE twitter_id = :twitter_id AND time < DATE_SUB(NOW(), INTERVAL 12 HOUR)');
		$clear->execute(array(':twitter_id'=>$this->userDetails['id']));

		// Output successful result
		$json['result'] = 'true';
		$json['time'] = Debug::getInstance()->getTimeElapsed();
		echo json_encode($json);
		exit;
	}

	private function printAccessTokenError($e) {
		Debug::l('Error loading '.$this->network.' friends '.$e);
		$json['result'] = 'false';
		$json['redirect'] = 'true';
		$json['title'] = (string)Content::c()->errors->auth_error;
		$json['message'] = str_replace('SOCIAL_NETWORK_NAME', $this->network, Content::c()->errors->invalid_access_token);
		echo json_encode($json);
		exit;
	}

	private function printCachedFriendsIfSet($cache) {
		if (!empty($cache)) {
			for ($i = 0, $len = count($cache); $i < $len; $i++) {
				if (!empty($cache[$i]['facebook_id'])) {
					$json['friends'][$i]['facebookId'] = $cache[$i]['facebook_id'];
				}
				if (!empty($cache[$i]['linkedin_id'])) {
					$json['friends'][$i]['linkedInId'] = $cache[$i]['linkedin_id'];
				}
				if (!empty($cache[$i]['twitter_id'])) {
					$json['friends'][$i]['twitterId'] = $cache[$i]['twitter_id'];
				}
				$json['friends'][$i]['name'] = $cache[$i]['name'];
			}
			$json['result'] = 'true';
			$json['time'] = Debug::getInstance()->getTimeElapsed();
			echo json_encode($json);
			exit;
		}
	}

	private function updateTemporaryFriends($json, $extraInfo, $tempFriendsId) {
		for ($i = 0, $len = count($extraInfo); $i < $len; $i++) {
			$fbId = $extraInfo[$i]['facebookId'];
			$liId = $extraInfo[$i]['linkedInId'];
			$twId = $extraInfo[$i]['twitterId'];
			if ($this->network == 'Facebook') {
				$json['friends'][$fbId]['linkedInId'] = $liId;
				$json['friends'][$fbId]['twitterId'] = $twId;
			} elseif ($this->network == 'LinkedIn') {
				$json['friends'][$liId]['facebookId'] = $fbId;
				$json['friends'][$liId]['twitterId'] = $twId;
			} elseif ($this->network == 'Twitter') {
				$json['friends'][$twId]['facebookId'] = $fbId;
				$json['friends'][$twId]['linkedInId'] = $liId;
			}
		}
		$sql = 'INSERT INTO temp_friend (temp_friends_id, facebook_id, linkedin_id, twitter_id, name) VALUES';
		$firstRow = true;
		foreach ($json['friends'] as $friend) {
			if (!$firstRow) { $sql .= ','; }
			$sql .= ' ('.$tempFriendsId.', '.
				((!empty($friend['facebookId'])) ? $this->db->quote($friend['facebookId']): 'NULL').', '.
				((!empty($friend['linkedInId'])) ? $this->db->quote($friend['linkedInId']): 'NULL').', '.
				((!empty($friend['twitterId'])) ? $this->db->quote($friend['twitterId']): 'NULL').', '.
				$this->db->quote($friend['name']).')';
			if ($firstRow) { $firstRow = false; }
		}
		$insertTempQ = $this->db->prepare($sql);
		$insertTempQ->execute();
		return $json;
	}

}

