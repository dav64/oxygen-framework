<?php
/**
 * Base class for Plugins handler
 *
 */
Class Plugins
{
    function beforeDispatch(Request &$request) {}
    function beforeAddingLayout(View &$view) {}
    function beforeRender(View &$view) {}
}