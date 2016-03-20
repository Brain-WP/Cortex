<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Controller;

/**
 * Interface for controllers.
 *
 * Controllers runs when a route match and can be defined per route.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
interface ControllerInterface
{
    /**
     * @param  array  $vars
     * @param  \WP    $wp
     * @param  string $template
     * @return bool
     */
    public function run(array $vars, \WP $wp, $template = '');
}
