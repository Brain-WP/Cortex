<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Tests;

use Andrew\StaticProxy;
use Brain\Cortex;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
class TestCaseFunctional extends TestCase
{

    protected function tearDown()
    {
        $proxy = new StaticProxy(Cortex::class);
        /** @noinspection PhpUndefinedFieldInspection */
        $proxy->booted = false;
        /** @noinspection PhpUndefinedFieldInspection */
        $proxy->late = false;

        parent::tearDown();
    }
}
