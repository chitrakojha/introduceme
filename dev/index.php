<?php

require_once 'php/config.php';
require_once 'php/utils/Content.php';

class PageController {

	public function __construct() {

		//error_log(print_r($_REQUEST,1));

		// Instantiate a Content object to detect the locale and do a 302 redirect if necessary
		$content = Content::getInstance();

		$page = isset($_GET['page']) ? $_GET['page'] : '';

		switch ($page) {

			/* Login pages */
			case 'facebookcallback' :
				require_once 'php/login/LoginFacebookCallback.php';
				new LoginFacebookCallback();
				break;
			case 'linkedin' :
				require_once 'php/login/LoginLinkedIn.php';
				new LoginLinkedIn();
				break;
			case 'linkedincallback' :
				require_once 'php/login/LoginLinkedInCallback.php';
				new LoginLinkedInCallback();
				break;
			case 'twitter' :
				require_once 'php/login/LoginTwitter.php';
				new LoginTwitter();
				break;
			case 'twittercallback' :
				require_once 'php/login/LoginTwitterCallback.php';
				new LoginTwitterCallback();
				break;

			/* Normal pages */
			case 'about' :
				require_once 'php/pages/PageAbout.php';
				new PageAbout();
				break;
			case 'index' :
				require_once 'php/pages/PageIndex.php';
				new PageIndex();
				break;
			case 'logout' :
				require_once 'php/pages/PageLogout.php';
				new PageLogout();
				break;
			case 'merge-accounts' :
				require_once 'php/pages/PageMergeAccounts.php';
				new PageMergeAccounts();
				break;
			case 'send-introduction' :
				require_once 'php/pages/PageSendIntroduction.php';
				new PageSendIntroduction();
				break;
			case 'settings' :
				require_once 'php/pages/PageSettings.php';
				new PageSettings();
				break;
			case 'view-introduction' :
				require_once 'php/pages/PageViewIntroduction.php';
				new PageViewIntroduction();
				break;
			default :
				header('HTTP/1.0 404 Not Found');
		}
	}
}

new PageController();

