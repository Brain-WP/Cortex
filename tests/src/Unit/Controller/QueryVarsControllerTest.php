<?php
/*
 * This file is part of the cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Tests\Unit\Controller;

use Brain\Cortex\Controller\QueryVarsController;
use Brain\Cortex\Tests\TestCase;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class QueryVarsControllerTest extends TestCase
{

    public function testRun()
    {
        $wp = \Mockery::mock('WP');
        $controller = new QueryVarsController();

        $result = $controller->run(['foo' => 'bar'], $wp);

        assertFalse($result);
        assertSame(['foo' => 'bar'], $wp->query_vars);
    }
}