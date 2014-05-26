<?php namespace Brain\Cortex\Tests;

class ActionRoutableStub extends \Brain\Cortex\Controllers\ActionRoutable {

    function foo() {
        return 'Foo!';
    }

    static function foo_static() {
        return 'Foo Static!';
    }

}