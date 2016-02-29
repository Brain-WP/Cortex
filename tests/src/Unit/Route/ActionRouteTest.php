<?php
/*
 * This file is part of the cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Tests\Unit\Route;

use Brain\Cortex\Controller\QueryVarsController;
use Brain\Cortex\Route\ActionRoute;
use Brain\Cortex\Route\QueryRoute;
use Brain\Cortex\Tests\TestCase;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class ActionRouteTest extends TestCase
{
    public function testArrayAccess()
    {
        $handler = function (array $vars) {
            return $vars;
        };

        $route = new ActionRoute('foo/bar', $handler, [
            'foo',
            1,
            'vars'     => [],
            'path'     => '/',
            'meh'      => 'meh',
            'priority' => 0
        ]);

        assertFalse($route->offsetExists('foo'));
        assertNull($route->offsetGet('meh'));
        assertTrue($route->offsetExists('id')); // id is auto generated
        assertSame(0, $route->offsetGet('priority'));
        assertSame('foo/bar', $route->offsetGet('path'));
        assertSame([], $route->offsetGet('vars'));
        assertInternalType('callable', $route->offsetGet('handler'));

        unset($route['path']);
        $route['priority'] = 1;
        $route->offsetUnset('id');
        $route->offsetUnset('vars');

        assertNull($route->offsetGet('path'));
        assertSame(1, $route->offsetGet('priority'));
        assertTrue($route->offsetExists('id')); // id cannot be unset
        assertFalse($route->offsetExists('vars'));
    }
}
