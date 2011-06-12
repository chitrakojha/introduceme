<?php

/**
 * A Singleton to manage the locale and the localised copy
 *
 * @author Keegan Street
 */
class Content {

	protected static $instance;
	protected $validLocales = array('en');
	protected $locale;
	protected $copy = array();
	protected $emails = array();

	/**
	 * You can pass in a locale parameter the first time you call getInstance('fr') in order to set the locale manually.
	 */
	public static function getInstance($locale = null) {
		if (!(self::$instance instanceof self)) {
			self::$instance = new self;
			self::$instance->setLocale($locale);
		}
		return self::$instance;
	}

	/**
	 * Set language from params
	 */
	public function setLocale($locale) {

		// Set the locale manually?
		if (isset($locale) && in_array($locale, $this->validLocales)) {
			$this->locale = $locale;
			return;
		}

		// Is the locale explicitly set as a GET parameter?
		if (isset($_GET['locale']) && in_array($_GET['locale'], $this->validLocales)) {
			$this->locale = $_GET['locale'];
			return;
		}

		// Get language from the browser settings and do a 302 redirect
		if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$httpLangs = array();
			foreach ($this->validLocales as $l) {
				$httpLangs[] = array('locale' => $l, 'pos' => stripos($_SERVER['HTTP_ACCEPT_LANGUAGE'], $l));
			}
			usort($httpLangs, 'Content::sortHttpLangs');
			$this->locale = $httpLangs[0]['locale'];
			header('Location: /'.$this->locale.$this->getNonLocalUrl(), true, 302);
			exit;
		}

		// Set language to default
		// This is really just a fallback in case I made a mistake, actually it should never happen
		$this->locale = $this->validLocales[0];
	}

	public static function sortHttpLangs($a, $b) {
		if ($a['pos'] === $b['pos']) {
			return 0;
		} elseif ($b['pos'] === false) {
			return -1;
		} elseif ($a['pos'] === false) {
			return 1;
		} else {
			return ($a['pos'] < $b['pos']) ? -1 : 1;
		}
	}

	/**
	 * Returns the URL without the locale prefix (/en/prize/ becomes /prize)
	 */
	public function getNonLocalUrl() {
		$url = $_SERVER['REQUEST_URI'];
		foreach ($this->validLocales as $l) {
			if (substr($url, 0, 4) == '/'.$l.'/') {
				return substr($url, 3);
			}
		}
		return $url;
	}

	public function getRootUrl() {
		return APP_URL.'/'.$this->locale.'/';
	}

	/**
	 * Returns a string of the locale
	 */
	public function getLocale() {
		return $this->locale;
	}

	/**
	 * Returns an XML file containing copy
	 * @param $section The filename of the copy you want to load
	 */
	public function getCopy($section = 'common') {
		// Load the XML copy if it hasn't already been loaded
		if (!isset($this->copy[$section])) {
			$this->copy[$section] = simplexml_load_file(INCLUDE_PATH.'/content/'.$this->getLocale().'/'.$section.'.xml');
		}
		return $this->copy[$section];
	}

	public function getEmail($email) {
		// Load the email copy if it hasn't already been loaded
		if (!isset($this->emails[$email])) {
			$this->emails[$email] = file_get_contents(INCLUDE_PATH.'/content/'.$this->getLocale().'/'.$email.'.html');
		}
		return $this->emails[$email];
	}

	// Short fast super duper alias for getInstance()->getCopy()
	public static function c($section = 'common') {
		return self::getInstance()->getCopy($section);
	}

	// Short fast super duper alias for getInstance()->getLocale()
	public static function l() {
		return self::getInstance()->getLocale();
	}

	// This is a Singleton, you're not allowed to make an explicit call of the constructor: $c = new Content();
	final private function __construct() { }

	// Do not allow the clone operation: $x = clone $v;
	final private function __clone() { }

}

