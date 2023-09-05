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

use Brain\Cortex\Route\Route;
use Brain\Cortex\Tests\TestCase;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class RouteTest extends TestCase
{
    public function testArrayAccess()
    {
        $vars = function (array $vars) {
            return $vars;
        };

        $route = new Route([
            'foo',
            1,
            'vars'     => $vars,
            'path'     => '/',
            'handler'  => '__return_true',
            'meh'      => 'meh',
            'priority' => 0,
        ]);

        static::assertFalse($route->offsetExists('foo'));
        static::assertSame('meh', $route->offsetGet('meh'));
        static::assertTrue($route->offsetExists('id')); // id is auto generated
        static::assertSame(0, $route->offsetGet('priority'));
        static::assertSame('/', $route->offsetGet('path'));
        static::assertSame($vars, $route->offsetGet('vars'));
        static::assertSame('__return_true', $route->offsetGet('handler'));

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
        $route = new Route([
            'foo',
            1,
            'path'     => 'foo/bar',
            'vars'     => ['foo' => 'bar'],
            'meh'      => ['foo' => 'bar'],
            'priority' => 0,
        ]);

        $array = $route->toArray();

        static::assertTrue(array_key_exists('id', $array));
        static::assertTrue(array_key_exists('vars', $array));
        static::assertTrue(array_key_exists('priority', $array));
        static::assertFalse(array_key_exists('handler', $array));
        static::assertFalse(array_key_exists('foo', $array));
        static::assertFalse(array_key_exists(0, $array));
        static::assertFalse(array_key_exists(1, $array));
        static::assertSame(['foo' => 'bar'], $array['meh']);
        static::assertSame('foo/bar', $array['path']);
        static::assertSame(['foo' => 'bar'], $array['vars']);
        static::assertStringMatchesFormat('route_%s', $array['id']);
    }

    public function testId()
    {
        $route1 = new Route([]);
        $route2 = new Route(['id' => 'route_2']);

        static::assertStringMatchesFormat('route_%s', $route1->id());
        static::assertSame('route_2', $route2->id());
    }
}
