<?php
/**
 * Base class for Plugins handler.
 * Your application's plugins must extend this class !
 */
Class Plugins
{
    /**
     * Called before running the project.
     * You can register custom routes to the router.
     *
     * @param Router $router
     */
    public function beforeBootstrap(Router &$router) {}

    /**
     * Called before dispatching the request.
     * You can choose which controller / action to use in router.
     *
     * @param Request $request
     */
    public function beforeDispatch(Request &$request) {}

    /**
     * Called before adding layouts / partials to view
     * You can add edit anything in view
     *
     * @param View $view
     */
    public function beforeAddingLayout(View &$view) {}

    /**
     * Called before sending the output to the browser
     * You can compress (gzip) the response or add custom headers
     *
     * @param Response $response
     */
    public function beforeRender(Response &$response) {}
}