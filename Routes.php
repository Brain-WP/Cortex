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

use Brain\Cortex\Group\Group;
use Brain\Cortex\Group\GroupCollection;
use Brain\Cortex\Group\GroupCollectionInterface;
use Brain\Cortex\Route\PriorityRouteCollection;
use Brain\Cortex\Route\QueryRoute;
use Brain\Cortex\Route\RedirectRoute;
use Brain\Cortex\Route\RouteCollectionInterface;
use Brain\Cortex\Router\ResultHandler;
use Brain\Cortex\Router\ResultHandlerInterface;
use Brain\Cortex\Router\Router;
use Brain\Cortex\Router\RouterInterface;
use Brain\Cortex\Uri\PsrUri;
use Brain\Cortex\Uri\WordPressUri;
use Brain\Cortex\Factory\Factory;
use Psr\Http\Message\UriInterface as PsrUriInterface;
use Brain\Cortex\Uri\UriInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
class Routes
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
     * @param \Psr\Http\Message\UriInterface|null $psrUri
     * @return bool
     */
    public static function boot(PsrUriInterface $psrUri = null)
    {
        if (self::$booted) {
            return false;
        }

        self::checkTiming(__METHOD__);

        add_filter('do_parse_request', function ($do, \WP $wp) use($psrUri) {

            self::$late = true;

            try {

                /** @var \Brain\Cortex\Group\GroupCollectionInterface $groups */
                $groups = Factory::factoryByHook(
                    'group-collection',
                    GroupCollectionInterface::class,
                    function () {
                        return new GroupCollection();
                    }
                );

                do_action('cortex.groups', $groups);

                /** @var \Brain\Cortex\Route\RouteCollectionInterface $routes */
                $routes = Factory::factoryByHook(
                    'group-collection',
                    RouteCollectionInterface::class,
                    function () {
                        return new PriorityRouteCollection();
                    }
                );

                do_action('cortex.routes', $routes);

                /** @var \Brain\Cortex\Router\RouterInterface $router */
                $router = Factory::factoryByHook(
                    'router',
                    RouterInterface::class,
                    function () use ($routes, $groups) {
                        return new Router($routes, $groups);
                    }
                );

                /** @var ResultHandlerInterface $handler */
                $handler = Factory::factoryByHook(
                    'result-handler',
                    ResultHandlerInterface::class,
                    function () {
                        return new ResultHandler();
                    }
                );

                /** @var UriInterface $uri */
                $uri = Factory::factoryByHook(
                    'result-handler',
                    UriInterface::class,
                    function () use ($psrUri) {
                        $psrUri instanceof PsrUriInterface or $psrUri = new PsrUri();

                        return new WordPressUri($psrUri);
                    }
                );

                return $handler->handle($router->match($uri), $wp, $do);

            } catch (\Exception $e) {

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    throw $e;
                }

                do_action('cortex.fail', $e);

                return $do;
            }

        }, 100, 2);

        self::$booted = true;

        return true;
    }

    /**
     * @param string $method
     */
    private static function checkTiming($method)
    {
        if ( ! self::$late && ! did_action('parse_request')) {
            return;
        }

        $exception = new \BadMethodCallException(
            sprintf('%s must be called before "do_parse_request".', $method)
        );

        if (defined('WP_DEBUG') && WP_DEBUG) {
            throw $exception;
        }

        do_action('cortex.fail', $exception);
    }

    /**
     * @param string $path
     * @param array  $query
     * @param array  $options
     * @return \Brain\Cortex\Route\RouteInterface
     */
    public static function add($path, $query, $options = [])
    {
        self::checkTiming(__METHOD__);

        $routeObj = new QueryRoute($path, $query, $options);

        add_action(
            'cortex.routes',
            function (RouteCollectionInterface $collection) use ($routeObj) {
                $collection->addRoute($routeObj);
            }
        );

        return $routeObj;
    }

    /**
     * @param string $path
     * @param string $to
     * @param int    $status
     * @param bool   $allowExternal
     * @return \Brain\Cortex\Route\RedirectRoute
     */
    public static function redirect($path, $to, $status = 301, $allowExternal = false)
    {
        self::checkTiming(__METHOD__);

        $routeObj = new RedirectRoute([
            'path'              => $path,
            'redirect_to'       => $to,
            'redirect_status'   => $status,
            'redirect_external' => $allowExternal
        ]);

        add_action(
            'cortex.routes',
            function (RouteCollectionInterface $collection) use ($routeObj) {
                $collection->addRoute($routeObj);
            }
        );

        return $routeObj;
    }

    /**
     * @param string $id
     * @param array  $group
     * @return \Brain\Cortex\Group\GroupInterface
     */
    public static function group($id, array $group)
    {
        self::checkTiming(__METHOD__);

        $group['id'] = $id;
        $groupObj = new Group($group);

        add_action(
            'cortex.groups',
            function (GroupCollectionInterface $collection) use ($groupObj) {
                $collection->addGroup($groupObj);
            }
        );

        return $groupObj;
    }

}