<?php namespace Brain\Cortex\Controllers;

/**
 * Generic controller interface. It's an object that do something running the method run().
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
interface ControllerInterface {

    /**
     * Main controller function. What it does depends on more specialized Controller type
     */
    function run();
}