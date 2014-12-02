<?php namespace Brain\Cortex;

/**
 * Interface for frontend route objects.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
interface FrontendRouteInterface extends RouteInterface
{

    /**
     * Set the route id
     */
    function setId($id);

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
    function setPath($path = '', Array $requirements = []);

    /**
     * Get route path
     *
     * @return string
     */
    function getPath();

    /**
     * Check route integrity and prepare to be matched
     */
    function prepare();
}