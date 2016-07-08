<?php
/*
    Plugin Name: Post Link Shortcodes
    Description: A collection of shortcodes for building links, images, or URLs to a given post/archive of any type.
    Version: 0.4.1
    Author: Evan Mattson
    Author URI: http://aaemnnost.tv
    Plugin URI: https://github.com/aaemnnosttv/post-link-shortcodes
    License: GPL2
*/

/*
    Copyright 2013  Evan Mattson  (email : me at aaemnnost dot tv)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


require_once( dirname(__FILE__) . '/inc/class-plugin.php' );
require_once( dirname(__FILE__) . '/inc/class-shortcode.php' );
/**
 * Initialize plugin!
 */
PostLinkShortcodes();


/**
 * Initializes the plugin and stores the global instance
 * @return PostLinkShortcodes
 */
function PostLinkShortcodes()
{
    static $plugin;
    if ( ! $plugin ) {
        $plugin = new PostLinkShortcodes();
        $plugin->setup_hooks();
    }

    return $plugin;
}

/**
 * Register a NEW shortcode as an alias of a PLS shortcode
 *
 * Optionally define default values for attributes &/or prefix/suffix them
 * to the attributes passed by the shortcode!
 *
 * Note: function arguments differ from add_shortcode! (with the exception of the first)
 *
 * @param  string	$tag 		name of shortcode to add
 * @param  string	$alias_of	tag of shortcode to "connect" to
 * @param  mixed 	$defaults 	array of default attributes => values
 *
 * Prefix / Suffix
 * these are always applied as they are additive
 *
 * Prefix a value:
 * +class => 'someclass ' with a shortcode that passes class="myclass"
 * will produce an html class attribute class="someclass myclass"
 *
 * Suffix a value:
 * class+ => ' someclass' with a shortcode that passes class="myclass"
 * will produce an html class attribute class="myclass someclass"
 *
 * Defaults (no prefix/suffix):
 * defined values that are added if there is no existing value for the attribute
 * passed shortcode values will override defined defaults completely
 */
function pls_add_shortcode_alias( $tag, $alias_of, $defaults = false )
{
    PostLinkShortcodes()->alias( $tag, $alias_of, $defaults );
}

/**
 * A notice to display in the admin if the installed version of PHP is too old
 */
function _pls_insufficient_php_version_notice()
{
    if ( ! current_user_can('activate_plugins') ) return;
    ?>
<div id="message" class="error">
    <p><strong>As of version 1.0.0, <em>Post Link Shortcodes</em> requires PHP 5.4 or above.</strong></p>
    <p><a href="http://php.net/supported-versions.php" target="_blank">See here for more information on current supported versions of PHP.</a></p>
</div>
    <?php
}
