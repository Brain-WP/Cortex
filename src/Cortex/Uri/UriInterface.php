<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Uri;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
interface UriInterface
{
    /**
     * @return string
     */
    public function scheme();

    /**
     * @return string
     */
    public function host();

    /**
     * @return string
     */
    public function path();

    /**
     * @return array
     */
    public function chunks();

    /**
     * @return array
     */
    public function vars();
}
