<?php namespace Brain\Cortex\Tests\Unit\Controllers;

use Brain\Cortex\Tests\TestCase;
use Brain\Cortex\Tests\MockedRequest;
use Brain\Cortex\Tests\MockedHooks;

class RouterTest extends TestCase {

    private function get( $path = NULL ) {
        $request = new MockedRequest;
        if ( is_string( $path ) ) {
            $request->path = $path;
        }
        $hooks = new MockedHooks;
        $router = \Mockery::mock( 'Brain\Cortex\Controllers\Router' )->makePartial();
        $router->shouldReceive( 'getRequest' )->withNoArgs()->andReturn( $request );
        $router->shouldReceive( 'getHooks' )->withNoArgs()->andReturn( $hooks );
        return $router;
    }

    function testGetRoutableNoRoute() {
        $router = $this->get();
        $router->shouldReceive( 'matched' )->withNoArgs()->andReturn( FALSE );
        $router->shouldReceive( 'getDefaultRoutable' )->withNoArgs()->andReturn( 'Default' );
        assertEquals( 'Default', $router->getRoutable() );
    }

    function testGetRoutableNoRouteMatched() {
        $routable = \Mockery::mock( 'Brain\Cortex\Controllers\RoutableInterface' );
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'getRoutable' )->withNoArgs()->andReturn( $routable );
        $router = $this->get();
        $router->shouldReceive( 'matched' )->withNoArgs()->andReturn( TRUE );
        $router->shouldReceive( 'getMatched' )->withNoArgs()->andReturn( $route );
        assertEquals( $routable, $router->getRoutable() );
    }

    function testGetRoutableNoRouteMatchedNoRoutable() {
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'getRoutable' )->withNoArgs()->andReturnNull();
        $router = $this->get();
        $router->shouldReceive( 'matched' )->withNoArgs()->andReturn( TRUE );
        $router->shouldReceive( 'getMatched' )->withNoArgs()->andReturn( $route );
        $router->shouldReceive( 'getDefaultRoutable' )->withNoArgs()->andReturn( 'Default' );
        assertEquals( 'Default', $router->getRoutable() );
    }

    function testGetRoutableRouteNoRoutable() {
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'getRoutable' )->withNoArgs()->andReturnNull();
        $router = $this->get();
        $router->shouldReceive( 'getDefaultRoutable' )->withNoArgs()->andReturn( 'Default' );
        assertEquals( 'Default', $router->getRoutable( $route ) );
    }

    function testGetRoutableRouteRoutable() {
        $routable = \Mockery::mock( 'Brain\Cortex\Controllers\RoutableInterface' );
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'getRoutable' )->withNoArgs()->andReturn( $routable );
        $router = $this->get();
        assertEquals( $routable, $router->getRoutable( $route ) );
    }

    function testSetRoutableDefault() {
        $routable = \Mockery::mock( 'Brain\Cortex\Controllers\RoutableInterface' );
        $router = $this->get();
        $result = $router->setRoutable( $routable );
        assertEquals( $routable, $router->getDefaultRoutable() );
        assertEquals( $router, $result );
    }

    function testSetRoutableOnRoute() {
        $routable = \Mockery::mock( 'Brain\Cortex\Controllers\RoutableInterface' );
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'setRoutable' )->with( $routable )->once()->andReturnSelf();
        $router = $this->get();
        $result = $router->setRoutable( $routable, $route );
        assertEquals( $route, $result );
    }

    function testAddRouteNoFrontend() {
        $route = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $router = $this->get();
        $router->shouldReceive( 'setupRoute' )->with( $route )->andReturn( $route );
        assertEquals( $route, $router->addRoute( $route ) );
    }

    function testAddRoute() {
        $route = \Mockery::mock( 'Brain\Cortex\FrontendRouteInterface' );
        $route->shouldReceive( 'getPriority' )->andReturn( 1 );
        $route->shouldReceive( 'getId' )->andReturn( 'added_route_id' );
        $collection = \Mockery::mock( 'Brain\Cortex\RouteCollectionInterface' );
        $collection->shouldReceive( 'insert' )->with( $route )->once()->andReturn( $route );
        $routes = new \ArrayObject;
        $router = $this->get();
        $router->shouldReceive( 'setupRoute' )->with( $route )->andReturn( $route );
        $router->shouldReceive( 'getCollection' )->withNoArgs()->andReturn( $collection );
        $router->shouldReceive( 'getRoutes' )->withNoArgs()->andReturn( $routes );
        assertEquals( $route, $router->addRoute( $route ) );
        assertEquals( $route, $routes[ 'added_route_id' ] );
    }

    function testParseRoutesEmpty() {
        $router = $this->get( '/foo/bar' );
        $s_collection = \Mockery::mock( 'Symfony\Component\Routing\RouteCollection' );
        $s_collection->shouldReceive( 'count' )->withNoArgs()->andReturn( 0 );
        $collection = \Mockery::mock( 'Brain\Cortex\RouteCollection' );
        $collection->shouldReceive( 'getCollection' )->withNoArgs()->andReturn( $s_collection );
        $router->shouldReceive( 'getCollection' )->withNoArgs()->andReturn( $collection );
        assertFalse( $router->parseRoutes() );
    }

    function testParseRoutesNoValidRoutes() {
        $collection = \Mockery::mock( 'Brain\Cortex\RouteCollection' );
        $collection->shouldReceive( 'getCollection' )->withNoArgs()->andReturn( FALSE );
        $router = $this->get();
        $router->shouldReceive( 'getCollection' )->withNoArgs()->andReturn( $collection );
        assertFalse( $router->parseRoutes() );
    }

    function testParseRoutes() {
        $route1 = \Mockery::mock( 'Brain\Cortex\FrontendRouteInterface' );
        $route1->shouldReceive( 'getId' )->andReturn( 'route_1' );
        $route1->shouldReceive( 'get' )->with( 'group' )->andReturn( NULL );
        $route2 = \Mockery::mock( 'Brain\Cortex\FrontendRouteInterface' );
        $route2->shouldReceive( 'getId' )->andReturn( 'route_2' );
        $s_collection = \Mockery::mock( 'Symfony\Component\Routing\RouteCollection' );
        $s_collection->shouldReceive( 'count' )->withNoArgs()->andReturn( 2 );
        $collection = \Mockery::mock( 'Brain\Cortex\RouteCollection' );
        $collection->shouldReceive( 'getCollection' )->withNoArgs()->andReturn( $s_collection );
        $routes = new \ArrayObject;
        $routes[ 'route_1' ] = $route1;
        $routes[ 'route_2' ] = $route2;
        $context = \Mockery::mock( 'Symfony\Component\Routing\RequestContext' );
        $s_matcher = \Mockery::mock( 'Symfony\Component\Routing\Matcher' );
        $s_matcher->shouldReceive( 'match' )
            ->with( '/foo/bar' )
            ->once()
            ->andReturn( [ '_route' => 'route_1', 'foo' => 'bar' ] );
        $routable = \Mockery::mock( 'Brain\Cortex\Controllers\RoutableInterface' );
        $routable->shouldReceive( 'setRoute' )->with( $route1 )->once()->andReturnSelf();
        $routable->shouldReceive( 'setMatchedArgs' )->with( [ 'foo' => 'bar' ] )->once()->andReturnSelf();
        $router = $this->get( '/foo/bar' );
        $router->shouldReceive( 'getRoutes' )->withNoArgs()->andReturn( $routes );
        $router->shouldReceive( 'getMatcher' )->andReturn( $s_matcher );
        $router->shouldReceive( 'setupContext' )->withNoArgs()->andReturn( $context );
        $router->shouldReceive( 'getCollection' )->withNoArgs()->andReturn( $collection );
        $router->shouldReceive( 'getRoutable' )
            ->with( $route1 )
            ->atLeast( 1 )
            ->andReturn( $routable );
        assertTrue( $router->parseRoutes() );
        assertTrue( $router->matched() );
        assertEquals( 'route_1', $router->getMatched()->getId() );
        assertEquals( [ 'foo' => 'bar' ], $router->getMatchedArgs() );
    }

}