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

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
class Factory
{
    /**
     * @param  string        $name
     * @param  string|null   $abstract
     * @param  callable|null $default
     * @return object
     */
    public static function factoryByHook($name, $abstract = null, callable $default = null)
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
}
