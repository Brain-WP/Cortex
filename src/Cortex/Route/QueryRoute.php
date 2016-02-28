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
use Brain\Cortex\Controller\QueryVarsController;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
final class QueryRoute implements RouteInterface
{
    use DerivativeRouteTrait;

    /**
     * @var array
     */
    private $route;

    /**
     * QueryRoute constructor.
     *
     * @param string         $path
     * @param callable|array $queryBuilder
     * @param array          $options
     */
    public function __construct($path, $queryBuilder, array $options = [])
    {
        $options['path'] = $path;
        $options['vars'] = $queryBuilder;
        $handler = isset($options['handler']) && $options['handler'] instanceof ControllerInterface
            ? $options['handler']
            : new QueryVarsController();
        $options['handler'] = $handler;
        $default = isset($options['default_vars']) && is_array($options['default_vars'])
            ? $options['default_vars']
            : [];
        if (isset($options['default']) && is_array($options['default'])) {
            $options['default_vars'] = array_merge($options['default'], $default);
        }

        $this->route = new Route($options);
    }
}
