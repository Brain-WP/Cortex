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

    public function __construct($from, $to, array $options, ControllerInterface $controller = null)
    {
        $status = empty($options['redirect_status']) ? 301 : $options['redirect_status'];
        in_array((int) $status, range(300, 308), true) or $status = 301;

        $ext = empty($options['redirect_external']) ? false : $options['redirect_external'];
        $ext = filter_var($ext, FILTER_VALIDATE_BOOLEAN);

        $vars = [
            'redirect_status'   => $status,
            'redirect_external' => $ext,
            'redirect_to'       => null,
        ];

        if (is_callable($to)) {
            $vars = function (array $vars) use ($to, $status, $ext) {
                $to = $to($vars);

                return [
                    'redirect_to'       => filter_var($to, FILTER_VALIDATE_URL) ? $to : null,
                    'redirect_status'   => $status,
                    'redirect_external' => $ext
                ];
            };
        } elseif (filter_var($to, FILTER_VALIDATE_URL)) {
            $vars['redirect_to'] = $to;
        }

        $args = [
            'path'    => $from,
            'vars'    => $vars,
            'handler' => $controller ?: new RedirectController(),
        ];

        isset($options['id']) and $args['id'] = $options['id'];
        isset($options['merge_query_string']) and $args['merge_query_string'] = $options['merge_query_string'];

        $this->route = new Route($args);
    }
}
