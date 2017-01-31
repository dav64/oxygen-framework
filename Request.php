<?php
class Request
{
    private $controllerName = null;
    private $actionName = null;

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
}