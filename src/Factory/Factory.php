<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Factory;

use Brain\Cortex\Group\Group;
use Brain\Cortex\Group\GroupInterface;
use Brain\Cortex\Route\Route;
use Brain\Cortex\Route\RouteInterface;


/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
class Factory
{

    /**
     * @param string        $name
     * @param string|null   $abstract
     * @param callable|null $default
     * @return object
     */
    public static function factoryByHook($name, $abstract = null, callable $default = null)
    {
        if ( ! is_string($name)) {
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

        if ( ! is_object($thing)) {
            throw new \RuntimeException(sprintf('Impossible to factory "%s".', $name));
        }

        return $thing;
    }

    /**
     * @param array                                   $data
     * @param \Brain\Cortex\Route\RouteInterface|null $base
     * @param string|null                             $class
     * @return \Brain\Cortex\Route\RouteInterface
     */
    public static function factoryRoute(array $data, RouteInterface $base = null, $class = null)
    {
        $class = apply_filters("cortex.route.class", $class);
        if ( ! is_string($class) || ! is_subclass_of($class, RouteInterface::class, true)) {
            $class = Route::class;
        }

        $data = is_null($base) ? $data : array_merge($base->toArray(), $data);

        $route = new $class($data);
        $filtered = apply_filters("cortex.route.created", $route);
        $filtered instanceof RouteInterface and $route = $filtered;

        return $route;
    }

    /**
     * @param array                                   $data
     * @param \Brain\Cortex\Group\GroupInterface|null $base
     * @param string|null                             $class
     * @return \Brain\Cortex\Group\GroupInterface
     */
    public static function factoryGroup(array $data, GroupInterface $base = null, $class = null)
    {
        $class = apply_filters("cortex.route.class", $class);
        if ( ! is_string($class) || ! is_subclass_of($class, GroupInterface::class, true)) {
            $class = Group::class;
        }

        $data = is_null($base) ? $data : array_merge($base->toArray(), $data);

        $group = new $class($data);
        $filtered = apply_filters("cortex.group.created", $group);
        $filtered instanceof RouteInterface and $group = $filtered;

        return $group;
    }

}