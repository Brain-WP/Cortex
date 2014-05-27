<?php namespace Brain\Cortex\Tests\Unit;

use Brain\Cortex\Tests\TestCase;

class RouteTest extends TestCase {

    private function get() {
        $inner = \Mockery::mock( 'Symfony\Component\Routing\Route' );
        $route = \Mockery::mock( 'Brain\Cortex\Route' )->makePartial();
        $route->shouldReceive( 'getInner' )->withNoArgs()->andReturn( $inner );
        $route->shouldReceive( 'getId' )->withNoArgs()->andReturn( 'route_id' );
        $route->set();
        return $route;
    }

    function testAfterNullIfNotCallbable() {
        $route = $this->get();
        $route->set( 'after', 'foo' );
        assertNull( $route->runAfter() );
    }

    function testAfter() {
        $route = $this->get();
        $route->set( 'after', function() {
            return 'After!';
        } );
        assertEquals( 'After!', $route->runAfter() );
    }

    function testBefore() {
        $route = $this->get();
        $route->set( 'before', function() {
            return 'Before!';
        } );
        assertEquals( 'Before!', $route->runBefore() );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testBindToFailsIfBadBind() {
        $route = $this->get();
        $route->bindTo( 1 );
    }

    function testBindTo() {
        $route = $this->get();
        $route->bindTo( 'foo' );
        assertEquals( 'foo', $route->getBinding() );
    }

    function testBindToClosure() {
        $route = $this->get();
        $closure = function() {
            return 'Binded To Closure!';
        };
        $route->bindToClosure( $closure );
        assertEquals( 'cortex.closure_routable', $route->getBinding() );
        assertEquals( 'Binded To Closure!', call_user_func( $route->get( 'binded_closure' ) ) );
    }

    function testBindToActionRoutable() {
        $route = $this->get();
        $routable = \Mockery::mock( 'Brain\Cortex\Controllers\ActionRoutable' );
        $route->bindToAction( $routable, 'foo' );
        assertEquals( $routable, $route->getRoutable() );
        assertEquals( 'foo', $route->get( 'action_routable_id' ) );
    }

    function testBindToActionBind() {
        $route = $this->get();
        $route->bindToAction( 'bind_id', 'foo' );
        assertEquals( 'bind_id', $route->getBinding() );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testBindToMethodFailsIfBadMethod() {
        $route = $this->get();
        $route->bindToMethod( NULL, TRUE );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testBindToMethodFailsIfBadClass() {
        $route = $this->get();
        $route->bindToMethod( 'foo', 'foo' );
    }

    function testBindToMethodObject() {
        $route = $this->get();
        $stub = new \Brain\Cortex\Tests\ActionRoutableStub;
        $route->bindToMethod( $stub, 'foo' );
        $call = call_user_func( $route->get( 'binded_closure' ), NULL, NULL, NULL );
        assertEquals( 'cortex.closure_routable', $route->getBinding() );
        assertEquals( 'Foo!', $call );
    }

    function testBindToMethodString() {
        $route = $this->get();
        $route->bindToMethod( '\Brain\Cortex\Tests\ActionRoutableStub', 'foo' );
        $call = call_user_func( $route->get( 'binded_closure' ), NULL, NULL, NULL );
        assertEquals( 'cortex.closure_routable', $route->getBinding() );
        assertEquals( 'Foo!', $call );
    }

    function testBindToMethodStatic() {
        $route = $this->get();
        $route->bindToMethod( '\Brain\Cortex\Tests\ActionRoutableStub', 'foo_static', TRUE );
        $call = call_user_func( $route->get( 'binded_closure' ), NULL, NULL, NULL );
        assertEquals( 'cortex.closure_routable', $route->getBinding() );
        assertEquals( 'Foo Static!', $call );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testPathFailsIfBadPath() {
        $route = $this->get();
        $route->setPath( '' );
    }

    function testPathSanitizeUrl() {
        $route = $this->get();
        $route->setPath( 'http : \\ www . example . it' );
        assertEquals( 'http:\\www.example.it', $route->getPath() );
    }

    function testPathAndRequirements() {
        $route = $this->get();
        $route->setPath( 'http:\\www.example.it', [ 'foo' => 'bar' ] );
        assertEquals( [ 'foo' => 'bar' ], $route->get( 'requirements' ) );
    }

    function testSetRequirements() {
        $route = $this->get();
        $route->setRequirements( [ 'foo' => 'bar', 'baz', 2 => 'two' ] );
        assertEquals( [ 'foo' => 'bar' ], $route->get( 'requirements' ) );
    }

    function testPrepareFalseIfNotCheck() {
        $route = $this->get();
        assertFalse( $route->prepare() );
    }

    function testPrepare() {
        $route = $this->get();
        $route->shouldReceive( 'getPath' )->withNoArgs()->andReturn( '/' );
        $route->shouldReceive( 'getRequirements' )->withNoArgs()->andReturn( [ 'req' => 'foo' ] );
        $route->shouldReceive( 'getDefaults' )->withNoArgs()->andReturn( [ 'def' => 'bar' ] );
        $route->shouldReceive( 'getHost' )->withNoArgs()->andReturn( 'example.com' );
        $route->shouldReceive( 'getSchemes' )->withNoArgs()->andReturn( [ 'http' ] );
        $route->shouldReceive( 'getMethods' )->withNoArgs()->andReturn( [ 'GET' ] );
        $inner = $route->getInner();
        $inner->shouldReceive( 'setPath' )->with( '/' )->once()->andReturnNull();
        $inner->shouldReceive( 'setRequirements' )->with( [ 'req' => 'foo' ] )->once()->andReturnNull();
        $inner->shouldReceive( 'setDefaults' )->with( [ 'def' => 'bar' ] )->once()->andReturnNull();
        $inner->shouldReceive( 'setHost' )->with( 'example.com' )->once()->andReturnNull();
        $inner->shouldReceive( 'setSchemes' )->with( [ 'http' ] )->once()->andReturnNull();
        $inner->shouldReceive( 'setMethods' )->with( [ 'GET' ] )->once()->andReturnNull();
        assertEquals( $inner, $route->prepare() );
    }

    function testClonePaged() {
        \WP_Mock::wpFunction( 'trailingslashit', [ 'return' => function( $string ) {
            $string = rtrim( $string, '/\\ ' );
            return $string . '/';
        } ] );
        $GLOBALS['wp_rewrite'] = (object) [ 'pagination_base' => 'page' ];
        $inner = \Mockery::mock( 'Symfony\Component\Routing\Route' );
        $router = \Mockery::mock( '\Brain\Cortex\Controllers\Router' );
        $router->shouldReceive( 'addRoute' )->andReturnUsing( function( $cloned ) {
            return $cloned;
        } );
        $route = new \Brain\Cortex\Route( $inner );
        $route->setRouter( $router );
        $route->setId( 'route_id' );
        $route->setPath( '/' );
        $paged = $route->clonePaged();
        assertEquals( 'route_id-paged', $paged->getId() );
        assertEquals( '/page/{paged}', $paged->getPath() );
        assertEquals( [ 'paged' => 1 ], $paged->getDefaults() );
        assertEquals( [ 'paged' => '[0-9]+' ], $paged->getRequirements() );
    }

}