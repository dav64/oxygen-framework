<?php
class Router
{
    public static $defaultController = 'Index';
    public static $defaultAction = 'Index';
    public static $controllerSuffix = 'Controller';
    public static $actionSuffix = 'Action';

    protected $routes = array();
    protected $regexRoutes = array();

    protected $request = null;

    public function route()
    {
        $this->request = new Request();

        $controllerName = null;
        $actionName = null;

        $uri = strtok($_SERVER["REQUEST_URI"],'?');

        $explodedUri = explode('/', substr($uri, 1));

        if (!empty($this->regexRoutes) && !empty($explodedUri[0]))
            $regexRoute = $this->getRegexRoute($explodedUri[0]);

        if (!empty($regexRoute['controller']) && !empty($regexRoute['action']))
        {
            //Registered regEx route
            $controllerName = $regexRoute['controller'];
            $action = $regexRoute['action'];
        }
        else if(!empty($this->routes[$explodedUri[0]]))
        {
            // Registered route
            $route = $this->routes[$explodedUri[0]];
            $controllerName = $route['controller'];
            $action = $route['action'];
        }
        else if(!empty($explodedUri[0]))
        {
            // Requests like '/controller/action'
            $controllerName = ucfirst(preg_replace_callback('/[-_][a-z]/', function ($matches) {
                $upper = strtoupper($matches[0]);
                return $upper[1];
            }, strtolower($explodedUri[0])));

            $action = self::$defaultAction;

            if (!empty($explodedUri[1]))
                $action = str_replace(array('-', '_'), '', $explodedUri[1]);
        }
        else
        {
            // We are requesting the root page
            $controllerName = self::$defaultController;
            $action = self::$defaultAction;
        }

        $action = strtolower($action);

        $this->request->setControllerName($controllerName);
        $this->request->setActionName($action);

        // Before dispatch, call plugin's function
        Project::callPluginAction('beforeDispatch', array(&$this->request));

        $this->dispatch();
    }

    protected function dispatch()
    {
       $controllerSuffix = self::$controllerSuffix;
       $actionSuffix = self::$actionSuffix;

       $controllerName = $this->request->getControllerName();
       $action = $this->request->getActionName();

        // Make the dispatch
        if (!empty($controllerName) && class_exists($controllerName.$controllerSuffix) && is_subclass_of($controllerName.$controllerSuffix, 'Controller'))
        {
            $controllerClassName = $controllerName.$controllerSuffix;
            $controller = new $controllerClassName(); // Todo : put request & view inconstructor

            $controller->request = $this->request;
            $controller->view = new View(strtolower($controllerName).DIRECTORY_SEPARATOR.strtolower($action));

            $controller->init();

            if (!empty($action) && method_exists($controller, $action.$actionSuffix))
                call_user_func(array($controller, $action.$actionSuffix));
            else
                throw new MVC_Exception('Method "' . $controllerName. '->'. $action.$actionSuffix.'()' . '" not exists');

            $controller->view->render();
        }
        else
            throw new MVC_Exception('Controller "' . $controllerName . '" not exists');
    }

    public function addRoute($name, $data, $regex = false)
    {
        if ($regex)
            $this->regexRoutes[$name] = $data;
        else
            $this->routes[$name] = $data;
    }

    protected function getRegexRoute($ressource)
    {
        $result = false;

        foreach ($this->regexRoutes as $regex => $route) {
            if (preg_match($regex, $ressource))
                $result = $route;
        }

        return $result;
    }
}