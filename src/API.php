<?php namespace Brain\Cortex;

/**
 * API class.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 * @version 0.1
 */
class API {

    /**
     * Generate a route object and add it.
     *
     * @param string $path      Path for the route
     * @param string $id        Id for the route
     * @param integer $priority Priority for the route
     * @param array $options    Options for the route. Defaults:
     *                          [
     *                              'defaults'     => [ ],
     *                              'requirements' => [ ],
     *                              'methods'      => [ ],
     *                              'schemes'      => [ ],
     *                              'host'         => ''
     *                          ]
     * @return \Brain\Cortex\RouteInterface
     * @since 0.1
     */
    function add( $path = '', $id = NULL, $priority = NULL, Array $options = [ ] ) {
        return $this->create( $path, $id, $priority, $options )->add();
    }

    /**
     * Generate a route object.
     *
     * Do not the route, the add() can be called on the rote object to add it.
     *
     * @param string $path      Path to match
     * @param string $id        Id for the route object
     * @param integer $priority Priority for the route object
     * @param array $options    Options for the route. See add() method for keys and defaults.
     * @return \Brain\Cortex\RouteInterface
     * @since 0.1
     */
    function create( $path = '', $id = NULL, $priority = NULL, Array $options = [ ] ) {
        $route = $this->getBrain()->get( 'cortex.route' );
        if ( ! empty( $path ) ) {
            $route->setPath( $path );
        }
        if ( ! empty( $id ) ) {
            $route->setId( $id );
        }
        if ( ! empty( $priority ) ) {
            $route->setPriority( $priority );
        }
        $defaults = [
            'defaults'     => [ ],
            'requirements' => [ ],
            'methods'      => [ 'GET', 'POST' ],
            'schemes'      => [ 'http', 'https' ],
            'host'         => ''
        ];
        $args = wp_parse_args( $options, $defaults );
        $route->setDefaults( $args['defaults'] );
        $route->setRequirements( $args['requirements'] );
        $route->setMethods( $args['methods'] );
        $route->setHost( $args['host'] );
        $router = $this->getBrain()->get( 'cortex.router' );
        return $route->setRouter( $router );
    }

    /**
     * Generate a route object, assign the Redirector routable and set redirect options.
     *
     * @param string $path          Path to match
     * @param string|\Closure $to   Path to redirect or a closure that generate it
     * @param int $status           HTTP status for redirect
     * @param bool $external        Allow external redirect or not
     * @param array $options        Options for the route. See add() method for keys and defaults.
     * @param string $id            Id for the route object
     * @param int $priority         Priority for the route
     * @return \Brain\Cortex\RouteInterface
     * @see Brain\Cortex\Api::create()
     * @since 0.1
     */
    function createRedirect( $path = '', $to = '', $status = 301, $external = FALSE,
                             Array $options = [ ], $id = NULL, $priority = NULL ) {
        if ( ! isset( $options['methods'] ) ) {
            $options['methods'] = [ 'GET' ];
        }
        $route = $this->create( $path, $id, $priority, $options );
        $route->bindTo( 'cortex.redirector' );
        if ( is_callable( $to ) || is_string( $to ) ) {
            $route->set( 'redirectto', $to );
        }
        $route->set( 'redirectexternal', (bool) $external );
        $route->set( 'redirectstatus', (int) $status );
        return $route;
    }

    /**
     * Generate and add a route object, assign the Redirector routable and set redirect options.
     *
     * @param string $path          Path to match
     * @param string|\Closure $to   Path to redirect or a closure that generate it
     * @param int $status           HTTP status for redirect
     * @param bool $external        Allow external redirect or not
     * @param array $options        Options for the route. See add() method for keys and defaults.
     * @param string $id            Id for the route object
     * @param int $priority         Priority for the route
     * @return \Brain\Cortex\RouteInterface
     * @since 0.1
     */
    function redirect( $path = '', $to = '', $status = 301, $external = FALSE, Array $options = [ ],
                       $id = NULL, $priority = NULL ) {
        return $this->createRedirect( $path, $to, $status, $external, $options, $id, $priority )->add();
    }

    /**
     * Register a group.
     *
     * @param string $id
     * @param array $options
     * @return \Brain\Cortex\GroupContainer
     * @since 0.1
     */
    function group( $id = '', Array $options = [ ] ) {
        $groups = $this->getBrain()->get( 'cortex.groups' );
        return $groups->addGroup( $id, $options );
    }

    /**
     * Register a fallback for the router.
     *
     * A FallbackController object can be registered passing itself or a binding id for it.
     * Is possible limit the Fallback run under two conditions: a callaback and minimum/maximum/exact
     * number of url "pieces", where a piece is a part of url between two "/".
     *
     * @param string $bind
     * @param \Brain\Cortex\Controllers\FallbackController $object
     * @param \Closure $condition
     * @param int $min_pieces
     * @param bool $exact
     * @return \Brain\Cortex\Controllers\Router
     * @since 0.1
     */
    function useFallback( $bind = '', Controllers\FallbackController $object = NULL,
                          $condition = NULL, $min_pieces = 0, $exact = FALSE ) {
        if ( is_string( $bind ) && ! empty( $bind ) ) {
            $args = [ 'min_pieces' => $min_pieces, 'exact' => $exact, 'condition' => $condition ];
            return $this->getBrain()->get( 'cortex.router' )->setFallbackBind( $bind, $args );
        } elseif ( ! is_null( $object ) ) {
            if ( is_callable( $condition ) ) {
                $object->setCondition( $condition );
            }
            if ( is_numeric( $min_pieces ) ) {
                $object->setMinPieces( (int) $min_pieces );
            }
            $object->isExact( (bool) $exact );
            return $this->getBrain()->get( 'cortex.router' )->setFallback( $object );
        }
    }

    /**
     * Register the FallbackQueryBuilder as fallback controller for the router.
     *
     * Is possible limit the Fallback run under two conditions: a callaback and minimum/maximum/exact
     * number of url "pieces", where a piece is a part of url between two "/".
     *
     * @param \Closure $condition
     * @param int $min_pieces
     * @param boolean $exact
     * @return  \Brain\Cortex\Controllers\Router
     * @since 0.1
     */
    function useQueryFallback( $condition = NULL, $min_pieces = 0, $exact = FALSE ) {
        $bind = 'cortex.fallback_query_builder';
        return $this->useFallback( $bind, NULL, $condition, $min_pieces, $exact );
    }

    /**
     * Generate the url for a route taking its id and an array of arguments.
     *
     * @param type $route_id
     * @param array $args
     * @return string
     * @since 0.1
     */
    function url( $route_id = '', Array $args = [ ] ) {
        if ( ! did_action( 'parse_request' ) ) {
            return new \WP_Error( 'too-early-for-url-generator' );
        }
        return $this->getBrain()->get( 'symfony.generator' )->generate( $route_id, $args );
    }

    /**
     * Register a Controller factory closure in the container
     *
     * @param type $id Controller id
     * @return \Brain\Container
     * @since 0.1
     */
    function registerController( $id = '', \Closure $factory = NULL ) {
        return $this->getBrain()->set( $id, $factory );
    }

    /**
     * Get the matched route object if any.
     *
     * @return \Brain\Cortex\RouteInterface|void
     * @since 0.1
     */
    function getMatched() {
        return $this->getBrain()->get( 'cortex.router' )->getMatched();
    }

    private function getBrain() {
        return \Brain\Container::instance();
    }

}