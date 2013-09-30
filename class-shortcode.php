<?php

/**
 * Shortcode Class
 * @package  Post Link Shortcodes
 */

class PLS_SC
{
	private $url;
	private $data;
	private $attrs = array();

	function __construct( $atts, $content, $tag )
	{
		$this->setup_props( $tag );
		
		$this->setup_data( $atts, $content );
		
		// backup
		$this->data['orig'] = array(
			'atts'    => $atts,
			'content' => $content,
			'tag'     => $tag,
		);
	}

	/**
	 * Extract data bits from shortcode tag name
	 */
	function setup_props( $tag )
	{
		$this->tag     = $tag;
		$pieces        = explode( '_', $tag );
		$this->request = array_pop( $pieces ); // (url|link)
		$this->archive = ( count( $pieces ) > 1 && 'archive' == array_pop( $pieces ) ); // bool
		$this->type    = implode( '_', $pieces ); // post type
	}

	/**
	 * Separates attributes and control data from shortcode atts
	 * @param  [type] $atts [description]
	 * @return [type]       [description]
	 */
	function setup_data( $atts, $content )
	{
		if ( ! is_array( $atts ) )
			$atts = array();

		// prepare a separate array to for attributes
		$attrs = $atts;
		// remove reserved non-attributes
		foreach ( array( 0,'post_id','slug','text' ) as $key )
			unset( $attrs[ $key ] );

		// compensate for shortcodes not liking attributes with hyphens
		foreach ( $attrs as $name => $value )
		{
			$htmlattr = str_replace('_', '-', $name);
			$this->attrs[ $htmlattr ] = $value;
		}

		/**
		 * From here on, attribute names use hyphens
		 */
		
		// everything else
		$data = array_diff_assoc( $atts, $this->attrs );

		// allow "shorthand" for post_id / slug
		if ( !$this->archive && isset( $atts[0] ) )
		{
			$value        = $this->do_att_shortcode( $atts[0] );
			$key          = is_numeric( $value ) ? 'post_id' : 'slug';
			$data[ $key ] = $value;
			unset( $data[0] );
		}
		 
		// If someone took the time to use an enclosed shortcode, that denotes precedence
		// Allow alternate inner text to be set with a text="" att
		// Defaults to post title for single, or post type name for archive
		if ( '' !== $content )
			$data['inner'] = $content;
		else
			$data['inner'] = isset( $atts['text'] )
								? $this->do_att_shortcode( $atts['text'] )
								: '';

		$this->data = $data;
	}

	/**
	 * Get the target url
	 * @return (string|bool) The permalink URL, or false on failure (if the target doesn't exist).
	 */
	function get_url()
	{
		if ( is_null( $this->url ) )
		{
			if ( $this->archive )
			{
				$obj = get_post_type_object( $this->type );
				$url = get_post_type_archive_link( $this->type );
			}
			else
			{
				$d = array(
					'post_id' => '',
					'slug'    => '',
				);
				extract( shortcode_atts( $d, $this->data ) );

				$found = ( !$post_id && $slug )
					? get_posts( array(
						'name'           => $slug,
						'post_type'      => $this->type,
						'posts_per_page' => 1
					))
					: false;

				$obj = $found ? $found[0] : get_post( $post_id );
				$url = get_permalink( $obj );
			}
			// store results
			$this->obj = $obj;
			$this->_url = $url;
		}

		/**
		 * The URL
		 *  
		 * @filter 'pls/url'
		 * @param (mixed) the URL if found, or (bool) false on failure
		 * @param (array) current shortcode object variables
		 */
		return $this->url = apply_filters( 'pls/url', $url, $this->get_filter_data() );
	}

	function get_filter_data()
	{
		return get_object_vars( $this );
	}

