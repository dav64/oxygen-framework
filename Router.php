<?php
class Router_Exception extends Exception {}

class Router
{
    protected $routes = array();
    protected $regexRoutes = array();

    public function route(&$request)
    {
        $config = Config::getInstance();

        $controllerName = null;
        $actionName = null;

        $request_uri = explode('?', $request->getUri());
        $uri = $request_uri[0];

        // Don't bother with the beginig and last '/'
        $uri = trim($uri, '/');

        $explodedUri = explode('/', $uri);

        $routeData = $this->getRouteByUri($uri);

        if(!empty($routeData['route']))
        {
            // Registered route
            $controllerName = $routeData['route']['controller'];
            $action = $routeData['route']['action'];

            $request->setAllParams($routeData['params']);
        }
        else if(!empty($explodedUri[0]))
        {
            // Handle requests like '/controller-name/action-name'
            $controllerName = strtolower($explodedUri[0]);

            // get action name
            if (!empty($explodedUri[1]))
                $action = strtolower($explodedUri[1]);
            else
                $action = $config->getOption('defaultRoute/action', 'index');
        }
        else
        {
            // We are requesting the root page
            $controllerName = $config->getOption('defaultRoute/controller', 'index');
            $action = $config->getOption('defaultRoute/action', 'index');
        }

        // Convert get parameters to request parameters
        if (!empty($request_uri[1]))
        {
            $params = array();
            parse_str($request_uri[1], $params);

            foreach ($params as $name => $value)
            {
                $request->setParam($name, $value);
            }
        }

        $request->setControllerName($controllerName);
        $request->setActionName($action);
    }

    public function dispatch($request)
    {
        $config = Config::getInstance();

        $controllerSuffix = $config->getOption('routerSuffix/controller', 'Controller');
        $actionSuffix = $config->getOption('routerSuffix/action', 'Action');

        $controllerName = $request->getControllerName();
        $action = $request->getActionName();

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
                $request,
                new View($controllerName.DIRECTORY_SEPARATOR.$action)
            );

            $controller->init();

            if (!empty($action) && method_exists($controller, $actionMethod))
                call_user_func(array($controller, $actionMethod));
            else
                throw new Router_Exception('Method "' . $controllerClassName. '->'. $actionMethod.'()' . '" not exists');

            $controller->getView()->render();
        }
        else
            throw new Router_Exception('Controller "' . $controllerClassName . '" not exists or is not a controller');
    }

    public function addRoute($name, $routeData)
    {
        if (isset($this->routes[$name]))
            throw new Router_Exception('Route "'.$name.'" already exists');
        else if (!isset($routeData['url'], $routeData['controller'], $routeData['action']))
            throw new Router_Exception('Route "'.$name.'" is missing one of mandatory fields: "url", "controller" or "action"');
        else
            $this->routes[$name] = $routeData;
    }

    protected function getRouteByUri($uri)
    {
        $result = false;
        $params = array();
        $foundRoute = false;

        $explodedUri = explode('/', $uri);

        foreach ($this->routes as $name => $route)
        {
            $params = array();
            if (isset($route['url'], $route['controller'], $route['action']))
            {
                // Don't bother with the begining and last '/'
                $routeUrl = trim($route['url'], '/');

                $explodedRoute = explode('/', $routeUrl);

                // Route and Uri have different length => it is not this route
                if (count($explodedRoute) != count($explodedUri))
                    continue;

                // Consider we have probably found the route
                $foundRoute = true;

                foreach ($explodedUri as $i => $part)
                {
                    $explodedRoutePart = $explodedRoute[$i];
                    $explodedUriPart = $explodedUri[$i];

                    if ($explodedRoutePart != $explodedUriPart && $explodedRoutePart[0] != ':')
                    {
                        // Wrong route, remove params and treat next
                        $foundRoute = false;
                        $params = array();
                        break;
                    }
                    else if ($explodedRoutePart[0] == ':')
                    {
                        $paramName = substr($explodedRoutePart, 1);

                        // fill parameter with (in order of presence) : provided value, default or null
                        $params[$paramName] = !empty($explodedUriPart)
                            ? $explodedUriPart
                            : (isset($route['parameters'][$paramName]) ? $route['parameters'][$paramName] : null)
                        ;
                    }
                }

                // If we found the route, get out
                if ($foundRoute)
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
                        : (isset($route['parameters'][$paramName]) ? $route['parameters'][$paramName] : null)
                    ;
                }
            }

            $result = implode('/', $explodedRoute);

            // Don't bother with the beginig and last '/'
            $result = trim($result, '/');
        }

        return '/'.$result;
    }
}