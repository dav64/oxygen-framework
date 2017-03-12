<?php
class Oxygen_Utils
{
    public static function dump($var, $exit = false)
    {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';

        if ($exit)
            exit;
    }

    public static function isDev()
    {
        $env = getenv('APPLICATION_ENV') ?: 'development';
        return $env != 'production';
    }

    public static function redirect($url)
    {
        header('Location: '.$url);
        exit;
    }
}