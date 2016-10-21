<?php
class Controller
{
    public $view = null;

    protected $params = array();

    function init()
    {
        // Init function
    }

    function setParams($params)
    {
        $this->params = $params;
    }

    function getParams()
    {
        return $this->params;
    }
}