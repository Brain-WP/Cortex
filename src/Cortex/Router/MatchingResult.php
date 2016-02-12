<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Router;

use Brain\Cortex\Controller\ControllerInterface as Controller;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
class MatchingResult
{
    /**
     * @var array
     */
    private $data;

    /**
     * MatchingResult constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $defaults = [
            'route'    => null,
            'path'     => null,
            'vars'     => null,
            'handler'  => null,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $this->data = array_merge($defaults, array_change_key_case($data, CASE_LOWER));
    }

    /**
     * @return string
     */
    public function route()
    {
        return is_string($this->data['route']) ? $this->data['route'] : '';
    }

    /**
     * @return string
     */
    public function matchedPath()
    {
        return is_string($this->data['path']) ? $this->data['path'] : '';
    }

    /**
     * @return array
     */
    public function vars()
    {
        return is_array($this->data['vars']) ? $this->data['vars'] : [];
    }

    /**
     * @return bool
     */
    public function matched()
    {
        return $this->route() && $this->matchedPath();
    }

    /**
     * @return string
     */
    public function template()
    {
        return is_string($this->data['template']) ? $this->data['template'] : '';
    }

    /**
     * @return callable|\Brain\Cortex\Controller\ControllerInterface|null
     */
    public function handler()
    {
        if (is_callable($this->data['handler']) || $this->data['handler'] instanceof Controller) {
            return $this->data['handler'];
        }
    }

    /**
     * @return callable|\Brain\Cortex\Controller\ControllerInterface|null
     */
    public function beforeHandler()
    {
        if (is_callable($this->data['before']) || $this->data['before'] instanceof Controller) {
            return $this->data['before'];
        }
    }

    /**
     * @return callable|\Brain\Cortex\Controller\ControllerInterface|null
     */
    public function afterHandler()
    {
        if (is_callable($this->data['after']) || $this->data['after'] instanceof Controller) {
            return $this->data['after'];
        }
    }
}