	/**
	 * Get Link (anchor) markup
	 * By this point, a URL has successfully been found
	 * @return (string)
	 */
	function get_link()
	{
		// get filtered & sanitized attrs
		$attrs = $this->get_attrs();

		// build html attribute string
		$attr = '';
		foreach ( $attrs as $name => $value )
		{
			$value = $this->do_att_shortcode( $value );
			$attr .= sprintf('%s="%s" ', $name, $value);
		}
		$attr = trim( $attr );

		/**
		 * Inner link text/html
		 * 
		 * do_shortcode is applied by default
		 * @see  __construct()
		 * 
		 * @filter 'pls/link_text'
		 * @param (string)	inner 	current inner html of link
		 * @param (array)	array() current shortcode object variables
		 */
		$inner = apply_filters( 'pls/link_text', $this->get_inner(), $this->get_filter_data() );

		/**
		 * The final link markup
		 * 
		 * @filter 'pls/link'
		 * @param (string) markup
		 * @param (array) current shortcode object variables
		 */
		return apply_filters( 'pls/link', "<a $attr>$inner</a>", $this->get_filter_data() );
	}

	/**
	 * Determine inner html for the link
	 */
	function get_inner() 
	{
		// static - [sc]$content[/sc] takes precedence over [sc text=""]
		if ( !empty( $this->data['inner'] ) )
			return $this->data['inner'];


		// dynamic
		if ( $this->archive )
			/**
			 * Archive link text
			 * @filter 'pls/archive_text'
			 * @param (string) post type name
			 * @param (object) post type object
			 * @param (array) current shortcode object variables
			 */
			return apply_filters( 'pls/archive_text', $this->obj->labels->name, $this->obj, $this->get_filter_data() );
		else
			/**
			 * Single post link text
			 * @filter 'pls/single_text'
			 * @param (string) post title
			 * @param (object) post object
			 * @param (array) current shortcode object variables
			 */
			return apply_filters( 'pls/single_text', $this->obj->post_title, $this->obj, $this->get_filter_data() );
	}

	function get_attrs()
	{
		// base attributes
		// a shortcode-defined href is overriden by permalink
		$attrs = array_merge( $this->attrs, array('href' => $this->url) );

		/**
		 * Optionally restrict html attributes to only those defined here
		 * 
		 * This is a whitelist of allowed attribute names. All others will be removed.
		 * 
		 * To use: return a 1 dimensional array of allowed html attributes
		 * Ex: return array('href','id','class')
		 * 
		 * @filter 'pls/allowed_link_attributes'
		 * @param (array)	array()	empty array to be filled with html attribute names to whitelist
		 * @param (array)	$attrs	current set of html attribute => value
		 * @param (array)	array() current shortcode object variables
		 * 
		 * *** MAKE SURE TO INCLUDE 'href' IF YOU USE THIS! ***
		 */
		$allowed = apply_filters( 'pls/allowed_link_attributes', array(), $attrs, $this->get_filter_data() );

		if ( $allowed && is_array( $allowed ) )
		{
			foreach ( array_keys( $attrs ) as $k )
				if ( !in_array( $k, $allowed ) )
					unset( $attrs[ $k ] );
		}

		/**
		 * Optionally forbid specific attributes
		 *
		 * This is a blacklist of attribute names.
		 * 
		 * To use: return a 1 dimensional array of html attributes to exclude
		 * Ex: return array('style','height','width')
		 * 
		 * @filter 'pls/exclude_link_attributes'
		 * @param (array)	array()	empty array to be filled with html attribute names to blacklist
		 * @param (array)	$attrs	current set of html attribute => value
		 * @param (array)	array() current shortcode object variables
		 * 
		 * May be used together with 'pls/allowed_link_attributes' filter as well
		 */
		$exclude = apply_filters( 'pls/exclude_link_attributes', array(), $attrs, $this->get_filter_data() );

		if ( $exclude && is_array( $exclude ) )
		{
			foreach ( $exclude as $e )
				if ( array_key_exists( $e, $attrs ) )
					unset( $attrs[ $e ] );
		}

		// sanitize attribute values
		return array_map( 'esc_attr', $attrs );
	}

	/**
	 * Allow shortcodes to be used inside shortcode attribute values by using {{}} instead of []
	 */
	function do_att_shortcode( $content )
	{
		if ( false !== strpos($content, '{{') && false !== strpos($content, '}}') )
		{
			$convert = str_replace( array('{{','}}'), array('[',']'), $content );
			$content = do_shortcode( $convert );
		}
		return $content;
	}
} // PLS_SC