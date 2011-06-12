<?php

require_once 'php/config.php';
require_once 'php/utils/Content.php';

class AjaxController {

	public function __construct() {

		// Instantiate a Content object to detect the locale and do a 302 redirect if necessary
		$content = Content::getInstance();

		$page = isset($_GET['page']) ? $_GET['page'] : '';

		switch ($page) {
			case 'disconnect' :
				require_once 'php/ajax/AjaxDisconnect.php';
				new AjaxDisconnect();
				break;
			case 'introduce' :
				require_once 'php/ajax/AjaxIntroduce.php';
				new AjaxIntroduce();
				break;
			case 'load-friends' :
				require_once 'php/ajax/AjaxLoadFriends.php';
				new AjaxLoadFriends();
				break;
			case 'merge-accounts' :
				require_once 'php/ajax/AjaxMergeAccounts.php';
				new AjaxMergeAccounts();
				break;
			case 'save-email' :
				require_once 'php/ajax/AjaxSaveEmail.php';
				new AjaxSaveEmail();
				break;
			case 'send-message' :
				require_once 'php/ajax/AjaxSendMessage.php';
				new AjaxSendMessage();
				break;
		}
	}
}

new AjaxController();

