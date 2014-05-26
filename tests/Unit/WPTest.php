<?php namespace Brain\Cortex\Tests\Unit;

use Brain\Cortex\Tests\TestCase;

class WPTest extends TestCase {

    private function get( $init = TRUE ) {
        $hooks = \Mockery::mock( 'Brain\Cortex\Tests\MockedHooks' )->makePartial();
        $worker = \Mockery::mock( 'Brain\Cortex\Worker' );
        $worker->shouldReceive( 'getHooks' )->withNoArgs()->andReturn( $hooks );
        if ( is_bool( $init ) ) {
            $worker->shouldReceive( 'init' )->withNoArgs()->andReturn( $init );
        }
        $wp = new \Brain\Cortex\WP( $worker );
        return $wp;
    }

    function testParseRequestNullIfDid() {
        \WP_Mock::wpFunction( 'did_action', [ 'return' => TRUE ] );
        $wp = $this->get( TRUE );
        $wp->parse_request();
        assertNull( $wp->query_vars );
    }

    function testParseRequestCoreIfWorkerNotInit() {
        \WP_Mock::wpFunction( 'did_action', [ 'return' => FALSE ] );
        $wp = $this->get( FALSE );
        $wp->parse_request();
        assertEquals( [ 'this' => 'Core WP parse_request()' ], $wp->query_vars );
    }

    function testParseRequestCoreIfWorkerNotWork() {
        \WP_Mock::wpFunction( 'did_action', [ 'return' => FALSE ] );
        $wp = $this->get();
        $worker = $wp->cortexGetWorker();
        $worker->shouldReceive( 'work' )->withNoArgs()->andReturn( FALSE );
        $wp->parse_request();
        assertEquals( [ 'this' => 'Core WP parse_request()' ], $wp->query_vars );
    }

    function testParseRequestWorkNotBuild() {
        \WP_Mock::wpFunction( 'did_action', [ 'return' => FALSE ] );
        $wp = $this->get();
        $worker = $wp->cortexGetWorker();
        $worker->shouldReceive( 'work' )->withNoArgs()->andReturn( TRUE );
        $hooks = $worker->getHooks();
        $hooks->shouldReceive( 'trigger' )->with( 'parse_request', $wp )->once()->andReturnNull();
        $wp->parse_request();
        assertEquals( [ ], $wp->query_vars );
    }

    function testParseRequestWorkBuild() {
        \WP_Mock::wpFunction( 'did_action', [ 'return' => FALSE ] );
        $wp = $this->get();
        $worker = $wp->cortexGetWorker();
        $qb = \Mockery::mock( 'Brain\Cortex\Controllers\QueryBuilderInterface' );
        $vars = [ 'query' => 'var', 'builded_by' => 'QueryBuilder' ];
        $query = \Mockery::mock( '\WP_Query' );
        $result = [ $qb, $vars, $query ];
        $worker->shouldReceive( 'work' )->withNoArgs()->andReturn( $result );
        $hooks = $worker->getHooks();
        $hooks->shouldReceive( 'trigger' )->with( 'parse_request', $wp )->once()->andReturnNull();
        $wp->parse_request();
        assertEquals( $vars, $wp->query_vars );
        assertEquals( $query, $wp->cortexGetQueryObject() );
    }

    function testQueryPostCoreIfStopWP() {
        $wp = $this->get();
        $GLOBALS['wp_the_query'] = (object) [ 'posts' => [ ] ];
        $wp->query_posts();
        assertEquals( [ 'this' => 'Core WP query_posts()' ], $GLOBALS['wp_the_query']->posts );
    }

    function testQueryPostCoreIfNoCustomQuery() {
        $wp = $this->get();
        $wp->cortexStopWP( TRUE );
        $GLOBALS['wp_the_query'] = (object) [ 'posts' => [ ] ];
        $wp->query_posts();
        assertEquals( [ 'this' => 'Core WP query_posts()' ], $GLOBALS['wp_the_query']->posts );
    }

    function testQueryPostCustom() {
        \WP_Mock::wpFunction( 'did_action', [ 'return' => function( $action ) {
            return $action !== 'parse_query';
        } ] );
        $query_vars = [ 'foo' => 'bar', 'bar' => 'baz' ];
        $post = (object) ['id' => 1 ];
        $posts = [ $post, (object) ['id' => 2 ], (object) [ 'id' => 3 ] ];
        $custom = \Mockery::mock( '\WP_Query' );
        $custom->shouldReceive( 'init' )->withNoArgs()->once()->andReturnNull();
        $custom->shouldReceive( 'get_posts' )->withNoArgs()->once()->andReturn( $posts );
        $custom->shouldReceive( 'parse_query' )->with( $query_vars )->once()->andReturnNull();
        $custom->post_count = 0;
        $custom->posts = [ ];
        $custom->post = NULL;
        $wp = $this->get();
        $wp->cortexStopWP( TRUE );
        $wp->cortexSetQueryObject( $custom );
        $wp->query_vars = $query_vars;
        $wp->query_posts();
        assertEquals( $post, $custom->post );
        assertEquals( 3, $custom->post_count );
        assertEquals( $posts, $custom->posts );
    }

}