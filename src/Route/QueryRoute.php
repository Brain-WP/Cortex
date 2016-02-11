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
        $options['handler'] = new QueryVarsController();

        $this->route = new Route($options);
    }


}