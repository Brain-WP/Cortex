<?php
/*
 * This file is part of the cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Tests\Unit\Router;

use Brain\Cortex\Controller\ControllerInterface;
use Brain\Cortex\Router\MatchingResult;
use Brain\Cortex\Router\ResultHandler;
use Brain\Cortex\Tests\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\WP\Actions;
use Brain\Monkey\WP\Filters;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class ResultHandlerTest extends TestCase
{
    public function testHandleDoNothingIfNotMarched()
    {
        Actions::expectFired('cortex.matched')->never();

        $result = \Mockery::mock(MatchingResult::class);
        $result->shouldReceive('matched')->andReturn(false);

        $wp = \Mockery::mock('WP');

        $handler = new ResultHandler();

        static::assertTrue($handler->handle($result, $wp, true));
        static::assertFalse($handler->handle($result, $wp, false));
    }

    public function testHandleAllCallbacks()
    {
        $accumulator = [];

        Actions::expectFired('cortex.matched')->once();
        Filters::expectAdded('template_include')->never();
        Functions::when('remove_filter')->alias(function () use (&$accumulator) {
            $accumulator[] = func_get_args();
        });

        $before = function (array $vars, \WP $wp) use (&$accumulator) {
            $accumulator[] = $wp;
        };

        $handler = function (array $vars, \WP $wp) use (&$accumulator) {
            $accumulator[] = $vars;

            return false;
        };

        $after = \Mockery::mock(ControllerInterface::class);
        $after->shouldReceive('run')->once()->andReturnUsing(function () use (&$accumulator) {
            $accumulator[] = 'after';
        });

        $result = \Mockery::mock(MatchingResult::class);
        $result->shouldReceive('matched')->once()->andReturn(true);
        $result->shouldReceive('handler')->once()->andReturn($handler);
        $result->shouldReceive('beforeHandler')->once()->andReturn($before);
        $result->shouldReceive('afterHandler')->once()->andReturn($after);
        $result->shouldReceive('template')->once()->andReturnNull();
        $result->shouldReceive('vars')->once()->andReturn(['foo' => 'bar']);
        $result->shouldReceive('matches')->once()->andReturn([]);

        $wp = \Mockery::mock('WP');

        $expected = [
            $wp,                                        # returned by ResultHandler::beforeHandler()
            ['foo' => 'bar'],                           # returned by ResultHandler::handler()
            'after',                                    # returned by ResultHandler::afterHandler()
            ['template_redirect', 'redirect_canonical'], # passed to remove_filter()
        ];

        $handler = new ResultHandler();

        static::assertFalse($handler->handle($result, $wp, true));
        static::assertSame($expected, $accumulator);
    }

    public function testHandleTemplate()
    {
        Actions::expectFired('cortex.matched')->once();
        Filters::expectApplied('cortex.default-template-extension')->once()->andReturn('.mustache');
        Functions::expect('locate_template')
                 ->once()
                 ->with(['foo.mustache'], false)
                 ->andReturn('path/to/theme/foo.mustache');

        $hooks = [
            '404_template',
            'search_template',
            'front_page_template',
            'home_template',
            'archive_template',
            'taxonomy_template',
            'attachment_template',
            'single_template',
            'page_template',
            'singular_template',
            'category_template',
            'tag_template',
            'author_template',
            'date_template',
            'paged_template',
            'index_template',
        ];

        foreach ($hooks as $hook) {
            Filters::expectAdded($hook)->once();
        }

        Filters::expectAdded('template_include')->once()->with(\Mockery::type('Closure'), -1);

        $result = \Mockery::mock(MatchingResult::class);
        $result->shouldReceive('matched')->once()->andReturn(true);
        $result->shouldReceive('handler')->once()->andReturn(null);
        $result->shouldReceive('beforeHandler')->once()->andReturn(null);
        $result->shouldReceive('afterHandler')->once()->andReturn(null);
        $result->shouldReceive('template')->once()->andReturn('foo');
        $result->shouldReceive('vars')->once()->andReturn([]);
        $result->shouldReceive('matches')->once()->andReturn([]);

        $handler = new ResultHandler();

        $wp = \Mockery::mock('WP');

        static::assertTrue($handler->handle($result, $wp, true));
    }

    public function testHandleTemplateDoNothingIfNoTemplateFound()
    {
        Actions::expectFired('cortex.matched')->once();
        Filters::expectApplied('cortex.default-template-extension')->once()->andReturn('.mustache');
        Functions::expect('locate_template')
                 ->once()
                 ->with(['foo.mustache'], false)
                 ->andReturn(false);

        $hooks = [
            '404_template',
            'search_template',
            'front_page_template',
            'home_template',
            'archive_template',
            'taxonomy_template',
            'attachment_template',
            'single_template',
            'page_template',
            'singular_template',
            'category_template',
            'tag_template',
            'author_template',
            'date_template',
            'paged_template',
            'index_template',
        ];

        foreach ($hooks as $hook) {
            Filters::expectAdded($hook)->never();
        }

        Filters::expectAdded('template_include')->never();

        $result = \Mockery::mock(MatchingResult::class);
        $result->shouldReceive('matched')->once()->andReturn(true);
        $result->shouldReceive('handler')->once()->andReturn(null);
        $result->shouldReceive('beforeHandler')->once()->andReturn(null);
        $result->shouldReceive('afterHandler')->once()->andReturn(null);
        $result->shouldReceive('template')->once()->andReturn('foo');
        $result->shouldReceive('vars')->once()->andReturn([]);
        $result->shouldReceive('matches')->once()->andReturn([]);

        $handler = new ResultHandler();

        $wp = \Mockery::mock('WP');

        static::assertTrue($handler->handle($result, $wp, true));
    }
}
