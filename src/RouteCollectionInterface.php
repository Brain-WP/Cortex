<?php namespace Brain\Cortex;

/**
 * Wrapper for Symfony RouteCollection object
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
interface RouteCollectionInterface {

    /**
     * Set the underlying Symfony RouteCollection class and return it
     *
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getCollection();
}