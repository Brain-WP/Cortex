<?php namespace Brain\Cortex\Tests\Unit\Controllers;

use Brain\Cortex\Tests\TestCase;

class FallbackControllerTest extends TestCase {

    private function get() {
        return \Mockery::mock( 'Brain\Cortex\Controllers\FallbackController' )->makePartial();
    }

    private function getMocked( $n = 0, $exact = FALSE, $query = [ ] ) {
        $request = \Mockery::mock( 'Brain\Request' );
        $pieces = array_fill( 0, $n, 'foo' );
        $request->shouldReceive( 'pathPieces' )->andReturn( $pieces );
        $request->shouldReceive( 'getQuery->getRaw' )->andReturn( $query );
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getRequest' )->andReturn( $request );
        $ctrl->shouldReceive( 'isExact' )->andReturn( (bool) $exact );
        return $ctrl;
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testSetMinPiecesFailsIfBadPieces() {
        $ctrl = $this->get();
        $ctrl->setMinPieces( 'foo' );
    }

    function testSetMinPieces() {
        $ctrl = $this->get();
        $return = $ctrl->setMinPieces( '3' );
        assertTrue( 3 === $ctrl->getMinPieces() );
        assertEquals( $ctrl, $return );
    }

    function testIsExactGetNull() {
        $ctrl = $this->get();
        assertFalse( $ctrl->isExact() );
    }

    function testIsExactSetTrue() {
        $ctrl = $this->get();
        $return = $ctrl->isExact( TRUE );
        assertTrue( $ctrl->isExact() );
        assertEquals( $ctrl, $return );
    }

    function testIsExactSetFalse() {
        $ctrl = $this->get();
        $return = $ctrl->isExact( '' );
        assertFalse( $ctrl->isExact() );
        assertEquals( $ctrl, $return );
    }

    function testShouldNotIfCondition() {
        $ctrl = $this->getMocked( 1, FALSE, [ 'should' => FALSE ] );
        $ctrl->shouldReceive( 'getCondition' )->atLeast( 1 )->andReturn( function( $request ) {
            $query = $request->getQuery()->getRaw();
            return isset( $query['should'] ) && $query['should'];
        } );
        assertFalse( $ctrl->should() );
    }

    function testShouldIfCondition() {
        $ctrl = $this->getMocked( 1, FALSE, [ 'should' => TRUE ] );
        $ctrl->shouldReceive( 'getCondition' )->atLeast( 1 )->andReturn( function( $request ) {
            $query = $request->getQuery()->getRaw();
            return isset( $query['should'] ) && $query['should'];
        } );
        assertTrue( $ctrl->should() );
    }

    /**
     * @expectedException \DomainException
     */
    function testShouldIfBadMinPieces() {
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getMinPieces' )->atLeast( 1 )->andReturn( '' );
        $ctrl->should();
    }

    function testShouldTrueWhen0() {
        $ctrl = $this->get();
        $ctrl->shouldReceive( 'getMinPieces' )->atLeast( 1 )->andReturn( '0' );
        assertTrue( $ctrl->should() );
    }

    function testShouldNoExactPiecesMorethenRequestFalse() {
        $ctrl = $this->getMocked( 3, FALSE );
        $ctrl->shouldReceive( 'getMinPieces' )->andReturn( 4 );
        assertFalse( $ctrl->should() );
    }

    function testShouldNoExactPiecesMorethenRequestTrue() {
        $ctrl = $this->getMocked( 5, FALSE );
        $ctrl->shouldReceive( 'getMinPieces' )->andReturn( 4 );
        assertTrue( $ctrl->should() );
    }

    function testShouldNoExactPiecesMorethenRequestFalseNegativeMin() {
        $ctrl = $this->getMocked( 3, FALSE );
        $ctrl->shouldReceive( 'getMinPieces' )->andReturn( -4 );
        assertTrue( $ctrl->should() );
    }

    function testShouldNoExactPiecesMorethenRequestTrueNegativeMin() {
        $ctrl = $this->getMocked( 5, FALSE );
        $ctrl->shouldReceive( 'getMinPieces' )->andReturn( -4 );
        assertFalse( $ctrl->should() );
    }

    function testShouldExactFalse() {
        $ctrl = $this->getMocked( 5, TRUE );
        $ctrl->shouldReceive( 'getMinPieces' )->andReturn( 4 );
        assertFalse( $ctrl->should() );
    }

    function testShouldExactTrue() {
        $ctrl = $this->getMocked( 5, TRUE );
        $ctrl->shouldReceive( 'getMinPieces' )->andReturn( 5 );
        assertTrue( $ctrl->should() );
    }

}