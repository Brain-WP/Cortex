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
interface RouteCollectionInterface extends \Iterator, \Countable
{
    /**
     * @param  \Brain\Cortex\Route\RouteInterface $route
     * @return \Brain\Cortex\Route\RouteCollectionInterface
     */
    public function addRoute(RouteInterface $route);
}
