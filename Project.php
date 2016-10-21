<?php
require __DIR__ . '/Autoloader.php';
require __DIR__ . '/Router.php';
require __DIR__ . '/View.php';
require __DIR__ . '/Controller.php';
require __DIR__ . '/Helper.php';
require __DIR__ . '/Plugins.php';

class MVC_Exception extends Exception { }

// Main application handler
Class Project
{
    private $autoloader;
    private $router;

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
                //$plugins->$action($this);
            else
                throw new MVC_Exception('Plugins class "' . self::$pluginClass . '" doesn\'t extend "Plugin" class');
        }
        else
        {
            throw new MVC_Exception('Plugin class "' . self::$pluginClass . '" Not found');
        }
    }

    public function __construct($app_folder)
    {
        $this->autoloader = new Autoloader($app_folder);
        $this->router = new Router();
    }

    public function addClassType($type, $folder)
    {
        $this->autoloader->addClassType($type, $folder);
    }

    public function addRoute($name, $controller, $action, $regex = false)
    {
        $this->router->addRoute($name, array('controller' => $controller, 'action' => $action), $regex);
    }

    public function runAutoloader()
    {
        $this->autoloader->run();
    }

    public function run()
    {
        $this->router->route();
    }
}