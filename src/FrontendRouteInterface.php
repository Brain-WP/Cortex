<?php namespace Brain\Cortex;

/**
 * Interface for frontend route objects.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
interface FrontendRouteInterface extends RouteInterface {

    /**
     * Set the route id
     */
    function setId( $id = '' );

    /**
     * Get the route id
     *
     * @return string
     */
    function getId();

    /**
     * Set a path for the route. Optionally set path requirements.
     *
     * @param string $path
     * @param array $requirements
     * @return \Brain\Cortex\FrontendRouteInterface Self
     */
    function setPath( $path = '', Array $requirements = [ ] );

    /**
     * Get route path
     *
     * @return string
     */
    function getPath();

    /**
     * Bing the route to routable saveb in Brain controller using its id.
     *
     * @param string $bind
     * @return \Brain\Cortex\Route Self
     */
    function bindTo( $bind = '' );

    /**
     * Get id of currently binded routable
     *
     * @return string
     */
    function getBinding();

    /**
     * Check route integrity and prepare to be matched
     */
    function prepare();
}