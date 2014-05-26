<?php namespace Brain\Cortex;

/**
 * Handle the loading of a template file.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
interface TemplateLoaderInterface {

    /**
     * Load a template
     *
     * @param string $template
     */
    public function load( $template );
}