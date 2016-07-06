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
     * RedirectRoute constructor.
     *
     * @param string $from
     * @param string $to
     * @param array  $options
     */
    public function __construct($from, $to, array $options = [])
    {
        list($vars, $options) = $this->parseOptions($options);

        $options['vars'] = is_callable($to)
            ? $this->redirectToFromCallback($to, $vars)
            : array_merge($vars, ['redirect_to' => $this->redirectToFromString($to, $vars)]);

        $options['path'] = $from;
        $handler = isset($options['handler']) && $options['handler'] instanceof ControllerInterface
            ? $options['handler']
            : new RedirectController();
        $options['handler'] = $handler;

        $this->route = new Route($options);
    }

    /**
     * @param  array $options
     * @return array
     */
    private function parseOptions(array $options)
    {
        $status = empty($options['redirect_status']) ? 301 : $options['redirect_status'];
        in_array((int)$status, range(300, 308), true) or $status = 301;

        $external = empty($options['redirect_external']) ? false : $options['redirect_external'];

        $vars = [
            'redirect_status'   => $status,
            'redirect_external' => filter_var($external, FILTER_VALIDATE_BOOLEAN),
            'redirect_to'       => null,
        ];

        return [$vars, array_diff_key($options, $vars)];
    }

    /**
     * @param  callable $to
     * @param  array    $vars
     * @return \Closure
     */
    private function redirectToFromCallback(callable $to, array $vars)
    {
        return function (array $args) use ($to, $vars) {
            $vars['redirect_to'] = $this->redirectToFromString($to($args), $vars);

            return $vars;
        };
    }

    /**
     * @param  string $url
     * @param  array  $vars
     * @return string
     */
    private function redirectToFromString($url, array $vars)
    {
        if (! is_string($url)) {
            return '';
        }

        $url = filter_var($url, FILTER_SANITIZE_URL);
        if (empty($url)) {
            return '';
        }

        $valid = filter_var($url, FILTER_VALIDATE_URL);

        if ($vars['redirect_external']) {
            return $valid ? $url : '';
        }

        return $valid ? $url : home_url($url);
    }
}
