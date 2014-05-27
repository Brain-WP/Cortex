<?php namespace Brain\Cortex\Tests\Functional;

use Brain\Cortex\Tests\TestCaseFunctional;
use Brain\Cortex\API;

class MatchingTest extends TestCaseFunctional {

    public function doSomething() {
        return 'Something';
    }

    public static function doSomethingStatic() {
        return 'Something Static';
    }

    private function routes() {
        $api = new API;

        $api->add( '/foo', '1' );

        $api->add( '/{foo}/bar', '2' )->requirements( [ 'foo' => '[a-z]{5,}' ] );

        $api->add( '/foo/bar', '3' )->bindToMethod( $this, 'doSomething' );

        $api->add( '/{foo}/bar-{bar}/{baz}', '4' )
            ->requirements( [ 'foo' => '[a-z]{5,}', 'bar' => '[0-9]+', 'baz' => '[a-z]+' ] )
            ->defaults( [ 'type' => 'product' ] )
            ->query( function( $matches ) {
                return [
                    'post_type'     => $matches['type'],
                    'paged'         => $matches['bar'],
                    $matches['foo'] => $matches['baz'],
                    'tax_query'     => [
                        'taxonomy' => 'foo',
                        'terms'    => [ 'a', 'b', $matches['foo'] ]
                    ],
                    'meta_query'    => [
                        'key' => $matches['baz']
                    ]
                ];
            } );

        $api->add( '/hello/{foo}/{bar}', '5' )
            ->requirements( [ 'foo' => '[a-z]{3,}', 'bar' => '[a-z]{3,}-[0-9]{2}' ] );

        $api->add( '/hello/{foo}/{bar}', '6' )
            ->methods( [ 'POST' ] )
            ->bindToClosure( function( $matches ) {
                return 'Foo is ' . $matches['foo'] . ', Bar is ' . $matches['bar'];
            } );

        $api->add( '/goodbye/{foo}/{bar}', '7' )
            ->requirements( [ 'foo' => '[a-z]+', 'bar' => '[a-z]+\.html' ] );

        $api->add( '/goodbye/{foo}/{bar}', '8' )
            ->requirements( [ 'foo' => '[a-z]+', 'bar' => '[\w]+\.php' ] )
            ->bindToMethod( __CLASS__, 'doSomethingStatic', TRUE );

        $api->add( '/', '9' )->defaults( [ 'home' => 'home' ] );

        $api->createRedirect( '/{catch}', '/new/home/{foo}' )
            ->requirements( [ 'catch' => 'old|redir|redirectme' ] )
            ->defaults( [ 'foo' => 'home.html' ] )
            ->setId( '10' )
            ->add();
    }

    private function launchTest( $request ) {
        $this->routes();
        $router = \Brain\Container::instance()->get( 'cortex.router' );
        $router->run();
        $module = new \Brain\Cortex\BrainModule;
        if ( $router->matched() ) {
            $module->bindRoute( $router->getMatched() );
            $router->getRoutable()->setRoute( $router->getMatched() );
            $router->getRoutable()->setMatchedArgs( $router->getMatchedArgs() );
        } else {
            $module->bindFallback( $router );
            if ( $router->getFallback() instanceof Fallback ) {
                $fallback = $router->getFallback();
                $fallback->setRequest( $request );
            }
        }
        return $router;
    }

    function testMatch_1() {
        $request = new \Brain\Request;
        $request->simulate( '/foo' );
        $router = $this->launchTest( $request );
        assertInstanceOf( '\Brain\Cortex\Controllers\RouterInterface', $router );
        assertTrue( $router->matched() );
        assertEquals( '1', $router->getMatched()->getId() );
        assertInstanceOf( '\Brain\Cortex\Controllers\QueryBuilder', $router->getRoutable() );
        assertEquals( [ ], $router->getMatchedArgs() );
    }

    function testMatch_2() {
        $request = new \Brain\Request;
        $request->simulate( '/fooooooooooooooooo/bar' );
        $router = $this->launchTest( $request );
        assertInstanceOf( '\Brain\Cortex\Controllers\RouterInterface', $router );
        assertTrue( $router->matched() );
        assertEquals( '2', $router->getMatched()->getId() );
        assertInstanceOf( '\Brain\Cortex\Controllers\QueryBuilder', $router->getRoutable() );
        assertEquals( [ 'foo' => 'fooooooooooooooooo' ], $router->getMatchedArgs() );
    }

