<?php namespace Brain\Cortex\Tests\Functional;

use Brain\Cortex\Tests\TestCaseFunctional;

/**
 * @runTestsInSeparateProcesses
 */
class BrainModuleTest extends TestCaseFunctional {

    function testWP() {
        assertInstanceOf( '\Brain\Cortex\WP', $GLOBALS[ 'wp' ] );
    }

    function testBindRoute() {
        $route = \Brain\Container::instance()->get( 'cortex.route' );
        $route->bindTo( 'cortex.redirector' );
        $module = new \Brain\Cortex\BrainModule;
        $module->bindRoute( $route );
        assertInstanceOf( '\Brain\Cortex\Controllers\Redirector', $route->getRoutable() );
    }

    function testBindFallback() {
        $router = \Brain\Container::instance()->get( 'cortex.router' );
        $condtion = function( $request ) {
            return $request->method() === 'POST';
        };
        $args = ['min_pieces' => 3, 'exact' => TRUE, 'condition' => $condtion ];
        $router->setFallbackBind( 'cortex.fallback_query_builder', $args );
        $module = new \Brain\Cortex\BrainModule;
        $module->bindFallback( $router );
        $fallback = $router->getFallback();
        assertInstanceOf( '\Brain\Cortex\Controllers\FallbackQueryBuilder', $fallback );
        assertEquals( 3, $fallback->getMinPieces() );
        assertTrue( $fallback->isExact() );
        assertEquals( $condtion, $fallback->getCondition() );
    }

}