<?php namespace Brain\Cortex\Tests\Unit;

use Brain\Cortex\Tests\TestCase;
use Brain\Cortex\Worker;
use Brain\Cortex\Tests;

class WorkerTest extends TestCase {

    function testInitFalse() {
        $router = \Mockery::mock( 'Brain\Cortex\Controllers\Router' );
        $router->shouldReceive( 'run' )->once()->withNoArgs()->andReturnNull();
        $router->shouldReceive( 'matched' )->once()->withNoArgs()->andReturnNull();
        $router->shouldReceive( 'getFallback' )->once()->withNoArgs()->andReturnNull();
        $worker = new Worker( $router, new Tests\MockedRequest, new Tests\MockedHooks );
        assertFalse( $worker->init() );
    }

    function testInitFallbackShould() {
        $request = new Tests\MockedRequest;
        $fallback = \Mockery::mock( 'Brain\Cortex\Controllers\FallbackController' );
        $fallback->shouldReceive( 'setRequest' )->with( $request )->andReturnNull();
        $fallback->shouldReceive( 'should' )->withNoArgs()->atLeast( 1 )->andReturn( TRUE );
        $router = \Mockery::mock( 'Brain\Cortex\Controllers\Router' );
        $router->shouldReceive( 'run' )->once()->withNoArgs()->andReturnNull();
        $router->shouldReceive( 'matched' )->atLeast( 1 )->withNoArgs()->andReturnNull();
        $router->shouldReceive( 'getFallback' )->atLeast( 1 )->withNoArgs()->andReturn( $fallback );
        $worker = new Worker( $router, $request, new Tests\MockedHooks );
        assertEquals( $worker, $worker->init() );
        assertEquals( 'fallback', $worker->getStatus() );
        assertEquals( $fallback, $worker->getController() );
    }

    function testInitFallbackShouldNot() {
        $request = new Tests\MockedRequest;
        $fallback = \Mockery::mock( 'Brain\Cortex\Controllers\FallbackController' );
        $fallback->shouldReceive( 'setRequest' )->with( $request )->andReturnNull();
        $fallback->shouldReceive( 'should' )->withNoArgs()->atLeast( 1 )->andReturn( FALSE );
        $router = \Mockery::mock( 'Brain\Cortex\Controllers\Router' );
        $router->shouldReceive( 'run' )->once()->withNoArgs()->andReturnNull();
        $router->shouldReceive( 'matched' )->atLeast( 1 )->withNoArgs()->andReturnNull();
        $router->shouldReceive( 'getFallback' )->atLeast( 1 )->withNoArgs()->andReturn( $fallback );
        $worker = new Worker( $router, $request, new Tests\MockedHooks );
        assertFalse( $worker->init() );
        assertNull( $worker->getStatus() );
        assertNull( $worker->getController() );
    }

    function testInitMatched() {
        $routable = \Mockery::mock( 'Brain\Cortex\Controllers\RoutableInterface' );
        $route = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $router = \Mockery::mock( 'Brain\Cortex\Controllers\Router' );
        $router->shouldReceive( 'run' )->once()->withNoArgs()->andReturnNull();
        $router->shouldReceive( 'matched' )->atLeast( 1 )->withNoArgs()->andReturn( TRUE );
        $router->shouldReceive( 'getRoutable' )->atLeast( 1 )->withNoArgs()->andReturn( $routable );
        $router->shouldReceive( 'getMatched' )->atLeast( 1 )->withNoArgs()->andReturn( $route );
        $args = [ 'foo' => 'foo', 'bar' => 'bar' ];
        $router->shouldReceive( 'getMatchedArgs' )->atLeast( 1 )->withNoArgs()->andReturn( $args );
        $worker = new Worker( $router, new Tests\MockedRequest, new Tests\MockedHooks );
        assertEquals( $worker, $worker->init() );
        assertEquals( 'matched', $worker->getStatus() );
        assertEquals( $routable, $worker->getController() );
        assertEquals( $route, $worker->getMatched() );
        assertEquals( $args, $worker->getMatchedArgs() );
    }

    function testWorkNullIfWrongStatus() {
        $router = \Mockery::mock( 'Brain\Cortex\Controllers\Router' );
        $worker = new Worker( $router, new Tests\MockedRequest, new Tests\MockedHooks );
        assertNull( $worker->work() );
    }

