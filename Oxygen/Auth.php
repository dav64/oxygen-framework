<?php

class Oxygen_Auth
{
	/**
	 * Get the user identity
	 * 
	 * @return mixed
	 */
    public static function getIdentity()
    {
        $session = new Oxygen_Session();

        return $session->userData;
    }

    /**
     * Set the user identity
     *
     */
    public static function setIdentity($user = null)
    {
        $session = new Oxygen_Session();

        if ($user)
            $session->userData = $user;
        else
            unset($session->userData);
    }
}