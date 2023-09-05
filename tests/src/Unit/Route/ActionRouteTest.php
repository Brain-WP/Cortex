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

use Brain\Cortex\Route\ActionRoute;
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
            'priority' => 0
        ]);

        static::assertFalse($route->offsetExists('foo'));
        static::assertTrue($route->offsetExists('id')); // id is auto generated
        static::assertSame(0, $route->offsetGet('priority'));
        static::assertSame('foo/bar', $route->offsetGet('path'));
        static::assertSame([], $route->offsetGet('vars'));
        static::assertInternalType('callable', $route->offsetGet('handler'));

        unset($route['path']);
        $route['priority'] = 1;
        $route->offsetUnset('id');
        $route->offsetUnset('vars');

        static::assertNull($route->offsetGet('path'));
        static::assertSame(1, $route->offsetGet('priority'));
        static::assertTrue($route->offsetExists('id')); // id cannot be unset
        static::assertFalse($route->offsetExists('vars'));
    }
}
