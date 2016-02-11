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
            'vars'     => [],
            'matched'  => false,
            'handler'  => null,
            'before'   => null,
            'after'    => null,
            'template' => null,
        ];

        $data = array_merge($defaults, array_change_key_case($data, CASE_LOWER));
        is_array($data['vars']) or $data['vars'] = [];
        $data['matched'] = (bool) filter_var($data['matched'], FILTER_VALIDATE_BOOLEAN);
        is_callable($data['handler']) or $data['handler'] = null;
        is_callable($data['before']) or $data['before'] = null;
        is_callable($data['after']) or $data['after'] = null;
        is_string($data['template']) or $data['template'] = null;

        $this->data = $data;
    }

    /**
     * @return array
     */
    public function vars()
    {
        return $this->data['vars'];
    }

    /**
     * @return bool
     */
    public function matched()
    {
        return $this->data['matched'];
    }

    /**
     * @return string|null
     */
    public function template()
    {
        return $this->data['template'];
    }

    /**
     * @return callable|null
     */
    public function handler()
    {
        return $this->data['handler'];
    }

    /**
     * @return callable|null
     */
    public function beforeHandler()
    {
        return $this->data['before'];
    }

    /**
     * @return callable|null
     */
    public function afterHandler()
    {
        return $this->data['after'];
    }
}
