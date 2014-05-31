## Cortex ##

Cortex is **routing system for WordPress** based on the well-known and affordable [**Symfony Routing Component**][1].

![Cortex][2]

[![Build Status](https://api.travis-ci.org/Giuseppe-Mazzapica/Cortex.svg)](https://travis-ci.org/Giuseppe-Mazzapica/Cortex)


Using Cortex is possible tell WordPress what to do when a specific url, or better, a specific class of urls, are visited by users.

It is a package (not full plugin) and makes use of [**Composer**][3] to be embedded in larger projects.

It is part of a [**The Brain WP Project**][4].

Introduction
------------

WordPress frontend workflow can be summarized in:

 1. an url trigger a query
 2. a query is connected to a template using template hierarchy
 3. query runs and results are shown using related template

However, when the wanted query become complex, that workflow doesn't work anymore. As example, let's assume we have an url like:

    example.com/products/featured/{cat_name}/{orderby}/{order}/
    
where `{cat_name}`, `{orderby}` and `{order}` are variable parts. Now we want that when an url like that is visited, query arguments used are:

    $args = array(
      'post_type' => 'products',
      'meta_query' => array(
        array( 'key' => 'featured' 'value' => '1' ),
        array( 'key' => 'in_stock' 'value' => '1' )
      ),
      'tax_query' => array(
        array(
          'taxonomy' => 'product_cat',
          'terms' => array( 'special', $cat_name ), // $cat_name taken from url
          'include_children' => false
        )
      ),
      'orderby' => $orderby, // $orderby taken from url
      'order' => $order // $order taken from url
    );

After that, we also want to use the template `'custom-products.php'` to display results.

Nothing special, just a common feature in an average e-commerce site.
    
However, to do this task, using WordPress core features we have to:

 1. Write a rewrite rule to handle the url
 2. Flush rewrite rules, via code or manually, visiting permalink setting page
 3. Filter `pre_get_posts` to add argument like `meta_query` and `tax_query` that can't be added via url. This is hard, because is not easy to recognize if the query object passed via `pre_get_posts` is the right one, i.e. is the one we have to modify. 
 4. Add a filter on `template_include` to use our custom template

So much code! Moreover, the whole logic is parted in different places (function that add rule, `pre_get_posts` hook, function for custom template, rules flushing...) This makes our code hard to mantain, read and debug.

Using Cortex, the **same** result is obtained using:

    Brain\Routes::add('/products/featured/{cat_name}/{orderby}/{order}')
        ->requirements( [ 'cat_name' => '[a-z]+', 'orderby' => 'name|date', 'order' => 'asc|desc' ] )
        ->template( 'custom-products.php' )
        ->query( function( $matches ) {
            return [
                'post_type' => 'products',
                'meta_query' => [
                    [ 'key' => 'featured', 'value' => '1' ],
                    [ 'key' => 'in_stock', 'value' => '1' ]
                ],
                'tax_query' => [
                    [
                        'taxonomy' => 'product_cat',
                        'terms' => [ 'special', $matches['cat_name'] ],
                        'include_children' => false
                    ]
                ],
                'orderby' => $matches['orderby'],
                'order' => strtoupper( $matches['order'] )
            ];
    });
    
It's far less code, the whole logic is in one place, everything is very readable, there is no need to flush rules and everything is perfectly compatible with WordPress core.

If you think this is awesome, you should know that is only a portion of what Cortex can do.

Requirements
------------

 - PHP 5.4+
 - Composer (to install)
 - WordPress 3.9 (it maybe works with earlier versions, but it's not tested and versions < 3.9 will never supported).

Installation
------------

You need Composer to install the package. It is hosted on Packagist, so the only thing needed is insert `"brain/cortex": "dev-master"` in your `composer.json` `require` object

    {
        "require": {
            "php": ">=5.4",
            "brain/cortex": "dev-master"
        }
    }

See [Composer documentation][7] on how to install Composer itself, and packages. 

Developers & Contributors
-------------------------

Package is open to contributors and pull requests. It comes with a set of unit tests written for PHPUnit suite. Please be sure all tests pass before submit a PR. To run tests, please install package in stand-alone mode (i.e 'vendor' folder is inside package folder).

License
-------
Cortex own code is licensed under GPLv2+. Through Composer, it install code from:

 - [Composer][8] (MIT)
 - [Brain][9] (GPLv2+)
 - [Amygdala][10] (GPLv2+)
 - [Striatum][11] (GPLv2+)
 - [Pimple][12] (MIT) - required by Brain -
 - [PHPUnit][13] (BSD-3-Clause) - only dev install -
 - [Mockery][14] (BSD-3-Clause) - only dev install -
 - [WP_Mock][15] (GPL-v2+) - only dev install -


  [1]: http://symfony.com/doc/current/components/routing/introduction.html
  [2]: https://googledrive.com/host/0Bxo4bHbWEkMscmJNYkx6YXctaWM/cortex.png
  [3]: https://getcomposer.org/
  [4]: http://giuseppe-mazzapica.github.io/Brain/
  [5]: http://en.wikipedia.org/wiki/Facade_pattern
  [6]: http://laravel.com/docs/facades
  [7]: https://getcomposer.org/doc/
  [8]: https://getcomposer.org/
  [9]: https://github.com/Giuseppe-Mazzapica/Brain
  [10]: https://github.com/Giuseppe-Mazzapica/Amygdala
  [11]: https://github.com/Giuseppe-Mazzapica/Striatum
  [12]: http://pimple.sensiolabs.org/
  [13]: http://phpunit.de/
  [14]: https://github.com/padraic/mockery
  [15]: https://github.com/10up/wp_mock
