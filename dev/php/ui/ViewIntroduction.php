<?php

class ViewIntroduction {
	
	public function  __construct() { }

	public function top() {
		$top = new Top('', 'viewIntroductionPage');
		return $top->getOutput();
	}
	
	public function introductionTime($introTime) {
		return $this->formatTime($introTime);
	}

	private function formatTime($introTime) {
		return '<time class="'.(($introTime + (12 * 60 * 60) > time()) ? 'timeago' : '').'" datetime="'.date('c', $introTime).'">'.date('j F, Y', $introTime).'</time>';
	}

	public function confirmation($introducee1, $introducee2, $n1, $n2) {
		if (empty($n1) && empty($n2)) {
			$confirmation = Content::c()->view->confirmation->error;
			error_log('Ran into strife notifying user '.$introducee1->getId().' and user '.$introducee1->getId());
		} elseif (empty($n1)) {
			$confirmation = str_replace('INTRODUCEE_ERROR', $introducee1->getName(),
				str_replace('INTRODUCEE', $introducee2->getName(),
				str_replace('MESSAGE', Content::c()->view->confirmation->$n2->singular,
				Content::c()->view->confirmation->half)));
			error_log('Ran into strife notifying user '.$introducee1->getId());
		} elseif (empty($n2)) {
			$confirmation = str_replace('INTRODUCEE_ERROR', $introducee2->getName(),
				str_replace('INTRODUCEE', $introducee1->getName(),
				str_replace('MESSAGE', Content::c()->view->confirmation->$n1->singular,
				Content::c()->view->confirmation->half)));
			error_log('Ran into strife notifying user '.$introducee2->getId());
		} elseif ($n1 == $n2) {
			$confirmation = str_replace('MESSAGES', Content::c()->view->confirmation->$n1->plural, Content::c()->view->confirmation->same);
		} else {
			$confirmation = str_replace('MESSAGE1', Content::c()->view->confirmation->$n1->singular,
				str_replace('MESSAGE2', Content::c()->view->confirmation->$n2->singular,
				Content::c()->view->confirmation->separate));
		}
		$confirmation = str_replace('INTRODUCEE1', $introducee1->getName(),
			str_replace('INTRODUCEE2', $introducee2->getName(), $confirmation));
		return '<div class="confirmation">'.$confirmation.'</div>';
	}

	public function emailForm($person1, $person2) {
		$output = '<form id="formEmail" class="clearfix">'.
				'<label for="email">'.str_replace('PERSON1', $person1->getName(),
					str_replace('PERSON2', $person2->getName(), Content::c()->view->email_request->q)).'</label>'.
				'<input type="email" name="email" id="email" placeholder="'.Content::c()->view->email_request->placeholder.'" />'.
				'<input id="submitEmail" class="button" type="submit" value="'.Content::c()->view->email_request->submit.'" />'.
			'</form>';
		return $output;
	}

	public function socialProfileText($person) {
		$output = '';
		if ($person->getFacebookId() != '') {
			$output .= '<li><a target="_blank" href="http://www.facebook.com/profile.php?id='.$person->getFacebookId().'">'.
				str_replace('PERSON', $person->getName(), Content::c()->view->view_persons_facebook).'</a></li>';
		}
		if ($person->getLinkedInId() != '') {
			$db = Database::getInstance();
			$profileQ = $db->prepare('SELECT profile_url FROM temp_linkedin WHERE linkedin_id = :linkedin_id');
			$profileQ->execute(array(':linkedin_id' => $person->getLinkedInId()));
			$profile = $profileQ->fetch(PDO::FETCH_ASSOC);
			if (!empty($profile['profile_url'])) {
				$output .= '<li><a target="_blank" href="'.$profile['profile_url'].'">'.
					str_replace('PERSON', $person->getName(), Content::c()->view->view_persons_linkedin).'</a></li>';
			}
		}
		if ($person->getTwitterId() != '') {
			$output .= '<li><a target="_blank" href="http://www.twitter.com/'.$person->getTwitterScreenName().'">'.
				str_replace('PERSON', $person->getName(), Content::c()->view->view_persons_twitter).'</a></li>';
		}
		return $output;
	}

