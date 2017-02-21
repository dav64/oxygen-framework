<?php
require __DIR__ . '/Autoloader.php';
require __DIR__ . '/Router.php';
require __DIR__ . '/View.php';
require __DIR__ . '/Controller.php';
require __DIR__ . '/Helper.php';
require __DIR__ . '/Plugins.php';
require __DIR__ . '/Request.php';

class MVC_Exception extends Exception { }

// Main application handler
Class Project
{
    private static $instance = null;

    private $autoloader;
    private $router;

    private $request;

    public static $pluginClass = 'Plugins';

    public static function callPluginAction($action, $params)
    {
        $params = is_array($params) ? $params : array($params);
        if (class_exists(self::$pluginClass))
        {
            $plugins = new self::$pluginClass();

            if (is_a($plugins, 'Plugins'))
                call_user_func_array(
                    array($plugins, $action),
                    $params
                );
            else
                throw new MVC_Exception('Plugins class "' . self::$pluginClass . '" doesn\'t extend "Plugin" class');
        }
        else
        {
            throw new MVC_Exception('Plugin class "' . self::$pluginClass . '" Not found');
        }
    }

    public static function getInstance($app_folder = '')
    {
        if (self::$instance == null)
            self::$instance = new Project($app_folder);

        return self::$instance;
    }

    private function __construct($app_folder)
    {
        // TODO : loading config (default controller, error controller, ...)


        $this->autoloader = new Autoloader($app_folder);
        $this->router = new Router();
    }

    public function addClassType($type, $folder)
    {
        $this->autoloader->addClassType($type, $folder);
    }

    public function addRoute($name, $options)
    {
        $this->router->addRoute($name, $options);
    }

    public function getUrlByRoute($routeName, $params)
    {
        return $this->router->getUrlByRoute($routeName, $params);
    }

    public function runAutoloader()
    {
        $this->autoloader->run();
    }

    public function run()
    {
        $this->router->route();
        $this->router->dispatch();

        /*
        try { treat action }
        catch {redirect to ErrorController (config)}
        */
    }
}