    function testMatch_3() {
        $request = new \Brain\Request;
        $request->simulate( '/foo/bar' );
        $router = $this->launchTest( $request );
        assertInstanceOf( '\Brain\Cortex\Controllers\RouterInterface', $router );
        assertTrue( $router->matched() );
        assertEquals( '3', $router->getMatched()->getId() );
        assertInstanceOf( '\Brain\Cortex\Controllers\ClosureRoutable', $router->getRoutable() );
        assertEquals( [ ], $router->getMatchedArgs() );
        assertEquals( 'Something', $router->getRoutable()->run() );
    }

    function testMatch_4() {
        $request = new \Brain\Request;
        $request->simulate( '/foooo/bar-3/hello' );
        $router = $this->launchTest( $request );
        $args = [ 'foo' => 'foooo', 'bar' => '3', 'baz' => 'hello', 'type' => 'product' ];
        $query = [
            'post_type'  => 'product',
            'paged'      => '3',
            'foooo'      => 'hello',
            'tax_query'  => [
                'taxonomy' => 'foo',
                'terms'    => [ 'a', 'b', 'foooo' ]
            ],
            'meta_query' => [
                'key' => 'hello'
            ]
        ];
        assertInstanceOf( '\Brain\Cortex\Controllers\RouterInterface', $router );
        assertTrue( $router->matched() );
        assertEquals( '4', $router->getMatched()->getId() );
        assertInstanceOf( '\Brain\Cortex\Controllers\QueryBuilder', $router->getRoutable() );
        assertEquals( $args, $router->getMatchedArgs() );
        assertTrue( $router->getRoutable()->run() );
        assertEquals( $query, $router->getRoutable()->getQueryArgs() );
    }

    function testMatch_5() {
        $request = new \Brain\Request;
        $request->simulate( '/hello/aaaaaaaaaaaaaaaaaaa/prefix-22' );
        $router = $this->launchTest( $request );
        assertTrue( $router->matched() );
        assertEquals( '5', $router->getMatched()->getId() );
    }

    function testMatch_6() {
        $request = new \Brain\Request;
        $request->simulate( '/hello/foo/bar', [ ], [ 'fooooo' => 'barrrr' ] );
        $router = $this->launchTest( $request );
        assertTrue( $router->matched() );
        assertEquals( '6', $router->getMatched()->getId() );
        assertInstanceOf( '\Brain\Cortex\Controllers\ClosureRoutable', $router->getRoutable() );
        assertEquals( 'Foo is foo, Bar is bar', $router->getRoutable()->run() );
    }

    function testMatch_7() {
        $request = new \Brain\Request;
        $request->simulate( '/goodbye/file/home.html' );
        $router = $this->launchTest( $request );
        assertTrue( $router->matched() );
        assertEquals( '7', $router->getMatched()->getId() );
        assertEquals( [ 'foo' => 'file', 'bar' => 'home.html' ], $router->getMatchedArgs() );
    }

    function testMatch_8() {
        $request = new \Brain\Request;
        $request->simulate( '/goodbye/file/home.php' );
        $router = $this->launchTest( $request );
        assertTrue( $router->matched() );
        assertEquals( '8', $router->getMatched()->getId() );
        assertEquals( [ 'foo' => 'file', 'bar' => 'home.php' ], $router->getMatchedArgs() );
        assertInstanceOf( '\Brain\Cortex\Controllers\ClosureRoutable', $router->getRoutable() );
        assertEquals( 'Something Static', $router->getRoutable()->run() );
    }

    function testMatch_9() {
        $request = new \Brain\Request;
        $request->simulate( '/' );
        $router = $this->launchTest( $request );
        assertTrue( $router->matched() );
        assertEquals( '9', $router->getMatched()->getId() );
        assertEquals( [ 'home' => 'home' ], $router->getMatchedArgs() );
    }

    function testMatch_10() {
        $request = new \Brain\Request;
        $request->simulate( '/redir' );
        $router = $this->launchTest( $request );
        assertTrue( $router->matched() );
        assertEquals( '10', $router->getMatched()->getId() );
        assertEquals( [ 'catch' => 'redir', 'foo' => 'home.html' ], $router->getMatchedArgs() );
        assertInstanceOf( '\Brain\Cortex\Controllers\Redirector', $router->getRoutable() );
        assertEquals( 'http://www.example.com/new/home/home.html', $router->getRoutable()->getTo() );
    }

}