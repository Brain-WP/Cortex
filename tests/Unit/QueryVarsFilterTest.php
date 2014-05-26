<?php namespace Brain\Cortex\Tests\Unit;

use Brain\Cortex\Tests\TestCase;

class QueryVarsFilterTest extends TestCase {

    private function get( $query = [ ] ) {
        \WP_Mock::wpFunction( 'wp_parse_args', [ 'return' => function( $args, $default ) {
            return array_merge( (array) $default, (array) $args );
        } ] );
        $request = new \Brain\Cortex\Tests\MockedRequest;
        $request->mock( 'query', '__all', $query );
        $filter = \Mockery::mock( 'Brain\Cortex\QueryVarsFilter' )->makePartial();
        $filter->shouldReceive( 'getRequest' )->withNoArgs()->andReturn( $request );
        return $filter;
    }

    function testFilterDefault() {
        $vars = [ 'pagename' => 'foo' ];
        $filter = $this->get( [ 'foo' => 'bar', 'bar' => 'baz' ] );
        $result = $filter->filter( $vars );
        assertEquals( [ 'pagename' => 'foo', 'foo' => 'bar', 'bar' => 'baz' ], $result );
    }

    function testFilterNoQsMerge() {
        $vars = [ 'pagename' => 'foo' ];
        $filter = $this->get( [ 'foo' => 'bar', 'bar' => 'baz' ] );
        $result = $filter->filter( $vars, [ 'qsmerge' => FALSE ] );
        assertEquals( [ 'pagename' => 'foo' ], $result );
    }

    function testFilterNoAutoVars() {
        $vars = [ 'pagename' => 'foo' ];
        $filter = $this->get( [ 'foo' => 'bar', 'bar' => 'baz' ] );
        $result = $filter->filter( $vars, [ 'autocustomvars' => FALSE ] );
        assertEquals( [ 'pagename' => 'foo' ], $result );
    }

    function testFilterUseVars() {
        $vars = [ 'pagename' => 'foo', 2 => 'meh' ];
        $filter = $this->get( [ 'foo' => 'bar', 'bar' => 'baz' ] );
        $result = $filter->filter( $vars, [ 'autocustomvars' => FALSE, 'customvars' => [ 'foo', 2 ] ] );
        assertEquals( [ 'pagename' => 'foo', 'foo' => 'bar' ], $result );
    }

    function testFilterSkipVars() {
        $vars = [ 'pagename' => 'foo' ];
        $filter = $this->get( [ 'foo' => 'bar', 'bar' => 'baz' ] );
        $result = $filter->filter( $vars, [ 'skipvars' => [ 'foo' ] ] );
        assertEquals( [ 'pagename' => 'foo', 'bar' => 'baz' ], $result );
    }

}