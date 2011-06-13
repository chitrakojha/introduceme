<?php

require_once 'php/SessionManager.php';

class Bottom {

	private $output;
	private $userId;

	public function __construct($script = '') {

		$this->userId = SessionManager::getInstance()->getUserId();

		$this->output = '</div>'. // close #main
			'<div class="footer"><footer>'.
				'<a href="/'.Content::l().'/" class="home">'.Content::c()->home->home.'</a>'.
				'<a href="/'.Content::l().'/about/" class="about">'.Content::c()->about->about.'</a>'.
				(!empty($this->userId) ? '<a href="/'.Content::l().'/settings/" class="settings">'.Content::c()->settings->title.'</a>'.
					'<a href="/'.Content::l().'/logout/" class="logout">'.Content::c()->logout.'</a>' : '').
			'</footer></div>'.

			'<script src="/js/plugins.js"></script>'.
			'<script src="/js/introduceme.js"></script>'.
			$script.
			'<script>'.
				// Google Analytics
				'var _gaq = [["_setAccount", "UA-20937143-1"],["_trackPageview"]];'.
				'(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];g.async=1;g.src="//www.google-analytics.com/ga.js";s.parentNode.insertBefore(g,s);}(document,"script"));'.
				// Uservoice
				'var uservoiceOptions = { key: "introduceme", host: "introduceme.uservoice.com", forum: "99481", alignment: "left", background_color:"#152061", text_color: "white", hover_color: "#0f1644", lang: "'.Content::l().'", showTab: true };'.
				'$(document).ready(function () {'.
					'(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];g.async=1;g.src="//cdn.uservoice.com/javascripts/widgets/tab.js";s.parentNode.insertBefore(g,s);}(document,"script"));'.
				'});'.
			'</script>'.

			'</body>'.
			'</html>';
	}

	public function getOutput() {
		return $this->output;
	}
}

