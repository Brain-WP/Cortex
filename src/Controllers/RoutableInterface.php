<?php namespace Brain\Cortex\Controllers;

use Brain\Cortex\RouteInterface;

/**
 * Interface for routable controllers.
 *
 * Routable are controllers that run when a route match and can be defined per route.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
interface RoutableInterface extends ControllerInterface {

    /**
     * Set the matched route object in the instance. Should be called by router when a route match.
     *
     * @param \Brain\Cortex\RouteInterface $route The matched route object
     * @return void
     */
    function setRoute( RouteInterface $route );

    /**
     * Get the matched route object in the instance.
     *
     * @return \Brain\Cortex\RouteInterface the matched route
     */
    function getRoute();

    /**
     * Set the matched route arguments in the instance. Route arguments, are the url pieces
     * that match the variable pieces in the route path.
     * Should be called by router when a route match.
     *
     * @param array $args The route arguments
     * @return void
     */
    function setMatchedArgs( Array $args = [ ] );

    /**
     * Get the matched route arguments
     *
     * @return Array The route arguments
     * @see Cortex\Controllers\IRoutableController::setRouteArgs()
     */
    function getMatchedArgs();
}