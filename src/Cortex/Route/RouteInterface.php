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
interface RouteInterface extends \ArrayAccess
{
    const PAGED_SINGLE     = 'paged_single';
    const PAGED_ARCHIVE    = 'paged_archive';
    const PAGED_UNPAGED    = '';
    const PAGED_SEARCH     = 'paged_archive';
    const PAGED_FRONT_PAGE = 'paged_archive';
    const NOT_PAGED        = 'not_paged';

    /**
     * @return string
     */
    public function id();

    /**
     * @return array
     */
    public function toArray();
}
