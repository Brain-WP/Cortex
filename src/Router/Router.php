<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Router;

use Brain\Cortex\Group\GroupCollectionInterface;
use Brain\Cortex\Route\RouteCollectionInterface;
use Brain\Cortex\Route\RouteInterface;
use Brain\Cortex\Uri\WordPressUri;
use FastRoute\DataGenerator\GroupCountBased as DefDataGenerator;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased as DefDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;


/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
final class Router implements RouterInterface
{

    /**
     * @var \Brain\Cortex\Group\GroupCollectionInterface
     */
    private $groups;

    /**
     * @var \Brain\Cortex\Route\RouteCollectionInterface
     */
    private $routes;

    /**
     * @var \FastRoute\RouteCollector
     */
    private $collector;

    /**
     * @var callable
     */
    private $dispatcherFactory;

    /*
     * @var array
     */
    private $parsedRoutes = [];

    /**
     * @var null
     */
    private $results = null;

    /**
     * Router constructor.
     *
     * @param \Brain\Cortex\Route\RouteCollectionInterface $routes
     * @param \Brain\Cortex\Group\GroupCollectionInterface $groups
     * @param \FastRoute\RouteCollector                    $collector
     * @param callable                                     $dispatcherFactory
     */
    public function __construct(
        RouteCollectionInterface $routes,
        GroupCollectionInterface $groups,
        RouteCollector $collector = null,
        callable $dispatcherFactory = null
    ) {
        $this->groups = $groups;
        $this->routes = $routes;
        $this->collector = $collector ? : new RouteCollector(new Std(), new DefDataGenerator());
        $this->dispatcherFactory = $dispatcherFactory;
    }

    /**
     * @inheritdoc
     */
    public function match(WordPressUri $uri)
    {
        if ($this->results instanceof MatchingResult) {
            return $this->results;
        }

        if ( ! count($this->routes) || ! $this->parseRoutes($uri)) {
            $this->results = new MatchingResult(['matched' => false]);

            return $this->results;
        }

        $dispatcher = $this->buildDispatcher($this->collector->getData());
        unset($this->collector);

        $method = empty($_SERVER['REQUEST_METHOD']) ? 'GET' : strtoupper($_SERVER['REQUEST_METHOD']);

        $uriPath = rtrim($uri->path(), '/');
        $routeInfo = $dispatcher->dispatch($method, $uriPath ? : '/');
        if ($routeInfo[0] === Dispatcher::FOUND) {
            $route = $this->parsedRoutes[$routeInfo[1]];
            $vars = $routeInfo[2];

            $this->results = $this->finalizeRoute($route, $vars, $uri);
        }

        $this->results or $this->results = new MatchingResult(['matched' => false]);

        unset($this->parsedRoutes);

        return $this->results;
    }

    /**
     * @param \Brain\Cortex\Uri\WordPressUri $uri
     * @return int
     */
    private function parseRoutes(WordPressUri $uri)
    {
        $iterator = new RouteFilterIterator($this->routes, $uri);
        $parsed = 0;
        while ($iterator->valid()) {
            /** @var \Brain\Cortex\Route\RouteInterface $route */
            $route = $this->groups->mergeGroup($iterator->current());
            if ($route instanceof RouteInterface) {
                $id = $route->id();
                $this->parsedRoutes[$id] = $route;
                $path = '/'.trim($route['path'], '/');
                $this->collector->addRoute($route['method'], $path, $id);
                $parsed++;
            }
            $iterator->next();
        }

        unset($this->routes, $this->groups);

        return $parsed;
    }

    /**
     * @param array $data
     * @return \FastRoute\Dispatcher
     */
    private function buildDispatcher(array $data)
    {
        $dispatcher = null;
        if (is_callable($this->dispatcherFactory)) {
            $factory = $this->dispatcherFactor;
            $dispatcher = $factory($data);
        }

        $dispatcher instanceof Dispatcher or $dispatcher = new DefDispatcher($data);

        return $dispatcher;
    }

    /**
     * @param \Brain\Cortex\Route\RouteInterface $route
     * @param array                              $vars
     * @param \Brain\Cortex\Uri\WordPressUri     $uri
     * @return \Brain\Cortex\Router\MatchingResult
     */
    private function finalizeRoute(RouteInterface $route, array $vars, WordPressUri $uri)
    {
        is_null($route['merge_query_string']) and $route['merge_query_string'] = true;
        $route['merge_query_string'] and $vars = array_merge($vars, $uri->vars());
        if (is_array($route['defaults'])) {
            foreach ($route['defaults'] as $key => $value) {
                isset($vars[$key]) or $vars[$key] = $value;
            }
        }
        if (is_array($route['skip_vars'])) {
            foreach ($route['skip_vars'] as $key => $value) {
                unset($vars[$key]);
            }
        }

        return new MatchingResult([
            'vars'     => $vars,
            'matched'  => true,
            'handler'  => $route['handler'],
            'before'   => $route['before'],
            'after'    => $route['after'],
            'template' => $route['template']
        ]);
    }
}