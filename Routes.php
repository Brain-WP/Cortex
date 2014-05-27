<?php namespace Brain;

/**
 * Add a routing system to WordPress.
 *
 * This class is a sort of *proxy* to ease the package API calls.
 * All the API functions are defined in the class Brain\Cortex\API and can be called using this
 * class static methods, like:
 *
 *     Brain\Routes::add( '/' )->defaults( [ 'pagename' => 'home' ] );
 *
 * Same methods can be also called using dynamic methods:
 *
 *     $api = new Brain\Routes;
 *     $api->add( '/' )->defaults( [ 'pagename' => 'home' ] );
 *
 * This is useful when the package is used inside OOP plugins, making use of dependency injection.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
class Routes extends Facade {

    public static function getBindId() {
        return 'cortex.api';
    }

}