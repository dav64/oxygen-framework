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

    // String Utils
    public static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0)
            return true;

        return (substr($haystack, -$length) === $needle);
    }

    public static function convertToClassName($table_name)
    {
        return preg_replace_callback('/_[a-z]/', function ($matches) {
          return strtoupper($matches[0])[1];
        }, $table_name);
    }

    public static function convertUriToAction($action, $prefix = '', $suffix = '')
    {
        return $prefix.preg_replace_callback('/[-_][a-z]/', function ($matches) {
                $upper = strtoupper($matches[0]);
                return $upper[1];
        }, $action).$suffix;
    }
}