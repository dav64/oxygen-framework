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
        return $env == 'development';
    }

    public static function redirect($url)
    {
        header('Location: '.$url);
        exit;
    }

    public static function url($routeName, $params = array())
    {
        $project = Project::getInstance();
        return $project->getUrlByRoute($routeName, $params);
    }
}