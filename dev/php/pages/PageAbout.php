<?php

require_once 'php/utils/Content.php';
require_once 'php/utils/Database.php';
require_once 'php/ui/Top.php';
require_once 'php/ui/Bottom.php';

class PageAbout {

	public function __construct() {

		$top = new Top('', 'aboutPage');
		echo $top->getOutput();

		echo Content::c()->about->desc;

		$bottom = new Bottom('');
		echo $bottom->getOutput();

	}

}

