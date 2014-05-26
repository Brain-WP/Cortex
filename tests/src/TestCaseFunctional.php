<?php namespace Brain\Cortex\Tests;

use Brain\Container as Brain;

class TestCaseFunctional extends TestCase {

    public function setUp() {
        \WP_Mock::setUp();
        Brain::boot( new \Pimple, FALSE );
        global $wp;
        $wp = new \WP;
        if ( ! defined( 'CORTEXBASEPATH' ) ) {
            define( 'CORTEXBASEPATH', dirname( dirname( dirname( __FILE__ ) ) ) );
        }
    }

}