<?php namespace Brain\Cortex\Controllers;

use Brain\Cortex\RequestableInterface;
use Brain\Cortex\HooksableInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher as SymfonyMatcher;
use Symfony\Component\Routing\RequestContext as SymfonyContext;
use Symfony\Component\Routing\RouteCollection as SymfonyCollection;
use Brain\Cortex\RouteCollectionInterface as Collection;
use Brain\Cortex\GroupContainerInterface as Groups;
use Brain\Cortex\RouteInterface;
use Brain\Cortex\FrontendRouteInterface;
use Brain\Cortex\Hooksable;
use Brain\Cortex\Requestable;

/**
 * Concrete implementation for RouterInterface.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
class Router implements RouterInterface, RequestableInterface, HooksableInterface {

    use Requestable,
        Hooksable;

    /**
     * Default routable
     * @var \Brain\Cortex\Controllers\RoutableInterface
     */
    private $def_routable;

    /**
     * @var \Symfony\Component\Routing\Matcher
     */
    private $matcher;

    /**
     * @var \Symfony\Component\Routing\RequestContext
     */
    private $req_context;

    /**
     * @var \Brain\Cortex\RouteCollectionInterface
     */
    private $collection;

    /**
     * Routes storage
     * @var \ArrayObject
     */
    private $routes;

    /**
     * Groups container
     * @var \Brain\Cortex\GroupContainerInterface
     */
    private $groups;

    /**
     * @var \Brain\Cortex\Controllers\FallbackController
     */
    private $fallback;

    /**
     * Container binding for fallback
     * @var string
     */
    private $fallback_bind;

    /**
     * @var array
     */
    private $priorities = [ ];

    /**
     * @var \Brain\Cortex\RouteInterface
     */
    private $matched;

    /**
     * Matched array returned by Symfony matcher
     * @var array
     */
    private $matched_args;

    /**
     * Default priority
     * @var int
     */
    private $def_priority = 10;

    public function __construct( SymfonyMatcher $matcher, SymfonyContext $req_context,
                                 Collection $collection, Groups $groups ) {
        $this->req_context = $req_context;
        $this->matcher = $matcher;
        $this->collection = $collection;
        $this->routes = new \ArrayObject;
        $this->groups = $groups;
    }

    public function getMatcher() {
        return $this->matcher;
    }

    public function getCollection() {
        return $this->collection;
    }

    public function getRequestContext() {
        return $this->req_context;
    }

    public function getRoutes() {
        return $this->routes;
    }

    public function getGroups() {
        return $this->groups;
    }

    public function getPriorities() {
        return $this->priorities;
    }

    public function getFallback() {
        return $this->fallback;
    }

    public function setFallback( FallbackController $fallback ) {
        $this->fallback = $fallback;
        return $this;
    }

    public function setFallbackBind( $bind = '', Array $args = [ ] ) {
        if ( ! is_string( $bind ) || $bind === '' ) {
            throw new \InvalidArgumentException;
        }
        $defaults = [ 'min_pieces' => 0, 'exact' => FALSE, 'condition' => NULL ];
        $args = wp_parse_args( $args, $defaults );
        $this->fallback_bind = (object) [ 'bind' => $bind, 'args' => $args ];
        return $this;
    }

    public function getFallbackBind() {
        return $this->fallback_bind;
    }

    public function getDefaultRoutable() {
        return $this->def_routable;
    }

    public function getRoutable( RouteInterface $route = NULL ) {
        if ( is_null( $route ) && $this->matched() ) $route = $this->getMatched();
        $routable = ! is_null( $route ) ? $route->getRoutable() : FALSE;
        return $routable instanceof RoutableInterface ? $routable : $this->getDefaultRoutable();
    }

    public function setRoutable( RoutableInterface $routable, RouteInterface $route = NULL ) {
        if ( ! is_null( $route ) ) {
            return $route->setRoutable( $routable );
        } else {
            $this->def_routable = $routable;
            return $this;
        }
    }

    public function run() {
        $this->getHooks()->trigger( 'cortex.pre_parse_routes' );
        $parsed = (bool) $this->parseRoutes();
        $this->getHooks()->trigger( 'cortex.after_parse_routes', $parsed );
    }

    public function addRoute( RouteInterface $route ) {
        $route = $this->getHooks()->filter( 'cortex.pre_add_rule', $this->setupRoute( $route ) );
        if ( $route instanceof FrontendRouteInterface ) {
            $this->priorities[] = (int) $route->getPriority();
            $added = $this->getCollection()->insert( $route );
            $this->getRoutes()->offsetSet( $route->getId(), $added );
        }
        return $route;
    }

    public function parseRoutes() {
        $matched = FALSE;
        $collection = $this->getCollection()->getCollection();
        $count = $collection instanceof SymfonyCollection ? $collection->count() : 0;
        if ( $count >= 1 && $this->match() ) {
            $matched = TRUE;
            $this->route();
        }
        if ( $count >= 1 && ! $matched ) {
            $this->getHooks()->trigger( 'cortex.not_matched', $this );
        }
        $this->matcher = NULL;
        $this->collection = NULL;
        $this->routes = NULL;
        $this->groups = NULL;
        return $matched;
    }

    public function matched() {
        return $this->getMatched() instanceof RouteInterface;
    }

    public function setMatched( RouteInterface $route = NULL ) {
        $this->matched = $route;
    }

    public function getMatched() {
        return $this->matched;
    }

    public function setMatchedArgs( Array $args = [ ] ) {
        $this->matched_args = $args;
    }

    public function getMatchedArgs() {
        return $this->matched_args;
    }

    private function match() {
        $routes = $this->getCollection()->getCollection();
        if ( ! $routes instanceof SymfonyCollection || $routes->count() <= 0 ) {
            return FALSE;
        }
        try {
            $this->setupContext();
            $args = $this->getMatcher()->match( $this->getRequest()->path() );
            $this->getHooks()->trigger( 'cortex.matcher_result_args', $args, $this );
            $this->setMatched( $this->getRoutes()->offsetGet( $args[ '_route' ] ) );
            unset( $args[ '_route' ] );
            $this->setMatchedArgs( $args );
            return TRUE;
        } catch ( \Exception $e ) {
            return FALSE;
        }
    }

    private function route() {
        if ( ! $this->matched() ) return;
        $matched = $this->getMatched();
        $group = $matched->get( 'group' );
        if ( ! empty( $group ) ) {
            $this->getGroups()->mergeGroup( $matched );
        }
        $request = $this->getRequest();
        $args = $this->getMatchedArgs();
        $this->getHooks()->trigger( 'cortex.matched', $matched, $args, $request );
        $routable = $this->getRoutable( $matched );
        if ( ! $matched instanceof RouteInterface || ! $routable instanceof RoutableInterface ) {
            throw new \DomainException;
        }
        $routable->setRoute( $matched );
        $routable->setMatchedArgs( $args );
    }

    public function nextPriority() {
        $priorities = $this->getPriorities();
        return ! empty( $priorities ) ? max( $priorities ) + 1 : $this->def_priority;
    }

    public function setupContext() {
        $context = $this->getRequestContext();
        $request = $this->getRequest();
        $context->setBaseUrl( rtrim( home_url(), '/\\ ' ) );
        $context->setMethod( $request->method() );
        $http_port = $request->isSecure() ? 80 : $request->port();
        $https_port = $request->isSecure() ? $request->port() : 443;
        $context->setHttpPort( $http_port );
        $context->setHttpsPort( $https_port );
        $context->setHost( $request->host() );
        $context->setQueryString( $request->server( 'QUERY_STRING' ) );
        return $context;
    }

    public function setupRoute( RouteInterface $route ) {
        if ( ! is_int( $route->getPriority() ) ) {
            $route->setPriority( (int) $this->nextPriority() );
        }
        if ( ! is_string( $route->getId() ) ) {
            $route->setId( spl_object_hash( $route ) );
        }
        return $route;
    }

}