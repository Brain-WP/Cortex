<?php namespace Brain\Cortex;

use Symfony\Component\Routing\RouteCollection as SymfonyCollection;
use Symfony\Component\Routing\Route as SymfonyRoute;

/**
 * Concrete implementation for RouteCollectionInterface.
 *
 * Extend SplHeap to handle routes priority.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
class RouteCollection extends \SplHeap implements RouteCollectionInterface {

    private $collection;

    private $prepared = FALSE;

    public function __construct( SymfonyCollection $collection ) {
        $this->collection = $collection;
    }

    /**
     * SplHeap::insert() implementation
     *
     * @param \Brain\Cortex\RouteInterface $value
     * @return \Brain\Cortex\RouteInterface
     */
    public function insert( $value ) {
        if ( $value instanceof RouteInterface ) {
            parent::insert( $value );
        }
        return $value;
    }

    /**
     * SplHeap::compare() implementation
     *
     * @param \Brain\Cortex\RouteInterface $value1
     * @param \Brain\Cortex\RouteInterface $value2
     * @return int
     */
    public function compare( $value1, $value2 ) {
        if ( $value1 instanceof RouteInterface && $value2 instanceof RouteInterface ) {
            if ( $value1->getPriority() === $value2->getPriority() ) return 0;
            return $value1->getPriority() < $value2->getPriority() ? 1 : -1;
        }
        return 0;
    }

    public function getCollection() {
        $collection = $this->getUnderlyingCollection();
        if ( ! $this->prepared ) {
            $this->rewind();
            while ( $this->valid() ) {
                $route = $this->current()->prepare();
                if ( $route instanceof SymfonyRoute ) {
                    $collection->add( $this->current()->getId(), $route );
                }
                $this->next();
            }
            $this->prepared = TRUE;
        }
        return $collection;
    }

    public function getUnderlyingCollection() {
        return $this->collection;
    }

}