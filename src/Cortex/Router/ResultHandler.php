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
        /** @var \Brain\Cortex\Router\MatchingResult $result */
        $result = apply_filters('cortex.match.done', $result, $wp, $doParseRequest);
        $handlerResult = $doParseRequest;

        if (!$result instanceof MatchingResult) {
            return $result;
        }

        /** @var \Brain\Cortex\Router\MatchingResult $result */
        if ($result->matched()) {
            $doParseRequest = false;
            $origHandler = $result->handler();
            $handler = $this->buildCallback($origHandler);
            $before = $this->buildCallback($result->beforeHandler());
            $after = $this->buildCallback($result->afterHandler());
            $template = $result->template();
            $vars = $result->vars();
            $matches = $result->matches();

            if (is_callable($template)) {
                $template = $template($vars, $wp, $matches);
            }

            (is_string($template) || $template === 'false') or $template = '';

            do_action('cortex.matched', $result, $wp);

            is_callable($before) and $before($vars, $wp, $template, $matches);
            is_callable($handler) and $handlerResult = $handler($vars, $wp, $template, $matches);
            is_callable($after) and $after($vars, $wp, $template, $matches);
            $this->setTemplate($template);

            do_action('cortex.matched-after', $result, $wp, $handlerResult);

            is_bool($handlerResult) and $doParseRequest = $handlerResult;
            $doParseRequest = apply_filters('cortex.do-parse-request', $doParseRequest);

            if (!$doParseRequest) {
                remove_filter('template_redirect', 'redirect_canonical');

                return false;
            }
        }

        do_action('cortex.result.done', $result, $wp, $handlerResult);

        return $doParseRequest;
    }

    /**
     * @param  mixed $handler
     * @return callable|null
     */
    private function buildCallback($handler)
    {
        $built = null;
        if (is_callable($handler)) {
            $built = $handler;
        }

        if (!$built && $handler instanceof ControllerInterface) {
            $built = function (array $vars, \WP $wp, $template) use ($handler) {
                return $handler->run($vars, $wp, $template);
            };
        }

        return $built;
    }

    /**
     * @param $template
     */
    private function setTemplate($template)
    {
        if (is_string($template) && $template) {
            $ext = apply_filters('cortex.default-template-extension', 'php');
            pathinfo($template, PATHINFO_EXTENSION) or $template .= '.' . ltrim($ext, '.');
            $template = is_file($template) ? $template : locate_template([$template], false);
            $template or $template = '';
        }

        if ($template === '' || !(is_string($template) || $template === false)) {
            return;
        }

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

        $returnTemplate = function () use ($template) {
            current_filter() === 'template_include' and remove_all_filters('template_include');

            return $template;
        };

        /*
         * If template is `false`, we return `true` on `"{$type}_template"` to speed up
         * `template-loader.php`, in fact, if template is `false` we are going to return `false` at
         * 'template_include' anyway, so no need to waste time looping all template types, even
         * because every type check for file(s) in filesystem, so it's a slow operation.
         */
        $template_setter = $template !== false ? $returnTemplate : '__return_true';

        array_walk($types, function ($type) use ($template_setter) {
            add_filter("{$type}_template", $template_setter);
        });

        add_filter('template_include', $returnTemplate, -1);
    }
}
