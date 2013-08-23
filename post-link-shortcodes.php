<?php
/*
	Plugin Name: Post Link Shortcodes
	Description: A plugin for dynamically adding a collection of shortcodes for building links to a given post/archive (of any type) or simply returning the requested URL!
	Version: 0.1
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

class PostLinkShortcodes {

	function __construct()
	{
		$this->types = get_post_types( array('show_ui' => true) );
		$this->register_dynamic_shortcodes();

		// default filters
		add_filter( 'pls/link_text',		'do_shortcode' );
		// clone the_title filters
		add_filter( 'pls/post_title_text',	'wptexturize'   );
		add_filter( 'pls/post_title_text',	'convert_chars' );
		add_filter( 'pls/post_title_text',	'trim'          );
	}

	/**
	 * Registers 4 shortcodes for each registered post type
	 * [posttype_url]
	 * [posttype_link]
	 * [posttype_archive_url]
	 * [posttype_archive_link]
	 */
	function register_dynamic_shortcodes()
	{
		foreach ( $this->types as $type ) :
			foreach ( array('url','link') as $request ) :
				add_shortcode( "{$type}_$request",			array(&$this, 'handler') );
				add_shortcode( "{$type}_archive_$request",	array(&$this, 'handler') );
			endforeach;
		endforeach;
	}

	/**
	 * MASTER SHORTCODE HANDLER
	 * @param  array 	$atts    	shortcode atts
	 * @param  string 	$content 	enclosed shortcode content
	 * @param  string 	$tag     	shortcode tag
	 * @return string 				url / html
	 */
	function handler( $atts, $content, $tag )
	{
		$data = $this->get_scdata( $tag );
		// allow alternate inner text to be set with a text att
		$data['inner'] = isset( $atts['text'] ) ? $this->do_att_shortcodes( $atts['text'] ) : $content;

		// allow "shorthand" for post id / slug
		if ( !$data['archive'] && isset( $atts[0] ) ) {
			$key = is_numeric( $atts[0] ) ? 'post_id' : 'slug';
			$data[ $key ] = $atts[0];
		}

		// store data
		$atts['data'] = $data;

		$url = $this->get_url( $atts );

		// url failed means target was not found
		if ( false === $url )
			return '';

		if ( 'url' == $data['request'] )
			$output = $url;
		else
			$output = $this->build_link( $atts );

		/**
		 * @filter 'pls/output'
		 * Final output
		 */
		return apply_filters( 'pls/output', $output, $atts );
	}

	/**
	 * Get the target url
	 * @param  array $atts master atts
	 * @return (string|bool) The permalink URL, or false on failure (if the page doesn't exist).
	 */
	function get_url( &$atts )
	{
		if ( $atts['data']['archive'] )
		{
			$obj = get_post_type_object( $atts['data']['type'] );
			$url = get_post_type_archive_link( $atts['data']['type'] );
		}
		else
		{
			$d = array(
				'post_id' => '',
				'slug'    => '',
			);
			extract( shortcode_atts( $d, $atts['data'] ) );

			if ( !$post_id && $slug ) {
				$found = get_posts( array(
					'name'           => $slug,
					'post_type'      => $atts['data']['type'],
					'posts_per_page' => 1
				));
			}

			$obj = $found ? $found[0] : get_post( $post_id );
			$url = get_permalink( $obj );
		}
		// store results
		$atts['data']['obj'] = $obj;
		$atts['data']['url'] = $url;
		// set href
		$atts['href'] = $url;

		/**
		 * @filter 'pls/url'
		 * The URL 
		 */
		return apply_filters( 'pls/url', $url, $atts );
	}

	function build_link( $atts = array() )
	{
		$attrs = $atts;
		// remove non-attributes
		foreach ( array( 0,'data','post_id','slug','text' ) as $key )
			unset( $attrs[ $key ] );

		/**
		 * @filter 'pls/allowed_link_attributes'
		 * Optionally filter out any html attributes to only those defined here
		 * To use: return a 1 dimensional array of allowed html attributes
		 * All others will be removed
		 * *** MAKE SURE TO INCLUDE 'href' IF YOU USE THIS! ***
		 */
		$allowed = apply_filters( 'pls/allowed_link_attributes', array(), $attrs, $atts );

		if ( $allowed && is_array( $allowed ) )
		{
			$allowed = array_values( $allowed );
			foreach ( $attrs as $k )
				if ( !in_array($k, $allowed) )
					unset( $attrs[ $k ] );
		}

		/**
		 * @filter 'pls/exclude_link_attributes'
		 * Optionally exclude specific attributes
		 * to use, return a 1 dimensional array of html attributes to exclude
		 * May be used together with allowed filter as well
		 */
		$exclude = apply_filters( 'pls/exclude_link_attributes', array(), $attrs, $atts );

		if ( $exclude && is_array( $exclude ) )
		{
			$exclude = array_values( $exclude );
			foreach ( $exclude as $e )
				if ( in_array($e, $attrs) )
					unset( $attrs[ $e ] );
		}

		// sanitize attributes
		$attrs = array_map( 'esc_attr', $attrs );

		// begin html attribute construction
		$attr = '';
		foreach ( $attrs as $name => $value ) {
			$value = $this->do_att_shortcodes( $value );
			$attr .= sprintf('%s="%s" ', $name, $value);
		}

		/**
		 * @filter 'pls/link_text'
		 * Inner html of generated link
		 */
		$inner = apply_filters( 'pls/link_text', $this->get_inner( $atts ), $atts );

		$link = sprintf('<a %s>%s</a>',
			trim( $attr ),
			$inner	
		);

		/**
		 * @filter 'pls/link'
		 * The LINK markup
		 */
		return apply_filters( 'pls/link', $link, $atts );
	}

	/**
	 * Allow shortcodes to be used inside shortcode attribute values by using {{}} instead of []
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	function do_att_shortcodes( $content )
	{
		if ( false !== strpos($content, '{{') && false !== strpos($content, '}}') )
		{
			$convert = str_replace( array('{{','}}'), array('[',']'), $content );
			$content = do_shortcode( $convert );
		}
		return $content;
	}

	function get_inner( $atts ) 
	{
		// manually set
		if ( !empty( $atts['data']['inner'] ) )
			return $atts['data']['inner'];

		if ( $atts['data']['archive'] )
			return apply_filters( 'pls/post_type_name',		$atts['data']['obj']->labels->name, $atts );
		else
			return apply_filters( 'pls/post_title_text',	$atts['data']['obj']->post_title, $atts );
	}

	/**
	 * Extract data bits from shortcode tag name
	 * @param  [type] $tag [description]
	 * @return [type]      [description]
	 */
	function get_scdata( $tag )
	{
		$pieces = explode('_', $tag);
		// extract request (url|link) from end of tag
		$request = array_pop( $pieces );

		return array(
			'tag'     => $tag,
			'request' => $request,
			'archive' => ( count( $pieces ) > 1 && 'archive' == array_pop( $pieces ) ), // should only pop if count is > 1
			'type'    => join( $pieces ), // anything left
		);
	}
} // PostLinkShortcodes

/**
 * Initialize the plugin after post types are registered
 */
function init_post_link_shortcodes()
{
	new PostLinkShortcodes;
}
add_action( 'init', 'init_post_link_shortcodes', 500 );
