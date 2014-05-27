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

    function testMergeGroup() {
        $route = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $route->shouldReceive( 'get' )->with( 'group' )->andReturn( 'foo' );
        $route->shouldReceive( 'set' )->with( 'foo', 1 )->once()->andReturnSelf();
        $route->shouldReceive( 'set' )->with( 'bar', 2 )->once()->andReturnSelf();
        $gc = \Mockery::mock( 'Brain\Cortex\GroupContainer' )->makePartial();
        $gc->shouldReceive( 'getGroup' )->with( 'foo' )->andReturn( [ 'foo' => 1, 'bar' => 2 ] );
        assertEquals( $route, $gc->mergeGroup( $route ) );
    }

    function testMergeGroupMultiple() {
        $route = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $route->shouldReceive( 'get' )->with( 'group' )->andReturn( [ 'foo', 'foo2' ] );
        $route->shouldReceive( 'set' )->with( 'foo', 1 )->once()->andReturnSelf();
        $route->shouldReceive( 'set' )->with( 'bar', 2 )->once()->andReturnSelf();
        $route->shouldReceive( 'set' )->with( 'foo2', 3 )->once()->andReturnSelf();
        $gc = \Mockery::mock( 'Brain\Cortex\GroupContainer' )->makePartial();
        $gc->shouldReceive( 'getGroup' )->with( 'foo' )->once()->andReturn( [ 'foo' => 1, 'bar' => 2 ] );
        $gc->shouldReceive( 'getGroup' )->with( 'foo2' )->once()->andReturn( [ 'foo2' => 3 ] );
        assertEquals( $route, $gc->mergeGroup( $route ) );
    }

    function testMergeGroupSkipDuplicateAndNonKeyed() {
        $route = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $route->shouldReceive( 'get' )->with( 'group' )->andReturn( [ 'foo', 'foo2' ] );
        $route->shouldReceive( 'set' )->with( 'foo', 1 )->once()->andReturnSelf();
        $route->shouldReceive( 'set' )->with( 'bar', 2 )->once()->andReturnSelf();
        $route->shouldReceive( 'set' )->with( 'foo2', 3 )->once()->andReturnSelf();
        $gc = \Mockery::mock( 'Brain\Cortex\GroupContainer' )->makePartial();
        $group1 = [ 'foo' => 1, 'bar' => 2, 'baz', 3 => 'hello' ];
        $group2 = [ 'foo' => 1, 'foo2' => 3, 'baz2', 4 => 'hello2' ];
        $gc->shouldReceive( 'getGroup' )->with( 'foo' )->once()->andReturn( $group1 );
        $gc->shouldReceive( 'getGroup' )->with( 'foo2' )->once()->andReturn( $group2 );
        assertEquals( $route, $gc->mergeGroup( $route ) );
    }

}