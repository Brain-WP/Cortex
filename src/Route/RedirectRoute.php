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

use Brain\Cortex\Controller\ControllerInterface;
use Brain\Cortex\Controller\RedirectController;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
final class RedirectRoute implements RouteInterface
{

    /**
     * @var array
     */
    private static $defaults = [
        'redirect_to'       => null,
        'redirect_status'   => null,
        'redirect_external' => null,
    ];

    /**
     * @var array
     */
    private $storage = [];

    /**
     * Route constructor.
     *
     * @param array                                        $data
     * @param \Brain\Cortex\Controller\ControllerInterface $controller
     */
    public function __construct(array $data, ControllerInterface $controller = null)
    {
        $id = is_string($data['id']) && $data['id'] ? $data['id'] : 'route_'.spl_object_hash($this);
        $storage['id'] = $id;
        isset($data['merge_query_string']) && $storage['merge_query_string'] = $data['merge_query_string'];
        $storage['vars'] = array_diff_key($data, self::$defaults);
        $storage['handler'] = $controller ? : new RedirectController();
        $this->storage = $storage;
    }

    /**
     * @inheritdoc
     */
    public function id()
    {
        return $this->storage['id'];
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return $this->storage;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->storage);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->storage[$offset] : null;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->storage[$offset] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->storage[$offset]);
        }
    }
}