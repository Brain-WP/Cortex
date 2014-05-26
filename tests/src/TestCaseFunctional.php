<?php namespace Brain\Cortex\Tests;

use Brain\Container as Brain;

class TestCaseFunctional extends TestCase {

    public function setUp() {
        \WP_Mock::setUp();
        $brain = Brain::boot( new \Pimple, FALSE );
        global $wp;
        $wp = new \WP;
        if ( ! defined( 'CORTEXBASEPATH' ) ) {
            define( 'CORTEXBASEPATH', dirname( dirname( dirname( __FILE__ ) ) ) );
        }
        $brain->addModule( new \Brain\Amygdala\BrainModule );
        $brain->addModule( new \Brain\Striatum\BrainModule );
        $brain->addModule( new \Brain\Cortex\BrainModule );
    }

    function tearDown() {
        Brain::flush();
        parent::tearDown();
    }

}