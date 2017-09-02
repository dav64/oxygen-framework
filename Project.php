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

/**
 * Main application handler
 *
 */
Class Project
{
    private static $instance = null;

    private $_appFolder;

    private $_autoloader;
    private $_router;

    /**
     * Instanciate a MVC project
     *
     * @param string $app_folder
     *      Folder where are all application files (Controllers / Models / Views)
     * @param array $projectOptions
     * @throws Project_Exception
     * @return Project
     */
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

    /**
     * Call a specific action plugin event
     *
     * @param string $action
     *      The plugin method to call
     * @param array $params
     * @throws Plugin_Exception
     */
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

    /**
     * Add a route to the router
     * @see Router::addRoute()
     *
     * @param string $name
     * @param array $options
     */
    public function addRoute($name, $options)
    {
        $this->_router->addRoute($name, $options);
    }

    /**
     * Get URI by route and parameters
     * @see Router::getUrlByRoute()
     *
     * @param string $routeName
     * @param array $params
     * @return string
     */
    public function getUrlByRoute($routeName, $params)
    {
        return $this->_router->getUrlByRoute($routeName, $params);
    }

    public function getAppFolder()
    {
        return $this->_appFolder;
    }

    /**
     * Add our own __autoload implementation
     *
     * Register our autoloader and some namespaces (Oxygen + Helper + those registered in configuration)
     *
     * @return Project
     */
    public function registerAutoloader()
    {
        $config = Config::getInstance();

        // Register our namespaces
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

        // aka spl_autoload_register
        $this->_autoloader->register();

        return $this;
    }

    /**
     * Main project function
     *
     * @throws Exception
     */
    public function run()
    {
        $config = Config::getInstance();

        $request = new Request();

        $this->_router->route($request);

        try
        {
            // Before dispatch, call plugin's 'beforeDispatch' handler
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

            // If error controller / action are defined, let it handle the exception
            if (!empty($errorControllerName) && !empty($errorActionName))
            {
                // Save old request route
                $requestData = array(
                    'controllerName' => $request->getControllerName(),
                    'actionName' => $request->getActionName(),
                    'params' => $request->getAllParams()
                );

                // Store the exception data in the request and go to the error handler
                $request->setParam('exception', $e)->setParam('request', $requestData)
                    ->setControllerName($errorControllerName)
                    ->setActionName($errorActionName);

                // Call plugin's 'beforeDispatch' handler before dispatching the error controller
                self::callPluginAction('beforeDispatch', array(&$request));

                // Load the error handler controller file
                $this->_autoloader->loadControllerClass($request->getControllerName());

                // Make the actual dispatch
                $this->_router->dispatch($request);
            }
            else
                throw $e; // No error handler found, so just throw the exception
        }
    }
}