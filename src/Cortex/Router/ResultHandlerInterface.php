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
interface ResultHandlerInterface
{
    /**
     * @param  \Brain\Cortex\Router\MatchingResult $result
     * @param  \WP                                 $wp
     * @param  bool                                $doParseRequest
     * @return bool
     */
    public function handle(MatchingResult $result, \WP $wp, $doParseRequest);
}
