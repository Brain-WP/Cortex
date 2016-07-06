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
use Brain\Cortex\Group\GroupCollectionInterface;
use Brain\Cortex\Route\QueryRoute;
use Brain\Cortex\Route\RedirectRoute;
use Brain\Cortex\Route\RouteCollectionInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
class Routes
{
    /**
     * @param string $method
     */
    private static function checkTiming($method)
    {
        if (! Cortex::late() && ! did_action('parse_request')) {
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
     * @param  string         $path
     * @param  callable|array $query
     * @param  array          $options
     * @return \Brain\Cortex\Route\RouteInterface
     */
    public static function add($path, $query, array $options = [])
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
     * @param  string          $path
     * @param  string|callable $to
     * @param  int             $status
     * @param  bool            $external
     * @return \Brain\Cortex\Route\RedirectRoute
     */
    public static function redirect($path, $to, $status = 301, $external = false)
    {
        self::checkTiming(__METHOD__);

        $routeObj = new RedirectRoute($path, $to, [
            'redirect_status'   => $status,
            'redirect_external' => $external,
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
     * @param  string $id
     * @param  array  $group
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
