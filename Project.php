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
    private static $config = null;

    private $autoloader;
    private $router;

    private $request;

    public static function create($app_folder, $projectOptions = array())
    {
        if (self::$instance == null)
            self::$instance = new Project($app_folder, $projectOptions);
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

    private function __construct($app_folder, $projectOptions)
    {
        self::$config = Config::getInstance();
        self::$config->loadConfig($projectOptions);

        View::$viewsFolder = $app_folder . self::$config->getOption('view/folder', '/Views');
        View::$defaultExt = self::$config->getOption('view/extension', '.phtml');

        View::setHelpers(self::$config->getOption('helpers', array()));
        View::setMainLayout(self::$config->getOption('view/mainLayout'));

        $this->autoloader = new Autoloader($app_folder);
        $this->router = new Router();
    }

    public static function callPluginAction($action, $params)
    {
        $pluginClass = self::$config->getOption('pluginsClass', 'Plugins');

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
        {
            throw new Plugin_Exception('Plugin class "' . $pluginClass . '" Not found');
        }
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

    public function registerAutoloader()
    {
        $this->addClassType('Oxygen', __DIR__ . '/Oxygen');

        $this->autoloader->register();
        return $this;
    }

    public function run()
    {
        $this->request = new Request();

        $this->router->route($this->request);

        try
        {
            // Before dispatch, call plugin's handler function
            self::callPluginAction('beforeDispatch', array(&$this->request));

            // Make the actual dispatch
            $this->router->dispatch($this->request);
        }
        catch (Exception $e)
        {
            $errorControllerName = self::$config->getOption('router/error/controller');
            $errorActionName = self::$config->getOption('router/error/action');

            // If error controller / action is defined, let it handle the exception
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