<?php namespace Brain\Cortex;

/**
 * Concrete implementation of GroupContainerInterface
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
class GroupContainer implements GroupContainerInterface {

    use \Brain\Contextable;

    protected $groups;

    function __construct() {
        $this->groups = new \ArrayObject;
    }

    function getGroup( $index = NULL ) {
        return $this->getContext( 'groups', $index );
    }

    public function addGroup( $group = NULL, Array $args = [ ] ) {
        if ( ! is_string( $group ) || $group === '' || empty( $args ) ) {
            throw new \InvalidArgumentException;
        }
        return $this->setContext( 'groups', $group, $args );
    }

    public function mergeGroup( RouteInterface $route ) {
        $index = $route->get( 'group' );
        if ( is_string( $index ) && ! empty( $index ) ) {
            $group = $this->getGroup( $index );
            if ( ! is_array( $group ) ) {
                throw new \UnexpectedValueException;
            }
            foreach ( $group as $key => $value ) {
                $route->set( $key, $value );
            }
        }
        return $route;
    }

}