<?php namespace Brain\Cortex;

use Brain\Cortex\Controllers\QueryBuilderInterface;

/**
 * Extends WordPress WP class, and is used to override global `$wp` object.
 * In this way, when WordPress call `parse_request` method on global `$wp` object, it is called
 * on this class, rather than in core one.
 * Let the magic happen.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 *
 */
class WP extends \WP {

    private $cortex_worker;

    private $cortex_query_object;

    private $cortex_stop_wp = FALSE;

    public function __construct( Worker $worker, Array $props = [ ] ) {
        $this->cortex_worker = $worker;
        if ( ! empty( $props ) ) {
            foreach ( $props as $prop => $value ) {
                $this->$prop = $value;
            }
        }
    }

    public function cortexToWP( $extra = '' ) {
        parent::parse_request( $extra );
    }

    public function cortexGetWorker() {
        return $this->cortex_worker;
    }

    public function cortexSetQueryObject( \WP_Query $query_object ) {
        $this->cortex_query_object = $query_object;
        return $this;
    }

    public function cortexGetQueryObject() {
        return $this->cortex_query_object;
    }

    public function cortexStopWP( $set = NULL ) {
        if ( $set === TRUE ) $this->cortex_stop_wp = TRUE;
        return $this->cortex_stop_wp;
    }

    /**
     * Method that override core one. If a plugin route is found and route callable implements
     * IQueryBuilder interface, then query class and query vars via routable.
     * If the routable is a redirector, then the request is redirect accordingly.
     * For different routable behaviour depends on the $stop_wp variable: if it set to true via
     * cortexStopWP() method than function do nothing, otherwise core parse_request is ran.
     *
     * @param array|string $extra_query_vars extra query vars core pass to method
     * @see WP::parse_request()
     * @see Brain\Cortex\WPWorker;
     */
    public function parse_request( $extra_query_vars = '' ) {
        if ( did_action( 'parse_request' ) ) return;
        if ( $this->cortexGetWorker()->init() === FALSE ) {
            $this->cortexToWP( $extra_query_vars );

            return;
        }
        $work = $this->cortexGetWorker()->work();
        $this->cortexStopWP(  ! empty( $work ) );
        if ( is_array( $work ) && isset( $work[0] ) && $work[0] instanceof QueryBuilderInterface ) {
            $this->query_vars = isset( $work[1] ) && is_array( $work[1] ) ? $work[1] : [ ];
            if ( isset( $work[2] ) && $work[2] instanceof \WP_Query ) {
                $this->cortexSetQueryObject( $work[2] );
            }
        }
        if ( $this->cortexStopWP() !== TRUE ) {
            $this->cortexToWP( $extra_query_vars );
        } else {
            if ( is_null( $this->query_vars ) ) {
                $this->query_vars = [ ];
            }
            $this->cortexGetWorker()->getHooks()->trigger( 'parse_request', $this );
        }
    }

    /**
     * Override core WP::query_posts() and allow a custom WP_Query class to be used for main query
     */
    function query_posts() {
        if ( $this->cortexStopWP() === TRUE && $this->cortexGetQueryObject() instanceof \WP_Query ) {
            global $wp_the_query, $wp_query;
            $wp_the_query = $this->cortexGetQueryObject();
            $wp_the_query->init();
            $wp_the_query->query = $wp_the_query->query_vars = $this->query_vars;
            $posts = $wp_the_query->get_posts();
            $count = count( $posts );
            // ensure custom query class set $posts and post_count
            if ( $count !== $wp_the_query->post_count || empty( $wp_the_query->post ) ) {
                $wp_the_query->posts = $posts;
                $wp_the_query->post = reset( $posts );
                $wp_the_query->post_count = $count;
            }
            $wp_query = $wp_the_query;
            // ensure parse_query is called to rely on conditional tags
            if ( did_action( 'parse_query' ) ) return;
            // filters are nonsense at this point: core call parse_query() before getting posts
            if ( isset( $GLOBALS['wp_filter']['parse_query'] ) ) {
                unset( $GLOBALS['wp_filter']['parse_query'] );
            }
            if ( isset( $GLOBALS['wp_filter']['parse_tax_query'] ) ) {
                unset( $GLOBALS['wp_filter']['parse_tax_query'] );
            }
            $wp_query->parse_query( $wp_the_query->query );
        } else {
            parent::query_posts();
        }
    }

}