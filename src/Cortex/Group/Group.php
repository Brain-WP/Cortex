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

use Brain\Cortex\Route\Route;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
final class Group implements GroupInterface
{
    /**
     * @var \Brain\Cortex\Route\Route
     */
    private $route;

    /**
     * Group constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        array_change_key_case($data, CASE_LOWER);
        if (isset($data['group'])) {
            unset($data['group']);
        }

        $data['id'] = ! empty($data['id']) && is_string($data['id'])
            ? $data['id']
            : 'group_'.spl_object_hash($this);

        $this->route = new Route($data);
    }

    /**
     * @inheritdoc
     */
    public function id()
    {
        return $this->route->id();
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return $this->route->toArray();
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $this->route->offsetExists($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->route->offsetGet($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->route->offsetSet($offset, $value);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        $this->route->offsetUnset($offset);
    }
}
