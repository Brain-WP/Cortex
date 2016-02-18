<?php namespace Brain\Cortex;

use Brain\Contextable;

/**
 * Concrete implementation of GroupContainerInterface
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
class GroupContainer implements GroupContainerInterface {

    use Contextable;

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
        $id = $route->get( 'group' );
        if ( empty( $id ) || ( ! is_string( $id ) && ! is_array( $id ) ) ) return $route;
        $group = [ ];
        if ( is_string( $id ) ) {
            $group = $this->getGroupData( $id );
        } elseif ( is_array( $id ) ) {
            $group = [ ];
            foreach ( $id as $group_id ) {
                $group = array_merge( $group, $this->getGroupData( $group_id ) );
            }
        }
        $filtered = ! empty( $group ) ? \Brain\stringKeyed( $group ) : [];
        if ( ! empty( $filtered ) ) {
            foreach ( $filtered as $key => $value ) {
                $key = strtolower( $key ) !== 'bindtoclosure' ? strtolower( $key ) : 'bound_closure';
                if ( is_null( $route->get( $key ) ) ) {
                    $route->set( $key, $value );
                }
            }
        }
        return $route;
    }

    private function getGroupData( $id ) {
        if ( ! is_string( $id ) || $id === '' ) return [ ];
        $data = $this->getGroup( $id ) ? : [ ];
        return is_array( $data ) ? $data : [ ];
    }

}