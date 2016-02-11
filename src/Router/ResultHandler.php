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
use Brain\Cortex\Controller\QueryVarsController;


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

            do_action('cortex.matched', $result);

            $handler = $this->buildCallback($result->handler());
            is_null($handler) and $handler = $this->buildCallback(new QueryVarsController());
            $before = $this->buildCallback($result->beforeHandler());
            $after = $this->buildCallback($result->afterHandler());
            $template = $result->template();
            $vars = $result->vars();

            is_callable($before) and $before($vars, $wp);
            $doParseRequest = $handler($vars, $wp);
            is_callable($after) and $after($vars, $wp, $doParseRequest);
            is_string($template) and $this->setTemplate($template);

            remove_filter('template_redirect', 'redirect_canonical');
        }

        return $doParseRequest;
    }

    /**
     * @param mixed $handler
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

        return null;
    }

    /**
     * @param $template
     */
    private function setTemplate($template)
    {
        pathinfo($template, PATHINFO_EXTENSION) or $template .= '.php';
        $template = is_file($template) ? $template : locate_template([$template], false);
        if (!$template) {
            return;
        }

        $setter = function() use($template) {
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
            'index'
        ];

        array_walk($types, function($type) use($setter) {
            add_filter("{$type}_template", $setter);
        });

        add_filter('template_include', function() {
            remove_all_filters('template_include');
        }, -1);
    }
}