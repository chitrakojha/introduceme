<?php

require_once 'php/utils/Content.php';
require_once 'php/utils/Database.php';
require_once 'php/SessionManager.php';
require_once 'php/utils/BaseConvert.php';
require_once 'php/ui/Top.php';
require_once 'php/ui/Bottom.php';

class PageIndex {

	private $db;
	private $userId;
	private $userDetails;
	private $facebookLoginUrl;

	public function __construct() {
		session_start();

		// Connect to the database
		$this->db = Database::getInstance();

		// Get the website user
		$this->userId = SessionManager::getInstance()->getUserId();
		if (!empty($this->userId)) {
			$userDetailsQ = $this->db->prepare('SELECT f.id as facebook_id, f.access_token as facebook_access_token, l.id as linkedin_id, l.access_token as linkedin_access_token, t.id as twitter_id, t.access_token as twitter_access_token FROM person p LEFT JOIN facebook f ON p.id = f.person_id LEFT JOIN linkedin l ON p.id = l.person_id LEFT JOIN twitter t ON p.id = t.person_id WHERE p.id = :id');
			$userDetailsQ->execute(array(':id' => $this->userId));
			$this->userDetails = $userDetailsQ->fetch(PDO::FETCH_ASSOC);
		}

		$this->facebookLoginUrl = SessionManager::getInstance()->getFacebook()->getLoginUrl(array('redirect_uri' => APP_URL.'/'.Content::l().'/login/facebookcallback/', 'scope' => 'publish_stream, offline_access'));

		$top = new Top('', 'homePage');
		echo $top->getOutput();

		echo '<div id="info">'.
				'<p>'.Content::c()->home->desc.'</p>'.
			'</div>'.

			'<div id="formLogin" class="clearfix">'.
				'<p>'.Content::c()->introduce->login.'</p>'.
				'<a href="'.$this->facebookLoginUrl.'" id="loginFacebook" class="ir'.
					(!empty($this->userDetails['facebook_access_token']) ? ' loggedIn' : '').'">Facebook</a>'.
				'<a href="/'.Content::l().'/login/linkedin/" id="loginLinkedIn" class="ir'.
					(!empty($this->userDetails['linkedin_access_token']) ? ' loggedIn' : '').'">LinkedIn</a>'.
				'<a href="/'.Content::l().'/login/twitter/" id="loginTwitter" class="ir'.
					(!empty($this->userDetails['twitter_access_token']) ? ' loggedIn' : '').'">Twitter</a>'.
				'<p id="loginFirst">'.Content::c()->introduce->login_here_first.'</p>'.
			'</div>'.

			'<form id="formIntroduce" class="clearfix" novalidate="novalidate" autocomplete="off">'.
				'<div class="friendSelector introduceeInput1">'.
					'<label for="introducee1">'.Content::c()->introduce->introduce.'</label>'.
					'<input type="text" id="introducee1" placeholder="'.Content::c()->introduce->enter_name.'" />'.
					'<ul class="filteredFriends"></ul>'.
				'</div>'.
				'<div class="friendSelector introduceeInput2">'.
					'<label for="introducee2">'.Content::c()->introduce->with.'</label>'.
					'<input type="text" id="introducee2" placeholder="'.Content::c()->introduce->enter_name.'" />'.
					'<ul class="filteredFriends"></ul>'.
				'</div>'.
				'<label for="message">'.Content::c()->introduce->why.'</label>'.
				'<textarea id="message" placeholder="'.Content::c()->introduce->message.'"></textarea>'.
				'<input id="submitIntroduce" class="button" type="submit" value="'.Content::c()->introduce->submit.'" />'.
				'<a href="/'.Content::l().'/about/" class="help">'.Content::c()->introduce->help.'</a>'.
			'</form>';

		if (!empty($this->userId)) {
			echo $this->previousIntroductions();
		}

		$script = '<script>'.
			'var introduceme = (function (module) {'.
				'module.content = module.content || {};'.
				'module.content.loginFirst = "'.Content::c()->introduce->login_first.'";'.
				'module.personId = '.(!empty($this->userId) ? '"'.$this->userId.'"' : 'null').';'.
				'module.facebookId = '.(!empty($this->userDetails['facebook_access_token']) ? '"'.$this->userDetails['facebook_id'].'"' : 'null').';'.
				'module.linkedInId = '.(!empty($this->userDetails['linkedin_access_token']) ? '"'.$this->userDetails['linkedin_id'].'"' : 'null').';'.
				'module.twitterId = '.(!empty($this->userDetails['twitter_access_token']) ? '"'.$this->userDetails['twitter_id'].'"' : 'null').';'.
				'return module;'.
			'}(introduceme || {}));'.
		'</script>';
		$bottom = new Bottom($script);
		echo $bottom->getOutput();
	}

	private function previousIntroductions() {
		$output = '';
		$introductionsQ = $this->db->prepare('SELECT i.id, i.introducer_id, introducer.name as introducer_name, i.introducee1_id, in1.name as introducee1_name, i.introducee2_id, in2.name as introducee2_name, i.time, i.link_password
			FROM introduction i
			LEFT JOIN person introducer ON introducer.id = i.introducer_id
			LEFT JOIN person in1 ON in1.id = i.introducee1_id
			LEFT JOIN person in2 ON in2.id = i.introducee2_id
			WHERE (i.introducer_id = :id OR i.introducee1_id = :id OR i.introducee2_id = :id)
			ORDER BY time DESC');
		$introductionsQ->execute(array(':id' => $this->userId));
		$introductions = $introductionsQ->fetchAll(PDO::FETCH_ASSOC);
		if (!empty($introductions)) {
			$you = (string)Content::c()->home->you;
			$youCapital = (string)Content::c()->home->you_capital;
			$story = (string)Content::c()->home->story;
			$output .= '<div id="previousIntroductions"><h2>'.Content::c()->home->history.'</h2>';
			foreach ($introductions as $introd) {
				$url = APP_URL.'/'.Content::l().'/A'.$introd['link_password'].BaseConvert::base10ToBase62($introd['id']);
				if ($this->userId == $introd['introducer_id']) {
					$output .= '<p><a href="'.$url.'">'.
						str_replace('INTRODUCEE1_NAME', '<strong>'.$introd['introducee1_name'].'</strong>',
						str_replace('INTRODUCEE2_NAME', '<strong>'.$introd['introducee2_name'].'</strong>',
						str_replace('INTRODUCER_NAME', $youCapital, $story))).'</a></p>';
				} elseif ($this->userId == $introd['introducee1_id']) {
					$output .= '<p><a href="'.$url.'">'.
						str_replace('INTRODUCEE1_NAME', $you,
						str_replace('INTRODUCEE2_NAME', '<strong>'.$introd['introducee2_name'].'</strong>',
						str_replace('INTRODUCER_NAME', '<strong>'.$introd['introducer_name'].'</strong>', $story))).'</a></p>';
				} else {
					$output .= '<p><a href="'.$url.'">'.
						str_replace('INTRODUCEE1_NAME', $you,
						str_replace('INTRODUCEE2_NAME', '<strong>'.$introd['introducee1_name'].'</strong>',
						str_replace('INTRODUCER_NAME', '<strong>'.$introd['introducer_name'].'</strong>', $story))).'</a></p>';
				}
				$output .= $this->formatTime(strtotime($introd['time']));
			}
			$output .= '</div>';
		}
		return $output;
	}

	private function formatTime($time) {
		return '<time class="'.(($time + (12 * 60 * 60) > time()) ? 'timeago' : '').'" datetime="'.date('c', $time).'">'.
			date('j F, Y', $time).'</time>';
	}

}

