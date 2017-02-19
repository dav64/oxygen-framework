<?php
class Router
{
    public static $defaultController = 'index';
    public static $defaultAction = 'index';
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

        // Don't bother with the last '/'
        if (substr($uri, -1) == '/')
            $uri = substr($uri, 0, -1);

        $explodedUri = explode('/', substr($uri, 1));

        $routeData = $this->getRouteByUri($uri);

        if(!empty($routeData['route']))
        {
            // Registered route
            $controllerName = $routeData['route']['controller'];
            $action = $routeData['route']['action'];

            $this->request->setParams($routeData['params']);
        }
        else if(!empty($explodedUri[0]))
        {
            // Handle requests like '/controller/action'
            $controllerName = ucfirst(preg_replace_callback('/[-_][a-z]/', function ($matches) {
                $upper = strtoupper($matches[0]);
                return $upper[1];
            }, strtolower($explodedUri[0])));

            // get action name
            if (!empty($explodedUri[1]))
                $action = str_replace(array('-', '_'), '', $explodedUri[1]);
            else
                $action = self::$defaultAction;
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

        //print_r($this->request); exit;

        // Before dispatch, call plugin's function
        Project::callPluginAction('beforeDispatch', array(&$this->request));

        $this->dispatch(); // TODO: put this in 'project' class + verify that request is still a valid object of REQUEST
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

    public function addRoute($name, $routeData)
    {
        if (isset($this->routes[$name]))
            throw new Router_Exception('Route "'.$name.'" already exists');
        else if (!isset($routeData['url'], $routeData['controller'], $routeData['action']))
            throw new Router_Exception('Route "'.$name.'" does not contain all of mandatory fields: "url", "controller" or "action"');
        else
            $this->routes[$name] = $routeData;
    }

    protected function getRouteByUri($uri)
    {
        $result = false;
        $params = array();
        $foundRoute = false;

        $explodedUri = explode('/', substr($uri, 1));

        foreach ($this->routes as $name => $route)
        {
            $params = array();
            if (isset($route['url'], $route['controller'], $route['action']))
            {
                $explodedRoute = explode('/', $route['url']);

                // consider we have probably found the route
                $keepRoute = true;

                foreach ($explodedUri as $i => $part)
                {
                    $explodedRoutePart = $explodedRoute[$i];
                    $explodedUriPart = $explodedUri[$i];

                    if ($explodedRoutePart != $explodedUriPart && $explodedRoutePart[0] != ':')
                    {
                        // Wrong route, remove params and treat next
                        $keepRoute = false;
                        $params = array();
                        break;
                    }
                    else if ($explodedRoutePart[0] == ':')
                    {
                        $paramName = substr($explodedRoutePart, 1);

                        // fill parameter with (in order of presence) : provided value, default or null
                        $params[$paramName] = !empty($explodedUriPart)
                            ? $explodedUriPart
                            : (isset($route['values'][$paramName]) ? $route['values'][$paramName] : null)
                        ;
                    }
                }

                // If we found the route, get out
                if ($keepRoute)
                {
                    $result = $route;
                    break;
                }
            }
        }

        return array(
            'route' => $result,
            'params' => $params,
        );
    }
}

class Router_Exception extends Exception {}