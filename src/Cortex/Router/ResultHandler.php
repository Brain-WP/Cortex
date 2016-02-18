<?php
/*
 * This file is part of the Cortex package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Cortex\Router;

use Brain\Cortex\Controller\ControllerInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Cortex
 */
final class ResultHandler implements ResultHandlerInterface
{
    /**
     * @inheritdoc
     */
    public function handle(MatchingResult $result, \WP $wp, $doParseRequest)
    {
        if ($result->matched()) {
            $handler = $this->buildCallback($result->handler());
            $before = $this->buildCallback($result->beforeHandler());
            $after = $this->buildCallback($result->afterHandler());
            $template = $result->template();
            $vars = $result->vars();

            do_action('cortex.matched', $result, $wp);

            is_callable($before) and $before($vars, $wp);
            is_callable($handler) and $doParseRequest = $handler($vars, $wp);
            is_callable($after) and $after($vars, $wp);
            (is_string($template) && $template) and $this->setTemplate($template);

            do_action('cortex.matched-after', $result, $wp, $doParseRequest);

            if (! apply_filters('cortex.do-parse-request', $doParseRequest)) {
                remove_filter('template_redirect', 'redirect_canonical');

                return false;
            }
        }

        return $doParseRequest;
    }

    /**
     * @param  mixed         $handler
     * @return callable|null
     */
    private function buildCallback($handler)
    {
        if (is_callable($handler)) {
            return $handler;
        }

        if ($handler instanceof ControllerInterface) {
            return function (array $vars, \WP $wp) use ($handler) {
                return $handler->run($vars, $wp);
            };
        }

        return;
    }

    /**
     * @param $template
     */
    private function setTemplate($template)
    {
        $ext = apply_filters('cortex.default-template-extension', 'php');
        pathinfo($template, PATHINFO_EXTENSION) or $template .= '.'.ltrim($ext, '.');
        $template = is_file($template) ? $template : locate_template([$template], false);
        if (! $template) {
            return;
        }

        $setter = function () use ($template) {
            return $template;
        };

        $types = [
            '404',
            'search',
            'front_page',
            'home',
            'archive',
            'taxonomy',
            'attachment',
            'single',
            'page',
            'singular',
            'category',
            'tag',
            'author',
            'date',
            'paged',
            'index',
        ];

        array_walk($types, function ($type) use ($setter) {
            add_filter("{$type}_template", $setter);
        });

        add_filter('template_include', function () {
            remove_all_filters('template_include');
        }, -1);
    }
}
