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
    use DerivativeRouteTrait;

    /**
     * @var \Brain\Cortex\Route\Route
     */
    private $route;

    /**
     * Route constructor.
     *
     * @param array                                        $data
     * @param \Brain\Cortex\Controller\ControllerInterface $controller
     */
    public function __construct(array $data, ControllerInterface $controller = null)
    {
        $args = [
            'path'    => empty($data['path']) ? null : $data['path'],
            'vars'    => [
                'redirect_to'       => empty($data['redirect_to']) ? null : $data['redirect_to'],
                'redirect_status'   => empty($data['redirect_status']) ? 301 : $data['redirect_status'],
                'redirect_external' => empty($data['redirect_external']) ? false : $data['redirect_external'],
            ],
            'handler' => $controller ?: new RedirectController(),
        ];

        isset($data['id']) and $args['id'] = $data['id'];
        isset($data['merge_query_string']) and $args['merge_query_string'] = $data['merge_query_string'];

        $this->route = new Route($args);
    }
}
