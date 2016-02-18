<?php namespace Brain\Cortex;

use Brain\Container as Brain;
use Brain\Hooks;
use Brain\Module;
use Brain\Request;
use Symfony\Component\Routing as Symfony;

/**
 * Brain module. See http://giuseppe-mazzapica.github.io/Brain/
 *
 * @see Brain\Module
 * @see Brain\Container
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
class BrainModule implements Module {

    static $booted = FALSE;

    public function boot( Brain $brain ) {
        if ( ! is_admin() && ! self::$booted ) {
            add_action( 'brain_loaded', function( $brain ) {
                $this->bootFrontend( $brain );
            } );
        }
    }

    public function getBindings( Brain $brain ) {

        $brain[ 'cortex.template_loader' ] = function( ) {
            return new TemplateLoader( new Hooks );
        };

        $brain[ 'cortex.groups' ] = function() {
            return new GroupContainer;
        };

        $brain[ 'cortex.queryvars_filter' ] = function() {
            return new QueryVarsFilter( new Request );
        };

        $brain[ 'cortex.query_builder' ] = function( $c ) {
            return new Controllers\QueryBuilder(
                $c[ 'cortex.queryvars_filter' ], $c[ 'cortex.template_loader' ], new Request, new Hooks
            );
        };

        $brain[ 'cortex.fallback_query_builder' ] = function( $c ) {
            return new Controllers\FallbackQueryBuilder(
                $c[ 'cortex.queryvars_filter' ], $c[ 'cortex.template_loader' ], new Hooks
            );
        };

        $brain[ 'cortex.redirector' ] = function() {
            return new Controllers\Redirector( new Request, new Hooks );
        };

        $brain[ 'cortex.closure_routable' ] = function() {
            return new Controllers\ClosureRoutable( new Request, new Hooks );
        };

        $brain[ 'symfony.route' ] = $brain->factory( function() {
            return new Symfony\Route( '/' );
        } );

        $brain[ 'symfony.routes' ] = function() {
            return new Symfony\RouteCollection;
        };

        $brain[ 'symfony.context' ] = function() {
            return new Symfony\RequestContext;
        };

        $brain[ 'symfony.matcher' ] = function( $c ) {
            return new Symfony\Matcher\UrlMatcher( $c[ "symfony.routes" ], $c[ "symfony.context" ] );
        };

        $brain[ 'symfony.generator' ] = function( $c ) {
            return new Symfony\Generator\UrlGenerator( $c[ "symfony.routes" ], $c[ "symfony.context" ] );
        };

        $brain[ 'cortex.route' ] = $brain->factory( function( $c ) {
            return new Route( $c[ "symfony.route" ] );
        } );

        $brain[ 'cortex.routes' ] = function( $c ) {
            return new RouteCollection( $c[ 'symfony.routes' ] );
        };

        $brain[ 'cortex.router' ] = function( $b ) {
            $s = 'symfony.';
            $c = 'cortex.';
            $r = new Controllers\Router(
                $b[ "{$s}matcher" ], $b[ "{$s}context" ], $b[ "{$c}routes" ], $b[ "{$c}groups" ]
            );
            return $r->setRequest( new Request )
                    ->setHooks( new Hooks )
                    ->setRoutable( $b[ "{$c}query_builder" ] );
        };

        $brain[ 'cortex.worker' ] = function( $c ) {
            return new Worker( $c[ 'cortex.router' ], new Request, new Hooks );
        };

        $brain[ 'cortex.wp' ] = function( $c ) {
            global $wp;
            if ( is_object( $wp ) && ( get_class( $wp ) === 'WP' ) ) {
                return new WP( $c[ 'cortex.worker' ], get_object_vars( $wp ) );
            }

            return $wp;
        };

        $brain[ 'cortex.api' ] = function() {
            return new API;
        };
    }

    public function getPath() {
        return dirname( dirname( __FILE__ ) );
    }

    /**
     * Get Cortex\WP (extends core WP class) from container. Use it to override global $wp object.
     * In this way, when WordPress call parse_request method on global $wp object, the function
     * is called on plugin WP class, instead of core one.
     * Plugin will parse the added routes and if one match, query vars are set accordingly.
     * Otherwise plugin call parse_request on core WP class making request transparent to WordPress.
     * Also load API file.
     *
     * @param \Brain\Container $brain
     * @return null
     */
    public function bootFrontend( Brain $brain ) {
        if ( self::$booted ) {
            return;
        }
        self::$booted = TRUE;
        $wp = $brain->get( 'cortex.wp' );
        if ( $wp instanceof \WP ) {
            $GLOBALS[ 'wp' ] = $wp;
        }
        Hooks::addAction(
            'cortex.route_bind', 'cortex.matched', [ $this, 'bindRoute' ], 10, 1, 1
        );
        Hooks::addAction(
            'cortex.fallback_bind', 'cortex.not_matched', [ $this, 'bindFallback' ], 10, 1, 1
        );
    }

    public function bindRoute( RouteInterface $route ) {
        $bind = $route->getBinding();
        if (
            empty( $bind )
            || $route->getRoutable() instanceof RoutableInterface
            || ! is_string( $bind )
        ) {
            return;
        }
        $routable = Brain::instance()->get( $bind );
        if ( $routable instanceof Controllers\RoutableInterface ) {
            $route->setRoutable( $routable );
        }
    }

    public function bindFallback( Controllers\Router $router ) {
        $bind = $router->getFallbackBind();
        if ( ! is_object( $bind ) || ! isset( $bind->bind ) || ! is_string( $bind->bind ) ) {
            return;
        }
        $fallback = ! empty( $bind->bind ) ? Brain::instance()->get( $bind->bind ) : FALSE;
        if ( $fallback instanceof Controllers\FallbackController ) {
            $defaults = [ 'min_pieces' => 0, 'exact' => FALSE, 'condition' => NULL ];
            $bound_args = isset( $bind->args ) && is_array( $bind->args ) ? $bind->args : [ ];
            $args = wp_parse_args( $bound_args, $defaults );
            $fallback->setMinPieces( (int) $args[ 'min_pieces' ] );
            $fallback->isExact( (bool) $args[ 'exact' ] );
            if ( is_callable( $args[ 'condition' ] ) ) {
                $fallback->setCondition( $args[ 'condition' ] );
            }
            $router->setFallback( $fallback );
        }
    }

}