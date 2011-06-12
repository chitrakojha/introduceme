<?php

require_once('php/utils/Content.php');
require_once('php/Logout.php');

class PageLogout {

	public function __construct() {

		session_start();

		// Log the user out
		new Logout();

		// Redirect to the home page
		header('Location: '.APP_URL.'/'.Content::l().'/');

	}

}

