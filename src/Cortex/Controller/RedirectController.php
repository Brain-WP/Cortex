<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Controller;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
final class RedirectController implements ControllerInterface
{
    /**
     * @codeCoverageIgnore
     */
    public static function doExit()
    {
        exit();
    }

    /**
     * @inheritdoc
     */
    public function run(array $vars, \WP $wp, $template = '')
    {
        $to = empty($vars['redirect_to']) ? home_url() : $vars['redirect_to'];

        if (filter_var($to, FILTER_VALIDATE_URL)) {
            $status = empty($vars['redirect_status']) ? 301 : $vars['redirect_status'];
            in_array((int)$status, range(300, 308), true) or $status = 301;
            $external = empty($vars['redirect_external']) ? false : $vars['redirect_external'];
            /** @var callable $cb */
            $cb = filter_var($external, FILTER_VALIDATE_BOOLEAN)
                ? 'wp_redirect'
                : 'wp_safe_redirect';
            $cb($to, $status);

            add_action('cortex.exit.redirect', [__CLASS__, 'doExit'], 100);
            do_action('cortex.exit.redirect');
        }

        return true;
    }
}
