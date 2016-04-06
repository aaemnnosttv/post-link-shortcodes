<?php
/**
 * PHPUnit bootstrap file
 *
 * @package post-link-shortcodes
 */

if (! $_tests_dir = getenv('WP_TESTS_DIR')) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once __DIR__ . '/trait-shortcode-assertions.php';

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
tests_add_filter('muplugins_loaded', function() {
	require realpath(__DIR__ . '/../post-link-shortcodes.php');
});

/**
 * Register some custom post types for our tests
 */
tests_add_filter('init', function () {
    register_post_type('xyz', ['show_ui' => true]);
    register_post_type('secret', ['show_ui' => false]);

    $args = ['public' => true, 'show_ui' => true, 'has_archive' => true];
    register_post_type('cpt-with-dashes', $args);
    register_post_type('cpt_with_underscores', $args);
});

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
