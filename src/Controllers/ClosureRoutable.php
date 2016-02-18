<?php namespace Brain\Cortex\Controllers;

use Brain\Cortex\RouteInterface;
use Brain\Request;
use Brain\Hooks;

/**
 * ClosureRoutable is is a routable controller.
 *
 * Routable are controllers that run when a route match and can be defined per route.
 * This class is used to run a closure saved in the 'bound_closure' route property.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
class ClosureRoutable extends RoutableBase {

    function __construct( Request $request, Hooks $hooks ) {
        $this->request = $request;
        $this->hooks = $hooks;
    }

    /**
     * After sanity check, check the route for a bound closure and if found runs it.
     *
     * @return mixed whatever returned by called closure
     * @throws \DomainException
     * @access public
     */
    public function run() {
        $route = $this->getRoute();
        if ( ! $route instanceof RouteInterface ) {
            throw new \DomainException;
        }
        $closure = $route->get( 'bound_closure' );

        return  $closure instanceof \Closure
            ? call_user_func( $closure, $this->getMatchedArgs(), $route, $this->getRequest() )
            : null;
    }

}