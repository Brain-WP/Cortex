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
        $this->collector = $collector ? : new RouteCollector(new Std(), new DefDataGenerator());
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

        if (! count($this->routes) || ! $this->parseRoutes($uri, $httpMethod)) {
            $this->results = new MatchingResult(['route' => null]);

            return $this->results;
        }

        // in case of exact match, no need to go further
        if ($this->results instanceof MatchingResult) {
            return $this->results;
        }

        $dispatcher = $this->buildDispatcher($this->collector->getData());
        unset($this->collector);

        $uriPath = '/'.trim($uri->path(), '/');
        $routeInfo = $dispatcher->dispatch($httpMethod, $uriPath ? : '/');
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
     * @param  string                         $httpMethod
     * @return int
     */
    private function parseRoutes(UriInterface $uri, $httpMethod)
    {
        $iterator = new RouteFilterIterator($this->routes, $uri);
        $parsed = 0;
        /** @var \Brain\Cortex\Route\RouteInterface $route */
        foreach ($iterator as $route) {
            $route = $this->sanitizeRouteMethod($this->groups->mergeGroup($route), $httpMethod);
            if (! $this->validateRoute($route, $httpMethod)) {
                continue;
            }

            $parsed++;
            $id = $route->id();
            $this->parsedRoutes[$id] = $route;
            $path = '/'.trim($route['path'], '/');
            // exact match
            if ($path === '/'.trim($uri->path(), '/')) {
                $this->results = $this->finalizeRoute($route, [], $uri);
                unset($this->parsedRoutes, $this->collector);
                break;
            }

            $this->collector->addRoute(strtoupper($route['method']), $path, $id);
        }

        unset($this->routes, $this->groups);

        return $parsed;
    }

    /**
     * @param  \Brain\Cortex\Route\RouteInterface $route
     * @param  string                             $httpMethod
     * @return \Brain\Cortex\Route\RouteInterface
     */
    private function sanitizeRouteMethod(RouteInterface $route, $httpMethod)
    {
        if (empty($route['method']) || ! (is_string($route['method']) || is_array($route['method']))) {
            $route['method'] = $httpMethod;
        }

        if (is_array($route['method'])) {
            $route['method'] = array_map('strtoupper', array_filter($route['method'], 'is_string'));

            return $route;
        }

        (strtolower($route['method']) === 'any') and $route['method'] = $httpMethod;

        $route['method'] = strtoupper($route['method']);

        return $route;
    }

    /**
     * @param  \Brain\Cortex\Route\RouteInterface $route
     * @param                                     $httpMethod
     * @return bool
     */
    private function validateRoute(RouteInterface $route, $httpMethod)
    {
        $id = $route->id();
        $path = trim($route['path'], '/');
        $handler = $route['handler'];

        return
            is_string($id)
            && $id
            && filter_var($path, FILTER_SANITIZE_URL) === $path
            && in_array($httpMethod, (array) $route['method'], true)
            && (is_callable($handler) || $handler instanceof ControllerInterface);
    }

    /**
     * @param  array $data
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
     * @param  \Brain\Cortex\Route\RouteInterface $route
     * @param  array                              $vars
     * @param  \Brain\Cortex\Uri\UriInterface     $uri
     * @return \Brain\Cortex\Router\MatchingResult
     */
    private function finalizeRoute(RouteInterface $route, array $vars, UriInterface $uri)
    {
        is_null($route['merge_query_string']) and $route['merge_query_string'] = true;
        $merge = filter_var($route['merge_query_string'], FILTER_VALIDATE_BOOLEAN);
        $uriVars = $uri->vars();
        $merge and $vars = array_merge($vars, $uriVars);
        // vars is going to be modified if route vars is a callback, lets save this as a backup
        $varsOriginal = $vars;
        $result = null;
        switch (true) {
            case (is_callable($route['vars'])) :
                /** @var callable $cb */
                $cb = $route['vars'];
                $routeVars = $cb($vars, $uri);
                is_array($routeVars) and $vars = $routeVars;
                $routeVars instanceof MatchingResult and $result = $routeVars;
                break;
            case (is_array($route['vars'])) :
                $vars = array_merge($route['vars'], $vars);
                break;
            case ($route['vars'] instanceof MatchingResult) :
                $result = $route['vars'];
                break;
        }

        if ($result instanceof MatchingResult) {
            return $result;
        }

        if (! empty($route['default_vars']) && is_array($route['default_vars'])) {
            $vars = array_merge($route['default_vars'], $vars);
        }

        $vars = $this->ensurePreviewVars($vars, $uriVars);
        $vars = apply_filters('cortex.matched-vars', $vars, $route, $uri);
        $noTemplate = filter_var($route['no_template'], FILTER_VALIDATE_BOOLEAN);

        return new MatchingResult([
            'vars'     => (array)$vars,
            'matches'  => (array)$varsOriginal,
            'route'    => $route->id(),
            'path'     => $route['path'],
            'handler'  => $route['handler'],
            'before'   => $route['before'],
            'after'    => $route['after'],
            'template' => $noTemplate ? false : $route['template'],
        ]);
    }

    /**
     * To ensure preview works, we need to merge preview-related query string
     * to query arguments.
     *
     * @param  array $vars
     * @param  array $uriVars
     * @return array
     */
    private function ensurePreviewVars(array $vars, array $uriVars)
    {
        if (! is_user_logged_in()) {
            return $vars;
        }

        foreach (['preview', 'preview_id', 'preview_nonce'] as $var) {
            if (! isset($vars[$var]) && isset($uriVars[$var])) {
                $vars[$var] = $uriVars[$var];
            }
        }

        return $vars;
    }
}
