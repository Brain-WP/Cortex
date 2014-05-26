<?php namespace Brain\Cortex\Tests\Unit\Controllers;

use Brain\Cortex\Tests\TestCase;

class ActionRoutableTest extends TestCase {

    private function get() {
        return \Mockery::mock( 'Brain\Cortex\Tests\ActionRoutableStub' )->makePartial();
    }

    /**
     * @expectedException \DomainException
     */
    function testRunFailsIfBadRoute() {
        $ctrl = $this->get();
        $ctrl->run();
    }

    /**
     * @expectedException \DomainException
     */
    function testRunFailsIfBadArgs() {
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'get' )->with( 'action_routable_id' )->andReturnNull();
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getRoute' )->atLeast( 1 )->withNoArgs()->andReturn( $route );
        $ctrl->shouldReceive( 'getMatchedArgs' )->atLeast( 1 )->withNoArgs()->andReturn( TRUE );
        $ctrl->run();
    }

    function testRun() {
        $route = \Mockery::mock( 'Brain\Cortex\Route' );
        $route->shouldReceive( 'get' )->with( 'action_routable_id' )->andReturnNull();
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getRoute' )->atLeast( 1 )->withNoArgs()->andReturn( $route );
        $ctrl->shouldReceive( 'getActionVar' )->atLeast( 1 )->withNoArgs()->andReturn( 'foo' );
        $args = [ 'foo' => 'foo' ];
        $ctrl->shouldReceive( 'getMatchedArgs' )->atLeast( 1 )->withNoArgs()->andReturn( $args );
        // method foo defined in Brain\Cortex\Tests\ActionRoutableStub class
        assertEquals( 'Foo!', $ctrl->run() );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testSetActionVarFailsIfBadVar() {
        $ctrl = $this->get();
        $ctrl->setActionVar( TRUE );
    }

    function testSetActionVar() {
        $ctrl = $this->get();
        $return = $ctrl->setActionVar( 'foo' );
        assertEquals( 'foo', $ctrl->getActionVar() );
        assertEquals( $ctrl, $return );
    }

}