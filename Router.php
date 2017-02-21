<?php
class Router_Exception extends Exception {}

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
            // Handle requests like '/controller-name/action-name'
            $controllerName = strtolower($explodedUri[0]);

            // get action name
            if (!empty($explodedUri[1]))
                $action = strtolower($explodedUri[1]);
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
    }

    public function dispatch()
    {
        // Before dispatch, call plugin's function
        Project::callPluginAction('beforeDispatch', array(&$this->request));

        $controllerSuffix = self::$controllerSuffix;
        $actionSuffix = self::$actionSuffix;

        $controllerName = $this->request->getControllerName();
        $action = $this->request->getActionName();

        // Format controller and action name
        $controllerClassName = ucfirst(preg_replace_callback('/[-_][a-z]/', function ($matches) {
                $upper = strtoupper($matches[0]);
                return $upper[1];
        }, $controllerName)).$controllerSuffix;

        $actionMethod = preg_replace_callback('/[-_][a-z]/', function ($matches) {
                $upper = strtoupper($matches[0]);
                return $upper[1];
        }, $action).$actionSuffix;

        // Make the dispatch
        if (!empty($controllerClassName) && class_exists($controllerClassName) && is_subclass_of($controllerClassName, 'Controller'))
        {
            $controller = new $controllerClassName(
                $this->request,
                new View($controllerName.DIRECTORY_SEPARATOR.$action)
            );

            $controller->init();

            if (!empty($action) && method_exists($controller, $actionMethod))
                call_user_func(array($controller, $actionMethod));
            else
                throw new MVC_Exception('Method "' . $controllerClassName. '->'. $actionMethod.'()' . '" not exists');

            $controller->getView()->render();
        }
        else
            throw new MVC_Exception('Controller "' . $controllerClassName . '" not exists or is not a controller');
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

    public function getUrlByRoute($routeName, $params)
    {
        $result = '';

        if (isset($this->routes[$routeName]))
        {
            $route = $this->routes[$routeName];

            $explodedRoute = explode('/', $route['url']);
            foreach ($explodedRoute as $i => $part)
            {
                $explodedRoutePart = $explodedRoute[$i];

                if ($explodedRoutePart[0] == ':')
                {
                    $paramName = substr($explodedRoutePart, 1);

                    // fill parameter with (in order of presence) : provided value, default or null
                    $explodedRoute[$i] = isset($params[$paramName])
                        ? $params[$paramName]
                        : (isset($route['values'][$paramName]) ? $route['values'][$paramName] : null)
                    ;
                }
            }

            $result = implode('/', $explodedRoute);
            // Don't bother with the last '/'
            if (substr($result, -1) == '/')
                $result = substr($result, 0, -1);
        }

        return '/'.$result;
    }
}