	public function socialProfile($person) {
		$output = '';
		if ($person->getFacebookId() != '') {
			$output .= '<div class="facebookProfile clearfix"><div class="facebookLogo"><a target="_blank" href="http://www.facebook.com/profile.php?id='.$person->getFacebookId().'" class="ir">&nbsp;</a></div>'.
				'<a target="_blank" href="http://www.facebook.com/profile.php?id='.$person->getFacebookId().'"><img src="https://graph.facebook.com/'.$person->getFacebookId().'/picture?type=normal" class="profilePic" /></a>'.
				'<h2><a target="_blank" href="http://www.facebook.com/profile.php?id='.$person->getFacebookId().'">'.$person->getName().'</a></h2>'.
				'<p><a target="_blank" href="http://www.facebook.com/profile.php?id='.$person->getFacebookId().'">'.Content::c()->view->view_facebook.'</a></p>'.
				'</div>';
		}
		if ($person->getLinkedInId() != '') {
			// Retrieve the LinkedIn profile URL
			$db = Database::getInstance();
			$profileQ = $db->prepare('SELECT profile_url FROM temp_linkedin WHERE linkedin_id = :linkedin_id');
			$profileQ->execute(array(':linkedin_id' => $person->getLinkedInId()));
			$profile = $profileQ->fetch(PDO::FETCH_ASSOC);
			if (!empty($profile['profile_url'])) {
				$output .= '<div class="linkedInProfile"><a target="_blank" class="linkedin-profileinsider-inline" href="'.$profile['profile_url'].'">'.$person->getName().'</a></div>';
			}
		}
		if ($person->getTwitterId() != '') {
			$screenName = $person->getTwitterScreenName();
			$protected = $person->getTwitterProtected();
			if (!empty($screenName)) {
				if ($protected != '1') {
					$output .= '<div class="twitterProfileWidget"><script>'.
						'if (!introduceme.mobile) {'.
							'$("body").bind("twitterWidgetLoaded", function (e) {'.
								'new TWTR.Widget({'.
									'version: 2, type: "profile", rpp: 4, interval: 6000, width: 305, height: 300,'.
									'theme: {'.
										'shell: { background: "#0099c7", color: "#ffffff" },'.
										'tweets: { background: "#ffffff", color: "#444444", links: "#607890" }'.
									'},'.
									'features: { scrollbar: false, loop: false, live: false, hashtags: true, timestamp: true, avatars: false, behavior: "all" }'.
								'}).render().setUser("'.$screenName.'").start();'.
							'});'.
							'$(".twitterProfileWidget").append("<script src=\'/js/twitter-widget.js\'>\x3C/script>");'.
						'}'.
					'</script></div>';
				} else {
					$output .= '<div class="twitterProfile clearfix">'.
						'<div class="twitterLogo"><a target="_blank" href="http://www.twitter.com/'.$person->getTwitterScreenName().'" class="ir">&nbsp;</a></div>'.
						(($person->getTwitterPicture() != null) ? '<a target="_blank" href="http://www.twitter.com/'.$person->getTwitterScreenName().'"><img src="'.$person->getTwitterPicture().'" class="profilePic" /></a>' : '').
						'<h2><a target="_blank" href="http://www.twitter.com/'.$person->getTwitterScreenName().'">'.$person->getName().' <span class="screenName">@'.$person->getTwitterScreenName().'</span></a></h2>'.
						'<p><a target="_blank" href="http://www.twitter.com/'.$person->getTwitterScreenName().'">'.Content::c()->view->view_twitter.'</a></p>'.
						'</div>';
				}
			}
		}
		return $output;
	}

	public function messageBox($person1, $person2, $id) {
		$output = '<form id="formMessage" class="clearfix" novalidate="novalidate">'.
			'<input type="hidden" name="id" value="'.$id.'" />'.
			'<textarea id="message" name="message" placeholder="'.str_replace('PERSON1', $person1->getName(),
				str_replace('PERSON2', $person2->getName(), Content::c()->view->message)).'"></textarea>'.
			'<input id="submitMessage" class="button" type="submit" value="'.Content::c()->view->submit.'" />'.
		'</form>';
		return $output;
	}

	public function message($name, $time, $body) {
		$output = '<div class="message"><h2>'.str_replace('PERSON', $name, Content::c()->view->wrote).'</h2>'.
			$this->formatTime($time).
			'<p>'.$body.'</p></div>';
		return $output;
	}

}

