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
				'<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />'.
				'<link rel="stylesheet" href="/css/style.css" /><!-- Style source: /css/style.scss -->'.
				$content.
				'<script src="/js/modernizr.js"></script>'.
				'<script>'.
					'var introduceme = (function (module) {'.
						'module.mobile = Modernizr.mq("only all and (max-width: 640px)");'.
						'module.content = module.content || {};'.
						'module.content.errorAjaxTitle = "' . htmlentities(Content::c()->errors->ajax->title , ENT_QUOTES, 'UTF-8') . '";'.
						'module.content.errorAjaxRefresh = "' . htmlentities(Content::c()->errors->ajax->refresh , ENT_QUOTES, 'UTF-8') . '";'.
						'return module;'.
					'}(introduceme || {}));'.
				'</script>'.
				// jQuery is in the head until we get 1.6.2 because of a bug in opera http://bugs.jquery.com/ticket/9239
				'<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>'.
				'<script>window.jQuery || document.write("<script src=\'/js/jquery-1.6.min.js\'>\x3C/script>")</script>'.
			'</head>'.

			'<body class="lang-'.Content::l().' '.$page.'">'.
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

