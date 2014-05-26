<?php namespace Brain\Cortex;

/**
 * Interface for GroupContainer objects.
 * Groups objects are the way to share common settings among different routes.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
interface GroupContainerInterface {

    /**
     * Save a group
     *
     * @param string $group
     * @param array $args
     */
    public function addGroup( $group, Array $args = [ ] );

    /**
     * Get a saved group
     *
     * @param string $group
     */
    public function getGroup( $group );

    /**
     * Merge group settings into a given route
     *
     * @param \Brain\Cortex\RouteInterface $route
     * @return \Brain\Cortex\RouteInterface Edited route
     */
    public function mergeGroup( RouteInterface $route );
}