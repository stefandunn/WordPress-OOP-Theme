<?php

class FlashMessage
{

    public static $flashMessages = [];

    /**
     * Ensure session has initiated
     * @return void
     */
    public static function initialise_session() : void
    {
        if (!session_id()) {
            session_start();
        }

    }

    /**
     * Sets a flash message
     * @param   string $key
     * @param 	mixed $message
     * @return  void
     */
    public static function set(string $key, $message)
    {

        self::initialise_session();

        // Remove old value (if exists)
        if (isset($_SESSION["flash_{$key}"])) {
            dd($_SESSION["flash_{$key}"]);
            unset($_SESSION["flash_{$key}"]);
        }

        // Set value
        $_SESSION["flash_{$key}"] = serialize($message);

        return;

    }

    /**
     * Get a flash message via key
     * @param  string     $key
     * @param  mixed     $fallback
     * @return mixed
     */
    public static function get(string $key, $fallback = null)
    {

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
        else {
            return $fallback;
        }

    }

    /**
     * Checks if flash message exists
     * @param  string  $key
     * @return boolean
     */
    public static function has(string $key): bool
    {
        self::initialise_session();
        return (isset($_SESSION["flash_{$key}"]));
    }

    /**
     * Get's all the flash messages
     * @return array|null
     */
    public static function get_all():  ?array
    {
        self::initialise_session();
        if (!empty($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                if (preg_match("/^flash\_/", $key)) {
                    self::$flashMessages[str_replace("flash_", "", $key)] = unserialize($value);
                }

            }
        }

        return self::$flashMessages;
    }

}
