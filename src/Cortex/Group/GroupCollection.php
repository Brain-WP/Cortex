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
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
final class GroupCollection implements GroupCollectionInterface
{
    /**
     * @var array
     */
    private $groups = [];

    /**
     * @inheritdoc
     */
    public function addGroup(GroupInterface $group)
    {
        $this->groups[$group->id()] = $group;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function mergeGroup(RouteInterface $route)
    {
        $groups = $route->offsetGet('group');
        if (empty($groups)) {
            return $route;
        }

        $data = array_reduce((array)$groups, function (array $data, $group) {
            if (is_string($group) && array_key_exists($group, $this->groups)) {
                /** @var \Brain\Cortex\Group\GroupInterface $groupObj */
                $groupObj = $this->groups[$group];
                $data = array_merge($data, $groupObj->toArray());
                unset($data['id']);
            }

            return $data;
        }, []);

        $clone = clone $route;
        array_walk($data, function ($value, $key) use (&$clone) {
            $skip =
                in_array($key, ['id', 'group'], true)
                || ($clone->offsetExists($key) && ! is_null($clone->offsetGet($key)));
            $skip or $clone->offsetSet($key, $value);
        });

        return $clone;
    }
}
