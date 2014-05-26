<?php
if ( ! class_exists( 'Brain\Cortex\BrainModule' ) && is_file( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if ( function_exists( 'add_action' ) ) {

    add_action( 'brain_init', function( $brain ) {
        $brain->addModule( new Brain\Cortex\BrainModule );
    } );
}