    function testWorkFallback() {
        $fallback = \Mockery::mock( 'Brain\Cortex\Controllers\FallbackController' );
        $fallback->shouldReceive( 'run' )->once()->withNoArgs()->andReturn( 'Fallback!' );
        $hooks = \Mockery::mock( 'Brain\Cortex\Tests\MockedHooks' )->makePartial();
        $hooks->shouldReceive( 'trigger' )
            ->with( 'cortex.pre_contoller_run', $fallback, [ ] )
            ->once()
            ->andReturnNull();
        $hooks->shouldReceive( 'trigger' )
            ->with( 'cortex.after_controller_run', $fallback, [ ] )
            ->once()
            ->andReturnNull();
        $worker = \Mockery::mock( 'Brain\Cortex\Worker' )->makePartial();
        $worker->shouldReceive( 'getHooks' )->andReturn( $hooks );
        $worker->shouldReceive( 'getStatus' )->atLeast( 1 )->andReturn( 'fallback' );
        $worker->shouldReceive( 'getController' )->atLeast( 1 )->andReturn( $fallback );
        $worker->shouldReceive( 'maybeQuery' )
            ->with( $fallback, 'Fallback!' )
            ->once()
            ->andReturn( 'Fallback!' );
        assertEquals( 'Fallback!', $worker->work() );
    }

    function testWorkMatched() {
        $args = [ 'foo' => 'bar' ];
        $route = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $route->shouldReceive( 'runBefore' )->with( $args )->once()->andReturnNull();
        $route->shouldReceive( 'runAfter' )->with( $args )->once()->andReturnNull();
        $routable = \Mockery::mock( 'Brain\Cortex\Controllers\RoutableInterface' );
        $routable->shouldReceive( 'run' )->once()->withNoArgs()->andReturn( 'Routable!' );
        $routable->shouldReceive( 'setRoute' )->with( $route )->once()->andReturnNull();
        $routable->shouldReceive( 'setMatchedArgs' )->with( $args )->once()->andReturnNull();
        $hooks = \Mockery::mock( 'Brain\Cortex\Tests\MockedHooks' )->makePartial();
        $hooks->shouldReceive( 'trigger' )
            ->with( 'cortex.pre_contoller_run', $routable, $args )
            ->once()
            ->andReturnNull();
        $hooks->shouldReceive( 'trigger' )
            ->with( 'cortex.after_controller_run', $routable, $args )
            ->once()
            ->andReturnNull();
        $worker = \Mockery::mock( 'Brain\Cortex\Worker' )->makePartial();
        $worker->shouldReceive( 'getHooks' )->andReturn( $hooks );
        $worker->shouldReceive( 'getStatus' )->atLeast( 1 )->andReturn( 'matched' );
        $worker->shouldReceive( 'getController' )->atLeast( 1 )->andReturn( $routable );
        $worker->shouldReceive( 'getMatchedArgs' )->atLeast( 1 )->andReturn( $args );
        $worker->shouldReceive( 'getMatched' )->atLeast( 1 )->andReturn( $route );
        $worker->shouldReceive( 'maybeQuery' )
            ->with( $routable, 'Routable!' )
            ->once()
            ->andReturn( 'Routable!' );
        assertEquals( 'Routable!', $worker->work() );
    }

    function testMaybeQueryGeneric() {
        $controller = \Mockery::mock( 'Brain\Cortex\Controllers\RoutableInterface' );
        $router = \Mockery::mock( 'Brain\Cortex\Controllers\Router' );
        $worker = new Worker( $router, new Tests\MockedRequest, new Tests\MockedHooks );
        assertEquals( 'foo', $worker->maybeQuery( $controller, 'foo' ) );
    }

    function testMaybeQueryRedirector() {
        $controller = \Mockery::mock( 'Brain\Cortex\Controllers\Redirector' );
        $worker = \Mockery::mock( 'Brain\Cortex\Worker' )->makePartial();
        $worker->shouldReceive( 'doneAndExit' )->withNoArgs()->once()->andReturn( 'Exit!' );
        assertEquals( 'Exit!', $worker->maybeQuery( $controller, 'foo' ) );
    }

    function testMaybeQueryQuery() {
        \WP_Mock::wpFunction( 'remove_filter' );
        $args = [ 'foo' => 'bar' ];
        $controller = \Mockery::mock( 'Brain\Cortex\Controllers\QueryBuilder' );
        $controller->shouldReceive( 'getQueryArgs' )->withNoArgs()->atLeast( 1 )->andReturn( $args );
        $controller->shouldReceive( 'getQueryClass' )
            ->withNoArgs()
            ->atLeast( 1 )
            ->andReturn( '\Brain\Cortex\Tests\ActionRoutableStub' );
        $worker = \Mockery::mock( 'Brain\Cortex\Worker' )->makePartial();
        $expected = [ $controller, $args, new \Brain\Cortex\Tests\ActionRoutableStub ];
        assertEquals( $expected, $worker->maybeQuery( $controller, 'foo' ) );
    }

}