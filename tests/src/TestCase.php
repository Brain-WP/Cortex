<?php namespace Brain\Cortex\Tests;

use Brain\Container as Brain;

class TestCase extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        \WP_Mock::setUp();
        Brain::boot( new \Pimple\Container, FALSE );
    }

    public function tearDown() {
        Brain::flush();
        \WP_Mock::tearDown();
        \Mockery::close();
    }

}