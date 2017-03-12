<?php
class Oxygen_Auth
{
    public static function getIdentity()
    {
        $session = new Oxygen_Session();

        return $session->userData;
    }

    public static function setIdentity($user = null)
    {
        $session = new Oxygen_Session();

        if ($user)
            $session->userData = $user;
        else
            unset($session->userData);
    }
}