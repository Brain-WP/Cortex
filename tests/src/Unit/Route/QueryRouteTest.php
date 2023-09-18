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
use Brain\Cortex\Route\QueryRoute;
use Brain\Cortex\Tests\TestCase;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class QueryRouteTest extends TestCase
{
    public function testArrayAccess()
    {
        $vars = function (array $vars) {
            return $vars;
        };

        $route = new QueryRoute('foo/bar', $vars, [
            'foo',
            1,
            'vars'     => [],
            'path'     => '/',
            'handler'  => '__return_true',
            'priority' => 0
        ]);

        static::assertFalse($route->offsetExists('foo'));
        static::assertTrue($route->offsetExists('id')); // id is auto generated
        static::assertSame(0, $route->offsetGet('priority'));
        static::assertSame('foo/bar', $route->offsetGet('path'));
        static::assertSame($vars, $route->offsetGet('vars'));
        static::assertInstanceOf(QueryVarsController::class, $route->offsetGet('handler'));

        unset($route['path']);
        $route['priority'] = 1;
        $route->offsetUnset('id');
        $route->offsetUnset('vars');

        static::assertNull($route->offsetGet('path'));
        static::assertSame(1, $route->offsetGet('priority'));
        static::assertTrue($route->offsetExists('id')); // id cannot be unset
        static::assertFalse($route->offsetExists('vars'));
    }

    public function testToArray()
    {
        $route = new QueryRoute('foo/bar', ['foo' => 'bar'], [
            'foo',
            1,
            'meh'      => 'meh',
            'priority' => 0
        ]);

        $array = $route->toArray();

        static::assertTrue(array_key_exists('id', $array));
        static::assertTrue(array_key_exists('handler', $array));
        static::assertTrue(array_key_exists('vars', $array));
        static::assertTrue(array_key_exists('priority', $array));
        static::assertFalse(array_key_exists('foo', $array));
        static::assertFalse(array_key_exists(0, $array));
        static::assertFalse(array_key_exists(1, $array));
        static::assertTrue(array_key_exists('meh', $array));
        static::assertSame('foo/bar', $array['path']);
        static::assertSame(['foo' => 'bar'], $array['vars']);
        static::assertInstanceOf(QueryVarsController::class, $array['handler']);
    }

    public function testId()
    {
        $route1 = new QueryRoute('foo/bar', []);
        $route2 = new QueryRoute('foo/bar', [], ['id' => 'route_2']);

        static::assertStringMatchesFormat('route_%s', $route1->id());
        static::assertSame('route_2', $route2->id());
    }
}
