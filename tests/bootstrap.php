<?php
// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once getenv('WP_PHPUNIT__DIR') . '/includes/functions.php';

tests_add_filter('muplugins_loaded', function () {
    require realpath(__DIR__ . '/../post-link-shortcodes.php');
});

/**
 * Register some custom post types for our tests
 */
tests_add_filter('init', function () {
    register_post_type('xyz', ['show_ui' => true]);
    register_post_type('secret', ['show_ui' => false]);

    $args = [
        'public'      => true,
        'show_ui'     => true,
        'has_archive' => true,
        'supports'    => ['title', 'editor', 'thumbnail']
    ];
    register_post_type('cpt-with-dashes', $args);
    register_post_type('cpt_with_underscores', $args);
});

// Start up the WP testing environment.
require getenv('WP_PHPUNIT__DIR') . '/includes/bootstrap.php';
