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
            'meh'      => 'meh',
            'priority' => 0
        ]);

        assertFalse($route->offsetExists('foo'));
        assertNull($route->offsetGet('meh'));
        assertTrue($route->offsetExists('id')); // id is auto generated
        assertSame(0, $route->offsetGet('priority'));
        assertSame('foo/bar', $route->offsetGet('path'));
        assertSame($vars, $route->offsetGet('vars'));
        assertInstanceOf(QueryVarsController::class, $route->offsetGet('handler'));

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
        $route = new QueryRoute('foo/bar', ['foo' => 'bar'], [
            'foo',
            1,
            'meh'      => 'meh',
            'priority' => 0
        ]);

        $array = $route->toArray();

        assertTrue(array_key_exists('id', $array));
        assertTrue(array_key_exists('handler', $array));
        assertTrue(array_key_exists('vars', $array));
        assertTrue(array_key_exists('priority', $array));
        assertFalse(array_key_exists('foo', $array));
        assertFalse(array_key_exists(0, $array));
        assertFalse(array_key_exists(1, $array));
        assertFalse(array_key_exists('meh', $array));
        assertSame('foo/bar', $array['path']);
        assertSame(['foo' => 'bar'], $array['vars']);
        assertInstanceOf(QueryVarsController::class, $array['handler']);
    }

    public function testId()
    {
        $route1 = new QueryRoute('foo/bar', []);
        $route2 = new QueryRoute('foo/bar', [], ['id' => 'route_2']);

        assertStringMatchesFormat('route_%s', $route1->id());
        assertSame('route_2', $route2->id());
    }

}