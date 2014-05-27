<?php namespace Brain\Cortex\Tests\Functional;

use Brain\Cortex\Tests\TestCaseFunctional;
use Brain\Cortex\API;

class APITest extends TestCaseFunctional {

    function testAdd() {
        $api = new API;
        $routes = \Brain\Container::instance()->get( 'cortex.routes' );
        assertInstanceOf( 'Brain\Cortex\Route', $api->add( '/' ) );
        assertInstanceOf( 'Brain\Cortex\Route', $api->add( '/foo' ) );
        assertEquals( 2, count( $routes ) );
    }

    function testAddFluent() {
        $api = new API;
        $query = function( $matches ) {
            return [
                'category_name' => $matches['foo'],
                'paged'         => (int) $matches['bar']
            ];
        };
        $before = function() {
            do_action( 'foo_bar_baz' );
        };
        $closure = function() {
            echo 'Code == Poetry, Code === Code';
        };
        $route = $api->add( '/{foo}/{bar}', 'foo_bar_baz', 10 )
            ->defaults( [ 'foo' => 'bar', 'bar' => 1 ] )
            ->requirements( [ 'foo' => '[a-z]{3}', 'bar' => 'd+' ] )
            ->query( $query )
            ->template( 'foo.php' )
            ->before( $before )
            ->bindToClosure( $closure );
        $routes = \Brain\Container::instance()->get( 'cortex.routes' );
        assertInstanceOf( 'Brain\Cortex\Route', $route );
        assertEquals( 1, count( $routes ) );
        assertEquals( 'foo_bar_baz', $route->getId() );
        assertEquals( 10, $route->getPriority() );
        assertEquals( [ 'foo' => 'bar', 'bar' => 1 ], $route->getDefaults() );
        assertEquals( [ 'foo' => '[a-z]{3}', 'bar' => 'd+' ], $route->getRequirements() );
        assertEquals( $query, $route->getQueryCallback() );
        assertEquals( 'foo.php', $route->getTemplate() );
        assertEquals( $before, $route->getBefore() );
        assertEquals( 'cortex.closure_routable', $route->getBinding() );
    }

    function testCreate() {
        $api = new API;
        $api->create( '/{foo}/bar' )->id( 'route1' )->priority( 20 )
            ->defaults( [ 'foo' => 'route1' ] )->add();
        $api->create( '/{foo}/baz' )->id( 'route2' )->priority( 10 )
            ->defaults( [ 'foo' => 'route2' ] )->add();
        $api->create( '/{foo}' )->id( 'route3' )->priority( 35 )
            ->defaults( [ 'foo' => 'route3' ] )->add();
        $api->create( '/foo/{foo}' )->id( 'route4' )->priority( 3 )
            ->defaults( [ 'foo' => 'route4' ] )->add();
        $ids = [ 'route4', 'route2', 'route1', 'route3' ];
        $routes = \Brain\Container::instance()->get( 'cortex.routes' );
        assertEquals( 4, count( $routes ) );
        while ( $routes->valid() ) {
            isset( $i ) || $i = 0;
            assertEquals( $ids[$i], $routes->current()->getId() );
            $i ++;
            $routes->next();
        }
    }

    function testCreateRedirect() {
        $api = new API;
        $route = $api->createRedirect( '/foo', '/foo/bar/baz', 301, [ ], TRUE );
        assertEquals( [ 'GET' ], $route->getMethods() );
        assertEquals( 'cortex.redirector', $route->getBinding() );
        assertEquals( '/foo/bar/baz', $route->getRedirectTo() );
    }

    function testCreateRedirectFluent() {
        $api = new API;
        $route = $api->createRedirect()
            ->path( '/foo' )
            ->redirectTo( '/foo/{bar}/baz' )
            ->redirectStatus( 308 );
        assertEquals( '/foo/{bar}/baz', $route->getRedirectTo() );
        assertEquals( 308, $route->getRedirectStatus() );
    }

    function testRedirect() {
        $api = new API;
        $route1 = $api->redirect()->path( '/foo' )->redirectTo( '/foo/{bar}/baz' );
        $route2 = $api->redirect()->path( '/foo/bar' )->redirectTo( '/foo/baz/baz' );
        $routes = \Brain\Container::instance()->get( 'cortex.routes' );
        assertEquals( '/foo/{bar}/baz', $route1->getRedirectTo() );
        assertEquals( '/foo/baz/baz', $route2->getRedirectTo() );
        assertEquals( 2, count( $routes ) );
    }

    function testGroup() {
        $api = new API;
        $api->group( 'a', [ 'template' => 'foo.php' ] );
        $route = \Brain\Container::instance()->get( 'cortex.router' )
            ->getGroups()
            ->mergeGroup( $api->add( '/' )->group( 'a' ) );
        assertEquals( 'foo.php', $route->getTemplate() );
    }

}