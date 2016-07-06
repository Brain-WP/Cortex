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
    private static $pagedFlags = [RouteInterface::PAGED_ARCHIVE, RouteInterface::PAGED_SINGLE];

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
     * @param  \Brain\Cortex\Route\RouteInterface $route
     * @return \Brain\Cortex\Route\RouteCollectionInterface
     */
    public function addRoute(RouteInterface $route)
    {
        if (! $route->offsetExists('priority') || ! is_numeric($route->offsetGet('priority'))) {
            $next = $this->priorities ? max($this->priorities) + 1 : 10;
            $route->offsetSet('priority', $next);
        }

        $priority = $route->offsetGet('priority');

        $paged = $this->maybeBuildPaged($route);
        if (
            $paged instanceof RouteInterface
            && ! in_array($paged['paged'], self::$pagedFlags, true) // ensure avoid infinite loops
        ) {
            $paged->offsetSet('priority', $priority);
            $priority++;
            $this->addRoute($paged);
        }

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
     * @return \Brain\Cortex\Route\RouteInterface|null
     */
    private function maybeBuildPaged(RouteInterface $route)
    {
        $built = null;
        $pagedArg = $route->offsetExists('paged') ? $route->offsetGet('paged') : '';
        $path = $route->offsetExists('path') ? $route->offsetGet('path') : '';
        if (in_array($pagedArg, self::$pagedFlags, true) && $path && is_string($path)) {
            $base = 'page';
            /** @var \WP_Rewrite $wp_rewrite */
            global $wp_rewrite;
            $wp_rewrite instanceof \WP_Rewrite and $base = $wp_rewrite->pagination_base;
            $array = $route->toArray();
            $array['id'] = $route->id().'_paged';
            $array['paged'] = RouteInterface::PAGED_UNPAGED;
            $array['path'] = $pagedArg === RouteInterface::PAGED_ARCHIVE
                ? $path.'/'.$base.'/{paged:\d+}'
                : $path.'/{page:\d+}';

            $routeVars = $route->offsetExists('vars') ? $route->offsetGet('vars') : [];
            if (is_callable($routeVars)) {
                $array['vars'] = $this->buildPagedVars($routeVars, $pagedArg);
            }

            $built = apply_filters('cortex.paged-route', new Route($array), $route);
        }

        return $built;
    }

    /**
     * When route `vars` property is a callback and the route is paged, we ensure that the `vars`
     * callable receives the proper `paged` (or `page`) query argument and also that it returned
     * array includes `paged` (or `page`) query argument.
     *
     * @param  callable $routeVars
     * @param  string   $pagedArg
     * @return \Closure
     */
    private function buildPagedVars(callable $routeVars, $pagedArg)
    {
        $key = $pagedArg === RouteInterface::PAGED_SINGLE ? 'page' : 'paged';

        return function (array $vars) use ($routeVars, $key) {
            (isset($vars[$key]) && is_numeric($vars[$key])) or $vars[$key] = 1;
            $vars[$key] = (int)$vars[$key];
            $result = $routeVars($vars);
            if (is_array($result) && ! (isset($result[$key]) && is_numeric($result[$key]))) {
                $result[$key] = $vars[$key];
            }
            $result[$key] = (int)$result[$key];

            return $result;
        };
    }
}
