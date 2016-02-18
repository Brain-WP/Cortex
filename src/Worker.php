<?php namespace Brain\Cortex;

use Brain\Hooks;
use Brain\Request;
use Brain\Cortex\Controllers\RouterInterface;
use Brain\Cortex\Controllers\ControllerInterface as Controller;
use Brain\Cortex\Controllers\FallbackController as Fallback;
use Brain\Cortex\Controllers\QueryBuilderInterface as Builder;
use Brain\Cortex\Controllers\Redirector;

/**
 * Used by WP class to init, check and run matched route routable or fallback.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
class Worker implements HooksableInterface, RequestableInterface {

    use Hooksable,
        Requestable;

    /**
     * @var \Brain\Hooks
     */
    private $hooks;

    /**
     * @var \Brain\Request
     */
    private $request;

    /**
     * Matched route
     * @var \Brain\Cortex\RouteInterface
     */
    private $matched;

    /**
     * Matched route routable or fallback
     * @var \Brain\Cortex\Controllers\ControllerInterface
     */
    private $controller;

    /**
     * Matched arguments returned by Symfony roting
     * @var array
     */
    private $args = [ ];

    private $status;

    function __construct( RouterInterface $router, Request $request, Hooks $hooks ) {
        $this->router = $router;
        $this->request = $request;
        $this->hooks = $hooks;
    }

    /**
     * Get the router instance
     *
     * @return \Brain\Cortex\Controllers\RouterInterface
     */
    function getRouter() {
        return $this->router;
    }

    /**
     * Get matched route instance
     *
     * @return \Brain\Cortex\RouteInterface
     */
    function getMatched() {
        return $this->matched;
    }

    /**
     * Get current controller instance that can be the matched route routable or a fallback
     *
     * @return\ Brain\Cortex\Controllers\ControllerInterface
     */
    function getController() {
        return $this->controller;
    }

    /**
     * Get matched arguments returned by Symfony roting
     *
     * @return array
     */
    function getMatchedArgs() {
        return $this->args;
    }

    /**
     * Return current worker status: 'matched' when a route match 'fallback' if not and a fallback
     * is set
     *
     * @return string
     */
    function getStatus() {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return \Brain\Cortex\Worker
     * @throws \InvalidArgumentException
     */
    function setStatus( $status = '' ) {
        if ( ! in_array( $status, [ 'matched', 'fallback' ], TRUE ) ) {
            throw new \InvalidArgumentException;
        }
        $this->status = $status;
        return $this;
    }

    /**
     * Launch routes parsing using router and set class status according with results.
     *
     * @return \Brain\Cortex\Worker|boolean
     */
    function init() {
        $router = $this->getRouter();
        $router->run();
        $args = [ ];
        $matched = NULL;
        if ( $router->matched() ) {
            $this->controller = $router->getRoutable();
            $matched = $router->getMatched();
            $args = $router->getMatchedArgs();
        } elseif ( $router->getFallback() instanceof Fallback ) {
            $fallback = $router->getFallback();
            $fallback->setRequest( $this->getRequest() );
            if ( ! $fallback->should() ) {
                return FALSE;
            }
            $this->controller = $fallback;
        } else {
            return FALSE;
        }
        $this->args = $args;
        $status = 'fallback';
        if ( $matched instanceof RouteInterface ) {
            $this->matched = $matched;
            $status = 'matched';
        }
        $this->setStatus( $status );
        return $this;
    }

    /**
     * Check the status then run saved controller if any. Controller is the matched route routable
     * or the registered fallback controller for the router.
     *
     * @return mixed
     */
    function work() {
        if ( ! in_array( $this->getStatus(), [ 'matched', 'fallback' ], TRUE ) ) return '';
        $controller = $this->getController();
        $args = $this->getMatchedArgs();
        if ( $this->getStatus() === 'matched' ) {
            $route = $this->getMatched();
            $controller->setRoute( $route );
            $controller->setMatchedArgs( $args );
            $route->runBefore( $args, $route );
        }
        $this->getHooks()->trigger( 'cortex.pre_controller_run', $controller, $args );
        $result = $controller->run();
        $this->getHooks()->trigger( 'cortex.after_controller_run', $controller, $args );
        if ( isset($route) ) {
            $this->getMatched()->runAfter( $args, $route );
        }
        return $this->maybeQuery( $controller, $result );
    }

    /**
     * When routable is a query builder build a result array, to be used by WP class, when routable
     * is a redirector exit.
     * In all other cases just return what controller run() method returned.
     *
     * @param \Brain\Cortex\Controllers\ControllerInterface $controller
     * @param mixed $result
     * @return \Brain\Cortex\query_class
     */
    function maybeQuery( Controller $controller, $result ) {
        if ( $controller instanceof Builder && $controller->getQueryArgs() ) {
            $result = [ $controller, $controller->getQueryArgs() ];
            $query_class = trim( $controller->getQueryClass(), '\\' );
            if ( $query_class !== 'WP_Query' && class_exists( $query_class ) ) {
                $result[] = new $query_class;
            }
            remove_filter( 'template_redirect', 'redirect_canonical' );
        } elseif ( $controller instanceof Redirector ) {
            return $this->doneAndExit();
        }
        return $result;
    }

    function doneAndExit() {
        $this->getHooks()->trigger( "cortex.exit_redirect" );
        exit();
    }

}