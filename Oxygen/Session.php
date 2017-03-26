<?php
class Oxygen_Session
{
    private static $session_started = false;

    public function __construct()
    {
        if (!self::$session_started)
        {
            session_start();
            self::$session_started = true;
        }
    }

    public function destroy()
    {
        session_destroy();
        self::$session_started = true;
    }

    public function __get($name)
    {
        return isset($_SESSION[$name]) ? unserialize($_SESSION[$name]) : null;
    }

    public function __set($name, $value)
    {
        $_SESSION[$name] = serialize($value);
    }

    public function __isset($name)
    {
        return isset($_SESSION[$name]);
    }

    public function __unset($name)
    {
        unset($_SESSION[$name]);
    }

    public static function setCookie($name, $value = null, $expire = null)
    {
        setCookie($name, $value, $expire);
    }

    public static function getCookie($name)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
    }
}