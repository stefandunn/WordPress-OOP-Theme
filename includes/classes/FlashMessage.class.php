<?php

Class FlashMessage {

	public static $flashMessages = [];

	/**
	* Ensures session_start has been called
	*/
	public static function initialise_session () {
		if (!session_id())
		    session_start();
	}

	/**
	* Sets a flash message
	*/
	public static function set ($key, $message) {

		self::initialise_session();

		// Remove old value (if exists)
		if (isset($_SESSION["flash_{$key}"])){
			dd($_SESSION["flash_{$key}"]);
			unset($_SESSION["flash_{$key}"]);
		}

		// Set value
		$_SESSION["flash_{$key}"] = serialize($message);

		return;

	}

	/**
	* Get's a message
	*/
	public static function get ($key, $fallback = null) {

		self::initialise_session();
		
		// If found in $flashMessages array, get that
		if (array_key_exists($key, self::$flashMessages)) {
			$return = self::$flashMessages[$key];
			unset($_SESSION["flash_{$key}"]);
			return $return;
		}

		// If found, get message and unset it
		if (isset($_SESSION["flash_{$key}"])) {
			$return = unserialize($_SESSION["flash_{$key}"]);
			// Unset it.
			unset($_SESSION["flash_{$key}"]);
			// And return
			return $return;
		}
		// Otherwise, return fallback
		else return $fallback;

	}

	/**
	* Does a flash message exist via $key
	*/
	public static function has($key) {
		self::initialise_session();
		return (isset($_SESSION["flash_{$key}"]));
	}

	/**
	* Get's all flash messages
	*/
	public static function get_all () {
		self::initialise_session();
		if (!empty($_SESSION)) {
			foreach ($_SESSION as $key => $value) {
				if (preg_match("/^flash\_/", $key))
					self::$flashMessages[str_replace("flash_", "", $key)] = unserialize($value);
			}
		}

		return self::$flashMessages;
	}

}