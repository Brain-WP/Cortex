<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Tests;

use Brain\Monkey;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $wp_user_logged = true;

    protected function setUp()
    {
        parent::setUp();
        Monkey::setUpWP();
        Monkey\Functions::when('is_user_logged_in')->alias(function() {
            return $this->wp_user_logged;
        });
    }

    protected function tearDown()
    {
        $this->loginUser();
        Monkey::tearDownWP();
        parent::tearDown();
    }

    protected function loginUser()
    {
        $this->wp_user_logged = TRUE;
    }

    protected function logoutUser()
    {
        $this->wp_user_logged = FALSE;
    }
}
