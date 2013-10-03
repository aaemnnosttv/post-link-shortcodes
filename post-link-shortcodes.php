<?php
/*
	Plugin Name: Post Link Shortcodes
	Description: A plugin for dynamically adding a collection of shortcodes for building links to a given post/archive (of any type) or simply returning the requested URL!
	Version: 0.3
	Author: Evan Mattson
	Author URI: http://aaemnnost.tv
	Plugin URI: https://github.com/aaemnnosttv/Post-Link-Shortcodes
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

require_once( 'class-plugin.php' );
require_once( 'class-shortcode.php' );

/**
 * Initialize plugin!
 */
PostLinkShortcodes::get_instance();
