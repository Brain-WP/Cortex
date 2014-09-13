<?php namespace Brain\Cortex\Tests\Functional;

use Brain\Cortex\Tests\TestCaseFunctional;

/**
 * @runTestsInSeparateProcesses
 */
class InitTest extends TestCaseFunctional {

    function testBindRoute() {
        $hook = \Brain\Hooks::getHook( 'cortex.matched', 'cortex.route_bind' );
        assertTrue( is_array( $hook[ 'callback' ] ) );
        assertInstanceOf( 'Brain\Cortex\BrainModule', $hook[ 'callback' ][ 0 ] );
        assertEquals( 'bindRoute', $hook[ 'callback' ][ 1 ] );
    }

    function testBindFallback() {
        $hook = \Brain\Hooks::getHook( 'cortex.not_matched', 'cortex.fallback_bind' );
        assertTrue( is_array( $hook[ 'callback' ] ) );
        assertInstanceOf( 'Brain\Cortex\BrainModule', $hook[ 'callback' ][ 0 ] );
        assertEquals( 'bindFallback', $hook[ 'callback' ][ 1 ] );
    }

    function testApiReady() {
        $api = \Brain\Container::instance()->get( 'cortex.api' );
        assertInstanceOf( 'Brain\Cortex\API', $api );
    }

    function testRouterReady() {
        $router = \Brain\Container::instance()->get( 'cortex.router' );
        assertInstanceOf( 'Brain\Cortex\Controllers\Router', $router );
    }

    function testSimulateRequest() {
        $request = new \Brain\Request;
        $request->simulate( '/foo', [ 'foo' => 'bar' ], [ 'bar' => 'baz' ] );
        $req_data = \Brain\Container::instance()
            ->get( 'cortex.router' )
            ->getRequest()
            ->getRequest()
            ->getRaw();
        assertEquals( [ 'foo' => 'bar', 'bar' => 'baz' ], $req_data );
    }

}