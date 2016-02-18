<?php namespace Brain\Cortex\Controllers;

use Brain\Cortex\RequestableInterface;
use Brain\Cortex\HooksableInterface;
use Brain\Cortex\RouteInterface;
use Brain\Cortex\Hooksable;
use Brain\Cortex\Requestable;

/**
 * Abstract base class for routable controllers.
 *
 * Routable are controllers that run when a route match and can be defined per route.
 * This class define the basic setters/getters, allowing concrete subclasses to only define
 * main controller logic (the run() method).
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
abstract class RoutableBase implements RoutableInterface, RequestableInterface, HooksableInterface {

    use Hooksable,
        Requestable;

    protected $hooks;

    protected $request;

    protected $route;

    protected $route_args;

    public function setRoute( RouteInterface $route ) {
        $this->route = $route;
    }

    public function getRoute() {
        return $this->route;
    }

    public function getMatchedArgs() {
        return $this->route_args;
    }

    public function setMatchedArgs( Array $args = array () ) {
        $this->route_args = $args;
    }

}