<?php
/**
 * Base class for Plugins handler
 *
 */
Class Plugins
{

    /**
     * Called before running the project, you can register custom routes to the router
     *
     * @param Router $router
     */
    public function beforeBootstrap(Router &$router) {}

    /**
     * Called before dispatching the request, you can choose which controller / action to use
     *
     * @param Request $request
     */
    public function beforeDispatch(Request &$request) {}

    /**
     * Called before adding layouts / partials to view
     *
     * @param View $view
     */
    public function beforeAddingLayout(View &$view) {}

    /**
     * Called before sending the output to the browser
     *
     * @param View $view
     */
    public function beforeRender(View &$view) {}


}