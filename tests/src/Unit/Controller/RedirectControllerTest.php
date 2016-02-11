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

use Brain\Cortex\Controller\RedirectController;
use Brain\Cortex\Tests\TestCase;
use Brain\Monkey\WP\Actions;
use Brain\Monkey\Functions;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package cortex
 */
class RedirectControllerTest extends TestCase
{

    public function testRunDoNothingIfRedirectToIsInvalid()
    {
        Actions::expectAdded('cortex.exit.redirect')->never();
        Actions::expectFired('cortex.exit.redirect')->never();

        $wp = \Mockery::mock('WP');

        $controller = new RedirectController();

        assertTrue($controller->run(['redirect_to' => 'meh'], $wp));
    }

    public function testRunDefaultsToHomeUrlIfNoRedirectTo()
    {
        Actions::expectAdded('cortex.exit.redirect')->once();
        Actions::expectFired('cortex.exit.redirect')->once();
        Functions::when('home_url')->justReturn('http://www.example.com');
        Functions::expect('wp_redirect')->never();
        Functions::expect('wp_safe_redirect')->once()->with('http://www.example.com', 301);

        $wp = \Mockery::mock('WP');

        $controller = new RedirectController();

        assertTrue($controller->run([], $wp));
    }

    public function testRunAllStatusSettings()
    {
        Actions::expectAdded('cortex.exit.redirect')->once();
        Actions::expectFired('cortex.exit.redirect')->once();
        Functions::expect('wp_redirect')->once()->with('https://example.com', 307);
        Functions::expect('wp_safe_redirect')->never();

        $data = [
            'redirect_to' => 'https://example.com',
            'redirect_status' => 307,
            'redirect_external' => true
        ];

        $wp = \Mockery::mock('WP');

        $controller = new RedirectController();

        assertTrue($controller->run($data, $wp));
    }
}