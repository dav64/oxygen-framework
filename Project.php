<?php
require __DIR__ . '/Autoloader.php';
require __DIR__ . '/Router.php';
require __DIR__ . '/View.php';
require __DIR__ . '/Controller.php';
require __DIR__ . '/Helper.php';
require __DIR__ . '/Plugins.php';
require __DIR__ . '/Config.php';
require __DIR__ . '/Request.php';

class Project_Exception extends Exception { }
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

    public static function create($app_folder = '')
    {
        if (self::$instance == null)
            self::$instance = new Project($app_folder);
        else
            throw new Project_Exception('Project already initalized, use getInstance() Instead');

        return self::$instance;
    }

    public static function getInstance()
    {
        if (self::$instance == null)
            throw new Project_Exception('Project not initalieed');

        return self::$instance;
    }

    private function __construct($app_folder)
    {
        // TODO : loading config (default controller, error controller, ...)
        //$this->config = Config::getInstance();

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
        return $this;
    }

    public function run()
    {
        $this->request = new Request();

        $this->router->route($this->request);

        try
        {
            // Before dispatch, call plugin's function
            self::callPluginAction('beforeDispatch', array(&$this->request));

            // Make the actual dispatch
            $this->router->dispatch($this->request);
        }
        catch (Exception $e)
        {
            $config = Config::getInstance();

            $errorControllerName = $config->getOption('errorRoute/controller');
            $errorActionName = $config->getOption('errorRoute/action');

            // If error controller / action is defined, let them handle the exception
            if (!empty($errorControllerName) && !empty($errorActionName))
            {
                $requestData = array(
                    'controllerName' => $this->request->getControllerName(),
                    'actionName' => $this->request->getActionName(),
                    'params' => $this->request->getAllParams()
                );

                $this->request->setParam('exception', $e)->setParam('request', $requestData)
                    ->setControllerName($errorControllerName)
                    ->setActionName($errorActionName);

                // Before dispatch, call plugin's function
                self::callPluginAction('beforeDispatch', array(&$this->request));

                $this->router->dispatch($this->request);
            }
            else
                throw $e;
        }
    }
}