<?php
/*
 * This file is part of the cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Tests\Unit\Group;

use Brain\Cortex\Group\Group;
use Brain\Cortex\Tests\TestCase;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class GroupTest extends TestCase
{

    public function testIdWhenGiven()
    {
        $group = new Group(['id' => 'test_me']);

        assertSame('test_me', $group->id());
    }

    public function testIdWhenDefault()
    {
        $group = new Group([]);

        assertStringMatchesFormat('group_%s', $group->id());
    }

    public function testToArrayStripsInvalid()
    {
        $group = new Group([
            'foo',
            1,
            'vars'     => [],
            'group'    => 'nested',
            'path'     => '/',
            'handler'  => '__return_true',
            'meh'      => 'meh',
            'priority' => 0,
            'id'       => 'test_me'
        ]);

        $expected = [
            'vars'     => [],
            'path'     => '/',
            'handler'  => '__return_true',
            'priority' => 0,
            'id'       => 'test_me'
        ];

        assertSame($expected, $group->toArray());
    }

    public function testArrayAccess()
    {
        $group = new Group([
            'foo',
            1,
            'vars'     => [],
            'path'     => '/',
            'handler'  => '__return_true',
            'meh'      => 'meh',
            'priority' => 0
        ]);

        assertFalse($group->offsetExists('foo'));
        assertNull($group->offsetGet('meh'));
        assertTrue($group->offsetExists('id')); // id is auto generated
        assertTrue($group->offsetExists('vars'));
        assertSame('/', $group->offsetGet('path'));
        assertSame(0, $group->offsetGet('priority'));

        unset($group['path']);
        $group['priority'] = 1;
        $group->offsetUnset('id');
        $group->offsetUnset('vars');

        assertNull($group->offsetGet('path'));
        assertSame(1, $group->offsetGet('priority'));
        assertTrue($group->offsetExists('id')); // id cannot be unset
        assertFalse($group->offsetExists('vars'));
        assertSame('__return_true', $group['handler']);
    }

}