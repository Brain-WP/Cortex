<?php namespace Brain\Cortex\Controllers;

use Brain\Cortex\QueryVarsFilter as Filter;
use Brain\Cortex\TemplateLoader as Loader;
use Brain\Request;
use Brain\Hooks;
use Brain\Cortex\FrontendRouteInterface;

/**
 * QueryBuilder is the default routable controller.
 *
 * Routables are controllers that run when a route match and can be defined per route.
 * When a route does not define a routable, this class is used.
 * QueryBuilder use arguments returnd by routing system to build a complete query arguments array.
 * Moreover, if the matched route has a 'template' param, it will be loaded using TemplateLoader class.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
class QueryBuilder extends RoutableBase implements QueryBuilderInterface {

    private $filter;

    private $template_loader;

    protected $query_args = [ ];

    function __construct( Filter $filter, Loader $template_loader, Request $request, Hooks $hooks ) {
        $this->filter = $filter;
        $this->template_loader = $template_loader;
        $this->request = $request;
        $this->hooks = $hooks;
    }

    /**
     * Main controller method. After instance sanity check, use the 'query' route param and the
     * matched route argumets to build a complete query arguments array.
     * Run template() method to setup a template if related argument is provided by matched route.
     *
     * @return boolean If non empty query args are built return TRUE, FALSE otherwise
     * @throws \DomainException
     * @access public
     */
    public function run() {
        $route = $this->getRoute();
        $matches = $this->getMatchedArgs();
        if ( ! $route instanceof FrontendRouteInterface ) {
            throw new \DomainException;
        }
        if ( ! is_array( $matches ) ) {
            throw new \DomainException;
        }
        return $this->build( $route, $matches );
    }

    public function getQueryArgs() {
        return $this->getHooks()->filter( 'cortex.wp_query_vars', $this->query_args, $this );
    }

    public function getQueryClass() {
        $class = $this->getRoute()->get( 'queryclass' );
        return $this->getHooks()->filter(
                'cortex.query_class', $class, $this->getRoute(), $this->getRequest()
        );
    }

    /**
     * Return QueryVarsFilter instance
     *
     * @return \Brain\Cortex\QueryVarsFilter
     */
    public function getFilter() {
        return $this->filter;
    }

    /**
     * Return template loader instance
     *
     * @return \Brain\Cortex\TemplateLoader
     */
    public function getTemplateLoader() {
        return $this->template_loader;
    }

    /**
     * Given the array from Symfony routing component, depending on route setting, merge query vars,
     * filter out unwanted vars or take only the wanted.
     *
     * @param array $vars Array from route matched via Symfony routing component
     * @return array
     */
    public function buildQueryVars( Array $vars = [ ] ) {
        $route = $this->getRoute();
        $query_vars = $this->runQueryCallback( $route, $vars );
        $args = [ ];
        foreach ( [ 'qsmerge', 'autocustomvars', 'customvars', 'skipvars' ] as $key ) {
            if ( ! is_null( $route->get( $key ) ) ) $args[$key] = $route->get( $key );
        }
        return $this->getFilter()->filter( $query_vars, $args );
    }

    private function build( FrontendRouteInterface $route, Array $matches = [ ] ) {
        $query_vars = $this->buildQueryVars( $matches );
        if ( ! empty( $query_vars ) && is_array( $query_vars ) ) {
            $this->getHooks()->trigger( 'cortex.query_vars', $query_vars );
            $this->query_args = $query_vars;
            $template = $this->getHooks()->filter(
                'cortex.route_template', $route->get( 'template' ), $route
            );
            if ( is_string( $template ) && ! empty( $template ) ) {
                $unfiltered = (bool) $route->get( 'template_unfiltered' );
                $this->getTemplateLoader()->load( $template, $unfiltered );
            }
            return TRUE;
        }
    }

    private function runQueryCallback( FrontendRouteInterface $route, Array $matches = [ ] ) {
        $query_cb = $route->get( 'querycallback' );
        if ( is_callable( $query_cb ) ) {
            $result = call_user_func( $query_cb, $matches );
            if ( is_array( $result ) ) {
                $matches = $result;
            }
        }
        return $matches;
    }

}