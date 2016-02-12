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
    private $priorities = [];

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
     * @param  \Brain\Cortex\Route\RouteInterface           $route
     * @return \Brain\Cortex\Route\RouteCollectionInterface
     */
    public function addRoute(RouteInterface $route)
    {
        if (! $route->offsetExists('priority') || ! is_numeric($route->offsetGet('priority'))) {
            $next = $this->priorities ? max($this->priorities) + 1 : 10;
            $route->offsetSet('priority', $next);
        }

        $priority = $this->maybeAddPaged($route, (int) $route->offsetGet('priority'));
        in_array($priority, $this->priorities, true) or $this->priorities[] = $priority;
        $this->queue->insert($route, ((-1) * $priority));

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

    /**
     * @param  \Brain\Cortex\Route\RouteInterface $route
     * @param  int                                $priority
     * @return int
     */
    private function maybeAddPaged(RouteInterface $route, $priority)
    {
        $pagedOpts = [RouteInterface::PAGED_ARCHIVE, RouteInterface::PAGED_SINGLE];
        $pagedArg = $route->offsetExists('paged') ? $route->offsetGet('paged') : '';
        $path = $route->offsetExists('path') ? $route->offsetGet('path') : '';
        if (in_array($pagedArg, $pagedOpts, true) && $path && is_string($path)) {
            $base = 'page';
            /** @var \WP_Rewrite $wp_rewrite */
            global $wp_rewrite;
            $wp_rewrite instanceof \WP_Rewrite and $base = $wp_rewrite->pagination_base;
            $paged = clone $route;
            $newPath = $pagedArg === RouteInterface::PAGED_ARCHIVE
                ? $path.'/'.$base.'/{paged:\d+}'
                : $path.'/{page:\d+}';
            $paged->offsetSet('path', $newPath);
            $paged->offsetSet('id', $route->offsetGet('id').'_paged');
            $paged->offsetSet('paged', '');
            $paged->offsetSet('priority', $priority);
            $this->addRoute($paged);
            $priority++;
        }

        return $priority;
    }
}
