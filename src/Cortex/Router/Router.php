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

use Brain\Cortex\Controller\ControllerInterface;
use Brain\Cortex\Group\GroupCollectionInterface;
use Brain\Cortex\Route\RouteCollectionInterface;
use Brain\Cortex\Route\RouteInterface;
use Brain\Cortex\Uri\UriInterface;
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
        $this->collector = $collector ?: new RouteCollector(new Std(), new DefDataGenerator());
        $this->dispatcherFactory = $dispatcherFactory;
    }

    /**
     * @inheritdoc
     */
    public function match(UriInterface $uri, $httpMethod)
    {
        if ($this->results instanceof MatchingResult) {
            return $this->results;
        }

        if (! count($this->routes) || ! $this->parseRoutes($uri)) {
            $this->results = new MatchingResult(['route' => null]);

            return $this->results;
        }

        $dispatcher = $this->buildDispatcher($this->collector->getData());
        unset($this->collector);

        $uriPath = '/'.trim($uri->path(), '/');
        $routeInfo = $dispatcher->dispatch($httpMethod, $uriPath ?: '/');
        if ($routeInfo[0] === Dispatcher::FOUND) {
            $route = $this->parsedRoutes[$routeInfo[1]];
            $vars = $routeInfo[2];

            $this->results = $this->finalizeRoute($route, $vars, $uri);
        }

        $this->results or $this->results = new MatchingResult(['route' => null]);

        unset($this->parsedRoutes);

        return $this->results;
    }

    /**
     * @param  \Brain\Cortex\Uri\UriInterface $uri
     * @return int
     */
    private function parseRoutes(UriInterface $uri)
    {
        $iterator = new RouteFilterIterator($this->routes, $uri);
        $parsed = 0;
        $iterator->rewind();
        while ($iterator->valid()) {
            /** @var \Brain\Cortex\Route\RouteInterface $route */
            $route = $this->groups->mergeGroup($iterator->current());
            empty($route['method']) and $route['method'] = 'GET';
            if ($route instanceof RouteInterface && $this->validate($route)) {
                $id = $route->id();
                $this->parsedRoutes[$id] = $route;
                $path = '/'.trim($route['path'], '/');
                $this->collector->addRoute(strtoupper($route['method']), $path, $id);
                $parsed++;
            }
            $iterator->next();
        }

        unset($this->routes, $this->groups);

        return $parsed;
    }

    /**
     * @param  \Brain\Cortex\Route\RouteInterface $route
     * @return bool
     */
    private function validate(RouteInterface $route)
    {
        $id = $route->id();
        $path = $route['path'];
        $method = $route['method'];
        $handler = $route['handler'];
        $methods = [
            'GET',
            'POST',
            'PUT',
            'OPTIONS',
            'HEAD',
            'DELETE',
            'TRACE',
            'CONNECT',
        ];

        return
            is_string($id)
            && $id
            && filter_var($path, FILTER_SANITIZE_URL) === $path
            && in_array(strtoupper((string) $method), $methods, true)
            && (is_callable($handler) || $handler instanceof ControllerInterface);
    }

    /**
     * @param  array                 $data
     * @return \FastRoute\Dispatcher
     */
    private function buildDispatcher(array $data)
    {
        $dispatcher = null;
        if (is_callable($this->dispatcherFactory)) {
            $factory = $this->dispatcherFactory;
            $dispatcher = $factory($data);
        }

        $dispatcher instanceof Dispatcher or $dispatcher = new DefDispatcher($data);

        return $dispatcher;
    }

    /**
     * @param  \Brain\Cortex\Route\RouteInterface  $route
     * @param  array                               $vars
     * @param  \Brain\Cortex\Uri\UriInterface      $uri
     * @return \Brain\Cortex\Router\MatchingResult
     */
    private function finalizeRoute(RouteInterface $route, array $vars, UriInterface $uri)
    {
        is_null($route['merge_query_string']) and $route['merge_query_string'] = true;
        $merge = filter_var($route['merge_query_string'], FILTER_VALIDATE_BOOLEAN);
        $merge and $vars = array_merge($vars, $uri->vars());
        if (is_callable($route['vars'])) {
            $cb = $route['vars'];
            $routeVars = $cb($vars);
            is_array($routeVars) and $vars = $routeVars;
        } elseif (is_array($route['vars'])) {
            foreach ($route['vars'] as $key => $value) {
                isset($vars[$key]) or $vars[$key] = $value;
            }
        }

        return new MatchingResult([
            'vars'     => $vars,
            'route'    => $route->id(),
            'path'     => $route['path'],
            'handler'  => $route['handler'],
            'before'   => $route['before'],
            'after'    => $route['after'],
            'template' => $route['template'],
        ]);
    }
}
