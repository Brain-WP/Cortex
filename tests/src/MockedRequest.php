<?php namespace Brain\Cortex\Tests;

class MockedRequest extends \Brain\Request {

    public $mocked = [ ];

    public $path = '/';

    public function __call( $name, $args ) {
        if ( ! isset( $this->mocked[$name] ) || ! is_array( $this->mocked[$name] ) ) return [ ];
        $arg = ! empty( $args ) ? array_shift( $args ) : FALSE;
        return isset( $this->mocked[$name][$arg] ) ? $this->mocked[$name][$arg] : [ ];
    }

    public function path() {
        return $this->path;
    }

    function mock( $bag = '', $tag = '', $return = NULL ) {
        $this->mocked[$bag][$tag] = $return;
    }

    function resetMock() {
        $this->mocked = [ ];
    }

}