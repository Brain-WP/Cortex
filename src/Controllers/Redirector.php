<?php namespace Brain\Cortex\Controllers;

use Brain\Request;
use Brain\Hooks;
use Brain\Cortex\RouteInterface;

/**
 * Redirector is a routable controller.
 *
 * Routables are controllers that run when a route match and can be defined per route.
 * This implementation is used to redirect the request to another url.
 * Redirect is done using core WordPress functions: `wp_safe_redirect` by default or
 * `wp_redirect` if 'redirectexternal' property is true fro the route.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
class Redirector extends RoutableBase {

    protected $def_status = 301;

    function __construct( Request $request, Hooks $hooks ) {
        $this->request = $request;
        $this->hooks = $hooks;
    }

    /**
     * Main controller method. After sanity check, trigger the redirect via redirect() method.
     *
     * @return bool true if redirect happen
     * @throws \DomainException
     * @access public
     */
    public function run() {
        if ( ! $this->getRoute() instanceof RouteInterface ) {
            throw new \DomainException;
        }
        if ( ! is_array( $this->getMatchedArgs() ) ) {
            throw new \DomainException;
        }
        return $this->redirect();
    }

    /**
     * Use the 'redirectto' and 'redirectstatus' route arguments to trigger the redirect using
     * core wp_redirect function
     *
     * @return bool true if redirect happen
     * @access public
     */
    public function redirect() {
        $route = $this->getRoute();
        $to = $this->getHooks()->filter(
            'cortex.redirect_to', $this->getTo(), $route, $this->getRequest()
        );
        $ext = (bool) $route->get( 'redirectexternal' );
        if ( empty( $to ) || ! filter_var( $to, FILTER_VALIDATE_URL ) ) {
            $to = home_url();
            $ext = FALSE;
        }
        $status = $this->getRoute()->get( 'redirectstatus' );
        if ( ! is_numeric( $status ) || ( (int) $status < 301 || (int) $status > 308 ) ) {
            $status = $this->def_status;
        }
        $this->getHooks()->trigger( 'cortex.pre_redirect', $to, (int) $status, $this->getRequest() );
        return $ext ? wp_redirect( $to, (int) $status ) : wp_safe_redirect( $to, (int) $status );
    }

    /**
     * Get the url where to redirect
     *
     * @return string
     */
    public function getTo() {
        $to = $this->getRoute()->get( 'redirectto' );
        $args = $this->getMatchedArgs();
        if ( is_callable( $to ) ) {
            $to = call_user_func( $to, $args, $this->getRequest() );
        }
        if ( is_string( $to ) && ( strpos( $to, 'http' ) !== 0 ) ) {
            $parsed = (array) parse_url( filter_var( $to, FILTER_SANITIZE_URL ) );
            $query = FALSE;
            if ( isset( $parsed['path'] ) ) {
                $to = $parsed['path'];
                $query = isset( $parsed['query'] ) ? $parsed['query'] : FALSE;
            }
            $to = home_url( $to );
            $to .= is_string( $query ) && ! empty( $query ) ? '?' . $query : '';
        }
        return is_string( $to ) ? filter_var( $to, FILTER_SANITIZE_URL ) : FALSE;
    }

}