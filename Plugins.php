<?php
Class Plugins
{
    function beforeDispatch(string &$controllerName, string &$action) {}
    function beforeAddingLayout(View &$view) {}
    function beforeRender(View &$view) {}
}