<?php

class Top {
	private $output;

	public function __construct($content = '', $page = '') {

		$this->output = "<!doctype html>\n".
			'<!--[if lt IE 7]><html class="no-js ie6" lang="'.Content::l().'"><![endif]-->'.
			'<!--[if IE 7]><html class="no-js ie7" lang="'.Content::l().'"><![endif]-->'.
			'<!--[if IE 8]><html class="no-js ie8" lang="'.Content::l().'"><![endif]-->'.
			'<!--[if gt IE 8]><!--><html class="no-js" lang="'.Content::l().'"><!--<![endif]-->'.
			'<head>'.
				'<meta charset="utf-8" />'.
				'<title>'.Content::c()->title.'</title>'.
				'<meta name="description" content="'.Content::c()->tagline.'" />'.
				'<meta name="author" content="Keegan Street" />'.
				'<meta property="og:image" content="'.APP_URL.'/images/facebook-share-image.png" />'.
				'<link rel="stylesheet" href="/css/style.css" /><!-- Style source: /css/style.scss -->'.
				$content.
				'<script src="/js/modernizr-1.7.min.js"></script>'.
				// jQuery is in the head until we get 1.6.2 because of a bug in opera http://bugs.jquery.com/ticket/9239
				'<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>'.
				'<script>window.jQuery || document.write("<script src=\'/js/jquery-1.6.min.js\'>\x3C/script>")</script>'.
			'</head>'.

			'<body class="'.Content::l().' '.$page.'">'.
			'<div id="fb-root"></div>'.
			'<div class="header"><header>'.
				'<a href="/'.Content::l().'/" class="home"><h1 class="ir">Introd.uce.me</h1></a>'.
				'<h2 class="ir">'.Content::c()->tagline.'</h2>'.
			'</header></div>'.

			'<div id="main" class="clearfix">';

	}

	public function getOutput() {
		return $this->output;
	}
}

