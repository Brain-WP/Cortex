<?php namespace Brain\Cortex\Tests;

class MockedHooks extends \Brain\Hooks {

    public $mocked = [ ];

    public $mocked_conditions = [ ];

    function addAction() {
        return $this;
    }

    function trigger() {
        return NULL;
    }

    function runOnce() {
        return NULL;
    }

    function filter() {
        $tag = func_get_arg( 0 );
        $result = func_get_arg( 1 );
        $args = func_get_args();
        array_shift( $args );
        if ( isset( $this->mocked[$tag] ) && isset( $this->mocked_conditions[$tag] ) ) {
            if ( call_user_func_array( $this->mocked_conditions[$tag], $args ) ) {
                $result = $this->mocked[$tag];
            }
        } elseif ( isset( $this->mocked[$tag] ) ) {
            $result = $this->mocked[$tag];
        }
        if ( is_callable( $result ) ) {
            return call_user_func_array( $result, $args );
        }
        return $result;
    }

    function mock( $tag = '', $return = NULL, $condition = NULL ) {
        if ( is_callable( $condition ) ) {
            $this->mocked_conditions[$tag] = $condition;
        }
        $this->mocked[$tag] = $return;
    }

    function resetMock() {
        $this->mocked = [ ];
        $this->mocked_conditions = [ ];
    }

}