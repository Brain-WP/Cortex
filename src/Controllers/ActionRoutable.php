<?php namespace Brain\Cortex\Controllers;

use Brain\Cortex\RouteInterface;

/**
 * ActionRoutable is a routable controller.
 *
 * Routables are controllers that run when a route match and can be defined per route.
 * This class is used to run a specific action depends on 'action' argument of matched route.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
abstract class ActionRoutable extends RoutableBase {

    protected $_action_var = 'action';

    /**
     * After sanity check, trigger method named like 'action' route arg.
     *
     * @return mixed whatever returned by called method
     * @throws \DomainException
     * @access public
     */
    public function run() {
        $route = $this->getRoute();
        if ( ! $route instanceof RouteInterface ) {
            throw new \DomainException;
        }
        $route_action_var = $route->get( 'action_routable_id' );
        if ( is_string( $route_action_var ) && $route_action_var !== '' ) {
            $this->setActionVar( $route_action_var );
        }
        $args = $this->getMatchedArgs();
        if ( ! is_array( $args ) ) {
            throw new \DomainException;
        }
        $var = $this->getActionVar();
        $action = isset( $args[$var] ) ? $args[$var] : FALSE;
        if ( is_string( $action ) && method_exists( $this, $action ) ) {
            return call_user_func_array( [ $this, $action ], $args );
        }
    }

    /**
     * Return the route variable name to use as method name for the routable object.
     * By default ir 'action'
     *
     * @return string
     */
    public function getActionVar() {
        return $this->_action_var;
    }

    /**
     * Set the route variable name to use as method name for the routable object.
     * By default ir 'action'
     *
     * @return string
     */
    public function setActionVar( $var = '' ) {
        if ( ! is_string( $var ) || empty( $var ) ) {
            throw new \InvalidArgumentException;
        }
        $this->_action_var = $var;
        return $this;
    }

}