<?php
if ( ! defined( 'CORTEXBASEPATH' ) ) define( 'CORTEXBASEPATH', dirname( dirname( __FILE__ ) ) );

$autoload = require_once CORTEXBASEPATH . '/vendor/autoload.php';

require_once CORTEXBASEPATH . '/vendor/phpunit/phpunit/PHPUnit/Framework/Assert/Functions.php';

if ( ! class_exists( 'WP' ) ) require_once __DIR__ . '/class-wp.php';

if ( ! class_exists( 'WP_Error' ) ) require_once __DIR__ . '/class-wp-error.php';

