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

        assertFalse($route->offsetExists('foo'));
        assertNull($route->offsetGet('meh'));
        assertTrue($route->offsetExists('id')); // id is auto generated
        assertSame(0, $route->offsetGet('priority'));
        assertSame('/', $route->offsetGet('path'));
        assertSame($vars, $route->offsetGet('vars'));
        assertSame('__return_true', $route->offsetGet('handler'));

        unset($route['path']);
        $route['priority'] = 1;
        $route->offsetUnset('id');
        $route->offsetUnset('vars');

        assertNull($route->offsetGet('path'));
        assertSame(1, $route->offsetGet('priority'));
        assertTrue($route->offsetExists('id')); // id cannot be unset
        assertFalse($route->offsetExists('vars'));
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

        assertTrue(array_key_exists('id', $array));
        assertTrue(array_key_exists('vars', $array));
        assertTrue(array_key_exists('priority', $array));
        assertFalse(array_key_exists('handler', $array));
        assertFalse(array_key_exists('foo', $array));
        assertFalse(array_key_exists(0, $array));
        assertFalse(array_key_exists(1, $array));
        assertFalse(array_key_exists('meh', $array));
        assertSame('foo/bar', $array['path']);
        assertSame(['foo' => 'bar'], $array['vars']);
        assertStringMatchesFormat('route_%s', $array['id']);
    }

    public function testId()
    {
        $route1 = new Route([]);
        $route2 = new Route(['id' => 'route_2']);

        assertStringMatchesFormat('route_%s', $route1->id());
        assertSame('route_2', $route2->id());
    }
}
