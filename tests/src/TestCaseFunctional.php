<?php namespace Brain\Cortex\Tests;

use Brain\Container as Brain;

class TestCaseFunctional extends TestCase {

    public function setUp() {
        if ( ! defined( 'CORTEXBASEPATH' ) ) {
            define( 'CORTEXBASEPATH', dirname( dirname( dirname( __FILE__ ) ) ) );
        }
        \WP_Mock::setUp();
        $brain = Brain::boot( new \Pimple, FALSE );
        \WP_Mock::wpFunction( 'is_admin', [ 'return' => FALSE ] );
        \WP_Mock::wpFunction( 'wp_parse_args', [ 'return' => function( $args, $defaults = [ ] ) {
            return array_merge( (array) $defaults, (array) $args );
        } ] );
        \WP_Mock::wpFunction( 'home_url', [ 'return' => function( $relative = '' ) {
            return 'http://www.example.com/' . ltrim( $relative, '/\\ ' );
        } ] );
        global $wp;
        $wp = new \WP;
        $amygdala = new \Brain\Amygdala\BrainModule;
        $amygdala->getBindings( $brain );
        $brain->addModule( new \Brain\Striatum\BrainModule );
        $cortex = new \Brain\Cortex\BrainModule;
        $brain->addModule( $cortex );
        Brain::bootModules( $brain, FALSE );
        $cortex->bootFrontend( $brain );
    }

    public function tearDown() {
        Brain::flush();
        $this->request = NULL;
        parent::tearDown();
    }

}