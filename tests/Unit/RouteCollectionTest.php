<?php namespace Brain\Cortex\Tests\Unit;

use Brain\Cortex\Tests\TestCase;

class RouteCollectionTest extends TestCase {

    private function get() {
        $symfony = \Mockery::mock( 'Symfony\Component\Routing\RouteCollection' );
        $collection = new \Brain\Cortex\RouteCollection( $symfony );
        return $collection;
    }

    function testInsert() {
        $collection = $this->get();
        $route1 = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $route1->shouldReceive( 'getPriority' )->withNoArgs()->atLeast( 1 )->andReturn( 20 );
        $route1->shouldReceive( 'getId' )->withNoArgs()->andReturn( 'route-1' );
        $route2 = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $route2->shouldReceive( 'getId' )->withNoArgs()->andReturn( 'route-2' );
        $route2->shouldReceive( 'getPriority' )->withNoArgs()->atLeast( 1 )->andReturn( 10 );
        $route3 = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $route3->shouldReceive( 'getId' )->withNoArgs()->andReturn( 'route-3' );
        $route3->shouldReceive( 'getPriority' )->withNoArgs()->atLeast( 1 )->andReturn( 15 );
        $ids = [ 'route-2', 'route-3', 'route-1' ];
        assertEquals( $route1, $collection->insert( $route1 ) );
        assertEquals( $route2, $collection->insert( $route2 ) );
        assertEquals( $route3, $collection->insert( $route3 ) );
        assertEquals( 3, $collection->count() );
        $i = 0;
        while ( $collection->valid() ) {
            assertEquals( $collection->current()->getId(), $ids[$i] );
            $i ++;
            $collection->next();
        }
    }

    function testGetCollection() {
        $collection = $this->get();
        $scollection = $collection->getUnderlyingCollection();
        $ids = [ 'route-2', 'route-3', 'route-1' ];
        $cbid = function( $id ) use (&$ids) {
            $now = array_shift( $ids );
            return $now === $id;
        };
        $scollection->shouldReceive( 'add' )
            ->with( \Mockery::on( $cbid ), \Mockery::type( 'Symfony\Component\Routing\Route' ) )
            ->times( 3 )
            ->andReturnNull();
        $sroute1 = \Mockery::mock( 'Symfony\Component\Routing\Route' );
        $sroute2 = \Mockery::mock( 'Symfony\Component\Routing\Route' );
        $sroute3 = \Mockery::mock( 'Symfony\Component\Routing\Route' );
        $route1 = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $route1->shouldReceive( 'getPriority' )->withNoArgs()->atLeast( 1 )->andReturn( 20 );
        $route1->shouldReceive( 'getId' )->withNoArgs()->andReturn( 'route-1' );
        $route1->shouldReceive( 'prepare' )->withNoArgs()->once()->andReturn( $sroute1 );
        $route2 = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $route2->shouldReceive( 'getId' )->withNoArgs()->andReturn( 'route-2' );
        $route2->shouldReceive( 'getPriority' )->withNoArgs()->atLeast( 1 )->andReturn( 10 );
        $route2->shouldReceive( 'prepare' )->withNoArgs()->once()->andReturn( $sroute2 );
        $route3 = \Mockery::mock( 'Brain\Cortex\RouteInterface' );
        $route3->shouldReceive( 'getId' )->withNoArgs()->andReturn( 'route-3' );
        $route3->shouldReceive( 'getPriority' )->withNoArgs()->atLeast( 1 )->andReturn( 15 );
        $route3->shouldReceive( 'prepare' )->withNoArgs()->once()->andReturn( $sroute3 );
        $collection->insert( $route1 );
        $collection->insert( $route2 );
        $collection->insert( $route3 );
        assertEquals( $scollection, $collection->getCollection() );
    }

}