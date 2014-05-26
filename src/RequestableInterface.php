<?php namespace Brain\Cortex;

use Brain\Request;

/**
 * For object that have to handle request
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
interface RequestableInterface {

    /**
     * Return Request API facade object saved in the $request property
     *
     * @return \Brain\Hooks
     */
    public function getRequest();

    /**
     * Save a Request API facade object in the $request property
     *
     * @param \Brain\Request $request
     * @return mixed Calling object
     */
    public function setRequest( Request $request );
}