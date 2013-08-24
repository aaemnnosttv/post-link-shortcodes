<?php
/*
	Plugin Name: Post Link Shortcodes
	Description: A plugin for dynamically adding a collection of shortcodes for building links to a given post/archive (of any type) or simply returning the requested URL!
	Version: 0.2
	Author: Evan Mattson
	Author URI: http://wp.evanmattson.com
	Plugin URI: https://github.com/aaemnnosttv/Post-Link-Shortcodes
	License: GPL2
*/

/*
	Copyright 2012  Evan Mattson  (email : me at evanmattson dot com)

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

require_once( 'class-plugin.php' );
require_once( 'class-shortcode.php' );


/**
 * Initialize the plugin after post types are registered
 */
function init_post_link_shortcodes()
{
	new PostLinkShortcodes;
}
add_action( 'init', 'init_post_link_shortcodes', 500 );
