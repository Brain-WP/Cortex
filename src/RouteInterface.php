<?php namespace Brain\Cortex;

/**
 * One of the main "actors" of the package. Routes link a path to a set of arguments.
 * When the route match thos arguments are passed to a routable, that can be set per route
 * or by default in the router.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
interface RouteInterface {

    /**
     * Get a setting from context
     *
     * @param string $index
     * @return mixed
     */
    public function get( $index = NULL );

    /**
     * Save a setting in context.
     *
     * @param string $index
     * @param type $value
     * @return \Brain\Cortex\Route Self
     */
    public function set( $index = NULL, $value = NULL );

    /**
     * Get currently binded routable object
     *
     * @return  \Brain\Cortex\Controllers\RoutableInterface
     */
    public function getRoutable();

    /**
     * Set a routable object for the route
     *
     * @param \Brain\Cortex\Controllers\RoutableInterface $routable
     * @return \Brain\Cortex\Route Self
     */
    public function setRoutable( Controllers\RoutableInterface $routable );

    /**
     * Method called when the route match, before the related routable ran.
     *
     * @return mixed
     */
    function runBefore();

    /**
     * Method called when the route match, after the related routable ran.
     *
     * @return mixed
     */
    function runAfter();
}