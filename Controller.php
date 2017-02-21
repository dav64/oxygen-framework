<?php
class Controller_Exception extends Exception {}

class Controller
{
    protected $_request = null;

    protected $_vars = array();
    protected $_params = array();

    function __construct($request, $view)
    {
        $this->_request = $request;
        $this->view = $view;
    }

    function init()
    {
        // Init function
    }

    function setParams($params)
    {
        $this->_params = $params;
    }

    function getRequest()
    {
        return $this->_request;
    }

    function getView()
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
}