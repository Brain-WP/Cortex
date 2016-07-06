<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Group;

use Brain\Cortex\Route\RouteInterface;

/**
 * Interface for GroupCollection objects.
 * Groups objects are the way to share common settings among different routes.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
interface GroupCollectionInterface
{
    /**
     * @param  \Brain\Cortex\Group\GroupInterface $group
     * @return \Brain\Cortex\Group\GroupCollectionInterface
     */
    public function addGroup(GroupInterface $group);

    /**
     * Merge group settings into a given route
     *
     * @param  \Brain\Cortex\Route\RouteInterface $route
     * @return \Brain\Cortex\Route\RouteInterface Edited route
     */
    public function mergeGroup(RouteInterface $route);
}
