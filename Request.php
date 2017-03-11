<?php
class Request
{
    private $controllerName = null;
    private $actionName = null;
    private $params = array();

    public function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public function getUri()
    {
        return $_SERVER["REQUEST_URI"];
    }

    public function getControllerName()
    {
        return $this->controllerName;
    }

    public function setControllerName($value)
    {
        $this->controllerName = $value;
        return $this;
    }

    public function getActionName()
    {
        return $this->actionName;
    }

    public function setActionName($value)
    {
        $this->actionName = $value;
        return $this;
    }

    public function getParam($paramName, $defaultValue = null)
    {
        if (isset($this->params[$paramName]))
            return $this->params[$paramName];
        return $defaultValue;
    }

    public function setParam($paramName, $value)
    {
        $this->params[$paramName] = $value;
        return $this;
    }

    public function setAllParams($params)
    {
        $this->params = $params;
        return $this;
    }

    public function getAllParams()
    {
        return $this->params;
    }
}