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

        static::assertSame('test_me', $group->id());
    }

    public function testIdWhenDefault()
    {
        $group = new Group([]);

        static::assertStringMatchesFormat('group_%s', $group->id());
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
            'priority' => 0,
            'id'       => 'test_me',
        ]);

        $expected = [
            'vars'     => [],
            'path'     => '/',
            'handler'  => '__return_true',
            'priority' => 0,
            'id'       => 'test_me',
        ];

        $actual = $group->toArray();

        ksort($expected);
        ksort($actual);

        static::assertSame($expected, $actual);
    }

    public function testArrayAccess()
    {
        $group = new Group([
            'foo',
            1,
            'vars'     => [],
            'path'     => '/',
            'handler'  => '__return_true',
            'priority' => 0,
        ]);

        static::assertFalse($group->offsetExists('foo'));
        static::assertTrue($group->offsetExists('id')); // id is auto generated
        static::assertTrue($group->offsetExists('vars'));
        static::assertSame('/', $group->offsetGet('path'));
        static::assertSame(0, $group->offsetGet('priority'));

        unset($group['path']);
        $group['priority'] = 1;
        $group->offsetUnset('id');
        $group->offsetUnset('vars');

        static::assertNull($group->offsetGet('path'));
        static::assertSame(1, $group->offsetGet('priority'));
        static::assertTrue($group->offsetExists('id')); // id cannot be unset
        static::assertFalse($group->offsetExists('vars'));
        static::assertSame('__return_true', $group['handler']);
    }
}
