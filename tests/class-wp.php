<?php
class WP {

    public $posts = [ ];

    public $query_vars;

    function parse_request( $extra_query_vars = '' ) {
        $this->query_vars = [ 'this' => 'Core WP parse_request()' ];
    }

    function query_posts() {
        $query = $GLOBALS['wp_the_query'];
        $query->posts = [ 'this' => 'Core WP query_posts()' ];
    }

}