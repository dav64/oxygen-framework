<?php
class Controller_Exception extends Exception {}

class Controller
{
    protected $_request = null;
    protected $_params = array();

    protected $view = null;

    public function __construct(Request $request)
    {
        $config = Config::getInstance();

        $controllerName = $request->getControllerName();
        $actionName = $request->getActionName();
        $viewExtension = $config->getOption('view/extension', '.phtml');

        $this->_request = $request;
        $this->view = new View($controllerName.DIRECTORY_SEPARATOR.$actionName . $viewExtension);
    }

    function init()
    {
        // Init function
    }

    public function setParams($params)
    {
        $this->_params = $params;
    }

    public function getRequest()
    {
        return $this->_request;
    }

    public function getView()
    {
        return $this->view;
    }

    public function __get($name)
    {
        return isset($this->_params[$name]) ? $this->_params[$name] : null;
    }

    public function __set($name, $value)
    {
        if ($name[0] == '_')
            throw new Controller_Exception('trying to set reserved property');

        $this->_params[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->_params[$name]);
    }

    public function __unset($name)
    {
        if ($name[0] == '_')
            throw new Controller_Exception('trying to unset reserved property');

        unset($this->_params[$name]);
    }

    public function render()
    {
        $this->view->render();
    }
}