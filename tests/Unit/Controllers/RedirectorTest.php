<?php namespace Brain\Cortex\Tests\Unit\Controllers;

use Brain\Cortex\Tests\TestCase;
use Brain\Cortex\Tests\MockedRequest;
use Brain\Cortex\Tests\MockedHooks;

class RedirectorTest extends TestCase {

    private function get() {
        $request = new MockedRequest;
        $hooks = new MockedHooks;
        $ctrl = \Mockery::mock( 'Brain\Cortex\Controllers\Redirector' )->makePartial();
        $ctrl->shouldReceive( 'getRequest' )->withNoArgs()->andReturn( $request );
        $ctrl->shouldReceive( 'getHooks' )->withNoArgs()->andReturn( $hooks );
        return $ctrl;
    }

    /**
     * @expectedException \DomainException
     */
    function testRunFailsIfBadRoute() {
        $this->get()->run();
    }

    /**
     * @expectedException \DomainException
     */
    function testRunFailsIfBadArgs() {
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getRoute' )->withNoArgs()->andReturn( $route );
        $ctrl->run();
    }

    function testRunReturnRedirect() {
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getRoute' )->withNoArgs()->andReturn( $route );
        $ctrl->shouldReceive( 'getMatchedArgs' )->withNoArgs()->andReturn( [ ] );
        $ctrl->shouldReceive( 'redirect' )->withNoArgs()->once()->andReturn( 'Redirect!' );
        assertEquals( 'Redirect!', $ctrl->run() );
    }

    function testRedirectFullUrlSafe() {
        \WP_Mock::wpFunction( 'wp_safe_redirect', [ 'return' => function( $to, $status ) {
            return $to === 'http://www.example.com/foo' && $status === 301;
        } ] );
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'get' )->with( 'redirectstatus' )->andReturn( '301' );
        $route->shouldReceive( 'get' )->with( 'redirectexternal' )->andReturn( FALSE );
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getRoute' )->withNoArgs()->andReturn( $route );
        $ctrl->shouldReceive( 'getMatchedArgs' )->withNoArgs()->andReturn( [ ] );
        $ctrl->shouldReceive( 'getTo' )->withNoArgs()->andReturn( 'http://www.example.com/foo' );
        assertTrue( $ctrl->run() );
    }

    function testRedirectFullUrlExternal() {
        \WP_Mock::wpFunction( 'wp_redirect', [ 'return' => function( $to, $status ) {
            return $to === 'http://www.example.com/foo' && $status === 301;
        } ] );
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'get' )->with( 'redirectstatus' )->andReturn( '301' );
        $route->shouldReceive( 'get' )->with( 'redirectexternal' )->andReturn( TRUE );
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getRoute' )->withNoArgs()->andReturn( $route );
        $ctrl->shouldReceive( 'getMatchedArgs' )->withNoArgs()->andReturn( [ ] );
        $ctrl->shouldReceive( 'getTo' )->withNoArgs()->andReturn( 'http://www.example.com/foo' );
        assertTrue( $ctrl->run() );
    }

    function testRedirectHomeUrlandStatusByDefault() {
        \WP_Mock::wpFunction( 'wp_safe_redirect', [ 'return' => function( $to, $status ) {
            return $to === 'http://www.example.com/' && $status === 301;
        } ] );
        \WP_Mock::wpFunction( 'home_url', [ 'return' => 'http://www.example.com/' ] );
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'get' )->with( 'redirectstatus' )->andReturn( '1004' );
        $route->shouldReceive( 'get' )->with( 'redirectexternal' )->andReturn( TRUE );
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getRoute' )->withNoArgs()->andReturn( $route );
        $ctrl->shouldReceive( 'getMatchedArgs' )->withNoArgs()->andReturn( [ ] );
        $ctrl->shouldReceive( 'getTo' )->withNoArgs()->andReturn( 'not a valid url' );
        assertTrue( $ctrl->run() );
    }

    function testGetToFullUrlFromRoute() {
        $url = 'http://www.example.com/';
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'get' )->with( 'redirectto' )->andReturn( $url );
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getRoute' )->withNoArgs()->andReturn( $route );
        $ctrl->shouldReceive( 'getMatchedArgs' )->withNoArgs()->andReturn( [ ] );
        assertEquals( $url, $ctrl->getTo() );
    }

    function testGetToFullUrlFromRouteArgs() {
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'get' )->with( 'redirectto' )->andReturn( function( $args ) {
            return 'http://www.example.com/' . $args['bar'];
        } );
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getRoute' )->withNoArgs()->andReturn( $route );
        $ctrl->shouldReceive( 'getMatchedArgs' )->withNoArgs()->andReturn( [ 'bar' => 'baz' ] );
        assertEquals( 'http://www.example.com/baz', $ctrl->getTo() );
    }

    function testGetToFullUrlFromRouteCallback() {
        $cb = function( Array $args, \Brain\Request $request ) {
            return 'http://www.example.com/' . $args['foo'] . '/' . $request->query( 'foo' );
        };
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'get' )->with( 'redirectto' )->andReturn( $cb );
        $ctrl = $this->get();
        $request = $ctrl->getRequest();
        $request->mock( 'query', 'foo', 'bar' );
        $ctrl->shouldReceive( 'getRoute' )->withNoArgs()->andReturn( $route );
        $ctrl->shouldReceive( 'getMatchedArgs' )->withNoArgs()->andReturn( [ 'foo' => 'bar' ] );
        assertEquals( 'http://www.example.com/bar/bar', $ctrl->getTo() );
    }

    function testGetToRelative() {
        \WP_Mock::wpFunction( 'home_url', [ 'return' => function( $relative ) {
            return 'http://www.example.com' . $relative;
        } ] );
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'get' )->with( 'redirectto' )->andReturn( '/foo?foo=bar' );
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getRoute' )->withNoArgs()->andReturn( $route );
        $ctrl->shouldReceive( 'getMatchedArgs' )->withNoArgs()->andReturn( [ ] );
        assertEquals( 'http://www.example.com/foo?foo=bar', $ctrl->getTo() );
    }

    function testGetToRelativeFromPath() {
        \WP_Mock::wpFunction( 'home_url', [ 'return' => function( $relative ) {
            return 'http://www.example.com' . $relative;
        } ] );
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $ftp = 'ftp://user:password@www.boo.com:80/foo';
        $route->shouldReceive( 'get' )->with( 'redirectto' )->andReturn( $ftp );
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getRoute' )->withNoArgs()->andReturn( $route );
        $ctrl->shouldReceive( 'getMatchedArgs' )->withNoArgs()->andReturn( [ ] );
        assertEquals( 'http://www.example.com/foo', $ctrl->getTo() );
    }

}