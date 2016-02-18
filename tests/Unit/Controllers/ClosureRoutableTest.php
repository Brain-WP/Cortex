<?php namespace Brain\Cortex\Tests\Unit\Controllers;

use Brain\Cortex\Tests\TestCase;
use Brain\Cortex\Tests\MockedHooks as Hooks;
use Brain\Request;

class ClosureRoutableTest extends TestCase {

    /**
     * @expectedException \DomainException
     */
    function testRunFailsIfBadRoute() {
        $ctrl = new \Brain\Cortex\Controllers\ClosureRoutable( new Request, new Hooks );
        $ctrl->run();
    }

    function testRun() {
        $route = \Mockery::mock( '\Brain\Cortex\Route' );
        $cb = function( $args, $_route, $request ) use( $route ) {
            if ( $args === [ 'foo' => 1 ] && $route === $_route && $request instanceof Request ) {
                return 'Success!';
            }
        };
        $route->shouldReceive( 'get' )->atLeast( 1 )->with( 'bound_closure' )->andReturn( $cb );
        $ctrl = \Mockery::mock( '\Brain\Cortex\Controllers\ClosureRoutable' )->makePartial();
        $ctrl->shouldReceive( 'getRoute' )->atLeast( 1 )->withNoArgs()->andReturn( $route );
        $args = [ 'foo' => 1 ];
        $ctrl->shouldReceive( 'getMatchedArgs' )->atLeast( 1 )->withNoArgs()->andReturn( $args );
        $ctrl->shouldReceive( 'getRequest' )->atLeast( 1 )->withNoArgs()->andReturn( new Request );
        assertEquals( 'Success!', $ctrl->run() );
    }

}