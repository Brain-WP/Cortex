<?php namespace Brain;

class Cortex {

    public static function boot() {
        if ( ! function_exists( 'add_action' ) ) {
            return;
        }
        add_action( 'brain_init', function( $brain ) {
            $brain->addModule( new Cortex\BrainModule );
        } );
    }

}