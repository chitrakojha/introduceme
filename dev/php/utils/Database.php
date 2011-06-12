<?php

/**
 * A Singleton interface to the database
 */
class Database {

	private static $instance;
	private $db;

	// Singleton get instance
	public static function getInstance() {
		if (!(self::$instance instanceof self)) {
			self::$instance = new self();
		}
		return self::$instance->getDb();
	}

	private function getDb() {
		if (!isset($this->db)) {
			try {
				$this->db = new PDO('mysql:host=localhost; dbname='.DB_NAME.'; charset=utf8', DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
				if (!PRODUCTION_SERVER) {
					$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				}
				$this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, 1);
			} catch (PDOException $e) {
			    error_log('PDO Error: '.$e->getMessage());
			    exit;
			}
		}
		return $this->db;
	}

	// Do not allow an explicit call of the constructor: $v = new Singleton();
	final private function __construct() { }

	// Do not allow the clone operation: $x = clone $v;
	final private function __clone() { }
}

