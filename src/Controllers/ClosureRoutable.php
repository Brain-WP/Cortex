<?php namespace Brain\Cortex\Controllers;

use Brain\Cortex\RouteInterface;
use Brain\Request;
use Brain\Hooks;

/**
 * ClosureRoutable is is a routable controller.
 * 
 * Routables are controllers that run when a route match and can be defined per route.
 * This class is used to run a closure saved in the 'binded_closure' route property.
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
     * After sanity check, check the route for a binded closure and if found runs it.
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
        $closure = $this->getRoute()->get( 'binded_closure' );
        if ( $closure instanceof \Closure ) {
            return call_user_func( $closure, $this->getMatchedArgs(), $route, $this->getRequest() );
        }
    }

}