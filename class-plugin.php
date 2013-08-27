<?php

/**
 * Plugin Class
 * @package  Post Link Shortcodes 
 */


class PostLinkShortcodes
{
	function __construct()
	{
		$this->types = get_post_types( array('show_ui' => true) );
		$this->register_dynamic_shortcodes();

		/**
		 * Default filters
		 */
		add_filter( 'pls/link_text',	'do_shortcode' );
		// clone the_title filters
		add_filter( 'pls/single_text',	'wptexturize'   );
		add_filter( 'pls/single_text',	'convert_chars' );
		add_filter( 'pls/single_text',	'trim'          );
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
		$sc = new PLS_SC( $atts, $content, $tag );

		if ( false === $url = $sc->get_url() )
			return '';

		if ( 'url' == $sc->request )
			$output = $url;
		else
			$output = $sc->get_link();

		/**
		 * Final output
		 * 
		 * @filter 'pls/output'
		 * @param (string) output
		 * @param (array) current shortcode object variables
		 */
		return apply_filters( 'pls/output', $output, $sc->get_filter_data() );
	}
} // PostLinkShortcodes