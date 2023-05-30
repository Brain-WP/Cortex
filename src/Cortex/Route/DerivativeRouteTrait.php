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
 *
 * @property RouteInterface $route
 */
trait DerivativeRouteTrait
{
    /**
     * @see RouteInterface::id()
     */
    public function id()
    {
        return $this->route->id();
    }

    /**
     * @see RouteInterface::toArray()
     */
    public function toArray(): array
    {
        return $this->route->toArray();
    }

    /**
     * @see RouteInterface::offsetExists()
     * @param  string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->route->offsetExists($offset);
    }

    /**
     * @see RouteInterface::offsetGet()
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->route->offsetGet($offset);
    }

    /**
     * @see RouteInterface::offsetSet()
     * @param string $offset
     * @param mixed  $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->route->offsetSet($offset, $value);
    }

    /**
     * @see RouteInterface::offsetUnset()
     * @param string $offset
     */
    public function offsetUnset($offset): void
    {
        $this->route->offsetUnset($offset);
    }
}
