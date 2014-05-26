<?php namespace Brain\Cortex;

use Brain\Hooks;

/**
 * For object that have to handle hooks
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
interface HooksableInterface {

    /**
     * Return Hooks API facade object saved in the $hooks property
     *
     * @return \Brain\Hooks
     */
    public function getHooks();

    /**
     * Save a Hooks API facade object in the $hooks property
     *
     * @param \Brain\Hooks $hooks
     * @return mixed Calling object
     */
    public function setHooks( Hooks $hooks );
}