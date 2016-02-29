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
final class ActionRoute implements RouteInterface
{
    use DerivativeRouteTrait;

    /**
     * @var array
     */
    private $route;

    /**
     * QueryRoute constructor.
     *
     * @param string   $path
     * @param callable $action
     * @param array    $options
     */
    public function __construct($path, callable $action, array $options = [])
    {
        $options['path'] = $path;
        $options['handler'] = $action;

        $this->route = new Route($options);
    }
}
