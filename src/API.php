<?php namespace Brain\Cortex;

/**
 * API class.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
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
        return $this->addRoute( $this->create( $path, $id, $priority, $options ) );
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
        foreach ( $route->getDefaultSettings() as $key => $value ) {
            $route->set( $key, $value );
        }
        return $route;
    }

    /**
     * Generate a route object, assign the Redirector routable and set redirect options.
     *
     * @param string $path          Path to match
     * @param string|\Closure $to   Path to redirect or a closure that generate it
     * @param int $status           HTTP status for redirect
     * @param array $options        Options for the route. See add() method for keys and defaults.
     * @param bool $external        Allow external redirect or not
     * @param string $id            Id for the route object
     * @param int $priority         Priority for the route
     * @return \Brain\Cortex\RouteInterface
     * @see Brain\Cortex\Api::create()
     * @since 0.1
     */
    function createRedirect( $path = '', $to = '', $status = 301, Array $options = [ ],
                             $external = FALSE, $id = NULL, $priority = NULL ) {
        if ( ! isset( $options['methods'] ) ) {
            $options['methods'] = [ 'GET' ];
        }
        $route = $this->create( $path, $id, $priority, $options );
        $route->bindTo( 'cortex.redirector' );
        if ( is_callable( $to ) ) {
            $route->set( 'redirectto', $to );
        } elseif ( is_string( $to ) && substr_count( $to, '{' ) === 0 ) {
            $route->set( 'redirectto', function ( ) use ( $to ) {
                return $to;
            } );
        } elseif ( is_string( $to ) && ( substr_count( $to, '{' ) === substr_count( $to, '}' ) ) ) {
            $defaults = isset( $options['defaults'] ) ? $options['defaults'] : [ ];
            $route->set( 'redirectto', $this->getDynamicRedirectTo( $to, $defaults ) );
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
     * @param array $options        Options for the route. See add() method for keys and defaults.
     * @param bool $external        Allow external redirect or not
     * @param string $id            Id for the route object
     * @param int $priority         Priority for the route
     * @return \Brain\Cortex\RouteInterface
     * @since 0.1
     */
    function redirect( $path = '', $to = '', $status = 301, Array $options = [ ], $external = FALSE,
                       $id = NULL, $priority = NULL ) {
        $route = $this->creteRedirect( $path, $to, $status, $options, $external, $id, $priority );
        return $this->addRoute( $route );
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
     * @return void
     * @since 0.1
     */
    function registerFallback( $bind = '', Controllers\FallbackController $object = NULL,
                               $condition = NULL, $min_pieces = 0, $exact = FALSE ) {
        if ( is_string( $bind ) && ! empty( $bind ) ) {
            $router = $this->getBrain()->get( 'cortex.router' );
            $args = [ 'min_pieces' => $min_pieces, 'exact' => $exact, 'condition' => $condition ];
            $router->setFallbackBind( $bind, $args );
        } elseif ( ! is_null( $object ) ) {
            if ( is_callable( $condition ) ) {
                $object->setCondition( $condition );
            }
            if ( is_numeric( $min_pieces ) ) {
                $object->setMinPieces( (int) $min_pieces );
            }
            $object->isExact( (bool) $exact );
            $router = $this->getBrain()->get( 'cortex.router' );
            return $router->setFallback( $object );
        }
    }

    /**
     * Register the FallbackQueryBuilder as fallaback controller for the router.
     *
     * Is possible limit the Fallback run under two conditions: a callaback and minimum/maximum/exact
     * number of url "pieces", where a piece is a part of url between two "/".
     *
     * @param \Closure $condition
     * @param int $min_pieces
     * @param boolean $exact
     * @return void
     * @since 0.1
     */
    function useQueryFallback( $condition = NULL, $min_pieces = 0, $exact = FALSE ) {
        $router = $this->getBrain()->get( 'cortex.router' );
        $args = [ 'min_pieces' => $min_pieces, 'exact' => $exact, 'condition' => $condition ];
        return $router->setFallbackBind( 'cortex.fallback_query_builder', $args );
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

    private function addRoute( RouteInterface $route ) {
        $router = $this->getBrain()->get( 'cortex.router' );
        return $router->addRoute( $route );
    }

    private function getDynamicRedirectTo( $to, Array $defaults = [ ] ) {
        $matches = [ ];
        preg_match_all( "|\{[\w]+\}|i", $to, $matches, PREG_PATTERN_ORDER, 0 );
        if ( ! isset( $matches[0] ) || empty( $matches[0] ) ) return FALSE;
        return function( $replacements ) use($matches, $defaults, $to) {
            $keys = array_unique( str_ireplace( [ '{', '}' ], '', $matches[0] ) );
            foreach ( $keys as $key ) {
                if ( isset( $replacements[$key] ) ) {
                    $to = str_ireplace( '{' . $key . '}', $replacements[$key], $to );
                } elseif ( isset( $defaults[$key] ) ) {
                    $to = str_ireplace( '{' . $key . '}', $defaults[$key], $to );
                } else {
                    $to = str_ireplace( '{' . $key . '}', '', $to );
                }
            }
            $parsed = parse_url( $to );
            return str_replace( $parsed['path'], str_replace( '//', '/', $parsed['path'] ), $to );
        };
    }

}