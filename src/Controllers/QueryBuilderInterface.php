<?php namespace Brain\Cortex\Controllers;

/**
 * Interface for controllers that generate query arguments from the array returned by routing.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
interface QueryBuilderInterface {

    /**
     * Given the array from Symfony routing component, depending on route setting, merge query vars,
     * filter out unwanted vars or take only the wanted.
     *
     * @param array $vars Array from route matched via Symfony routing component
     * @return array
     */
    public function buildQueryVars( Array $vars = [ ] );

    /**
     * Getter for the query vars build by object using route query e route arguments.
     * Before being returned query pass through 'cortex_wp_query_vars' filter.
     *
     * @return array Built query arguments
     */
    function getQueryArgs();

    /**
     * Allow to set custom WP_Query class in routes to used in main query
     *
     * @return void|string Custom query class name if set on route
     */
    function getQueryClass();
}