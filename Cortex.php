<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain;

use Brain\Cortex\Group\GroupCollection;
use Brain\Cortex\Group\GroupCollectionInterface;
use Brain\Cortex\Route\PriorityRouteCollection;
use Brain\Cortex\Route\RouteCollectionInterface;
use Brain\Cortex\Router\ResultHandler;
use Brain\Cortex\Router\ResultHandlerInterface;
use Brain\Cortex\Router\Router;
use Brain\Cortex\Router\RouterInterface;
use Brain\Cortex\Uri\PsrUri;
use Brain\Cortex\Uri\UriInterface;
use Brain\Cortex\Uri\WordPressUri;
use Psr\Http\Message\RequestInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
class Cortex
{
    /**
     * @var bool
     */
    private static $booted = false;

    /**
     * @var bool
     */
    private static $late = false;

    /**
     * @param  \Psr\Http\Message\RequestInterface $request
     * @return bool
     */
    public static function boot(RequestInterface $request = null)
    {
        if (self::$booted) {
            return false;
        }

        if (did_action('parse_request')) {
            $exception = new \BadMethodCallException(
                sprintf('%s must be called before "do_parse_request".', __METHOD__)
            );

            if (defined('WP_DEBUG') && WP_DEBUG) {
                throw $exception;
            }

            do_action('cortex.fail', $exception);
        }

        self::$booted = add_filter('do_parse_request', function ($do, \WP $wp) use ($request) {

            self::$late = true;

            try {
                $instance = new static();
                $routes = $instance->factoryRoutes();
                $groups = $instance->factoryGroups();
                $router = $instance->factoryRouter($routes, $groups);
                $handler = $instance->factoryHandler();
                $uri = $instance->factoryUri($request);
                $method = $instance->getMethod($request);
                $do = $handler->handle($router->match($uri, $method), $wp, $do);
                unset($method, $uri, $handler, $router, $groups, $routes, $instance);
                remove_all_filters('cortex.routes');
                remove_all_filters('cortex.groups');

                return $do;
            } catch (\Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    throw $e;
                }

                do_action('cortex.fail', $e);

                return $do;
            }

        }, 100, 2);

        return true;
    }

    /**
     * @return bool
     */
    public static function late()
    {
        return self::$late;
    }

    /**
     * @param  string        $name
     * @param  string|null   $abstract
     * @param  callable|null $default
     * @return object
     */
    private static function factoryByHook($name, $abstract = null, callable $default = null)
    {
        if (! is_string($name)) {
            throw new \InvalidArgumentException('Name of object to factory must be in a string.');
        }

        $thing = apply_filters("cortex.{$name}.instance", null);
        if (
            is_string($abstract)
            && (class_exists($abstract) || interface_exists($abstract))
            && ! is_subclass_of($thing, $abstract, true)
        ) {
            $thing = is_callable($default) ? $default() : null;
        }

        if (! is_object($thing)) {
            throw new \RuntimeException(sprintf('Impossible to factory "%s".', $name));
        }

        return $thing;
    }

    /**
     * @return \Brain\Cortex\Group\GroupCollectionInterface
     */
    private function factoryGroups()
    {
        /** @var \Brain\Cortex\Group\GroupCollectionInterface $groups */
        $groups = self::factoryByHook(
            'group-collection',
            GroupCollectionInterface::class,
            function () {
                return new GroupCollection();
            }
        );

        do_action('cortex.groups', $groups);

        return $groups;
    }

    /**
     * @return \Brain\Cortex\Route\RouteCollectionInterface
     */
    private function factoryRoutes()
    {
        /** @var \Brain\Cortex\Route\RouteCollectionInterface $routes */
        $routes = self::factoryByHook(
            'group-collection',
            RouteCollectionInterface::class,
            function () {
                return new PriorityRouteCollection();
            }
        );

        do_action('cortex.routes', $routes);

        return $routes;
    }

    /**
     * @param  \Brain\Cortex\Route\RouteCollectionInterface $routes
     * @param  \Brain\Cortex\Group\GroupCollectionInterface $groups
     * @return \Brain\Cortex\Router\RouterInterface
     */
    private function factoryRouter(
        RouteCollectionInterface $routes,
        GroupCollectionInterface $groups
    ) {
        /** @var \Brain\Cortex\Router\RouterInterface $router */
        $router = self::factoryByHook(
            'router',
            RouterInterface::class,
            function () use ($routes, $groups) {
                return new Router($routes, $groups);
            }
        );

        return $router;
    }

    /**
     * @return \Brain\Cortex\Router\ResultHandlerInterface
     */
    private function factoryHandler()
    {
        /** @var ResultHandlerInterface $handler */
        $handler = self::factoryByHook(
            'result-handler',
            ResultHandlerInterface::class,
            function () {
                return new ResultHandler();
            }
        );

        return $handler;
    }

    /**
     * @param  \Psr\Http\Message\RequestInterface $request
     * @return \Brain\Cortex\Uri\UriInterface
     * @internal param null|\Psr\Http\Message\UriInterface $psrUri
     */
    private function factoryUri(RequestInterface $request = null)
    {
        $psrUri = is_null($request) ? null : $request->getUri();

        /** @var UriInterface $uri */
        $uri = self::factoryByHook(
            'result-handler',
            UriInterface::class,
            function () use ($psrUri) {
                is_null($psrUri) and $psrUri = new PsrUri();

                return new WordPressUri($psrUri);
            }
        );

        return $uri;
    }

    /**
     * @param  \Psr\Http\Message\RequestInterface|null $request
     * @return string
     */
    private function getMethod(RequestInterface $request = null)
    {
        if ($request) {
            return $request->getMethod();
        }

        return empty($_SERVER['REQUEST_METHOD']) ? 'GET' : strtoupper($_SERVER['REQUEST_METHOD']);
    }
}
