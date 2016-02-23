<?php namespace Brain\Cortex;

/**
 * Route interface used by Cortex. Contain query-related route methods.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
interface QueryRouteInterface extends FrontendRouteInterface {

    /**
     * Set a template for the route. Make sense when using default query builder routable.
     *
     * @param string $callback
     * @return \Brain\Cortex\Route Self
     */
    public function setQuery( $callback = NULL );

    /**
     * Set a template for the route. Make sense when using default query builder routable.
     *
     * @param string $template
     * @return \Brain\Cortex\Route Self
     * @throws \InvalidArgumentException
     */
    public function setTemplate( $template = '' );
}