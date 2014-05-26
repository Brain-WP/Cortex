<?php

class WP_Error {

    var $errors = array();

    var $error_data = array();


    function __construct( $code = '', $message = '', $data = '' ) {
        if ( empty( $code ) ) return;
        $this->errors[ $code ][] = $message;
        if ( ! empty( $data ) ) $this->error_data[ $code ] = $data;
    }

    function get_error_codes() {
        if ( empty( $this->errors ) ) return array();
        return array_keys( $this->errors );
    }

    function get_error_code() {
        $codes = $this->get_error_codes();
        if ( empty( $codes ) ) return '';
        return $codes[ 0 ];
    }

    function get_error_messages( $code = '' ) {
        if ( empty( $code ) ) {
            $all_messages = array();
            foreach ( (array) $this->errors as $code => $messages ) {
                $all_messages = array_merge( $all_messages, $messages );
            }
            return $all_messages;
        }
        if ( isset( $this->errors[ $code ] ) ) {
            return $this->errors[ $code ];
        } else {
            return array();
        }
    }

    function get_error_message( $code = '' ) {
        if ( empty( $code ) ) $code = $this->get_error_code();
        $messages = $this->get_error_messages( $code );
        if ( empty( $messages ) ) return '';
        return $messages[ 0 ];
    }

    function get_error_data( $code = '' ) {
        if ( empty( $code ) ) $code = $this->get_error_code();
        if ( isset( $this->error_data[ $code ] ) ) return $this->error_data[ $code ];
        return null;
    }

    function add( $code, $message, $data = '' ) {
        $this->errors[ $code ][] = $message;
        if ( ! empty( $data ) ) $this->error_data[ $code ] = $data;
    }

    function add_data( $data, $code = '' ) {
        if ( empty( $code ) ) $code = $this->get_error_code();
        $this->error_data[ $code ] = $data;
    }
}

function is_wp_error( $thing ) {
    if ( is_object( $thing ) && is_a( $thing, 'WP_Error' ) ) return true;
    return false;
}
