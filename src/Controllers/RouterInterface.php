<?php namespace Brain\Cortex\Controllers;

use Brain\Cortex\RouteInterface;

/**
 * Interface for router object.
 *
 * Concrete objects receives routes, and parse them the routes comparing to request.
 * When a route match, the related routable is ran.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
interface RouterInterface extends ControllerInterface {

    /**
     * Get Symfony matcher instance
     *
     * @return \Symfony\Component\Routing\Matcher
     */
    public function getMatcher();

    /**
     * Get Symfony request context instance
     *
     * @return \Symfony\Component\Routing\RequestContext
     */
    public function getRequestContext();

    /**
     * Get route collection instance
     *
     * @return \Brain\Cortex\RouteCollectionInterface
     */
    public function getCollection();

    /**
     * Get router routes collection instance
     *
     * @return \ArrayObject
     */
    public function getRoutes();

    /**
     * Get groups container instance
     *
     * @return \Brain\Cortex\GroupContainerInterface
     */
    public function getGroups();

    /**
     * Get router fallback instance if any
     *
     * @return \Brain\Cortex\Controllers\FallbackController|void
     */
    public function getFallback();

    /**
     * Save the router fallback instance
     *
     * @param \Brain\Cortex\Controllers\FallbackController $catch_all
     * @return \Brain\Cortex\Controllers\RouterInterface Self
     */
    public function setFallback( FallbackController $catch_all );

    /**
     * Get the routable associated to a route
     *
     * @param \Brain\Cortex\RouteInterface $route
     */
    public function getRoutable( RouteInterface $route = NULL );

    /**
     * Save a routable reference in a route
     *
     * @param \Brain\Cortex\Controllers\RoutableInterface $routable
     * @param \Brain\Cortex\RouteInterface $route
     */
    public function setRoutable( RoutableInterface $routable, RouteInterface $route = NULL );

    /**
     * Add a route to a collection
     *
     * @param \Brain\Cortex\RouteInterface $route
     * @return \Brain\Cortex\Controllers\RouterInterface Self
     */
    function addRoute( RouteInterface $route );

    /**
     * Parse added routes to find a match
     */
    function parseRoutes();

    /**
     * Return true if there is a matched route
     *
     * @return boolean
     */
    function matched();

    /**
     * Get the matched route, if any
     *
     * @return \Brain\Cortex\RouteInterface|void
     */
    function getMatched();

    /**
     * Save a route reference as the matched once
     *
     * @param \Brain\Cortex\RouteInterface $route
     * @return \Brain\Cortex\Controllers\RouterInterface Self
     */
    function setMatched( RouteInterface $route );

    /**
     * Return the matched arguments by Symfony routing component, if any
     *
     * @return array|void
     */
    function getMatchedArgs();

    /**
     * Save an array as the matched arguments by Symfony routing component
     *
     * @param array $args
     * @return \Brain\Cortex\Controllers\RouterInterface Self
     */
    function setMatchedArgs( Array $args = [ ] );
}