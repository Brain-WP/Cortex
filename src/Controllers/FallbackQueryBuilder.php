<?php namespace Brain\Cortex\Controllers;

use Brain\Cortex\QueryVarsFilter as Filter;
use Brain\Cortex\TemplateLoader as Loader;
use Brain\Cortex\HooksableInterface;
use Brain\Hooks;

/**
 * FallbackQueryBuilder is a specialization of FallbackController that act as a query builder.
 *
 * When no route match, the run() method return a query arguments array to be set in main WP_Query.
 * Request object is injected by Worker.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
class FallbackQueryBuilder extends FallbackController implements QueryBuilderInterface, HooksableInterface {

    use \Brain\Cortex\Hooksable;

    private $filter;

    private $template_loader;

    private $hooks;

    private $query_args;

    private $auto_custom_vars = TRUE;

    private $custom_vars;

    private $skipvars;

    public function __construct( Filter $filter, Loader $template_loader, Hooks $hooks ) {
        $this->filter = $filter;
        $this->template_loader = $template_loader;
        $this->hooks = $hooks;
    }

    /**
     * Return QueryVarsFilter instance
     *
     * @return \Brain\Cortex\QueryVarsFilter
     */
    public function getFilter() {
        return $this->filter;
    }

    /**
     * Return template loader instance
     *
     * @return \Brain\Cortex\TemplateLoader
     */
    public function getTemplateLoader() {
        return $this->template_loader;
    }

    /**
     * Try to build query vars from request vars
     *
     * @return boolean
     */
    public function run() {
        $this->getHooks()->trigger( 'cortex.pre_query_vars_build', $this );
        $vars = $this->buildQueryVars( $this->getRequest()->getRequest()->getRaw() );
        if ( ! empty( $vars ) && is_array( $vars ) ) {
            $this->query_args = $vars;
            $this->getHooks()->trigger( 'cortex.query_vars', $this );
            $template = $this->getHooks()->filter( 'cortex.fallback_template', NULL, $this );
            $unfiltered = $this->getHooks()->filter( 'cortex.fallback_template_unfiltered', FALSE );
            if ( is_string( $template ) && ! empty( $template ) ) {
                $this->getTemplateLoader()->load( $template, $unfiltered );
            }
            return TRUE;
        }
        return FALSE;
    }

    public function getQueryArgs() {
        return $this->query_args;
    }

    public function getQueryClass() {
        return $this->getHooks()->filter( 'cortex.fallback_query_class', NULL, $this );
    }

    public function buildQueryVars( array $vars = [ ] ) {
        $args = [
            'qsmerge'        => FALSE,
            'autocustomvars' => $this->autoCustomVars(),
            'customvars'     => $this->customVars(),
            'skipvars'       => $this->skipVars(),
        ];
        $vars = $this->getFilter()->filter( $vars, $args );
        $request = $this->getRequest();
        $query_vars = $this->getHooks()->filter( 'cortex.fallback_query_vars', $vars, $request );
        return is_array( $query_vars ) && ! empty( $query_vars ) ? $query_vars : FALSE;
    }

    /**
     * Accessor for $auto_custom_vars property.
     * Act as a setter if $set argument is passed, as a getter otherwise.
     *
     * @param boolean|void $set
     * @return boolean
     */
    public function autoCustomVars( $set = NULL ) {
        if ( ! is_null( $set ) ) {
            $this->auto_custom_vars = (bool) $set;
            return $this->auto_custom_vars;
        }
        return $this;
    }

    /**
     * Getter for $custom_vars property.
     *
     * @return array
     */
    public function customVars() {
        return $this->custom_vars ? : [ ];
    }

    /**
     * Setter for $custom_vars property.
     *
     * @return array
     */
    public function setCustomVars( Array $vars = [ ] ) {
        $this->custom_vars = $vars;
        return $this;
    }

    /**
     * Getter for $skipvars property.
     *
     * @return array
     */
    public function skipVars() {
        return $this->skipvars ? : [ ];
    }

    /**
     * Setter for $skipvars property.
     *
     * @return array
     */
    public function setSkipVars( Array $vars = [ ] ) {
        $this->skipvars = $vars;
        return $this;
    }

}