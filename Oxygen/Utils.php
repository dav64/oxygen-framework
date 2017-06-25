<?php
class Oxygen_Utils
{
    /**
     * Pretty print a variable
     *
     * @param $var mixed the var to dump
     * @param $exit boolean exit the script execution after the dump
     */
    public static function dump($var, $exit = false)
    {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';

        if ($exit)
            exit;
    }

    /**
     * Tell if the application is on development or production server
     *
     * @return boolean true if we are on development mode (set by env)
     */
    public static function isDev()
    {
        $env = getenv('APPLICATION_ENV') ?: 'production';
        return $env == 'development';
    }

    /**
     * Redirect client to an url
     *
     * @param $url string the url to redirect
     */
    public static function redirect($url)
    {
        header('Location: '.$url);
        exit;
    }

    /**
     * Get an parameterized URL from the router
     *
     * @param $routeName string the route name
     * @param $params array route parameters (if any)
     */
    public static function url($routeName, $params = array())
    {
        $project = Project::getInstance();
        return $project->getUrlByRoute($routeName, $params);
    }

    /**
     * Check if the string $haystack starts with the $needle string
     */
    public static function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0)
            return true;

        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * Check if the string $haystack starts end the $needle string
     */
    public static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0)
            return true;

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * Convert a table name to a class name
     * ie. 'some_table_name' become 'SomeTableName'
     */
    public static function convertToClassName($table_name)
    {
        return preg_replace_callback('/_[a-z]/', function ($matches) {
          return strtoupper($matches[0])[1];
        }, $table_name);
    }

    /**
     * Convert a uri component to an action/controler classname
     * ie. 'action-name' become 'PrefixActionNameSuffix'
     */
    public static function convertUriToAction($action, $prefix = '', $suffix = '')
    {
        return $prefix.preg_replace_callback('/[-_][a-z]/', function ($matches) {
                $upper = strtoupper($matches[0]);
                return $upper[1];
        }, $action).$suffix;
    }
}