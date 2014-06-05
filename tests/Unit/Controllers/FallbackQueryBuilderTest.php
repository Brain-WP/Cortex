<?php namespace Brain\Cortex\Tests\Unit\Controllers;

use Brain\Cortex\Tests\TestCase;

class FallbackQueryBuilderTest extends TestCase {

    private function get( $args = [ ], $template = NULL ) {
        $filter = \Mockery::mock( 'Brain\Cortex\QueryVarsFilter' );
        $filter->shouldReceive( 'filter' )
            ->with( \Mockery::type( 'array' ), \Mockery::type( 'array' ) )
            ->andReturnUsing( function( $vars ) {
                return $vars;
            } );
        $ctrl = \Mockery::mock( 'Brain\Cortex\Controllers\FallbackQueryBuilder' )->makePartial();
        $ctrl->shouldReceive( 'getFilter' )->withNoArgs()->andReturn( $filter );
        $request = \Mockery::mock( 'Brain\Request' );
        $request->shouldReceive( 'getRequest->getRaw' )->andReturn( [ ] );
        $ctrl->shouldReceive( 'getRequest' )->andReturn( $request );
        $hooks = new \Brain\Cortex\Tests\MockedHooks;
        if ( ! is_null( $template ) ) {
            $hooks->mock( 'cortex.fallback_template', $template );
        }
        $ctrl->shouldReceive( 'getHooks' )->andReturn( $hooks );
        $ctrl->shouldReceive( 'buildQueryVars' )->with( [ ] )->andReturn( $args );
        return $ctrl;
    }

    function testRunFalse() {
        $ctrl = $this->get();
        assertFalse( $ctrl->run() );
    }

    function testRun() {
        $ctrl = $this->get( [ 'foo' => 'bar' ] );
        assertTrue( $ctrl->run() );
        assertEquals( [ 'foo' => 'bar' ], $ctrl->getQueryArgs() );
    }

    function testRunTemplate() {
        $ctrl = $this->get( [ 'foo' => 'bar' ], 'foo' );
        $loader = \Mockery::mock( 'Brain\Cortex\TemplateLoader' );
        $loader->shouldReceive( 'load' )->with( 'foo', FALSE )->once()->andReturnNull();
        $ctrl->shouldReceive( 'getTemplateLoader' )->withNoArgs()->andReturn( $loader );
        assertTrue( $ctrl->run() );
        assertEquals( [ 'foo' => 'bar' ], $ctrl->getQueryArgs() );
    }

}