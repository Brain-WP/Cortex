<?php namespace Brain\Cortex\Tests\Unit;

use Brain\Cortex\Tests\TestCase;
use Brain\Cortex\GroupContainer;

class GroupContainerTest extends TestCase {

    /**
     * @expectedException \InvalidArgumentException
     */
    function testAddGroupFailsIfNullGroup() {
        $gc = new GroupContainer();
        $gc->addGroup();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testAddGroupFailsIfBadGroup() {
        $gc = new GroupContainer();
        $gc->addGroup( TRUE );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testAddGroupFailsIfEmptyArgs() {
        $gc = new GroupContainer();
        $gc->addGroup( 'foo' );
    }

    function testAddGroup() {
        $gc = new GroupContainer();
        $gc->addGroup( 'foo', [ 'foo' => 'bar' ] );
        assertEquals( [ 'foo' => 'bar' ], $gc->getGroup( 'foo' ) );
    }

    function testMergeGroupNoGroup() {
        $route = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $route->shouldReceive( 'get' )->with( 'group' )->andReturnNull();
        $gc = new GroupContainer();
        assertEquals( $route, $gc->mergeGroup( $route ) );
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    function testMergeGroupFailsIfBadGroup() {
        $gc = \Mockery::mock( 'Brain\Cortex\GroupContainer' )->makePartial();
        $route = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $route->shouldReceive( 'get' )->with( 'group' )->andReturn( 'foo' );
        $gc->shouldReceive( 'getGroup' )->with( 'foo' )->andReturn( TRUE );
        $gc->mergeGroup( $route );
    }

    function testMergeGroup() {
        $route = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $route->shouldReceive( 'get' )->with( 'group' )->andReturn( 'foo' );
        $route->shouldReceive( 'set' )->with( 'foo', 1 )->once()->andReturnSelf();
        $route->shouldReceive( 'set' )->with( 'bar', 2 )->once()->andReturnSelf();
        $gc = \Mockery::mock( 'Brain\Cortex\GroupContainer' )->makePartial();
        $gc->shouldReceive( 'getGroup' )->with( 'foo' )->andReturn( [ 'foo' => 1, 'bar' => 2 ] );
        assertEquals( $route, $gc->mergeGroup( $route ) );
    }

}