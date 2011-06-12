<?php

/**
 * This function is used to write messages to the error log that aren't actually errors.
 */
class Debug {
	
	private static $instance;
	private $isEnabled;
	private $beginTime;
	private $endTime;
	
	// Singleton get instance
	public static function getInstance() {
		if (!(self::$instance instanceof self)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	// Record the time script began processing so we can see how long it takes
	public function beginProcessing() {
		$this->beginTime = microtime(true);
	}

	public function enabled($enabled) {
		$this->isEnabled = $enabled;
	}

	public function log($message) {
		if ($this->isEnabled == true) {
			error_log($message);
		}
	}

	// Short alias for getInstance()->log();
	public static function l($message) {
		return self::getInstance()->log($message);
	}

	public function getTimeElapsed() {
		$this->endTime = microtime(true);
		$seconds = $this->endTime - $this->beginTime;
		return $seconds;
	}

	public function traceVars() {
		if ($this->isEnabled) {
			echo '<div id="debug">'.
				'<h3>Session vars</h3>'.
				'<pre>'.print_r($_SESSION, true).'</pre>'.
				'<h3>Cookie vars</h3>'.
				'<pre>'.print_r($_COOKIE, true).'</pre>'.
				'<h3>POST vars</h3>'.
				'<pre>'.print_r($_POST, true).'</pre>'.
				'<h3>GET vars</h3>'.
				'<pre>'.print_r($_GET, true).'</pre>'.
				'<p>Processed in '.$this->getTimeElapsed().' seconds</p>'.
				'</div>';
		}
	}

	// Do not allow an explicit call of the constructor: $v = new Singleton();
	final private function __construct() { }

	// Do not allow the clone operation: $x = clone $v;
	final private function __clone() { }

}

