<?php
require __DIR__ . '/Autoloader.php';
require __DIR__ . '/Router.php';
require __DIR__ . '/View.php';
require __DIR__ . '/Controller.php';
require __DIR__ . '/Helper.php';
require __DIR__ . '/Plugins.php';
require __DIR__ . '/Config.php';
require __DIR__ . '/Request.php';

class Plugin_Exception extends Exception { }
class Project_Exception extends Exception { }

// Main application handler
Class Project
{
    private static $instance = null;

    private $_appFolder;

    private $_autoloader;
    private $_router;

    public static function create($app_folder, $projectOptions = array())
    {
        if (self::$instance == null)
            self::$instance = new Project($app_folder, $projectOptions);
        else
            throw new Project_Exception('Project already initalized, use Project::getInstance() instead');

        return self::$instance;
    }

    public static function getInstance()
    {
        if (self::$instance == null)
            throw new Project_Exception('Project not initialized');

        return self::$instance;
    }

    private function __construct($app_folder, $projectOptions)
    {
        $config = Config::getInstance();
        $config->loadConfig($projectOptions);

        $this->_appFolder = $app_folder;

        $this->_autoloader = new Autoloader($app_folder);
        $this->_router = new Router();
    }

    public static function callPluginAction($action, $params)
    {
        $config = Config::getInstance();
        $pluginClass = $config->getOption('pluginsClass', 'Plugins');

        $params = is_array($params) ? $params : array($params);
        if (class_exists($pluginClass))
        {
            $plugins = new $pluginClass();

            if (is_a($plugins, 'Plugins'))
                call_user_func_array(
                    array($plugins, $action),
                    $params
                );
            else
                throw new Plugin_Exception('Plugins class "' . $pluginClass . '" doesn\'t extend "Plugin" class');
        }
        else
            throw new Plugin_Exception('Plugin class "' . $pluginClass . '" Not found');
    }

    public function addRoute($name, $options)
    {
        $this->_router->addRoute($name, $options);
    }

    public function getUrlByRoute($routeName, $params)
    {
        return $this->_router->getUrlByRoute($routeName, $params);
    }

    public function getAppFolder()
    {
        return $this->_appFolder;
    }

    public function registerAutoloader()
    {
        $config = Config::getInstance();

        $this->_autoloader->addClassType('Oxygen', __DIR__ . '/Oxygen');
        $this->_autoloader->addClassType('Helper', $this->_appFolder. $config->getOption('view/helpersFolder', '/Helpers'));

        // Register configured namespaces
        if (!empty($config->getOption('namespaces')))
        {
            $classtypes = $config->getOption('namespaces');

            foreach ($classtypes as $prefix => $folder)
            {
                $this->_autoloader->addClassType($prefix, $this->_appFolder . $folder);
            }
        }

        $this->_autoloader->register();
        return $this;
    }

    public function run()
    {
        $config = Config::getInstance();

        $request = new Request();

        $this->_router->route($request);

        try
        {
            // Before dispatch, call plugin's handler function
            self::callPluginAction('beforeDispatch', array(&$request));

            // Load controller class
            $this->_autoloader->loadControllerClass($request->getControllerName());

            // Make the actual dispatch
            $this->_router->dispatch($request);
        }
        catch (Exception $e)
        {
            $errorControllerName = $config->getOption('router/error/controller');
            $errorActionName = $config->getOption('router/error/action');

            // If error controller / action is defined, let it handle the exception
            if (!empty($errorControllerName) && !empty($errorActionName))
            {
                $requestData = array(
                    'controllerName' => $request->getControllerName(),
                    'actionName' => $request->getActionName(),
                    'params' => $request->getAllParams()
                );

                $request->setParam('exception', $e)->setParam('request', $requestData)
                    ->setControllerName($errorControllerName)
                    ->setActionName($errorActionName);

                $this->_autoloader->loadControllerClass($errorControllerName);

                // Before dispatch, call plugin's function
                self::callPluginAction('beforeDispatch', array(&$request));

                $this->_router->dispatch($request);
            }
            else
                throw $e;
        }
    }
}