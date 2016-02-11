<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Route;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
final class PriorityRouteCollection implements RouteCollectionInterface
{

    /**
     * @var \SplPriorityQueue
     */
    private $queue;

    /**
     * @var array
     */
    private $priorities = [9];

    /**
     * PriorityRouteCollection constructor.
     */
    public function __construct()
    {
        $queue = new \SplPriorityQueue();
        $queue->setExtractFlags(\SplPriorityQueue::EXTR_DATA);
        $this->queue = $queue;
    }

    /**
     * @param \Brain\Cortex\Route\RouteInterface $route
     * @return \Brain\Cortex\Route\RouteCollectionInterface
     */
    public function addRoute(RouteInterface $route)
    {
        $i = is_int($route['priority']) ? $route['priority'] : max($this->priorities) + 1;
        in_array($i, $this->priorities, true) or $this->priorities[] = $i;

        if ($route['paged']) {
            $paged = clone $route;
            $paged['id'] .= '_paged';
            $paged['paged'] = false;
            $paged['path'] = rtrim($paged['path'], '/').'/page/{id:\d+}';
            $paged['priority'] = $i;
            $this->addRoute($paged);
            $i++;
        }

        $this->queue->insert($route, ((-1) * $i));

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        return $this->queue->current();
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        $this->queue->next();
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return $this->queue->key();
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return $this->queue->valid();
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        $this->queue->rewind();
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return $this->queue->count();
    }
}